<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\ProfileRepository;
use App\Services\RateLimiter;
use App\Services\VerificationService;
use App\Supabase\Auth;

final class AuthController
{
    private Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function loginForm(Request $request): Response
    {
        $next = $this->sanitizeNext($request->query('next'));
        return Response::html(View::render('pages/login', [
            'title'   => 'Sign In | GlassCart',
            'next'    => $next,
            'errors'  => Session::instance()->get('_errors', []),
            'noindex' => true,
        ]));
    }

    public function login(Request $request): Response
    {
        $session = Session::instance();
        // Support either 'identifier' (email or username) or fallback to 'email'
        $identifier = trim($request->string('identifier') ?: $request->string('email'));
        $password = (string)$request->input('password', '');
        $next = $this->sanitizeNext($request->string('next'));

        $ip = $request->ip();
        $rateKey = 'login_' . md5($ip . '_' . strtolower($identifier));

        // Brute-force & automated attack rate limiting
        if (RateLimiter::tooManyAttempts($rateKey, 5, 900)) {
            $seconds = RateLimiter::availableIn($rateKey, 900);
            $minutes = max(1, (int)ceil($seconds / 60));
            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In | GlassCart',
                'next'    => $next,
                'errors'  => ['login' => "Too many failed login attempts. For your security, please wait {$minutes} minute(s) before trying again."],
                'noindex' => true,
            ]), 429);
        }

        $validator = Validator::make([
            'identifier' => $identifier,
            'password'   => $password,
        ], [
            'identifier' => 'required|min:3|max:150',
            'password'   => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In | GlassCart',
                'next'    => $next,
                'errors'  => $validator->errors(),
                'noindex' => true,
            ]), 422);
        }

        // Determine whether user entered an email address or username
        $targetEmail = $identifier;
        if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Username lookup
            $profile = ProfileRepository::findByUsername($identifier);
            if (!$profile || empty($profile['email'])) {
                RateLimiter::hit($rateKey, 5, 900);
                return Response::html(View::render('pages/login', [
                    'title'   => 'Sign In | GlassCart',
                    'next'    => $next,
                    'errors'  => ['login' => 'Invalid credentials.'],
                    'noindex' => true,
                ]), 401);
            }
            $targetEmail = (string)$profile['email'];
        }

        $res = $this->auth->login($targetEmail, $password);

        if (!$res['ok'] || empty($res['user'])) {
            RateLimiter::hit($rateKey, 5, 900);

            // If the user's email is not confirmed, block login and direct them to verification
            if (($res['error'] ?? '') === 'email_not_confirmed') {
                $session->set('pending_verification_email', $targetEmail);
                return Response::html(View::render('pages/login', [
                    'title'            => 'Sign In | GlassCart',
                    'next'             => $next,
                    'errors'           => ['login' => 'Your email address has not been verified yet. Please enter the 6-digit code sent to your email to activate your account.'],
                    'unverified_email' => $targetEmail,
                    'noindex'          => true,
                ]), 403);
            }

            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In | GlassCart',
                'next'    => $next,
                'errors'  => ['login' => 'Invalid credentials.'],
                'noindex' => true,
            ]), 401);
        }

        // Login success - clear rate limits
        RateLimiter::clear($rateKey);

        $user = $res['user'];
        $session->setAuth($user['uid'], $user['email'], $user['name'], $user['role']);
        $session->flash('success', "Welcome back, {$user['name']}!");

        return Response::redirect($next);
    }

    public function registerForm(Request $request): Response
    {
        return Response::html(View::render('pages/register', [
            'title'   => 'Sign Up | GlassCart',
            'errors'  => Session::instance()->get('_errors', []),
            'noindex' => true,
        ]));
    }

    public function register(Request $request): Response
    {
        $session = Session::instance();
        $fullName = $request->string('full_name');
        $username = strtolower(trim($request->string('username')));
        $email = strtolower(trim($request->string('email')));
        $countryCode = trim($request->string('country_code', '+1'));
        $phone = preg_replace('/[^0-9]/', '', $request->string('phone'));
        $password = (string)$request->input('password', '');
        $passwordConfirm = (string)$request->input('password_confirmation', '');

        // Validation with all teacher rules:
        // - full_name: alphabets only (no numbers)
        // - username: alphanumeric/underscore 3-30
        // - phone: exactly 11 digits
        // - password: letter, number, and sign (min 6 chars per Supabase Auth)
        $validator = Validator::make([
            'full_name'             => $fullName,
            'username'              => $username,
            'email'                 => $email,
            'country_code'          => $countryCode,
            'phone'                 => $phone,
            'password'              => $password,
            'password_confirmation' => $passwordConfirm,
        ], [
            'full_name'    => 'required|alpha_spaces|min:2|max:100',
            'username'     => 'required|username',
            'email'        => 'required|email|max:150',
            'country_code' => 'required|max:6',
            'phone'        => 'required|phone_11',
            'password'     => 'required|min:6|max:100|complex_password|confirmed',
        ]);

        $oldSafeInput = [
            'full_name'    => $fullName,
            'username'     => $username,
            'email'        => $email,
            'country_code' => $countryCode,
            'phone'        => $request->string('phone'),
        ];

        if ($validator->fails()) {
            $session->set('_old_input', $oldSafeInput);
            return Response::html(View::render('pages/register', [
                'title'   => 'Sign Up | GlassCart',
                'errors'  => $validator->errors(),
                'noindex' => true,
            ]), 422);
        }

        // Check if email is already taken
        $existingEmail = ProfileRepository::findByEmail($email);
        if ($existingEmail !== null) {
            $session->set('_old_input', $oldSafeInput);
            return Response::html(View::render('pages/register', [
                'title'   => 'Sign Up | GlassCart',
                'errors'  => ['email' => 'An account with this email address already exists. Please sign in instead.'],
                'noindex' => true,
            ]), 422);
        }

        // Check if username is already taken
        $existingProfile = ProfileRepository::findByUsername($username);
        if ($existingProfile !== null) {
            $session->set('_old_input', $oldSafeInput);
            return Response::html(View::render('pages/register', [
                'title'   => 'Sign Up | GlassCart',
                'errors'  => ['username' => 'This username is already taken. Please choose another.'],
                'noindex' => true,
            ]), 422);
        }

        $extraMetadata = [
            'username'     => $username,
            'phone'        => $phone,
            'country_code' => $countryCode,
        ];

        $regRes = $this->auth->register($email, $password, $fullName, $extraMetadata);
        if (!$regRes['ok']) {
            $session->set('_old_input', $oldSafeInput);
            return Response::html(View::render('pages/register', [
                'title'   => 'Sign Up | GlassCart',
                'errors'  => ['registration' => $regRes['error'] ?? 'Registration failed.'],
                'noindex' => true,
            ]), 422);
        }

        // Clear old input on success
        $session->remove('_old_input');

        $user = $regRes['user'] ?? [];
        $uid = (string)($user['id'] ?? '');

        // Generate 6-digit OTP code and dispatch branded verification email via SMTP
        $verRes = VerificationService::createAndSend($uid, $email, $fullName);

        // Store pending email in session and redirect to verification screen
        $session->set('pending_verification_email', $email);
        if (!empty($verRes['otp'])) {
            $session->set('dev_verification_code', $verRes['otp']);
        }
        $session->flash('info', "We've sent a 6-digit verification code to {$email}. Please verify your email to activate your account.");

        return Response::redirect('/verify-email?email=' . urlencode($email));
    }

    public function verifyEmailForm(Request $request): Response
    {
        $session = Session::instance();

        // Check if user clicked a direct verification link from email
        $token = trim($request->query('token', ''));
        $email = strtolower(trim($request->query('email') ?: (string)$session->get('pending_verification_email', '')));

        if (!empty($token) && !empty($email)) {
            $verifyRes = VerificationService::verifyToken($email, $token);
            if ($verifyRes['ok'] && !empty($verifyRes['user'])) {
                $user = $verifyRes['user'];
                $session->remove('pending_verification_email');
                $session->remove('dev_verification_code');
                $session->setAuth((string)$user['uid'], $user['email'], $user['name'], $user['role']);
                $session->flash('success', 'Your email address has been successfully verified! Welcome to GlassCart.');
                return Response::redirect('/account');
            }

            $errors = ['general' => $verifyRes['error'] ?? 'Invalid or expired verification link.'];
        } else {
            $errors = $session->get('_errors', []);
        }

        if (empty($email)) {
            $session->flash('info', 'Please sign in or register to verify your email address.');
            return Response::redirect('/login');
        }

        $remainingCooldown = VerificationService::getRemainingCooldown($email);
        $isSmtpConfigured = !empty(\App\Core\Env::get('SMTP_HOST')) && !empty(\App\Core\Env::get('SMTP_USER'));
        $devCode = $session->get('dev_verification_code');

        return Response::html(View::render('pages/verify-email', [
            'title'             => 'Verify Email | GlassCart',
            'email'             => $email,
            'errors'            => $errors,
            'successMessage'    => $session->get('_flash_success'),
            'remainingCooldown' => $remainingCooldown,
            'unconfiguredSmtp'  => !$isSmtpConfigured,
            'devCode'           => $devCode,
            'noindex'           => true,
        ]));
    }

    public function verifyEmail(Request $request): Response
    {
        $session = Session::instance();
        $email = strtolower(trim($request->string('email') ?: (string)$session->get('pending_verification_email', '')));
        $otp = trim($request->string('otp', ''));

        if (empty($email)) {
            $session->flash('error', 'Session expired. Please sign in or register again.');
            return Response::redirect('/login');
        }

        $rateKey = 'verify_otp_' . md5($request->ip() . '_' . $email);
        if (RateLimiter::tooManyAttempts($rateKey, 10, 300)) {
            $seconds = RateLimiter::availableIn($rateKey, 300);
            return Response::html(View::render('pages/verify-email', [
                'title'             => 'Verify Email | GlassCart',
                'email'             => $email,
                'errors'            => ['otp' => "Too many verification attempts. Please wait {$seconds} seconds before trying again."],
                'remainingCooldown' => VerificationService::getRemainingCooldown($email),
                'unconfiguredSmtp'  => empty(\App\Core\Env::get('SMTP_HOST')),
                'devCode'           => $session->get('dev_verification_code'),
                'noindex'           => true,
            ]), 429);
        }

        $res = VerificationService::verifyOtp($email, $otp);
        if (!$res['ok']) {
            RateLimiter::hit($rateKey, 10, 300);
            return Response::html(View::render('pages/verify-email', [
                'title'             => 'Verify Email | GlassCart',
                'email'             => $email,
                'errors'            => ['otp' => $res['error'] ?? 'Invalid verification code.'],
                'remainingCooldown' => VerificationService::getRemainingCooldown($email),
                'unconfiguredSmtp'  => empty(\App\Core\Env::get('SMTP_HOST')),
                'devCode'           => $session->get('dev_verification_code'),
                'noindex'           => true,
            ]), 422);
        }

        RateLimiter::clear($rateKey);

        $user = $res['user'];
        $session->remove('pending_verification_email');
        $session->remove('dev_verification_code');
        $session->setAuth((string)$user['uid'], $user['email'], $user['name'], $user['role']);
        $session->flash('success', 'Your email address has been verified successfully! Welcome to GlassCart.');

        return Response::redirect('/account');
    }

    public function resendVerification(Request $request): Response
    {
        $session = Session::instance();
        $email = strtolower(trim($request->string('email') ?: (string)$session->get('pending_verification_email', '')));

        if (empty($email)) {
            $session->flash('error', 'Unable to determine email address for resend. Please try signing in.');
            return Response::redirect('/login');
        }

        // IP-based rate limiting on resend requests (max 6 per hour per IP)
        $ipKey = 'resend_otp_ip_' . md5($request->ip());
        if (RateLimiter::tooManyAttempts($ipKey, 6, 3600)) {
            $seconds = RateLimiter::availableIn($ipKey, 3600);
            $minutes = max(1, (int)ceil($seconds / 60));
            $session->flash('error', "Too many resend requests from your network. Please wait {$minutes} minute(s) before requesting again.");
            return Response::redirect('/verify-email?email=' . urlencode($email));
        }

        $res = VerificationService::resend($email);
        if (!$res['ok']) {
            $session->flash('error', $res['error'] ?? 'Could not resend verification code.');
            return Response::redirect('/verify-email?email=' . urlencode($email));
        }

        if (!empty($res['otp'])) {
            $session->set('dev_verification_code', $res['otp']);
        }

        RateLimiter::hit($ipKey, 6, 3600);
        $session->flash('success', "A fresh 6-digit verification code has been dispatched to {$email}.");
        return Response::redirect('/verify-email?email=' . urlencode($email));
    }

    public function confirmEmailLink(Request $request): Response
    {
        $session = Session::instance();
        $token = trim($request->query('token', ''));
        $email = strtolower(trim($request->query('email', '')));

        if (empty($token) || empty($email)) {
            $session->flash('error', 'Invalid verification link.');
            return Response::redirect('/login');
        }

        $res = VerificationService::verifyToken($email, $token);
        if (!$res['ok']) {
            $session->flash('error', $res['error'] ?? 'Verification link is invalid or has expired.');
            return Response::redirect('/verify-email?email=' . urlencode($email));
        }

        $user = $res['user'];
        $session->remove('pending_verification_email');
        $session->remove('dev_verification_code');
        $session->setAuth((string)$user['uid'], $user['email'], $user['name'], $user['role']);
        $session->flash('success', 'Your email address has been verified successfully! Welcome to GlassCart.');

        return Response::redirect('/account');
    }

    public function logout(Request $request): Response
    {
        Session::instance()->clearAuth();
        Session::instance()->flash('info', 'You have been signed out.');
        return Response::redirect('/');
    }

    public function forgotPasswordForm(Request $request): Response
    {
        return Response::html(View::render('pages/forgot-password', [
            'title'   => 'Reset Password | GlassCart',
            'errors'  => Session::instance()->get('_errors', []),
            'success' => false,
            'noindex' => true,
        ]));
    }

    public function sendPasswordReset(Request $request): Response
    {
        $email = strtolower(trim($request->string('email')));
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|max:150',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('pages/forgot-password', [
                'title'   => 'Reset Password | GlassCart',
                'errors'  => $validator->errors(),
                'success' => false,
                'noindex' => true,
            ]), 422);
        }

        // Anti-brute force delay
        usleep(300000);

        $appUrl = rtrim(\App\Core\Env::get('APP_URL', 'http://localhost:8080'), '/');
        $callbackUrl = "{$appUrl}/reset-password";

        // Dispatch recovery through Supabase GoTrue Auth
        $this->auth->recoverPassword($email, $callbackUrl);

        // Anti-enumeration: Always show success confirmation regardless of email existence
        return Response::html(View::render('pages/forgot-password', [
            'title'   => 'Reset Password | GlassCart',
            'errors'  => [],
            'success' => true,
            'noindex' => true,
        ]));
    }

    public function resetPasswordForm(Request $request): Response
    {
        $token = $request->query('token', $request->query('access_token', ''));
        return Response::html(View::render('pages/reset-password', [
            'title'   => 'Set New Password | GlassCart',
            'token'   => $token,
            'errors'  => Session::instance()->get('_errors', []),
            'noindex' => true,
        ]));
    }

    public function updatePassword(Request $request): Response
    {
        $password = (string)$request->input('password', '');
        $passwordConfirm = (string)$request->input('password_confirmation', '');
        $token = (string)$request->input('token', '');

        $validator = Validator::make([
            'password'              => $password,
            'password_confirmation' => $passwordConfirm,
        ], [
            'password' => 'required|min:6|max:100|confirmed',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('pages/reset-password', [
                'title'   => 'Set New Password | GlassCart',
                'token'   => $token,
                'errors'  => $validator->errors(),
                'noindex' => true,
            ]), 422);
        }

        $session = Session::instance();
        $session->flash('success', 'Your password has been securely reset. Please sign in with your new password.');
        return Response::redirect('/login');
    }

    public function oauth(Request $request): Response
    {
        $provider = strtolower(trim((string)$request->param('provider', '')));
        $allowed = ['google', 'facebook'];
        if (!in_array($provider, $allowed, true)) {
            return Response::redirect('/login');
        }

        $appUrl = rtrim(\App\Core\Env::get('APP_URL', 'http://localhost:8080'), '/');
        $callbackUrl = "{$appUrl}/auth/oauth/callback";

        // Preflight check against Supabase to handle unconfigured providers gracefully
        $status = $this->auth->checkOAuthProvider($provider, $callbackUrl);
        if (!$status['enabled']) {
            $name = ucfirst($provider);
            Session::instance()->flash(
                'warning',
                "{$name} Sign-In is not currently enabled in your Supabase project settings. Please sign in with email/password, or enable {$name} in your Supabase Dashboard under Authentication → Providers."
            );
            return Response::redirect('/login');
        }

        return Response::redirect($status['url']);
    }

    public function oauthCallback(Request $request): Response
    {
        $session = Session::instance();

        // 1. Check for provider/cancellation error in query string
        $error = $request->query('error');
        if ($error !== null && $error !== '') {
            $desc = (string)$request->query('error_description', 'Social login was cancelled or failed.');
            $session->flash('warning', $desc);
            return Response::redirect('/login');
        }

        // 2. PKCE Authorization Code flow
        $code = (string)$request->query('code', '');
        if ($code !== '') {
            $tokenRes = $this->auth->exchangeOAuthCode($code);
            if ($tokenRes['ok'] && !empty($tokenRes['data']['access_token'])) {
                $userData = $tokenRes['data']['user'] ?? null;
                if (!$userData) {
                    $userRes = $this->auth->getUserFromToken($tokenRes['data']['access_token']);
                    $userData = $userRes['user'] ?? null;
                }

                if ($userData && !empty($userData['id'])) {
                    $this->establishOAuthSession($userData);
                    $session->flash('success', 'Social authentication connected successfully.');
                    return Response::redirect('/account');
                }
            }

            $session->flash('error', 'Unable to complete social sign-in. Please try again.');
            return Response::redirect('/login');
        }

        // 3. Client-side hash fragment receiver (for implicit OAuth flow: #access_token=...)
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completing Sign-In | GlassCart</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0b0f19;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #f1f5f9;
    }
    .card {
      text-align: center;
      padding: 2.5rem;
      background: rgba(30, 41, 59, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 1rem;
      backdrop-filter: blur(12px);
      max-width: 400px;
      width: 90%;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .spinner {
      width: 44px;
      height: 44px;
      border: 3px solid rgba(255, 255, 255, 0.1);
      border-top-color: #38bdf8;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 1.5rem;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    h2 { margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 600; }
    p { margin: 0; color: #94a3b8; font-size: 0.875rem; }
  </style>
</head>
<body>
  <div class="card">
    <div class="spinner"></div>
    <h2>Completing Sign-In</h2>
    <p>Connecting your account securely...</p>
  </div>
  <script>
    (function() {
      var hash = window.location.hash.substring(1);
      var params = new URLSearchParams(hash);
      var token = params.get('access_token');
      var err = params.get('error_description') || params.get('error');

      if (token) {
        fetch('/auth/oauth/session', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ access_token: token })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data && data.ok) {
            window.location.replace(data.redirect || '/account');
          } else {
            window.location.replace('/login');
          }
        })
        .catch(function() {
          window.location.replace('/login');
        });
      } else if (err) {
        window.location.replace('/login');
      } else {
        window.location.replace('/login');
      }
    })();
  </script>
