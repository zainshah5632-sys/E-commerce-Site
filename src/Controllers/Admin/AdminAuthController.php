<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\ProfileRepository;
use App\Services\RateLimiter;
use App\Supabase\Auth;

final class AdminAuthController
{
    private Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function loginForm(Request $request): Response
    {
        $session = Session::instance();

        // If already signed in as admin, go straight to admin dashboard
        if ($session->isAuthenticated() && $session->isAdmin()) {
            return Response::redirect('/admin');
        }

        $next = $request->query('next', '/admin');
        $errorMsg = null;
        if ($request->query('error') === 'denied') {
            $errorMsg = 'Access Denied: Your account does not have administrator privileges.';
        }

        return Response::html(View::render('admin/login', [
            'title'    => 'Admin Portal | GlassCart Control Center',
            'next'     => $next,
            'errors'   => $errorMsg ? ['login' => $errorMsg] : Session::instance()->get('_errors', []),
            'noindex'  => true,
        ], layout: null));
    }

    public function login(Request $request): Response
    {
        $session = Session::instance();
        $identifier = trim($request->string('identifier') ?: $request->string('email'));
        $password = (string)$request->input('password', '');
        $next = $request->string('next', '/admin');

        $ip = $request->ip();
        $rateKey = 'admin_login_' . md5($ip . '_' . strtolower($identifier));

        if (RateLimiter::tooManyAttempts($rateKey, 5, 900)) {
            $seconds = RateLimiter::availableIn($rateKey, 900);
            $minutes = max(1, (int)ceil($seconds / 60));
            return Response::html(View::render('admin/login', [
                'title'   => 'Admin Portal | GlassCart Control Center',
                'next'    => $next,
                'errors'  => ['login' => "Security lockout active. Too many failed attempts. Please retry in {$minutes} minute(s)."],
                'noindex' => true,
            ], layout: null), 429);
        }

        $validator = Validator::make([
            'identifier' => $identifier,
            'password'   => $password,
        ], [
            'identifier' => 'required',
            'password'   => 'required',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('admin/login', [
                'title'   => 'Admin Portal | GlassCart Control Center',
                'next'    => $next,
                'errors'  => $validator->errors(),
                'noindex' => true,
            ], layout: null), 422);
        }

        // Determine whether user provided email or username
        $targetEmail = $identifier;
        if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $profile = ProfileRepository::findByUsername($identifier);
            if (!$profile || empty($profile['email'])) {
                RateLimiter::hit($rateKey, 5, 900);
                return Response::html(View::render('admin/login', [
                    'title'   => 'Admin Portal | GlassCart Control Center',
                    'next'    => $next,
                    'errors'  => ['login' => 'Invalid administrative credentials.'],
                    'noindex' => true,
                ], layout: null), 401);
            }
            $targetEmail = (string)$profile['email'];
        }

        $res = $this->auth->login($targetEmail, $password);

        if (!$res['ok'] || empty($res['user'])) {
            RateLimiter::hit($rateKey, 5, 900);
            return Response::html(View::render('admin/login', [
                'title'   => 'Admin Portal | GlassCart Control Center',
                'next'    => $next,
                'errors'  => ['login' => 'Invalid administrative credentials.'],
                'noindex' => true,
            ], layout: null), 401);
        }

        $user = $res['user'];
        $uid = $user['uid'];

        // Strict Admin Privilege Verification
        $dbProfile = ProfileRepository::find($uid);
        $role = $dbProfile['role'] ?? $user['role'];

        if ($role !== 'admin') {
            RateLimiter::hit($rateKey, 5, 900);
            return Response::html(View::render('admin/login', [
                'title'   => 'Admin Portal | GlassCart Control Center',
                'next'    => $next,
                'errors'  => ['login' => 'Access Denied: Administrator clearance is required to enter this system.'],
                'noindex' => true,
            ], layout: null), 403);
        }

        RateLimiter::clear($rateKey);


        $session->setAuth($uid, $user['email'], $user['name'] ?: 'Administrator', 'admin');
        $session->flash('success', 'Admin session authenticated successfully.');

        // Sanitize redirect target
        if (!str_starts_with($next, '/admin')) {
            $next = '/admin';
        }

        return Response::redirect($next);
    }

    public function logout(Request $request): Response
    {
        $session = Session::instance();
        $session->clearAuth();
        $session->flash('info', 'Admin console session closed.');
        return Response::redirect('/admin/login');
    }
}
