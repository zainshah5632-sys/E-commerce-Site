<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Supabase\Storage;

final class ProductAdminController
{
    public function index(Request $request): Response
    {
        $page = max(1, $request->int('page', 1));
        $filters = [
            'q'           => $request->query('q', ''),
            'category_id' => $request->query('category_id', ''),
            'is_active'   => $request->query('is_active', ''),
        ];

        $catalog = ProductRepository::adminList($filters, $page, 20);
        $categories = CategoryRepository::allAdmin();

        return Response::html(View::render('admin/products-index', [
            'title'      => 'Manage Products — GlassCart Admin',
            'products'   => $catalog['products'],
            'total'      => $catalog['total'],
            'page'       => $catalog['page'],
            'pages'      => $catalog['pages'],
            'categories' => $categories,
            'filters'    => $filters,
        ], layout: 'admin'));
    }

    public function create(Request $request): Response
    {
        $categories = CategoryRepository::allAdmin();

        return Response::html(View::render('admin/product-form', [
            'title'      => 'New Product — GlassCart Admin',
            'product'    => null,
            'categories' => $categories,
            'errors'     => [],
        ], layout: 'admin'));
    }

    public function store(Request $request): Response
    {
        $session = Session::instance();
        $name = $request->string('name');
        $slug = $request->string('slug');
        if ($slug === '') {
            $slug = slugify($name);
        }

        $priceMajor = (float)$request->input('price', 0);
        $priceCents = (int)round($priceMajor * 100);

        $compareMajor = $request->input('compare_at_price');
        $compareCents = ($compareMajor !== null && $compareMajor !== '') ? (int)round((float)$compareMajor * 100) : null;

        $data = [
            'name'              => $name,
            'slug'              => $slug,
            'category_id'       => $request->string('category_id') ?: null,
            'sku'               => $request->string('sku') ?: null,
            'price_cents'       => $priceCents,
            'compare_at_cents'  => $compareCents,
            'stock'             => max(0, $request->int('stock', 0)),
            'short_description' => $request->string('short_description'),
            'description'       => $request->string('description'),
            'is_active'         => $request->bool('is_active'),
            'is_featured'       => $request->bool('is_featured'),
            'images'            => '{}',
        ];

        $validator = Validator::make([
            'name'        => $name,
            'slug'        => $slug,
            'price_cents' => $priceCents,
            'stock'       => $data['stock'],
        ], [
            'name'        => 'required|min:2|max:150',
            'slug'        => 'required|regex:/^[a-z0-9-]+$/',
            'price_cents' => 'required|min:0',
            'stock'       => 'required|min:0',
        ]);

        if ($validator->fails()) {
            return Response::html(View::render('admin/product-form', [
                'title'      => 'New Product — GlassCart Admin',
                'product'    => $data,
                'categories' => CategoryRepository::allAdmin(),
                'errors'     => $validator->errors(),
            ], layout: 'admin'), 422);
        }

        // Handle direct image URLs (up to 5 total)
        $imageUrls = $request->input('image_urls', []);
        $imagesList = [];
        if (is_array($imageUrls)) {
            foreach ($imageUrls as $u) {
                $trimmed = trim((string)$u);
                if ($trimmed !== '' && filter_var($trimmed, FILTER_VALIDATE_URL) && count($imagesList) < 5) {
                    $imagesList[] = $trimmed;
                }
            }
        }
        if (!empty($imagesList)) {
            $data['images'] = '{' . implode(',', array_map(static fn($u) => '"' . $u . '"', $imagesList)) . '}';
        }

        $created = ProductRepository::create($data);
        if (!$created || empty($created['id'])) {
            return Response::html(View::render('admin/product-form', [
                'title'      => 'New Product — GlassCart Admin',
                'product'    => $data,
                'categories' => CategoryRepository::allAdmin(),
                'errors'     => ['general' => 'Failed to create product. The slug or SKU may already exist.'],
            ], layout: 'admin'), 400);
        }

        // Handle uploaded images
        $productId = (string)$created['id'];
        $this->processUploadedImages($productId, $request);

        $session->flash('success', 'Product created successfully.');
        return Response::redirect('/admin/products');

    }

    public function edit(Request $request): Response
    {
        $id = (string)$request->param('id');
        $product = ProductRepository::findById($id);
        if (!$product) {
            return Response::html(View::render('pages/404', ['title' => 'Product Not Found']), 404);
        }

        $categories = CategoryRepository::allAdmin();

        return Response::html(View::render('admin/product-form', [
            'title'      => "Edit {$product['name']} — GlassCart Admin",
            'product'    => $product,
            'categories' => $categories,
            'errors'     => [],
        ], layout: 'admin'));
    }

