<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\CategoryRepository;

final class CategoryAdminController
{
    public function index(Request $request): Response
    {
        $categories = CategoryRepository::allAdmin();

        return Response::html(View::render('admin/categories', [
            'title'      => 'Manage Categories — GlassCart Admin',
            'categories' => $categories,
            'errors'     => [],
        ], layout: 'admin'));
    }

    public function store(Request $request): Response
    {
        $name = $request->string('name');
        $slug = $request->string('slug') ?: slugify($name);

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $request->string('description'),
            'sort_order'  => $request->int('sort_order', 0),
            'is_active'   => $request->bool('is_active', true),
        ];

        $validator = Validator::make($data, [
            'name' => 'required|min:2|max:80',
            'slug' => 'required|regex:/^[a-z0-9-]+$/',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('admin/categories', [
                'title'      => 'Manage Categories — GlassCart Admin',
                'categories' => CategoryRepository::allAdmin(),
                'errors'     => $validator->errors(),
            ], layout: 'admin'), 422);
        }

        $ok = CategoryRepository::create($data);
        if ($ok) {
            Session::instance()->flash('success', 'Category created successfully.');
        } else {
            Session::instance()->flash('error', 'Category creation failed. Slug may be duplicate.');
        }

        return Response::redirect('/admin/categories');
    }

    public function update(Request $request): Response
    {
        $id = (string)$request->param('id');
        $name = $request->string('name');
        $slug = $request->string('slug') ?: slugify($name);

        $patch = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $request->string('description'),
            'sort_order'  => $request->int('sort_order', 0),
            'is_active'   => $request->bool('is_active'),
        ];

        CategoryRepository::update($id, $patch);
        Session::instance()->flash('success', 'Category updated successfully.');
        return Response::redirect('/admin/categories');
    }

    public function destroy(Request $request): Response
    {
        $id = (string)$request->param('id');
        CategoryRepository::delete($id);
        Session::instance()->flash('info', 'Category removed.');
        return Response::redirect('/admin/categories');
    }
}
