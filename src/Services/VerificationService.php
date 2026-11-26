<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Env;
use App\Core\Logger;
use App\Repositories\ProfileRepository;
use App\Supabase\Auth;
use App\Supabase\Client;

/**
 * Service managing secure email verification codes (6-digit OTP),
 * expiration, cooldowns, brute-force protection, and account activation.
 */
final class VerificationService
{
    public const OTP_EXPIRATION_SECONDS = 300; // 5 minutes
    public const RESEND_COOLDOWN_SECONDS = 60;  // 60 seconds
    public const MAX_ATTEMPTS = 5;              // Lock after 5 bad guesses

    /**
     * Creates and dispatches a 6-digit OTP code and verification link.
     *
     * @return array{ok: bool, error?: string, expires_at?: int, cooldown_seconds?: int}
     */
    public static function createAndSend(string $userId, string $email, string $fullName): array
    {
        $cleanEmail = strtolower(trim($email));

        // Generate cryptographically secure 6-digit OTP and 64-char hex link token
        $otp = sprintf('%06d', random_int(100000, 999999));
        $linkToken = bin2hex(random_bytes(32));

        $now = time();
        $expiresAt = $now + self::OTP_EXPIRATION_SECONDS;
        $cooldownUntil = $now + self::RESEND_COOLDOWN_SECONDS;

        $verificationPayload = [
            'uid'           => $userId,
            'email'         => $cleanEmail,
            'name'          => $fullName,
            'otp_hash'      => hash('sha256', $otp),
            'token_hash'    => hash('sha256', $linkToken),
            'expires_at'    => $expiresAt,
            'sent_at'       => $now,
            'attempts'      => 0,
            'locked'        => false,
        ];

        // Store verification data in fast local cache (keyed by email hash)
        self::saveCache($cleanEmail, $verificationPayload);

        // Also persist verification payload to Supabase Auth user_metadata for cross-device resilience
        self::syncToSupabase($userId, [
            'email_verified'               => false,
            'email_verification_otp_hash'  => $verificationPayload['otp_hash'],
            'email_verification_token_hash'=> $verificationPayload['token_hash'],
            'email_verification_expires_at'=> $expiresAt,
            'email_verification_sent_at'   => $now,
            'email_verification_attempts'  => 0,
        ]);

        // Dispatch email via SMTP mailer (with local logging fallback)
        $mailResult = Mailer::sendVerificationEmail($cleanEmail, $fullName, $otp, $linkToken);

        Logger::info("Verification code created for {$cleanEmail}", [
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'mail_sent'  => $mailResult['sent'] ?? false,
        ]);

        return [
            'ok'               => true,
            'otp'              => $otp,
            'expires_at'       => $expiresAt,
            'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
            'mail_result'      => $mailResult,
        ];
    }

