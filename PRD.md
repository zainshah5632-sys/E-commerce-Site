# PRD — GlassCart (working title)

> Version 1.0 · Status: Approved for build · Brand name is configurable (`site_settings.store_name`), so "GlassCart" can be renamed any time without code changes.

---

## 1. Vision & Goals

Build a **modern, glassmorphism-styled online store** that is fast, accessible and fully functional end-to-end (browse → cart → checkout → order tracking → admin management) while running on a **100% free stack**:

| Layer | Choice |
|---|---|
| Backend / rendering | **PHP 8.3** (no framework, no Composer dependencies) |
| Database / Auth / File storage | **Supabase** (Postgres + Auth + Storage), accessed server-side over REST |
| Hosting | **Render free web service** (Docker, `php:8.3-apache`) |
| Frontend | Server-rendered PHP views + hand-written CSS + vanilla JS (no build step) |

**Goals**
1. A store owner can add products, receive orders and manage them without touching code.
2. A shopper can complete a purchase in under 2 minutes, on mobile or desktop.
3. The UI feels premium (glassmorphism, soft gradients, smooth motion) **without sacrificing readability or performance**.
4. Zero monthly cost to run and publish.

**Non-goals (MVP):** multi-vendor marketplace, native apps, multi-language, subscriptions, inventory variants (size/color), reviews, coupons, wishlists, email notifications, online card payments. These are listed in §12 as post-MVP.

---

## 2. Users

| Persona | Needs |
|---|---|
| **Shopper (guest)** | Browse, search, filter, add to cart, check out without creating an account. |
| **Shopper (registered)** | Everything above + saved details, order history, order detail. |
| **Store admin / owner** | Dashboard, manage products/categories/orders/settings, upload images. |

---

## 3. Scope — MVP

**In:** home, catalog (search / filter / sort / pagination), product page, cart (+ mini-cart drawer), checkout (guest or logged-in), COD + bank-transfer payment methods, order confirmation, register / login / logout, account (profile, orders), admin panel (dashboard, products, categories, orders, settings), static pages, SEO basics, dark/light theme, responsive design, deployment config.

**Out:** see §12.

---

## 4. Constraints (hard)

- **PHP is mandatory** for all server logic.
- **Supabase is the only database.** All access happens **server-side from PHP**; the browser never talks to Supabase directly and never receives any Supabase key.
- Must be **publishable for free** (Render free tier via Docker). Design around its limits:
  - Render free service sleeps after inactivity → first request is slow (cold start). **No local file persistence** → no file-based sessions, no local uploads.
  - Supabase free projects pause after ~7 days without activity → keep-alive job required (GitHub Actions cron).
  - Supabase free storage is small (1 GB) → cap upload size, compress images.
- No paid services, no Node build pipeline, no Composer packages.

---

## 5. User Stories

**Storefront**
- S-01 As a shopper I can see featured products, categories and new arrivals on the home page.
- S-02 I can search by keyword, filter by category and price range, sort (newest, price asc/desc, name) and paginate.
- S-03 I can open a product page with gallery, price, compare-at price, stock status, description and related products.
- S-04 I can add/remove/update quantities in the cart from the product page, mini-cart drawer and cart page.
- S-05 I can see subtotal, shipping and total, and a progress hint toward free shipping.
- S-06 I can check out as a guest or logged-in user, choosing COD or bank transfer.
- S-07 I get an order confirmation page with order number and next steps.
- S-08 I can toggle dark/light theme; my choice is remembered.

**Accounts**
- A-01 I can register, log in and log out securely.
- A-02 I can edit my name and phone.
- A-03 I can see my past orders and each order's status and items.

