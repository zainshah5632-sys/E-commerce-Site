<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
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
            'title'   => 'Sign In — GlassCart',
            'next'    => $next,
            'errors'  => Session::instance()->get('_errors', []),
            'noindex' => true,
        ]));
    }

    public function login(Request $request): Response
    {
        $session = Session::instance();
        $email = $request->string('email');
        $password = (string)$request->input('password', '');
        $next = $this->sanitizeNext($request->string('next'));

        $validator = Validator::make([
            'email'    => $email,
            'password' => $password,
        ], [
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In — GlassCart',
                'next'    => $next,
                'errors'  => $validator->errors(),
                'noindex' => true,
            ]), 422);
        }

        $res = $this->auth->login($email, $password);

        if (!$res['ok'] || empty($res['user'])) {
            return Response::html(View::render('pages/login', [
                'title'   => 'Sign In — GlassCart',
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
            'title'   => 'Create Account — GlassCart',
            'errors'  => Session::instance()->get('_errors', []),
            'noindex' => true,
        ]));
    }

    public function register(Request $request): Response
    {
        $session = Session::instance();
        $fullName = $request->string('full_name');
        $email = $request->string('email');
        $password = (string)$request->input('password', '');
        $passwordConfirm = (string)$request->input('password_confirmation', '');

        $validator = Validator::make([
            'full_name'             => $fullName,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $passwordConfirm,
        ], [
            'full_name' => 'required|min:2|max:100',
            'email'     => 'required|email|max:150',
            'password'  => 'required|min:8|max:100|confirmed',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('pages/register', [
                'title'   => 'Create Account — GlassCart',
                'errors'  => $validator->errors(),
                'noindex' => true,
            ]), 422);
        }

        $regRes = $this->auth->register($email, $password, $fullName);
        if (!$regRes['ok']) {
            return Response::html(View::render('pages/register', [
                'title'   => 'Create Account — GlassCart',
                'errors'  => ['registration' => $regRes['error'] ?? 'Registration failed.'],
                'noindex' => true,
            ]), 400);
        }

        // Auto-login after registration (Decision D-007)
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
