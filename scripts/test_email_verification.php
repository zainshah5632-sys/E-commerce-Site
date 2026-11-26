<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Env;
use App\Core\Logger;
use App\Repositories\ProfileRepository;
use App\Services\Mailer;
use App\Services\VerificationService;
use App\Supabase\Auth;

echo "=================================================================\n";
echo " GLASSCART EMAIL VERIFICATION SYSTEM VERIFICATION SUITE\n";
echo "=================================================================\n\n";

$auth = new Auth();
$testSuffix = time() . '_' . random_int(100, 999);
$testEmail = "verify_test_{$testSuffix}@example.com";
$testPassword = "TestVerify123!#";
$testName = "Verification Tester";
$testUsername = "testverify_{$testSuffix}";
$createdUid = null;

try {
    // -----------------------------------------------------------------
    // TEST 1: User Registration with Unverified Email Status
    // -----------------------------------------------------------------
    echo "[TEST 1] Registering user in Supabase with unconfirmed email...\n";
    $reg = $auth->register($testEmail, $testPassword, $testName, [
        'username'     => $testUsername,
        'phone'        => '03001234567',
        'country_code' => '+92',
    ]);

    if (!$reg['ok'] || empty($reg['user']['id'])) {
        throw new RuntimeException("Registration failed: " . ($reg['error'] ?? 'Unknown error'));
    }

    $createdUid = (string)$reg['user']['id'];
    echo "  -> User created with UID: {$createdUid}\n";
    echo "  -> email_confirmed_at: " . var_export($reg['user']['email_confirmed_at'] ?? null, true) . "\n";
    assert(empty($reg['user']['email_confirmed_at']), "User must NOT have email_confirmed_at populated on initial registration");
    echo "  [PASS] User created with unverified status.\n\n";

    // -----------------------------------------------------------------
    // TEST 2: Prevent Login Prior to Verification
    // -----------------------------------------------------------------
    echo "[TEST 2] Verifying login is strictly blocked for unverified account...\n";
    $loginRes = $auth->login($testEmail, $testPassword);
    echo "  -> Login result OK: " . ($loginRes['ok'] ? 'yes' : 'no') . "\n";
    echo "  -> Login error: " . ($loginRes['error'] ?? 'none') . "\n";
    assert(!$loginRes['ok'], "Login MUST fail for unverified account");
    assert(($loginRes['error'] ?? '') === 'email_not_confirmed', "Login error must be 'email_not_confirmed'");
    echo "  [PASS] Login successfully blocked with 'email_not_confirmed'.\n\n";

    // -----------------------------------------------------------------
    // TEST 3: Verification Service Code Generation & Dispatch
    // -----------------------------------------------------------------
    echo "[TEST 3] Generating 6-digit OTP code and verification email...\n";
    $sendRes = VerificationService::createAndSend($createdUid, $testEmail, $testName);
    assert($sendRes['ok'] === true, "VerificationService::createAndSend must succeed");
    echo "  -> Code generated. Expires at: " . date('Y-m-d H:i:s', $sendRes['expires_at']) . "\n";
    echo "  -> Cooldown seconds: {$sendRes['cooldown_seconds']}\n";
    echo "  [PASS] Code generated and dispatched.\n\n";

    // -----------------------------------------------------------------
    // TEST 4: Resend Cooldown Enforcement (60 seconds)
    // -----------------------------------------------------------------
    echo "[TEST 4] Testing 60-second resend cooldown enforcement...\n";
    $resendRes = VerificationService::resend($testEmail);
    echo "  -> Immediate resend allowed: " . ($resendRes['ok'] ? 'yes' : 'no') . "\n";
    echo "  -> Error message: " . ($resendRes['error'] ?? 'none') . "\n";
    echo "  -> Remaining cooldown: " . ($resendRes['remaining_seconds'] ?? 0) . "s\n";
    assert($resendRes['ok'] === false, "Immediate resend MUST be blocked by cooldown");
    assert(($resendRes['remaining_seconds'] ?? 0) > 0, "Remaining cooldown must be greater than 0");
    echo "  [PASS] Resend cooldown strictly enforced.\n\n";

    // -----------------------------------------------------------------
    // TEST 5: Brute-Force & Incorrect OTP Protection
    // -----------------------------------------------------------------
    echo "[TEST 5] Testing brute-force and bad OTP guess detection...\n";
    $badOtpRes = VerificationService::verifyOtp($testEmail, "000000");
    echo "  -> Bad OTP result OK: " . ($badOtpRes['ok'] ? 'yes' : 'no') . "\n";
    echo "  -> Error returned: " . ($badOtpRes['error'] ?? 'none') . "\n";
    assert($badOtpRes['ok'] === false, "Bad OTP must fail");
    assert(str_contains($badOtpRes['error'] ?? '', 'attempt(s) remaining'), "Must report remaining attempts");
    echo "  [PASS] Incorrect OTP detected and attempts counter incremented.\n\n";

    // -----------------------------------------------------------------
    // TEST 6: Successful Verification via OTP Code
    // -----------------------------------------------------------------
    echo "[TEST 6] Testing successful OTP code verification...\n";
    // Read the logged OTP from local dev log or cache
    $logFile = dirname(__DIR__) . '/storage/logs/email_log.txt';
    $logContent = is_file($logFile) ? file_get_contents($logFile) : '';
    preg_match_all('/Your 6-digit email verification code is:\s*([0-9]{6})/', $logContent, $matches);
    $otps = $matches[1] ?? [];
    $validOtp = end($otps);

    if (empty($validOtp)) {
        throw new RuntimeException("Could not find generated OTP in verification log.");
    }

    echo "  -> Found valid generated OTP: {$validOtp}\n";
    $verifyRes = VerificationService::verifyOtp($testEmail, $validOtp);
    echo "  -> OTP verification result OK: " . ($verifyRes['ok'] ? 'yes' : 'no') . "\n";
    assert($verifyRes['ok'] === true, "Valid OTP verification must succeed");
    echo "  -> Activated user name: " . ($verifyRes['user']['name'] ?? '') . "\n";
    echo "  [PASS] Account activated via OTP verification.\n\n";

    // -----------------------------------------------------------------
    // TEST 7: Login Allowed Post-Verification
    // -----------------------------------------------------------------
    echo "[TEST 7] Testing login after successful verification...\n";
    $postLoginRes = $auth->login($testEmail, $testPassword);
    echo "  -> Post-verification login OK: " . ($postLoginRes['ok'] ? 'yes' : 'no') . "\n";
    echo "  -> Authenticated UID: " . ($postLoginRes['user']['uid'] ?? '') . "\n";
    assert($postLoginRes['ok'] === true, "Login MUST succeed once email is verified");
    assert(($postLoginRes['user']['uid'] ?? '') === $createdUid, "Logged in UID must match created UID");
    echo "  [PASS] User can sign in immediately after verification.\n\n";

    // -----------------------------------------------------------------
    // TEST 8: Token Link Verification Flow
    // -----------------------------------------------------------------
    echo "[TEST 8] Testing direct link token verification...\n";
    $testEmail2 = "verify_token_{$testSuffix}@example.com";
    $reg2 = $auth->register($testEmail2, $testPassword, "Token Tester", [
        'username' => "tokentest_{$testSuffix}",
        'phone'    => '03007654321',
    ]);
    $uid2 = (string)$reg2['user']['id'];
    VerificationService::createAndSend($uid2, $testEmail2, "Token Tester");

    $logContent = file_get_contents($logFile);
    preg_match_all('/token=([a-f0-9]{64})&email=' . preg_quote(urlencode($testEmail2), '/') . '/', $logContent, $tokenMatches);
    $validToken = !empty($tokenMatches[1]) ? end($tokenMatches[1]) : '';

    if (empty($validToken)) {
        throw new RuntimeException("Could not find generated verification token in log.");
    }

    echo "  -> Found valid link token: " . substr($validToken, 0, 16) . "...\n";
    $tokenVerifyRes = VerificationService::verifyToken($testEmail2, $validToken);
    assert($tokenVerifyRes['ok'] === true, "Verification by link token must succeed");
    echo "  [PASS] Account activated via direct email link token.\n\n";

    // Clean up second test user
    $auth->deleteUser($uid2);

    echo "=================================================================\n";
    echo " ALL 8 EMAIL VERIFICATION TESTS PASSED SUCCESSFULLY!\n";
    echo "=================================================================\n";

} finally {
    // Clean up test user
    if ($createdUid) {
        $auth->deleteUser($createdUid);
        echo "Cleaned up test user {$createdUid}.\n";
    }
}
