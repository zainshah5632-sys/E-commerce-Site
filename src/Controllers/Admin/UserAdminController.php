<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\ProfileRepository;
use App\Supabase\Auth;

final class UserAdminController
{
    private Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q'    => $request->query('q', ''),
            'role' => $request->query('role', ''),
        ];
        $page = max(1, $request->int('page', 1));
        $data = ProfileRepository::list($filters, $page, 15);

        return Response::html(View::render('admin/users-index', [
            'title'   => 'User Management — GlassCart Admin',
            'users'   => $data['users'],
            'total'   => $data['total'],
            'page'    => $data['page'],
            'pages'   => $data['pages'],
            'filters' => $filters,
        ], layout: 'admin'));
    }

    public function create(Request $request): Response
    {
        return Response::html(View::render('admin/user-form', [
            'title'  => 'Create New User — GlassCart Admin',
            'user'   => null,
            'errors' => Session::instance()->get('_errors', []),
        ], layout: 'admin'));
    }

    public function store(Request $request): Response
    {
        $session = Session::instance();
        $fullName = $request->string('full_name');
        $username = strtolower(trim($request->string('username')));
        $email = strtolower(trim($request->string('email')));
        $phone = preg_replace('/[^0-9]/', '', $request->string('phone'));
        $countryCode = trim($request->string('country_code', '+1'));
        $role = $request->string('role', 'customer');
        $password = (string)$request->input('password', '');

        $validator = Validator::make([
            'full_name'    => $fullName,
            'username'     => $username,
            'email'        => $email,
            'role'         => $role,
            'password'     => $password,
            'country_code' => $countryCode,
            'phone'        => $phone,
        ], [
            'full_name'    => 'required|alpha_spaces|min:2|max:100',
            'username'     => 'required|username',
            'email'        => 'required|email|max:150',
            'role'         => 'required|in:customer,admin',
            'password'     => 'required|min:8|complex_password',
            'country_code' => 'max:6',
        ]);

        if ($phone !== '' && strlen($phone) !== 11) {
            $validator->errors()['phone'] = 'Phone number must be exactly 11 digits.';
        }

        if ($validator->fails()) {
            return Response::html(View::render('admin/user-form', [
                'title'  => 'Create New User — GlassCart Admin',
                'user'   => null,
                'errors' => $validator->errors(),
            ], layout: 'admin'), 422);
        }

        // Check if username taken
        if (ProfileRepository::findByUsername($username) !== null) {
            return Response::html(View::render('admin/user-form', [
                'title'  => 'Create New User — GlassCart Admin',
                'user'   => null,
                'errors' => ['username' => 'This username is already taken.'],
            ], layout: 'admin'), 422);
        }

        $res = $this->auth->register($email, $password, $fullName, [
            'username'     => $username,
            'phone'        => $phone,
            'country_code' => $countryCode,
            'role'         => $role,
        ]);

        if (!$res['ok']) {
            return Response::html(View::render('admin/user-form', [
                'title'  => 'Create New User — GlassCart Admin',
                'user'   => null,
                'errors' => ['general' => $res['error'] ?? 'User creation failed.'],
            ], layout: 'admin'), 400);
        }

        // Ensure role is set in profile
        $uid = $res['user']['id'] ?? null;
        if ($uid && $role === 'admin') {
            ProfileRepository::update((string)$uid, ['role' => 'admin']);
        }

        $session->flash('success', "User account for {$fullName} created successfully.");
        return Response::redirect('/admin/users');
    }

    public function edit(Request $request): Response
    {
        $id = (string)$request->param('id');
        $user = ProfileRepository::find($id);

        if (!$user) {
            return Response::html(View::render('pages/404', ['title' => 'User Not Found']), 404);
        }

        return Response::html(View::render('admin/user-form', [
            'title'  => 'Edit User — ' . e($user['full_name'] ?? 'User'),
            'user'   => $user,
            'errors' => Session::instance()->get('_errors', []),
        ], layout: 'admin'));
    }

    public function update(Request $request): Response
    {
        $session = Session::instance();
        $id = (string)$request->param('id');
        $user = ProfileRepository::find($id);

        if (!$user) {
            return Response::html(View::render('pages/404', ['title' => 'User Not Found']), 404);
        }

        $fullName = $request->string('full_name');
        $username = strtolower(trim($request->string('username')));
        $phone = preg_replace('/[^0-9]/', '', $request->string('phone'));
        $countryCode = trim($request->string('country_code', '+1'));
        $role = $request->string('role', 'customer');
        $password = (string)$request->input('password', '');

        $rules = [
            'full_name'    => 'required|alpha_spaces|min:2|max:100',
            'username'     => 'required|username',
            'role'         => 'required|in:customer,admin',
            'country_code' => 'max:6',
        ];

        if ($password !== '') {
            $rules['password'] = 'min:8|complex_password';
        }

        $validator = Validator::make([
            'full_name'    => $fullName,
            'username'     => $username,
            'role'         => $role,
            'country_code' => $countryCode,
            'phone'        => $phone,
            'password'     => $password,
        ], $rules);

        if ($phone !== '' && strlen($phone) !== 11) {
            $validator->errors()['phone'] = 'Phone number must be exactly 11 digits.';
        }

        if ($validator->fails()) {
            return Response::html(View::render('admin/user-form', [
                'title'  => 'Edit User — ' . e($fullName),
                'user'   => array_merge($user, [
                    'full_name'    => $fullName,
                    'username'     => $username,
                    'phone'        => $phone,
                    'country_code' => $countryCode,
                    'role'         => $role,
                ]),
                'errors' => $validator->errors(),
            ], layout: 'admin'), 422);
        }

        // Check if username taken by someone else
        $existing = ProfileRepository::findByUsername($username);
        if ($existing && ($existing['id'] ?? '') !== $id) {
            return Response::html(View::render('admin/user-form', [
                'title'  => 'Edit User — ' . e($fullName),
                'user'   => $user,
                'errors' => ['username' => 'This username is already taken by another user.'],
            ], layout: 'admin'), 422);
        }

        // Prevent admin from demoting themselves
        if ($id === $session->userId() && $role !== 'admin') {
            $session->flash('error', 'You cannot remove your own administrator privileges.');
            return Response::redirect('/admin/users/' . $id . '/edit');
        }

        // Update password if provided
        if ($password !== '') {
            $this->auth->updateUser($id, ['password' => $password]);
        }

        ProfileRepository::update($id, [
            'full_name'    => $fullName,
            'username'     => $username,
            'phone'        => $phone,
            'country_code' => $countryCode,
            'role'         => $role,
        ]);

        $session->flash('success', "User {$fullName} updated successfully.");
        return Response::redirect('/admin/users');
    }

    public function destroy(Request $request): Response
    {
        $session = Session::instance();
        $id = (string)$request->param('id');

        if ($id === $session->userId()) {
            $session->flash('error', 'Security violation: You cannot delete your own active administrator account.');
            return Response::redirect('/admin/users');
        }

        $this->auth->deleteUser($id);
        ProfileRepository::delete($id);

        $session->flash('success', 'User account was deleted permanently.');
        return Response::redirect('/admin/users');
    }

    public function toggleRole(Request $request): Response
    {
        $session = Session::instance();
        $id = (string)$request->param('id');

        if ($id === $session->userId()) {
            $session->flash('error', 'You cannot alter your own administrative role.');
            return Response::redirect('/admin/users');
        }

        $user = ProfileRepository::find($id);
        if (!$user) {
            $session->flash('error', 'User not found.');
            return Response::redirect('/admin/users');
        }

        $newRole = (($user['role'] ?? '') === 'admin') ? 'customer' : 'admin';
        ProfileRepository::update($id, ['role' => $newRole]);

        $session->flash('success', "Role for {$user['full_name']} updated to {$newRole}.");
        return Response::redirect('/admin/users');
    }
}
