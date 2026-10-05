<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\ProfileRepository;
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
                return Response::html(View::render('pages/login', [
                    'title'   => 'Sign In | GlassCart',
                    'next'    => $next,
                    'errors'  => ['login' => 'Invalid username or password.'],
                    'noindex' => true,
                ]), 401);
            }
            $targetEmail = (string)$profile['email'];
        }

        $res = $this->auth->login($targetEmail, $password);

        if (!$res['ok'] || empty($res['user'])) {
            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In | GlassCart',
                'next'    => $next,
                'errors'  => ['login' => $res['error'] ?? 'Invalid credentials.'],
                'noindex' => true,
            ]), 401);
        }

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

        // Auto-login after registration (Decision D-007)
        $user = $regRes['user'] ?? [];
        $uid = $user['id'] ?? null;
        if ($uid) {
            $session->setAuth((string)$uid, $email, $fullName, 'customer');
            $session->flash('success', 'Your account has been created successfully!');
            return Response::redirect('/account');
        }

        $loginRes = $this->auth->login($email, $password);
        if ($loginRes['ok'] && !empty($loginRes['user'])) {
            $user = $loginRes['user'];
            $session->setAuth($user['uid'], $user['email'], $user['name'], $user['role']);
            $session->flash('success', 'Your account has been created successfully!');
            return Response::redirect('/account');
        }

        $session->flash('success', 'Registration successful! Please sign in with your credentials.');
        return Response::redirect('/login');
    }

    public function logout(Request $request): Response
    {
        Session::instance()->clearAuth();
        Session::instance()->flash('info', 'You have been signed out.');
        return Response::redirect('/');
    }

    private function sanitizeNext(?string $next): string
    {
        if ($next === null || $next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return '/account';
        }
        return $next;
    }
}