    public function update(Request $request): Response
    {
        $id = (string)$request->param('id');
        $product = ProductRepository::findById($id);
        if (!$product) {
            return Response::html(View::render('pages/404', ['title' => 'Product Not Found']), 404);
        }

        $name = $request->string('name');
        $slug = $request->string('slug') ?: slugify($name);

        $priceMajor = (float)$request->input('price', 0);
        $priceCents = (int)round($priceMajor * 100);

        $compareMajor = $request->input('compare_at_price');
        $compareCents = ($compareMajor !== null && $compareMajor !== '') ? (int)round((float)$compareMajor * 100) : null;

        $patch = [
            'name'              => $name,
            'slug'              => $slug,
            'category_id'       => $request->string('category_id') ?: null,
            'sku'               => $request->string('sku') ?: null,
            'price_cents'       => $priceCents,
            'compare_at_cents'  => $compareCents,
            'stock'             => max(0, $request->int('stock', 0)),
            'short_description' => $request->string('short_description'),
            'description'       => $request->string('description'),
            'is_active'         => $request->bool('is_active'),
            'is_featured'       => $request->bool('is_featured'),
        ];

        // Process any image removal
        $removeImage = $request->string('remove_image');
        $currentImages = is_array($product['images'] ?? null) ? $product['images'] : [];
        if ($removeImage !== '') {
            $storage = new Storage();
            $storage->deleteImage($removeImage);
            $currentImages = array_values(array_filter($currentImages, static fn($img) => $img !== $removeImage));
        }

        // Add any newly provided image URLs
        $newUrls = $request->input('image_urls', []);
        if (is_array($newUrls)) {
            foreach ($newUrls as $u) {
                $trimmed = trim((string)$u);
                if ($trimmed !== '' && filter_var($trimmed, FILTER_VALIDATE_URL) && !in_array($trimmed, $currentImages, true) && count($currentImages) < 5) {
                    $currentImages[] = $trimmed;
                }
            }
        }
        $patch['images'] = '{' . implode(',', array_map(static fn($u) => '"' . $u . '"', $currentImages)) . '}';

        ProductRepository::update($id, $patch);

        // Process newly uploaded files
        $this->processUploadedImages($id, $request);


        Session::instance()->flash('success', 'Product updated successfully.');
        return Response::redirect('/admin/products');
    }

    public function destroy(Request $request): Response
    {
        $id = (string)$request->param('id');
        $product = ProductRepository::findById($id);

        if ($product) {
            // Delete associated images from Supabase storage
            $storage = new Storage();
            $images = is_array($product['images'] ?? null) ? $product['images'] : [];
            foreach ($images as $imgUrl) {
                if (is_string($imgUrl)) {
                    $storage->deleteImage($imgUrl);
                }
            }
            ProductRepository::delete($id);
            Session::instance()->flash('success', 'Product and its images were deleted.');
        }

        return Response::redirect('/admin/products');
    }

    public function toggle(Request $request): Response
    {
        $id = (string)$request->param('id');
        $field = $request->string('field', 'is_active');

        ProductRepository::toggle($id, $field);
        return Response::redirect('/admin/products');
    }

    private function processUploadedImages(string $productId, Request $request): void
    {
        $files = $request->file('image_uploads');
        if (!$files || empty($files['name'])) {
            return;
        }

        $storage = new Storage();
        $product = ProductRepository::findById($productId);
        $currentImages = is_array($product['images'] ?? null) ? $product['images'] : [];

        $uploadedUrls = [];

        // Handle single or multiple file uploads
        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count && (count($currentImages) + count($uploadedUrls)) < 5; $i++) {
                if (($files['error'][$i] ?? 1) === UPLOAD_ERR_OK) {
                    $item = [
                        'name'     => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'size'     => $files['size'][$i],
                        'type'     => $files['type'][$i],
                    ];
                    $url = $storage->uploadProductImage($productId, $item);
                    if ($url !== null) {
                        $uploadedUrls[] = $url;
                    }
                }
            }
        } elseif (($files['error'] ?? 1) === UPLOAD_ERR_OK && count($currentImages) < 5) {
            $url = $storage->uploadProductImage($productId, $files);
            if ($url !== null) {
                $uploadedUrls[] = $url;
            }
        }

        if (!empty($uploadedUrls)) {
            $allImages = array_merge($currentImages, $uploadedUrls);
            $formatted = '{' . implode(',', array_map(static fn($u) => '"' . $u . '"', $allImages)) . '}';
            ProductRepository::update($productId, ['images' => $formatted]);
        }
    }
}