**Admin**
- M-01 I can log in and reach `/admin` only if my role is `admin`.
- M-02 I see key stats: orders today, pending orders, 30-day revenue, low-stock count.
- M-03 I can create / edit / archive / delete products with up to 5 images, price, compare-at price, SKU, stock, category, featured flag, active flag.
- M-04 I can manage categories (name, slug, description, order, active).
- M-05 I can view orders, filter by status, open one, change status, mark payment as paid, and cancel (which restores stock).
- M-06 I can edit store settings (name, currency, shipping fee, free-shipping threshold, announcement bar, contact email, bank instructions).

---

## 6. Functional Requirements

### 6.1 Catalog
- FR-C1 Only `is_active = true` products and categories appear publicly; inactive product URLs return 404.
- FR-C2 Listing page size: 12. Search matches name and short description (case-insensitive). Sorting uses a **whitelist** of sort keys.
- FR-C3 Out-of-stock products show an "Out of stock" badge and a disabled add-to-cart button.
- FR-C4 Products without images show a generated gradient placeholder.

### 6.2 Cart
- FR-K1 Cart is stored in the **encrypted cookie session** as `[{product_id, qty}]` — never prices.
- FR-K2 Max 20 distinct lines; quantity per line 1–10 and ≤ current stock.
- FR-K3 Prices, names and stock are always re-fetched from the database when rendering the cart or checkout.
- FR-K4 Cart actions work with JS (fetch, no reload) **and** without JS (form POST + redirect).

### 6.3 Checkout & Orders
- FR-O1 Required fields: full name, email, phone, address line 1, city, postal code, country; optional: address line 2, notes.
- FR-O2 Order creation is **atomic** through the Postgres function `place_order` (validates stock, decrements stock, writes order + items, returns order number).
- FR-O3 If any item is unavailable/out of stock, no order is created and the user sees which item failed.
- FR-O4 After success the cart is cleared and the user lands on `/order/success/{order_number}`.
- FR-O5 Order number format: `ORD-YYMMDD-XXXXXX`.
- FR-O6 Order status lifecycle: `pending → confirmed → shipped → delivered`; `cancelled` allowed only from `pending` or `confirmed` (admin only), restoring stock.
- FR-O7 Payment status: `pending | paid | failed | refunded`, edited by admin.

### 6.4 Auth
- FR-A1 Registration creates a Supabase Auth user (admin API, `email_confirm = true`, to avoid free-tier email limits) and a `profiles` row (DB trigger).
- FR-A2 Login verifies email + password through Supabase Auth, then stores `{user_id, email, name, role}` in the encrypted cookie session.
- FR-A3 Passwords minimum 8 characters. Generic error text on failed login ("Email or password is incorrect").
- FR-A4 Admin routes re-check the user's role **from the database on every request**.

### 6.5 Admin
- FR-M1 Image upload: JPEG/PNG/WebP only (validated by content, not just extension), max 2 MB each, max 5 per product, stored in the Supabase Storage bucket `product-images`.
- FR-M2 Slugs auto-generated from name (lowercase, hyphenated, unique with numeric suffix on collision).
- FR-M3 Deleting a product that has past orders is allowed (order items keep their name/price snapshot); UI recommends "Archive" instead.

---

## 7. Business Rules

1. Money is stored as **integer minor units** (`*_cents`). Display uses `money()` with currency settings.
2. Shipping = flat fee, **free when subtotal ≥ threshold** (both configurable in settings).
3. No taxes in MVP (prices are tax-inclusive; mention in the footer/shipping page).
4. Stock check happens at add-to-cart (soft) and inside `place_order` (hard, authoritative).
5. Guest orders are viewable only on the success page right after purchase (order number stored in the session).
6. Logged-in users see only their own orders (`orders.user_id = session user`).
7. Online card payments are **not** implemented; checkout exposes a `PaymentMethod` list so a gateway can be added later.

---

## 8. Non-Functional Requirements

