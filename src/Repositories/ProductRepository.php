<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Supabase\Client;

final class ProductRepository
{
    private const CACHE_FEATURED = 'products_featured';
    private const CACHE_NEWEST = 'products_newest';
    private const TTL = 60;

    /**
     * Complete seed fallback catalog for resilience when remote database is unreachable.
     */
    public static function defaults(): array
    {
        return [
            [
                'id'                => 'a1000000-0000-0000-0000-000000000001',
                'category_id'       => 'c1000000-0000-0000-0000-000000000001',
                'name'              => 'Aura One Wireless Headphones',
                'slug'              => 'aura-one-wireless-headphones',
                'short_description' => 'Active noise-canceling studio cans with custom 40mm beryllium drivers.',
                'description'       => 'Engineered for discerning audiophiles and focused professionals alike. Aura One features adaptive spatial acoustic tuning, low-latency Bluetooth 5.3, and plush memory-foam ear cushions wrapped in breathable vegan leather for all-day listening.',
                'price_cents'       => 24900,
                'compare_at_cents'  => 29900,
                'sku'               => 'AUD-AURA-1',
                'stock'             => 35,
                'images'            => ['/assets/img/products/aura-one-wireless-headphones.jpg'],
                'is_active'         => true,
                'is_featured'       => true,
                'created_at'        => '2026-10-01T12:00:00Z',
                'category'          => ['name' => 'Audio', 'slug' => 'audio'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000002',
                'category_id'       => 'c1000000-0000-0000-0000-000000000001',
                'name'              => 'Clarity Desktop Studio Monitors',
                'slug'              => 'clarity-desktop-studio-monitors',
                'short_description' => 'Pair of compact reference speakers with transparent acrylic waveguide.',
                'description'       => 'Delivers an uncolored flat-frequency profile that elevates nearfield production and critical listening. Each monitor features custom acoustic waveguides and discrete Class-D amplification for pristine high-fidelity sound.',
                'price_cents'       => 18900,
                'compare_at_cents'  => null,
                'sku'               => 'AUD-CLAR-2',
                'stock'             => 18,
                'images'            => ['/assets/img/products/clarity-desktop-studio-monitors.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:05:00Z',
                'category'          => ['name' => 'Audio', 'slug' => 'audio'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000003',
                'category_id'       => 'c1000000-0000-0000-0000-000000000001',
                'name'              => 'Pulse True Wireless Earbuds',
                'slug'              => 'pulse-true-wireless-earbuds',
                'short_description' => 'Sweatproof in-ear buds with transparency mode and 32-hour battery life.',
                'description'       => 'Designed for movement and daily commute. Pulse earbuds seal comfortably with silicone contour tips and incorporate environmental beamforming microphones for crystal-clear voice transmission.',
                'price_cents'       => 9900,
                'compare_at_cents'  => 12900,
                'sku'               => 'AUD-PULS-3',
                'stock'             => 42,
                'images'            => ['/assets/img/products/pulse-true-wireless-earbuds.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:10:00Z',
                'category'          => ['name' => 'Audio', 'slug' => 'audio'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000004',
                'category_id'       => 'c1000000-0000-0000-0000-000000000002',
                'name'              => 'Vektor Chrono Smartwatch',
                'slug'              => 'vektor-chrono-smartwatch',
                'short_description' => 'Titanium unibody smartwatch with sapphire crystal display and health telemetry.',
                'description'       => 'Blending Scandinavian horology with modern health intelligence. Vektor monitors continuous heart rate variability, skin temperature, and sleep cycles while delivering seamless notifications with a 10-day battery reserve.',
                'price_cents'       => 28900,
                'compare_at_cents'  => null,
                'sku'               => 'WEAR-VEK-1',
                'stock'             => 25,
                'images'            => ['/assets/img/products/vektor-chrono-smartwatch.jpg'],
                'is_active'         => true,
                'is_featured'       => true,
                'created_at'        => '2026-10-01T12:15:00Z',
                'category'          => ['name' => 'Wearables', 'slug' => 'wearables'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000005',
                'category_id'       => 'c1000000-0000-0000-0000-000000000002',
                'name'              => 'Lumen Blue-Light Frames',
                'slug'              => 'lumen-blue-light-frames',
                'short_description' => 'Ultralight acetate frames with anti-reflective visual wellness lenses.',
                'description'       => 'Alleviate digital eye strain during marathon creative sessions. Lumen frames feature precision optical filtration that blocks 420nm high-energy blue wavelengths without color distortion.',
                'price_cents'       => 6500,
                'compare_at_cents'  => null,
                'sku'               => 'WEAR-LUM-2',
                'stock'             => 50,
                'images'            => ['/assets/img/products/lumen-blue-light-frames.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:20:00Z',
                'category'          => ['name' => 'Wearables', 'slug' => 'wearables'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000006',
                'category_id'       => 'c1000000-0000-0000-0000-000000000002',
                'name'              => 'Apex Performance Fitness Ring',
                'slug'              => 'apex-performance-fitness-ring',
                'short_description' => 'Aerospace ceramic smart ring tracking recovery and athletic strain.',
                'description'       => 'A featherweight smart ring that disappears on your finger. Apex captures nocturnal metrics, resting vitals, and cardio recovery trends without distracting touchscreens or vibrations.',
                'price_cents'       => 19900,
                'compare_at_cents'  => null,
                'sku'               => 'WEAR-APX-3',
                'stock'             => 0,
                'images'            => ['/assets/img/products/apex-performance-fitness-ring.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:25:00Z',
                'category'          => ['name' => 'Wearables', 'slug' => 'wearables'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000007',
                'category_id'       => 'c1000000-0000-0000-0000-000000000003',
                'name'              => 'Horizon Linear Desk Lamp',
                'slug'              => 'horizon-linear-desk-lamp',
                'short_description' => 'Sculpted aluminum task lamp with capacitive touch dimming and warm-to-cool LED.',
                'description'       => 'Illuminate your creative workspace with glare-free edge-lit optics. Horizon balances a weighted brushed aluminum cantilever base with infinitely variable color temperature presets from 2700K to 6500K.',
                'price_cents'       => 14500,
                'compare_at_cents'  => null,
                'sku'               => 'DSK-HOR-1',
                'stock'             => 20,
                'images'            => ['/assets/img/products/horizon-linear-desk-lamp.jpg'],
                'is_active'         => true,
                'is_featured'       => true,
                'created_at'        => '2026-10-01T12:30:00Z',
                'category'          => ['name' => 'Desk & Home', 'slug' => 'desk-home'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000008',
                'category_id'       => 'c1000000-0000-0000-0000-000000000003',
                'name'              => 'Prism Solid Acrylic Laptop Stand',
                'slug'              => 'prism-solid-acrylic-laptop-stand',
                'short_description' => 'Laser-polished transparent riser promoting optimal ergonomic eye height.',
                'description'       => 'Crafted from thick structural acrylic with polished chamfered bevels. Prism elevates any notebook up to 16 inches for improved posture and increased passive thermal airflow.',
                'price_cents'       => 4900,
                'compare_at_cents'  => null,
                'sku'               => 'DSK-PRM-2',
                'stock'             => 30,
                'images'            => ['/assets/img/products/prism-solid-acrylic-laptop-stand.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:35:00Z',
                'category'          => ['name' => 'Desk & Home', 'slug' => 'desk-home'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000009',
                'category_id'       => 'c1000000-0000-0000-0000-000000000003',
                'name'              => 'Vertex Felt & Cork Desk Mat',
                'slug'              => 'vertex-felt-cork-desk-mat',
                'short_description' => 'Dual-surface reversible desk pad dampening keyboard resonance.',
                'description'       => 'Organize your desk surface in understated elegance. Vertex pairs high-density merino wool felt on one face with natural Portuguese non-slip cork on the other.',
                'price_cents'       => 3800,
                'compare_at_cents'  => null,
                'sku'               => 'DSK-VTX-3',
                'stock'             => 55,
                'images'            => ['/assets/img/products/vertex-felt-cork-desk-mat.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:40:00Z',
                'category'          => ['name' => 'Desk & Home', 'slug' => 'desk-home'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000010',
                'category_id'       => 'c1000000-0000-0000-0000-000000000004',
                'name'              => 'Flux Braided Thunderbolt Cable (2m)',
                'slug'              => 'flux-braided-thunderbolt-cable-2m',
                'short_description' => 'Reinforced 40Gbps data transfer and 100W Power Delivery cable with silicone ties.',
                'description'       => 'Built with rugged ballistic nylon outer weave and CNC alloy housings. Flux ensures full Thunderbolt 4 data rates, dual 4K video bandwidth, and rapid high-wattage device charging.',
                'price_cents'       => 2900,
                'compare_at_cents'  => null,
                'sku'               => 'ACC-FLX-1',
                'stock'             => 60,
                'images'            => ['/assets/img/products/flux-braided-thunderbolt-cable-2m.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:45:00Z',
                'category'          => ['name' => 'Accessories', 'slug' => 'accessories'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000011',
                'category_id'       => 'c1000000-0000-0000-0000-000000000004',
                'name'              => 'Kinetics Magnetic Cable Anchor',
                'slug'              => 'kinetics-magnetic-cable-anchor',
                'short_description' => 'Weighted steel desk puck with interchangeable silicone magnetic cord collars.',
                'description'       => 'Keep your charging and peripheral cables right where you need them. Kinetics anchors securely to any desktop via micro-suction tape and guides cables smoothly into place.',
                'price_cents'       => 1900,
                'compare_at_cents'  => null,
                'sku'               => 'ACC-KIN-2',
                'stock'             => 40,
                'images'            => ['/assets/img/products/kinetics-magnetic-cable-anchor.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:50:00Z',
                'category'          => ['name' => 'Accessories', 'slug' => 'accessories'],
            ],
            [
                'id'                => 'a1000000-0000-0000-0000-000000000012',
                'category_id'       => 'c1000000-0000-0000-0000-000000000004',
                'name'              => 'Nexus Anodized Multi-Port Hub',
                'slug'              => 'nexus-anodized-multi-port-hub',
                'short_description' => '7-in-1 compact USB-C hub supporting HDMI 4K60, UHS-II SD, and pass-through power.',
                'description'       => 'The quintessential travel companion for laptop creators. Nexus packs high-speed memory card readers, dual USB 3.2 Gen 2 ports, and 100W power delivery into an aluminum shell.',
                'price_cents'       => 7900,
                'compare_at_cents'  => null,
                'sku'               => 'ACC-NEX-3',
                'stock'             => 22,
                'images'            => ['/assets/img/products/nexus-anodized-multi-port-hub.jpg'],
                'is_active'         => true,
                'is_featured'       => false,
                'created_at'        => '2026-10-01T12:55:00Z',
                'category'          => ['name' => 'Accessories', 'slug' => 'accessories'],
            ],
        ];
    }

    /**
     * Ensures product has valid image array, falling back to local asset if available.
     */
    public static function normalize(array $product): array
    {
        $images = $product['images'] ?? [];
        if ((!is_array($images) || empty($images)) && !empty($product['slug'])) {
            $localRel = 'assets/img/products/' . $product['slug'] . '.jpg';
            $fullPath = dirname(__DIR__, 2) . '/public/' . $localRel;
            if (is_file($fullPath)) {
                $product['images'] = ['/' . $localRel];
            }
        }
        return $product;
    }

    /**
     * In-memory filtering and sorting for fallback catalog.
     */
    public static function filterDefaults(array $params = [], int $page = 1, int $perPage = 12): array
    {
        $items = self::defaults();

        // Category filter
        if (!empty($params['category_id'])) {
            $items = array_filter($items, static fn($p) => ($p['category_id'] ?? '') === $params['category_id']);
        }

        // Min price
        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $minCents = (int)$params['min_price'] * 100;
            $items = array_filter($items, static fn($p) => (int)($p['price_cents'] ?? 0) >= $minCents);
        }

        // Max price
        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $maxCents = (int)$params['max_price'] * 100;
            $items = array_filter($items, static fn($p) => (int)($p['price_cents'] ?? 0) <= $maxCents);
        }

        // Search term
        if (!empty($params['q'])) {
            $q = mb_strtolower(trim((string)$params['q']));
            $items = array_filter($items, static function ($p) use ($q) {
                $name = mb_strtolower((string)($p['name'] ?? ''));
                $short = mb_strtolower((string)($p['short_description'] ?? ''));
                return str_contains($name, $q) || str_contains($short, $q);
            });
        }

        // Sorting
        $sort = $params['sort'] ?? 'newest';
        usort($items, static function ($a, $b) use ($sort) {
            return match ($sort) {
                'price_asc'  => ((int)$a['price_cents']) <=> ((int)$b['price_cents']),
                'price_desc' => ((int)$b['price_cents']) <=> ((int)$a['price_cents']),
                'name'       => strcmp((string)$a['name'], (string)$b['name']),
                default      => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')),
            };
        });

        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice(array_values($items), $offset, $perPage);

        return [
            'products' => array_map([self::class, 'normalize'], $paged),
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function list(array $params = [], int $page = 1, int $perPage = 12): array
    {
        $client = new Client();
        $query = [
            'select'    => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,is_featured,category_id,category:categories(name,slug)',
            'is_active' => 'eq.true',
            'limit'     => (string)$perPage,
            'offset'    => (string)(($page - 1) * $perPage),
        ];

        // Category filter
        if (!empty($params['category_id'])) {
            $query['category_id'] = 'eq.' . $params['category_id'];
        }

        // Min price
        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $query['price_cents'] = 'gte.' . ((int)$params['min_price'] * 100);
        }

        // Max price
        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $maxCents = (int)$params['max_price'] * 100;
            if (isset($query['price_cents'])) {
                $minCents = (int)$params['min_price'] * 100;
                unset($query['price_cents']);
                $query['and'] = "(price_cents.gte.{$minCents},price_cents.lte.{$maxCents})";
            } else {
                $query['price_cents'] = 'lte.' . $maxCents;
            }
        }

        // Search term
        if (!empty($params['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$params['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(name.ilike.*{$clean}*,short_description.ilike.*{$clean}*)";
            }
        }

        // Sorting whitelist
        $sort = $params['sort'] ?? 'newest';
        $orderMap = [
            'newest'     => 'created_at.desc',
            'price_asc'  => 'price_cents.asc',
            'price_desc' => 'price_cents.desc',
            'name'       => 'name.asc',
        ];
        $query['order'] = $orderMap[$sort] ?? 'created_at.desc';

        $res = $client->get('products', $query, ['count' => true]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            $products = array_map([self::class, 'normalize'], $res->data);
            $total = $res->count ?? count($products);
            $pages = (int)ceil($total / $perPage);

            return [
                'products' => $products,
                'total'    => $total,
                'page'     => $page,
                'pages'    => max(1, $pages),
            ];
        }

        // Fallback to local catalog when database unreachable
        return self::filterDefaults($params, $page, $perPage);
    }

    public static function featured(int $limit = 3): array
    {
        return Cache::remember(self::CACHE_FEATURED, self::TTL, static function () use ($limit): array {
            $client = new Client();
            $res = $client->get('products', [
                'is_active'   => 'eq.true',
                'is_featured' => 'eq.true',
                'select'      => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
                'order'       => 'created_at.desc',
                'limit'       => (string)$limit,
            ]);
            if ($res->ok && is_array($res->data) && !empty($res->data)) {
                return array_map([self::class, 'normalize'], $res->data);
            }

            // Fallback featured items
            $featured = array_filter(self::defaults(), static fn($p) => !empty($p['is_featured']));
            return array_map([self::class, 'normalize'], array_slice(array_values($featured), 0, $limit));
        });
    }

    public static function newest(int $limit = 4): array
    {
        return Cache::remember(self::CACHE_NEWEST, self::TTL, static function () use ($limit): array {
            $client = new Client();
            $res = $client->get('products', [
                'is_active' => 'eq.true',
                'select'    => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
                'order'     => 'created_at.desc',
                'limit'     => (string)$limit,
            ]);
            if ($res->ok && is_array($res->data) && !empty($res->data)) {
                return array_map([self::class, 'normalize'], $res->data);
            }

            // Fallback newest items
            return array_map([self::class, 'normalize'], array_slice(self::defaults(), 0, $limit));
        });
    }

    public static function findBySlug(string $slug): ?array
    {
        $client = new Client();
        $res = $client->get('products', [
            'slug'      => 'eq.' . $slug,
            'is_active' => 'eq.true',
            'select'    => 'id,category_id,name,slug,short_description,description,price_cents,compare_at_cents,sku,stock,images,is_active,is_featured,created_at,category:categories(id,name,slug)',
            'limit'     => '1',
        ], ['single' => true]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return self::normalize($res->data);
        }

        foreach (self::defaults() as $p) {
            if ($p['slug'] === $slug) {
                return self::normalize($p);
            }
        }

        return null;
    }

    public static function findById(string $id): ?array
    {
        $client = new Client();
        $res = $client->get('products', [
            'id'     => 'eq.' . $id,
            'select' => 'id,category_id,name,slug,short_description,description,price_cents,compare_at_cents,sku,stock,images,is_active,is_featured,created_at,updated_at,category:categories(id,name,slug)',
            'limit'  => '1',
        ], ['single' => true]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return self::normalize($res->data);
        }

        foreach (self::defaults() as $p) {
            if ($p['id'] === $id) {
                return self::normalize($p);
            }
        }

        return null;
    }

    public static function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $validIds = array_filter($ids, static fn($id) => is_string($id) && preg_match($uuidRegex, $id));
        if (empty($validIds)) {
            return [];
        }

        $client = new Client();
        $res = $client->get('products', [
            'id'        => 'in.(' . implode(',', $validIds) . ')',
            'is_active' => 'eq.true',
            'select'    => 'id,name,slug,price_cents,compare_at_cents,images,stock,category_id',
        ]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return array_map([self::class, 'normalize'], $res->data);
        }

        $matched = array_filter(self::defaults(), static fn($p) => in_array($p['id'], $validIds, true));
        return array_map([self::class, 'normalize'], array_values($matched));
    }

    public static function related(string $categoryId, string $excludeId, int $limit = 4): array
    {
        $client = new Client();
        $res = $client->get('products', [
            'category_id' => 'eq.' . $categoryId,
            'id'          => 'neq.' . $excludeId,
            'is_active'   => 'eq.true',
            'select'      => 'id,name,slug,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
            'order'       => 'created_at.desc',
            'limit'       => (string)$limit,
        ]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return array_map([self::class, 'normalize'], $res->data);
        }

        $rel = array_filter(self::defaults(), static fn($p) =>
            ($p['category_id'] ?? '') === $categoryId &&
            ($p['id'] ?? '') !== $excludeId &&
            !empty($p['is_active'])
        );
        return array_map([self::class, 'normalize'], array_slice(array_values($rel), 0, $limit));
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $client = new Client();
        $query = [
            'select' => 'id,category_id,name,slug,price_cents,sku,stock,images,is_active,is_featured,created_at,category:categories(name,slug)',
            'order'  => 'created_at.desc',
            'limit'  => (string)$perPage,
            'offset' => (string)(($page - 1) * $perPage),
        ];

        if (!empty($filters['category_id'])) {
            $query['category_id'] = 'eq.' . $filters['category_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query['is_active'] = 'eq.' . ($filters['is_active'] ? 'true' : 'false');
        }

        if (!empty($filters['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$filters['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(name.ilike.*{$clean}*,sku.ilike.*{$clean}*)";
            }
        }

        $res = $client->get('products', $query, ['count' => true]);
        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            $products = array_map([self::class, 'normalize'], $res->data);
            $total = $res->count ?? count($products);

            return [
                'products' => $products,
                'total'    => $total,
                'page'     => $page,
                'pages'    => max(1, (int)ceil($total / $perPage)),
            ];
        }

        // Fallback admin catalog
        return self::filterDefaults($filters, $page, $perPage);
    }

    public static function create(array $data): ?array
    {
        $client = new Client();
        $res = $client->insert('products', $data, true);
        self::forgetCache();
        return ($res->ok && is_array($res->data)) ? ($res->data[0] ?? $res->data) : null;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('products', ['id' => 'eq.' . $id], $patch);
        self::forgetCache();
        return $res->ok;
    }

    public static function delete(string $id): bool
    {
        $client = new Client();
        $res = $client->delete('products', ['id' => 'eq.' . $id]);
        self::forgetCache();
        return $res->ok;
    }

    public static function toggle(string $id, string $field): bool
    {
        if (!in_array($field, ['is_active', 'is_featured'], true)) {
            return false;
        }

        $prod = self::findById($id);
        if (!$prod) {
            return false;
        }

        $newVal = !($prod[$field] ?? false);
        return self::update($id, [$field => $newVal]);
    }

    public static function lowStock(int $threshold = 5): array
    {
        $client = new Client();
        $res = $client->get('products', [
            'is_active' => 'eq.true',
            'stock'     => 'lte.' . $threshold,
            'select'    => 'id,name,slug,sku,stock,price_cents',
            'order'     => 'stock.asc',
            'limit'     => '10',
        ]);
        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return array_map([self::class, 'normalize'], $res->data);
        }

        $low = array_filter(self::defaults(), static fn($p) => (int)($p['stock'] ?? 0) <= $threshold);
        return array_map([self::class, 'normalize'], array_slice(array_values($low), 0, 10));
    }

    private static function forgetCache(): void
    {
        Cache::forget(self::CACHE_FEATURED);
        Cache::forget(self::CACHE_NEWEST);
    }
}