</body>
</html>
HTML;

        return Response::html($html);
    }

    public function oauthSession(Request $request): Response
    {
        $accessToken = (string)$request->input('access_token', '');
        if ($accessToken === '') {
            return Response::json(['ok' => false, 'error' => 'Missing access token.'], 400);
        }

        $res = $this->auth->getUserFromToken($accessToken);
        if (!$res['ok'] || empty($res['user']['id'])) {
            return Response::json(['ok' => false, 'error' => 'Invalid or expired session token.'], 401);
        }

        $this->establishOAuthSession($res['user']);
        Session::instance()->flash('success', 'Social authentication connected successfully.');

        return Response::json(['ok' => true, 'redirect' => '/account']);
    }

    private function establishOAuthSession(array $user): void
    {
        $uid = (string)$user['id'];
        $email = strtolower(trim((string)($user['email'] ?? '')));
        $meta = is_array($user['user_metadata'] ?? null) ? $user['user_metadata'] : [];
        $fullName = trim((string)($meta['full_name'] ?? ($meta['name'] ?? '')));

        // Check or upsert profile
        $profile = ProfileRepository::find($uid);
        if ($profile === null) {
            ProfileRepository::upsert($uid, [
                'email'     => $email,
                'full_name' => $fullName !== '' ? $fullName : 'Customer',
                'role'      => 'customer',
            ]);
            $role = 'customer';
            $finalName = $fullName !== '' ? $fullName : 'Customer';
        } else {
            $role = (string)($profile['role'] ?? 'customer');
            $finalName = (string)($profile['full_name'] ?? ($fullName !== '' ? $fullName : 'Customer'));
        }

        Session::instance()->setAuth($uid, $email, $finalName, $role);
    }

    public function sanitizeNext(?string $next): string
    {
        if ($next === null || $next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return '/account';
        }
        // Strict separation: Customer login must never redirect to the admin console
        if (str_starts_with($next, '/admin')) {
            return '/account';
        }
        return $next;
    }
}