| Area | Requirement |
|---|---|
| Performance | Warm-server HTML response < 800 ms on listing pages; page weight < 300 KB excluding images; CSS ≤ 60 KB, JS ≤ 30 KB total (unminified OK). Use settings/category caching (60 s file cache) and cURL handle reuse. |
| Security | OWASP basics: CSRF tokens on every POST, output escaping, strict CSP (no inline JS/CSS), secure cookie flags, server-only secrets, RLS enabled on all tables, upload validation, whitelist inputs. |
| Accessibility | WCAG 2.1 AA: contrast ≥ 4.5:1 on glass surfaces, visible focus, keyboard-operable drawer/menus, labels and alt text, reduced-motion / reduced-transparency support. |
| SEO | Unique title + meta description per page, canonical URLs, Open Graph tags, `Product` JSON-LD, `sitemap.xml`, `robots.txt`, clean URLs. |
| Responsive | Mobile-first; works from 320 px to 1920 px. |
| Browser support | Last 2 versions of Chrome, Edge, Firefox, Safari; graceful fallback when `backdrop-filter` is unsupported. |
| Reliability | Every Supabase call has timeout (10 s) and friendly error handling; GET requests retry once on 5xx/timeout. |

---

## 9. Page Inventory

**Public:** `/`, `/shop`, `/category/{slug}`, `/product/{slug}`, `/cart`, `/checkout`, `/order/success/{number}`, `/login`, `/register`, `/page/{about|contact|shipping|returns|privacy|terms}`, `/404`, `/500`, `/sitemap.xml`, `/robots.txt`, `/health`.

**Account:** `/account`, `/account/orders`, `/account/orders/{number}`.

**Admin:** `/admin`, `/admin/products`, `/admin/products/new`, `/admin/products/{id}/edit`, `/admin/categories`, `/admin/orders`, `/admin/orders/{number}`, `/admin/settings`.

(Full route → controller map lives in `Architecture.md` §5.)

---

## 10. Success Criteria (Definition of Done)

The project is done when **all** hold:
1. Every item in `Tasks.md` Phase 0–8 is ticked and Phase 9 (final QA) checklist passes.
2. A full purchase flow works for a guest and for a registered user; stock decrements; admin sees the order and can progress it to delivered; cancelling restores stock.
3. Admin can create a product with images and see it live on the storefront immediately.
4. The site is deployed on a public `onrender.com` URL (or custom domain) with all env vars set and the keep-alive workflow active.
5. No secret is present in the repository or in any HTML/JS served to the browser.
6. UI matches `Design.md` in light and dark themes on mobile and desktop.

---

## 11. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Render cold start (~1 min after idle) | Lightweight `/health` route; optional free uptime pinger; friendly note in README. |
| Supabase auto-pause after inactivity | GitHub Actions cron hits `/health?deep=1` every 3 days (touches the DB). |
| Render has no persistent disk | Stateless encrypted-cookie sessions; images in Supabase Storage; `/tmp` used only as disposable cache. |
| Supabase cold/slow latency | Cache settings/categories 60 s; reuse cURL connection; keep queries minimal (select only needed columns). |
| Glass effects hurt performance on low-end phones | Limit blurred layers, reduce blur on mobile, honor reduced-transparency (see `Design.md`). |
| Overselling under concurrency | Stock decrement inside a single Postgres function with row locks. |

---

## 12. Post-MVP Backlog (do NOT build now)

Online payments (via `PaymentMethod` interface), email notifications (custom SMTP), product variants, reviews & ratings, coupons, wishlist, order tracking numbers, analytics charts, multi-currency, i18n, image transformations/resizing.

---

## 13. Default Decisions (so the agent never needs to ask)

| Open question | Default |
|---|---|
| Brand name | GlassCart (editable in settings) |
| Currency | USD, symbol `$` (editable in settings) |
| Shipping | Flat 500 cents; free over 7500 cents |
| Payments | Cash on Delivery + Bank Transfer |
| Product catalog | Seed 4 categories + 12 demo products (no external images) |
| Accent color | Indigo → violet → pink gradient |
| Email verification | Off (accounts auto-confirmed server-side) |
