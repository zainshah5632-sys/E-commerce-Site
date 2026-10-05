# Architecture — GlassCart

> Single source of truth for **how** the system is built. If code and this file disagree, fix the code (or update this file and log the change in `Memory.md`).

---

## 1. Stack & Principles

| Concern | Decision |
|---|---|
| Language | PHP **8.3**, `declare(strict_types=1);` everywhere |
| Framework | **None.** Tiny custom core (router, view, session, supabase client) |
| Dependencies | **Zero Composer packages.** Own PSR-4-style autoloader (`App\` → `src/`) |
| DB / Auth / Files | Supabase (Postgres, Auth, Storage) via **REST from PHP (cURL)** |
| Frontend | PHP views, hand-written CSS, vanilla JS (ES2020), no bundler |
| Hosting | Render free web service, Docker image `php:8.3-apache` |
| Sessions | **Stateless encrypted cookie** (libsodium) — no server storage needed |
| Money | Integer minor units (`*_cents`) |

Principles: simple > clever · server-only secrets · progressive enhancement (works without JS) · small files (< 300 lines) · everything configurable lives in `site_settings`.

---

## 2. System Diagram

```
 Browser ──HTTPS──▶ Render (Docker: Apache + PHP 8.3)
                       │  public/index.php (front controller)
                       │   ├─ Router → Middleware → Controller → View
                       │   └─ Repositories/Services
                       │          │  cURL (HTTPS, server-side only)
                       ▼          ▼
                 Supabase  ├─ PostgREST  /rest/v1/*   (tables + RPC)
                           ├─ Auth       /auth/v1/*   (create user, password login)
                           └─ Storage    /storage/v1/* (bucket: product-images, public read)

 GitHub Actions (cron, every 3 days) ──▶ GET /health?deep=1  (wakes Render, touches Supabase)
```

The browser **never** receives a Supabase URL-with-key, key, or token. Public image URLs (`…/storage/v1/object/public/product-images/…`) are the only Supabase URLs exposed.

---

## 3. Folder Structure (create exactly this)

```
/
├─ public/                      # Apache DocumentRoot
│  ├─ index.php                 # front controller + route table
│  └─ assets/
│     ├─ css/   tokens.css base.css components.css pages.css admin.css
│     ├─ js/    theme-init.js ui.js cart.js admin.js
│     └─ img/   logo.svg favicon.svg placeholder.svg og-default.svg
├─ src/
│  ├─ autoload.php  bootstrap.php
│  ├─ Core/        Env.php Request.php Response.php Router.php Session.php
│  │               Validator.php Cache.php Logger.php View.php
│  ├─ Support/     helpers.php            # e(), url(), asset(), money(), old(), csrf_field(), icon(), slugify(), flash()
│  ├─ Supabase/    Client.php Auth.php Storage.php
│  ├─ Repositories/ SettingsRepository.php CategoryRepository.php ProductRepository.php
│  │                OrderRepository.php ProfileRepository.php
│  ├─ Services/    CartService.php PricingService.php CheckoutService.php
│  └─ Controllers/ HomeController.php ShopController.php ProductController.php CartController.php
│                  CheckoutController.php AuthController.php AccountController.php
│                  PageController.php SeoController.php HealthController.php
│                  Admin/ DashboardController.php ProductAdminController.php CategoryAdminController.php
│                          OrderAdminController.php SettingsAdminController.php
├─ views/
│  ├─ layouts/  main.php admin.php
│  ├─ partials/ header.php footer.php flash.php mini-cart.php product-card.php pagination.php icons.php head-meta.php
│  ├─ pages/    home.php shop.php product.php cart.php checkout.php order-success.php login.php register.php
│  │            account.php account-orders.php order-detail.php static.php 404.php 500.php
│  └─ admin/    dashboard.php products-index.php product-form.php categories.php orders-index.php order-show.php settings.php
├─ database/    schema.sql seed.sql make_admin.sql
├─ docker/      apache.conf php.ini entrypoint.sh
├─ .github/workflows/keepalive.yml
├─ docs/        (PRD.md Design.md Architecture.md Tasks.md Rules.md Memory.md)
├─ Dockerfile  render.yaml  .env.example  .gitignore  .dockerignore  README.md
```

---

## 4. Request Lifecycle

1. Apache `FallbackResource /index.php` sends any non-file URL to `public/index.php` (static assets are served directly).
2. `bootstrap.php`: load `Env`, register autoloader, set error/exception handlers, send security headers, load helpers.
3. `Request::capture()` → `Router::dispatch()` matches `METHOD + path` against the route table (§5).
4. Middleware (declared per route): `csrf` (all POST), `auth` (logged-in), `admin` (role re-checked from DB), `guest` (redirect if logged in).
5. Controller calls Repositories/Services → returns `Response` (view render, redirect, JSON).
6. `View::render('pages/x', $data, layout: 'main')` → HTML (escaped with `e()`).
7. Errors: `NotFoundException` → 404 view; any other `Throwable` → logged with `error_log` and generic 500 view (details shown only when `APP_ENV=local`).

**Built-in server support (local QA):** `public/index.php` must start with:
```php
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($p !== '/' && is_file(__DIR__ . $p)) { return false; }
}
```

---

## 5. Route Table

| Method | Path | Handler | Middleware |
|---|---|---|---|
| GET | `/` | HomeController@index | – |
| GET | `/shop` | ShopController@index | – |
| GET | `/category/{slug}` | ShopController@category | – |
| GET | `/product/{slug}` | ProductController@show | – |
| GET | `/cart` | CartController@index | – |
| GET | `/cart/mini` | CartController@mini (JSON: html + count + totals) | – |
| POST | `/cart/add` | CartController@add (JSON if `Accept: application/json`, else redirect) | csrf |
| POST | `/cart/update` | CartController@update | csrf |
| POST | `/cart/remove` | CartController@remove | csrf |
| GET/POST | `/checkout` | CheckoutController@show / @place | csrf (POST) |
| GET | `/order/success/{number}` | CheckoutController@success | – (session-guarded) |
| GET/POST | `/login` | AuthController@loginForm / @login | guest, csrf |
| GET/POST | `/register` | AuthController@registerForm / @register | guest, csrf |
| POST | `/logout` | AuthController@logout | csrf |
| GET | `/account` | AccountController@index | auth |
| POST | `/account` | AccountController@update | auth, csrf |
| GET | `/account/orders` | AccountController@orders | auth |
| GET | `/account/orders/{number}` | AccountController@order | auth |
| GET | `/page/{slug}` | PageController@show (about, contact, shipping, returns, privacy, terms) | – |
| GET | `/sitemap.xml` `/robots.txt` | SeoController@sitemap / @robots | – |
| GET | `/health` | HealthController@index (`?deep=1` queries Supabase) | – |
| GET | `/admin` | Admin\DashboardController@index | admin |
| GET | `/admin/products` | Admin\ProductAdminController@index | admin |
| GET | `/admin/products/new` | …@create | admin |
| POST | `/admin/products` | …@store | admin, csrf |
| GET | `/admin/products/{id}/edit` | …@edit | admin |
| POST | `/admin/products/{id}` | …@update | admin, csrf |
| POST | `/admin/products/{id}/delete` | …@destroy | admin, csrf |
| POST | `/admin/products/{id}/toggle` | …@toggle (active/featured) | admin, csrf |
| GET/POST | `/admin/categories` (+ `/{id}`, `/{id}/delete`) | Admin\CategoryAdminController | admin, csrf |
| GET | `/admin/orders` | Admin\OrderAdminController@index | admin |
| GET | `/admin/orders/{number}` | …@show | admin |
| POST | `/admin/orders/{number}/status` | …@status | admin, csrf |
| POST | `/admin/orders/{number}/payment` | …@payment | admin, csrf |
| POST | `/admin/orders/{number}/cancel` | …@cancel (RPC `cancel_order`) | admin, csrf |
| GET/POST | `/admin/settings` | Admin\SettingsAdminController | admin, csrf |

Route params: `{slug}` = `[a-z0-9-]+`, `{id}` = UUID regex, `{number}` = `ORD-\d{6}-[A-Z0-9]{6}`. Validate every param against these regexes **before** using it in any Supabase filter.

---

## 6. Environment Variables

| Name | Example / Default | Notes |
|---|---|---|
| `APP_ENV` | `production` \| `local` | `local` shows error details |
| `APP_URL` | `https://glasscart.onrender.com` | No trailing slash; used for canonical/sitemap; `https` ⇒ `Secure` cookies |
| `APP_SECRET` | 64 hex chars | Derives the session-cookie encryption key. Generate: `php -r "echo bin2hex(random_bytes(32));"` |
| `SUPABASE_URL` | `https://xxxx.supabase.co` | |
| `SUPABASE_PUBLISHABLE_KEY` | anon / `sb_publishable_…` | Used **only** for the password-login Auth call |
| `SUPABASE_SECRET_KEY` | service_role / `sb_secret_…` | **Server-only. Never in views/JS/logs.** |
| `SUPABASE_STORAGE_BUCKET` | `product-images` | |

Locally: `.env` file (gitignored) parsed by `Env`. On Render: real environment variables (`getenv`). `.env.example` lists every name with dummy values.

---

## 7. Database Schema (`database/schema.sql` — copy verbatim)

```sql
-- =====================================================================
-- GlassCart schema — run once in Supabase SQL Editor (safe to re-run)
-- =====================================================================

-- ---------- Tables ----------
create table if not exists public.profiles (
  id          uuid primary key references auth.users(id) on delete cascade,
  full_name   text,
  phone       text,
  role        text not null default 'customer' check (role in ('customer','admin')),
  created_at  timestamptz not null default now()
);

create table if not exists public.categories (
  id          uuid primary key default gen_random_uuid(),
  name        text not null,
  slug        text not null unique,
  description text,
  image_url   text,
  sort_order  integer not null default 0,
  is_active   boolean not null default true,
  created_at  timestamptz not null default now()
);

create table if not exists public.products (
  id               uuid primary key default gen_random_uuid(),
  category_id      uuid references public.categories(id) on delete set null,
  name             text not null,
  slug             text not null unique,
  short_description text,
  description      text,
  price_cents      integer not null check (price_cents >= 0),
  compare_at_cents integer check (compare_at_cents is null or compare_at_cents >= 0),
  sku              text unique,
  stock            integer not null default 0 check (stock >= 0),
  images           text[] not null default '{}',
  is_active        boolean not null default true,
  is_featured      boolean not null default false,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);
create index if not exists products_category_idx on public.products (category_id);
create index if not exists products_active_featured_idx on public.products (is_active, is_featured);
create index if not exists products_created_idx on public.products (created_at desc);

create table if not exists public.orders (
  id               uuid primary key default gen_random_uuid(),
  order_number     text not null unique,
  user_id          uuid references auth.users(id) on delete set null,
  email            text not null,
  customer_name    text not null,
  phone            text not null,
  shipping_address jsonb not null,
  payment_method   text not null check (payment_method in ('cod','bank_transfer')),
  payment_status   text not null default 'pending' check (payment_status in ('pending','paid','failed','refunded')),
  status           text not null default 'pending' check (status in ('pending','confirmed','shipped','delivered','cancelled')),
  subtotal_cents   integer not null default 0,
  shipping_cents   integer not null default 0,
  total_cents      integer not null default 0,
  notes            text,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);
create index if not exists orders_user_idx on public.orders (user_id);
create index if not exists orders_created_idx on public.orders (created_at desc);
create index if not exists orders_status_idx on public.orders (status);

create table if not exists public.order_items (
  id               uuid primary key default gen_random_uuid(),
  order_id         uuid not null references public.orders(id) on delete cascade,
  product_id       uuid references public.products(id) on delete set null,
  product_name     text not null,
  unit_price_cents integer not null,
  quantity         integer not null check (quantity > 0),
  image_url        text
);
create index if not exists order_items_order_idx on public.order_items (order_id);

create table if not exists public.site_settings (
  key   text primary key,
  value jsonb not null
);

-- ---------- Triggers ----------
create or replace function public.set_updated_at() returns trigger
language plpgsql as $$
begin new.updated_at = now(); return new; end $$;

drop trigger if exists products_set_updated on public.products;
create trigger products_set_updated before update on public.products
  for each row execute function public.set_updated_at();

drop trigger if exists orders_set_updated on public.orders;
create trigger orders_set_updated before update on public.orders
  for each row execute function public.set_updated_at();

create or replace function public.handle_new_user() returns trigger
language plpgsql security definer set search_path = public as $$
begin
  insert into public.profiles (id, full_name)
  values (new.id, coalesce(new.raw_user_meta_data->>'full_name', ''))
  on conflict (id) do nothing;
  return new;
end $$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users
  for each row execute function public.handle_new_user();

-- ---------- Functions (RPC, service_role only) ----------
create or replace function public.place_order(
  p_user_id uuid, p_email text, p_name text, p_phone text, p_address jsonb,
  p_payment_method text, p_notes text, p_items jsonb, p_shipping_cents integer
) returns jsonb
language plpgsql security definer set search_path = public as $$
declare
  v_order_id uuid; v_number text; v_subtotal integer := 0;
  v_item jsonb; v_prod record; v_qty integer; v_total integer;
begin
  if p_items is null or jsonb_array_length(p_items) = 0 then
    raise exception 'EMPTY_CART';
  end if;

  v_number := 'ORD-' || to_char(now(), 'YYMMDD') || '-' ||
              upper(substr(replace(gen_random_uuid()::text, '-', ''), 1, 6));

  insert into orders (order_number, user_id, email, customer_name, phone, shipping_address,
                      payment_method, shipping_cents, notes)
  values (v_number, p_user_id, p_email, p_name, p_phone, p_address,
          p_payment_method, p_shipping_cents, p_notes)
  returning id into v_order_id;

  -- items MUST be sorted by product_id by the caller (avoids deadlocks)
  for v_item in select * from jsonb_array_elements(p_items) loop
    v_qty := (v_item->>'quantity')::integer;
    select id, name, price_cents, stock, images into v_prod
      from products
      where id = (v_item->>'product_id')::uuid and is_active
      for update;
    if not found then raise exception 'PRODUCT_UNAVAILABLE:%', v_item->>'product_id'; end if;
    if v_qty < 1 or v_prod.stock < v_qty then raise exception 'OUT_OF_STOCK:%', v_prod.name; end if;

    update products set stock = stock - v_qty where id = v_prod.id;
    insert into order_items (order_id, product_id, product_name, unit_price_cents, quantity, image_url)
    values (v_order_id, v_prod.id, v_prod.name, v_prod.price_cents, v_qty, v_prod.images[1]);
    v_subtotal := v_subtotal + v_prod.price_cents * v_qty;
  end loop;

  v_total := v_subtotal + p_shipping_cents;
  update orders set subtotal_cents = v_subtotal, total_cents = v_total where id = v_order_id;

  return jsonb_build_object('order_id', v_order_id, 'order_number', v_number, 'total_cents', v_total);
end $$;

create or replace function public.cancel_order(p_order_id uuid) returns void
language plpgsql security definer set search_path = public as $$
declare v_status text;
begin
  select status into v_status from orders where id = p_order_id for update;
  if not found then raise exception 'ORDER_NOT_FOUND'; end if;
  if v_status not in ('pending','confirmed') then raise exception 'ORDER_NOT_CANCELLABLE'; end if;
  update products p set stock = p.stock + oi.quantity
    from order_items oi where oi.order_id = p_order_id and oi.product_id = p.id;
  update orders set status = 'cancelled' where id = p_order_id;
end $$;

create or replace function public.admin_stats() returns jsonb
language sql security definer set search_path = public as $$
  select jsonb_build_object(
    'orders_today',      (select count(*) from orders where created_at >= date_trunc('day', now())),
    'pending_orders',    (select count(*) from orders where status = 'pending'),
    'revenue_30d_cents', (select coalesce(sum(total_cents), 0) from orders
                           where status <> 'cancelled' and created_at >= now() - interval '30 days'),
    'low_stock',         (select count(*) from products where is_active and stock <= 5),
    'products_total',    (select count(*) from products)
  );
$$;

revoke all on function public.place_order(uuid,text,text,text,jsonb,text,text,jsonb,integer) from public, anon, authenticated;
revoke all on function public.cancel_order(uuid) from public, anon, authenticated;
revoke all on function public.admin_stats() from public, anon, authenticated;
grant execute on function public.place_order(uuid,text,text,text,jsonb,text,text,jsonb,integer) to service_role;
grant execute on function public.cancel_order(uuid) to service_role;
grant execute on function public.admin_stats() to service_role;

-- ---------- Row Level Security (deny-by-default; PHP uses the service key) ----------
alter table public.profiles      enable row level security;
alter table public.categories    enable row level security;
alter table public.products      enable row level security;
alter table public.orders        enable row level security;
alter table public.order_items   enable row level security;
alter table public.site_settings enable row level security;
-- No policies on purpose: anon/authenticated keys can read/write nothing.

-- ---------- Storage bucket (public read; writes only via service key) ----------
insert into storage.buckets (id, name, public)
values ('product-images', 'product-images', true)
on conflict (id) do nothing;

-- ---------- Default settings ----------
insert into public.site_settings (key, value) values
  ('store_name',                    '"GlassCart"'),
  ('currency_code',                 '"USD"'),
  ('currency_symbol',               '"$"'),
  ('flat_shipping_cents',           '500'),
  ('free_shipping_threshold_cents', '7500'),
  ('contact_email',                 '"hello@example.com"'),
  ('announcement',                  '"Free shipping on orders over $75"'),
  ('bank_instructions',             '"Transfer the total to: Example Bank, Account 0000 0000. Use your order number as the reference."')
on conflict (key) do nothing;
```

`database/make_admin.sql`:
```sql
-- Run AFTER registering on the site with this email
update public.profiles set role = 'admin'
where id = (select id from auth.users where email = 'YOUR_EMAIL@example.com');
```

---

## 8. Supabase Access Layer

### 8.1 `Supabase\Client`
Single class, one reused cURL handle per request (`curl_reset` between calls). Methods:

```php
get(string $table, array $query = [], array $opts = []): Result     // opts: ['count' => true, 'single' => true]
insert(string $table, array $row, bool $returning = true): Result
update(string $table, array $filters, array $patch): Result
delete(string $table, array $filters): Result
rpc(string $fn, array $args): Result
```
`Result` = `{ ok:bool, status:int, data:mixed, count:?int, error:?string }`.

**Headers (every call):** `apikey: <SUPABASE_SECRET_KEY>`, `Content-Type: application/json`, `Prefer: return=representation` for writes.
**Compatibility rule:** send `Authorization: Bearer <key>` **only if the key starts with `eyJ`** (legacy JWT keys). New `sb_secret_…` keys use the `apikey` header alone.
**Timeouts:** connect 5 s, total 10 s. **Retry:** GET only, once, on timeout/5xx.
**Count:** when `count=true` send `Prefer: count=exact` and parse the total from the `Content-Range` response header (`0-11/48` → 48).
**Errors:** never leak the raw response to the browser; log it with `Logger::error` and return a `Result` with a generic `error`.

### 8.2 PostgREST query conventions
- Filters: `['is_active' => 'eq.true', 'price_cents' => 'gte.1000']`; build with `http_build_query` **after** whitelisting columns/operators.
- Multiple values: `id=in.(uuid1,uuid2)` (validate each UUID first).
- Search: `or=(name.ilike.*term*,short_description.ilike.*term*)` — strip `, ( ) * % \` and limit to 60 chars first.
- Embed category: `select=id,name,slug,price_cents,compare_at_cents,images,stock,category:categories(name,slug)`.
- Pagination: `limit=12&offset=N`. Sorting: `order=price_cents.asc` from a **whitelist** map (`newest`, `price_asc`, `price_desc`, `name`).
- Select only the columns each page needs (no `select=*` on product lists).
- Upsert settings: `POST /rest/v1/site_settings` with header `Prefer: resolution=merge-duplicates`.

### 8.3 `Supabase\Auth`
- **Register:** `POST /auth/v1/admin/users` with `{email, password, email_confirm:true, user_metadata:{full_name}}` using the secret key. A "already registered" error → friendly "An account with this email already exists".
- **Login:** `POST /auth/v1/token?grant_type=password` with `{email, password}` and `apikey: SUPABASE_PUBLISHABLE_KEY`. On success read `user.id`, then fetch `profiles` (role, full_name) via `Client`. Discard the Supabase tokens — only `{uid, email, name, role}` go into the cookie session.
- On failed login: `usleep(400000)` then generic error message.

### 8.4 `Supabase\Storage`
- Upload: `POST /storage/v1/object/{bucket}/{path}` body = raw bytes, headers `Content-Type: <mime>`, `x-upsert: false`, `apikey` (+ Bearer rule).
- Path: `products/{product_id}/{uniqid}.{ext}` (ext from detected MIME: jpeg→jpg, png, webp).
- Public URL: `{SUPABASE_URL}/storage/v1/object/public/{bucket}/{path}`.
- Delete: `DELETE /storage/v1/object/{bucket}/{path}` (path derived from the stored URL).
- Validate with `finfo_file` (not the client MIME), size ≤ 2 MB, max 5 images per product.

---

## 9. Core Modules — Specs

| Module | Spec |
|---|---|
| **Env** | Parse `.env` if present (KEY=VALUE, `#` comments, quotes trimmed); otherwise `getenv`. `Env::get($k, $default)`, `Env::require($k)` throws if missing. |
| **Session** | Cookie `gc_s`; payload = JSON `{uid?, email?, name?, role?, cart:[{p,q}], csrf, flash:{}, last_order?, iat}` → `sodium_crypto_secretbox` with random nonce, key = `sodium_crypto_generichash(APP_SECRET)`; cookie value = `base64url(nonce . cipher)`. Flags: `HttpOnly; SameSite=Lax; Path=/; Secure` (when `APP_URL` is https); lifetime 7 days (session cookie refreshed on write). Reject if decrypt fails, `iat` too old, or size > 3800 bytes (cart add then returns "Cart is full"). Written once per request, in `Response::send()`, only when data changed. |
| **CSRF** | Token generated on first session use, stored in session. `csrf_field()` renders hidden `_csrf`; fetch sends `X-CSRF-Token` (read from `<meta name="csrf-token">`). Compare with `hash_equals`. Mismatch → 419 page. |
| **Router** | `add(method, pattern, [Class, 'method'], middleware[])`; patterns compile to regex with the param types in §5. 404 if no path match, 405 if path matches other method. |
| **View** | `View::render(string $template, array $data = [], ?string $layout = 'main'): string` using `extract` **inside a closure scope only** + output buffering. Helpers: `e()` for HTML, `ea()` for attributes. |
| **Validator** | Fluent rules: `required, email, min, max, in, int, regex, uuid`; returns `errors[field] = message`; old input flashed back via `old('field')`. |
| **Cache** | File cache in `sys_get_temp_dir().'/gc_cache'`; `Cache::remember(key, ttl, fn)`, `Cache::forget(key)`. TTL 60 s for settings, categories, featured, new arrivals. Admin writes call `forget`. Disposable — never required for correctness. |
| **Logger** | `error_log()` with JSON line (level, message, context) → stderr (visible in Render logs). Never log keys/passwords. |
| **CartService** | `items()` (hydrated from DB), `add/update/remove/clear`, enforce FR-K2; returns structured totals via `PricingService`. |
| **PricingService** | `subtotal(items)`, `shipping(subtotal, settings)`, `total()` — integer math only. |
| **CheckoutService** | Validate form → hydrate cart → compute shipping → sort items by `product_id` → call RPC `place_order` → map errors (`OUT_OF_STOCK:*`, `PRODUCT_UNAVAILABLE:*`, `EMPTY_CART`) to friendly messages → clear cart → set `last_order` in session. |

### Helpers (`Support/helpers.php`)
`e($s)`, `url($path, $query = [])`, `asset($path)` (appends `?v=filemtime`), `money(int $cents)` (uses settings), `old($key)`, `csrf_field()`, `csrf_token()`, `flash($type,$msg)`, `icon($name,$class='')`, `slugify($s)`, `is_active($path)` (nav state), `settings($key)`.

---

## 10. Security Architecture

1. **Secrets:** only in env. `SUPABASE_SECRET_KEY` is read in `Supabase\Client` and nowhere else. Never echoed, never in views.
2. **RLS on, no policies** → even a leaked anon key reads nothing. All data access = PHP + service key.
3. **Headers (all responses):**
   `Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.supabase.co; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'`
   `X-Content-Type-Options: nosniff` · `Referrer-Policy: strict-origin-when-cross-origin` · `X-Frame-Options: DENY` · `Permissions-Policy: camera=(), microphone=(), geolocation=()` · `Strict-Transport-Security` (production only).
   ⇒ **No inline `<script>`, `style=""`, or `on*=""` attributes anywhere.**
4. **CSRF** on every state-changing request; **SameSite=Lax** cookie.
5. **Output escaping** with `e()` on every dynamic value; JSON-LD via `json_encode` with `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`.
6. **Input:** whitelist sort/filter keys, regex-validate route params, cast ints, length-limit all strings, strip control characters.
7. **Open redirect:** `next` param accepted only if it starts with `/` and not `//`.
8. **Uploads:** content-sniffed MIME, size cap, random filenames, bucket path prefixed by product id.
9. **Authorization:** admin role re-read from `profiles` each admin request; order detail queries always include `user_id = session uid`.
10. **Errors:** generic user-facing messages; details only in logs.

---

## 11. Deployment

### 11.1 `Dockerfile`
```dockerfile
FROM php:8.3-apache
RUN a2enmod rewrite headers expires deflate
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint
WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html
EXPOSE 10000
ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
```
(The official image already ships `curl`, `mbstring`, `sodium`, `fileinfo`, `openssl` — no extra extensions are needed.)

### 11.2 `docker/entrypoint.sh`
```sh
#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec docker-php-entrypoint "$@"
```

### 11.3 `docker/apache.conf`
```apache
<VirtualHost *:10000>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>
    <FilesMatch "\.(css|js|svg|png|jpg|jpeg|webp|woff2)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript image/svg+xml application/json
    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
```

### 11.4 `docker/php.ini`
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /dev/stderr
upload_max_filesize = 3M
post_max_size = 12M
max_file_uploads = 6
date.timezone = UTC
session.auto_start = 0
```

### 11.5 `render.yaml`
```yaml
services:
  - type: web
    name: glasscart
    runtime: docker
    plan: free
    healthCheckPath: /health
    autoDeploy: true
    envVars:
      - { key: APP_ENV, value: production }
      - { key: APP_URL, sync: false }
      - { key: APP_SECRET, generateValue: true }
      - { key: SUPABASE_URL, sync: false }
      - { key: SUPABASE_PUBLISHABLE_KEY, sync: false }
      - { key: SUPABASE_SECRET_KEY, sync: false }
      - { key: SUPABASE_STORAGE_BUCKET, value: product-images }
```

### 11.6 `.github/workflows/keepalive.yml`
```yaml
name: keepalive
on:
  schedule: [{ cron: "0 6 */3 * *" }]
  workflow_dispatch:
jobs:
  ping:
    runs-on: ubuntu-latest
    steps:
      - run: curl -fsS --retry 5 --retry-delay 30 --max-time 120 "${{ secrets.APP_URL }}/health?deep=1"
```
(Requires repository secret `APP_URL`. The deep health check touches Supabase so the free project never reaches the inactivity pause.)

### 11.7 Deploy path (human steps are in `Tasks.md` Phase 9–10)
GitHub repo → Render "New Web Service" (Docker, Free) → set env vars → deploy → set `APP_URL` → add `APP_URL` secret on GitHub.

---

## 12. Performance Plan

- One cURL handle reused; select minimal columns; `limit` everywhere.
- 60 s file cache for settings, categories, featured, new arrivals.
- Static assets: long cache + `?v=mtime`; gzip via Apache `DEFLATE`.
- CSS: 5 small files, `<link rel="preload">` not needed; fonts via Google Fonts with `display=swap` + `preconnect`.
- Images: `loading="lazy"`, explicit `width`/`height`, `decoding="async"`; first hero/product image `fetchpriority="high"`.
- JS: `defer`, no libraries; each file < 10 KB.

---

## 13. Extension Points (design now, build later)

- `PaymentMethod` list in `CheckoutService` (add a gateway class implementing `initiate()/confirm()`).
- `site_settings` key/value for any new store option.
- `Mailer` interface (not implemented in MVP) invoked after `place_order`.