    /**
     * Verifies user by entered 6-digit numeric OTP code.
     *
     * @return array{ok: bool, error?: string, user?: array}
     */
    public static function verifyOtp(string $email, string $otp): array
    {
        $cleanEmail = strtolower(trim($email));
        $cleanOtp = trim($otp);

        if (!preg_match('/^[0-9]{6}$/', $cleanOtp)) {
            return ['ok' => false, 'error' => 'Please enter a valid 6-digit verification code.'];
        }

        $record = self::getRecord($cleanEmail);
        if (!$record) {
            return ['ok' => false, 'error' => 'No pending verification found for this email. Please request a new code.'];
        }

        // Brute-force protection: check if attempts exceeded
        if (!empty($record['locked']) || ($record['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'error' => 'Too many failed verification attempts. For your security, this code has been invalidated. Please click "Resend Code".'];
        }

        // Check expiration (5 minutes)
        if (time() > (int)($record['expires_at'] ?? 0)) {
            return ['ok' => false, 'error' => 'This verification code has expired (valid for 5 minutes). Please click "Resend Code" to receive a fresh one.'];
        }

        // Constant-time OTP comparison
        $calculatedHash = hash('sha256', $cleanOtp);
        if (!hash_equals((string)($record['otp_hash'] ?? ''), $calculatedHash)) {
            // Increment failed attempt counter
            $record['attempts'] = ($record['attempts'] ?? 0) + 1;
            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                $record['locked'] = true;
            }
            self::saveCache($cleanEmail, $record);

            $remaining = self::MAX_ATTEMPTS - $record['attempts'];
            if ($remaining > 0) {
                return ['ok' => false, 'error' => "Incorrect verification code. {$remaining} attempt(s) remaining."];
            }
            return ['ok' => false, 'error' => 'Too many failed verification attempts. This code has been invalidated. Please click "Resend Code".'];
        }

        // OTP is correct! Activate account
        return self::activateUser($record['uid'] ?? '', $cleanEmail, $record['name'] ?? '');
    }

    /**
     * Verifies user via direct link click token.
     *
     * @return array{ok: bool, error?: string, user?: array}
     */
    public static function verifyToken(string $email, string $token): array
    {
        $cleanEmail = strtolower(trim($email));
        $cleanToken = trim($token);

        if (empty($cleanToken)) {
            return ['ok' => false, 'error' => 'Invalid or missing verification link.'];
        }

        $record = self::getRecord($cleanEmail);
        if (!$record) {
            return ['ok' => false, 'error' => 'No pending verification found for this email. Please request a new code.'];
        }

        // Check expiration
        if (time() > (int)($record['expires_at'] ?? 0)) {
            return ['ok' => false, 'error' => 'This verification link has expired. Please request a new code from the verification page.'];
        }

        $calculatedHash = hash('sha256', $cleanToken);
        if (!hash_equals((string)($record['token_hash'] ?? ''), $calculatedHash)) {
            return ['ok' => false, 'error' => 'Invalid verification link. Please check your email or enter the 6-digit code manually.'];
        }

        // Token matches! Activate account
        return self::activateUser($record['uid'] ?? '', $cleanEmail, $record['name'] ?? '');
    }

    /**
     * Resends a fresh OTP code and link with strict 60-second cooldown enforcement.
     *
     * @return array{ok: bool, error?: string, remaining_seconds?: int}
     */
    public static function resend(string $email): array
    {
        $cleanEmail = strtolower(trim($email));
        $record = self::getRecord($cleanEmail);

        if (!$record) {
            // Try looking up the user from ProfileRepository
            $profile = ProfileRepository::findByEmail($cleanEmail);
            if (!$profile) {
                return ['ok' => false, 'error' => 'Account not found with this email address. Please register first.'];
            }
            $userId = (string)$profile['id'];
            $fullName = (string)($profile['full_name'] ?? 'Customer');
        } else {
            $userId = (string)$record['uid'];
            $fullName = (string)($record['name'] ?? 'Customer');
        }

        // Check 60-second cooldown
        $sentAt = (int)($record['sent_at'] ?? 0);
        $elapsed = time() - $sentAt;
        if ($sentAt > 0 && $elapsed < self::RESEND_COOLDOWN_SECONDS) {
            $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;
            return [
                'ok'                => false,
                'error'             => "Please wait {$remaining} seconds before requesting another verification code.",
                'remaining_seconds' => $remaining,
            ];
        }

        // Generate fresh OTP and send
        $result = self::createAndSend($userId, $cleanEmail, $fullName);
        if (!$result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? 'Unable to send verification code. Please try again.'];
        }

        return [
            'ok'                => true,
            'otp'               => $result['otp'] ?? null,
            'remaining_seconds' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Returns remaining cooldown seconds for the resend button countdown (0 if cooldown passed).
     */
    public static function getRemainingCooldown(string $email): int
    {
        $cleanEmail = strtolower(trim($email));
        $record = self::getRecord($cleanEmail);
        if (!$record) {
            return 0;
        }

        $sentAt = (int)($record['sent_at'] ?? 0);
        $elapsed = time() - $sentAt;
        if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
            return self::RESEND_COOLDOWN_SECONDS - $elapsed;
        }

        return 0;
    }

    /**
     * Activates the user in Supabase Auth and DB profiles, clears pending codes.
     */
    private static function activateUser(string $userId, string $email, string $fullName): array
    {
        $auth = new Auth();

        // 1. If user ID is missing, lookup via ProfileRepository
        if (empty($userId)) {
            $profile = ProfileRepository::findByEmail($email);
            if ($profile) {
                $userId = (string)$profile['id'];
                $fullName = $fullName ?: (string)($profile['full_name'] ?? '');
            }
        }

        // 2. Mark confirmed in Supabase Auth GoTrue via Admin API
        if (!empty($userId)) {
            $auth->confirmUserEmail($userId);

            // Also update public.profiles with verification flag
            ProfileRepository::update($userId, [
                'is_verified'       => true,
                'email_verified_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
        }

        // 3. Clear pending verification data from cache
        self::clearCache($email);

        Logger::info("User account successfully activated for {$email}", ['uid' => $userId]);

        return [
            'ok'   => true,
            'user' => [
                'uid'   => $userId,
                'email' => $email,
                'name'  => $fullName ?: 'Customer',
                'role'  => 'customer',
            ],
        ];
    }

    /**
     * Retrieves verification record from cache or falls back to Supabase user_metadata.
     */
    private static function getRecord(string $email): ?array
    {
        $cacheKey = 'verify_email_' . md5($email);
        $cached = Cache::remember($cacheKey, self::OTP_EXPIRATION_SECONDS + 60, static fn() => null);
        if (is_array($cached) && !empty($cached['otp_hash'])) {
            return $cached;
        }

        // Fallback: Check Supabase Auth user_metadata
        $profile = ProfileRepository::findByEmail($email);
        if (!$profile || empty($profile['id'])) {
            return null;
        }

        $userId = (string)$profile['id'];
        $client = new Client();
        $baseUrl = rtrim(Env::require('SUPABASE_URL'), '/');
        $secretKey = Env::require('SUPABASE_SECRET_KEY');
        $url = "{$baseUrl}/auth/v1/admin/users/{$userId}";
        $headers = [
            'apikey: ' . $secretKey,
            'Authorization: Bearer ' . $secretKey,
        ];

        $res = $client->execute('GET', $url, $headers, null);
        if ($res->ok && is_array($res->data) && !empty($res->data['user_metadata'])) {
            $meta = $res->data['user_metadata'];
            if (!empty($meta['email_verification_otp_hash'])) {
                $record = [
                    'uid'        => $userId,
                    'email'      => $email,
                    'name'       => $meta['full_name'] ?? ($profile['full_name'] ?? 'Customer'),
                    'otp_hash'   => $meta['email_verification_otp_hash'],
                    'token_hash' => $meta['email_verification_token_hash'] ?? '',
                    'expires_at' => (int)($meta['email_verification_expires_at'] ?? 0),
                    'sent_at'    => (int)($meta['email_verification_sent_at'] ?? 0),
                    'attempts'   => (int)($meta['email_verification_attempts'] ?? 0),
                    'locked'     => false,
                ];
                self::saveCache($email, $record);
                return $record;
            }
        }

        return null;
    }

    private static function saveCache(string $email, array $data): void
    {
        $cacheKey = 'verify_email_' . md5($email);
        Cache::remember($cacheKey, self::OTP_EXPIRATION_SECONDS + 60, static fn() => $data);
    }

    private static function clearCache(string $email): void
    {
        $cacheKey = 'verify_email_' . md5($email);
        Cache::remember($cacheKey, 1, static fn() => null);
    }

    private static function syncToSupabase(string $userId, array $metadata): void
    {
        if (empty($userId)) {
            return;
        }
        try {
            $client = new Client();
            $baseUrl = rtrim(Env::require('SUPABASE_URL'), '/');
            $secretKey = Env::require('SUPABASE_SECRET_KEY');
            $url = "{$baseUrl}/auth/v1/admin/users/{$userId}";
            $headers = [
                'apikey: ' . $secretKey,
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ];

            // Fetch current metadata to merge
            $res = $client->execute('GET', $url, $headers, null);
            $existing = (is_array($res->data) && isset($res->data['user_metadata']) && is_array($res->data['user_metadata']))
                ? $res->data['user_metadata']
                : [];

            $merged = array_merge($existing, $metadata);
            $client->execute('PUT', $url, $headers, json_encode(['user_metadata' => $merged]));
        } catch (\Throwable $e) {
            Logger::warning("Could not sync verification metadata to Supabase user {$userId}: " . $e->getMessage());
        }
    }
}
