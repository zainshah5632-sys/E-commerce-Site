# Tasks — GlassCart

> **How to use:** work top to bottom. Tick `[x]` when done. Each phase ends with a **Gate** (static checks only — see `Rules.md` §2). **No running/testing until Phase 9.**
> Section refs: `AR` = Architecture.md, `DS` = Design.md, `PRD` = PRD.md.

**Kickoff prompt for the agent:**
> Read `docs/Rules.md`, then `docs/Memory.md`, then `docs/Tasks.md`. Execute tasks from the first unchecked one through Phase 8 without asking questions and without running or testing the app. Follow the Testing Policy. Update `Memory.md` at each phase end. Stop at the Phase 9 human checkpoint.

---

## Phase 0 — Scaffold & Infrastructure Files

- [x] **T0.1** Create the folder structure from `AR §3` (add `.gitkeep` to empty dirs).
- [x] **T0.2** `.gitignore` (`.env`, `.DS_Store`, `*.log`, `/vendor`), `.dockerignore` (`.git`, `.env`, `docs`, `.github`), `.env.example` with every variable in `AR §6` (dummy values + comments).
- [x] **T0.3** `Dockerfile`, `docker/apache.conf`, `docker/php.ini`, `docker/entrypoint.sh` exactly per `AR §11.1–11.4`.
- [x] **T0.4** `render.yaml` per `AR §11.5`.
- [x] **T0.5** `.github/workflows/keepalive.yml` per `AR §11.6`.
- [x] **T0.6** `README.md` skeleton (title, stack, features, local run, env vars table, deploy) — completed in T8.1.
- [x] **Gate 0:** all files exist; env var names identical across `.env.example`, `render.yaml`, `AR §6`.

## Phase 1 — Database

- [x] **T1.1** `database/schema.sql` — copy `AR §7` **verbatim** (tables, indexes, triggers, functions, grants, RLS, bucket, default settings).
- [x] **T1.2** `database/seed.sql` — 4 categories (e.g. Audio, Wearables, Desk & Home, Accessories) and 12 realistic products: real names, short + long descriptions (2–3 sentences), prices 900–29900 cents, stock 0–60, 3 featured, 2 with `compare_at_cents`, **1 with stock 0**, empty `images` arrays. Idempotent (`on conflict do nothing`).
- [x] **T1.3** `database/make_admin.sql` per `AR §7`.
- [x] **Gate 1:** every table has `enable row level security`; all 3 functions revoked from `anon/authenticated` and granted to `service_role`; seed slugs unique.

## Phase 2 — Core PHP Layer

- [x] **T2.1** `src/autoload.php` (PSR-4 `App\` → `src/`), `src/bootstrap.php` (Env, autoload, helpers, error/exception handlers, security headers per `AR §10`).
- [x] **T2.2** `Core/Env.php`, `Core/Logger.php`, `Core/Cache.php` per `AR §9`.
- [x] **T2.3** `Core/Request.php` (method, path, query, input, json, files, ip, `wantsJson()`), `Core/Response.php` (html, json, redirect, status, headers, cookie write on send).
- [x] **T2.4** `Core/Session.php` — encrypted cookie session with flash + CSRF + cart storage per `AR §9`.
- [x] **T2.5** `Core/Router.php` — typed params, middleware (`csrf`, `auth`, `admin`, `guest`), 404/405.
- [x] **T2.6** `Core/Validator.php`, `Core/View.php` + `Support/helpers.php` (all helpers in `AR §9`).
- [x] **T2.7** `Supabase/Client.php` per `AR §8.1–8.2` (Result object, retry, count, key compatibility rule).
- [x] **T2.8** `Supabase/Auth.php` (register via admin API, password login) and `Supabase/Storage.php` per `AR §8.3–8.4`.
- [x] **T2.9** Repositories: `SettingsRepository` (cached get/all/set), `CategoryRepository`, `ProductRepository` (list with filters/sort/pagination/count, bySlug, byIds, featured, newest, related, admin CRUD), `OrderRepository` (create via RPC, byNumber, byUser, list/filter, updateStatus/Payment, cancel via RPC, stats via RPC), `ProfileRepository`.
- [x] **T2.10** Services: `PricingService`, `CartService` (rules FR-K1..K4), `CheckoutService` (per `AR §9`, error-code mapping).
- [x] **T2.11** `public/index.php` — cli-server guard, bootstrap, full route table from `AR §5`, dispatch.
- [x] **T2.12** Middleware implementations (csrf → 419 view, auth → redirect `/login?next=`, admin → DB role check → 403/redirect, guest).
- [x] **Gate 2:** lint; every route in `AR §5` maps to an existing controller method (grep); `SUPABASE_SECRET_KEY` only in `src/Supabase/*`.

## Phase 3 — Design System & Layout Shell

- [x] **T3.1** `public/assets/css/tokens.css` (light + dark, exactly `DS §2`) and `base.css` (reset, typography `DS §5`, aurora background `DS §3`, focus, skip link, reduced-motion/transparency).
- [x] **T3.2** `components.css`: glass (+ `--strong/--subtle/--flat`) `DS §4`, buttons, inputs, badges, product card, drawer, toast, pagination, breadcrumbs, tables, modal-less sheet, skeleton, status badges (all per `DS §7`).
- [x] **T3.3** `pages.css`: hero, sections, grids, shop layout, PDP, cart, checkout, success, account, static pages, 404/500, footer.
- [x] **T3.4** `admin.css`: shell, sidebar, stat cards, forms grid, uploader, tables.
- [x] **T3.5** `views/partials/icons.php` (all icons in `DS §11`) and SVG assets: `logo.svg`, `favicon.svg`, `placeholder.svg`, `og-default.svg`.
- [x] **T3.6** `views/layouts/main.php`, `views/layouts/admin.php`, partials: `head-meta`, `header`, `footer`, `flash`, `mini-cart`, `product-card`, `pagination`. Include skip link, announcement bar, theme-init script, `<meta name="csrf-token">`.
- [x] **T3.7** JS: `theme-init.js`, `ui.js` (theme toggle, mobile nav, drawer open/close + focus trap, toasts, gallery, qty stepper, filter sheet, auto-submit), `cart.js` (fetch add/update/remove with fallback, update badge + mini-cart via `/cart/mini`), `admin.js` (sidebar toggle, uploader preview, confirm dialogs via `data-confirm`).
- [x] **Gate 3:** run the `DS §12` checklist by reading code; greps show no `style=`/inline `<script>`/`on*=` in `views`; no hex colors outside `tokens.css`.

## Phase 4 — Storefront Pages

- [x] **T4.1** Home (`HomeController`, `home.php`): hero, category tiles, featured, value props, new arrivals — all from DB/cache.
- [x] **T4.2** Shop (`ShopController`, `shop.php`): search `q`, `category`, `min`/`max` price, `sort` whitelist, pagination (12), active-filter chips, empty state; `/category/{slug}` shares the same view.
- [x] **T4.3** Product page (`ProductController`, `product.php`): gallery, price/compare/discount %, stock states (in stock / low stock ≤ 5 / out), quantity, add-to-cart (form + JS), description, related products, breadcrumbs, `Product` JSON-LD.
- [x] **T4.4** Cart (`CartController`, `cart.php`, `mini-cart` fragment): add/update/remove, stock clamping with messages, totals, free-shipping progress, JSON responses `{ok, count, html, message}`.
- [x] **T4.5** Checkout (`CheckoutController`, `checkout.php`): validation per FR-O1, prefill for logged-in users, payment method radio cards (COD / bank transfer), summary, `place_order` via `CheckoutService`, friendly errors for stock problems.
- [x] **T4.6** Order success page (`order-success.php`): shown only if `last_order` in session matches `{number}` (or owner when logged in); bank instructions when applicable.
- [x] **T4.7** Static pages (`PageController`, `static.php`): About, Contact, Shipping, Returns, Privacy, Terms — **real written copy**, content arrays in the controller (not lorem).
- [x] **T4.8** `404.php`, `500.php`, 419 and 403 views; `SeoController` placeholders (finished in Phase 7); `HealthController` (`/health` fast "ok"; `?deep=1` runs one tiny Supabase query and reports ok/fail).
- [x] **Gate 4:** lint; self-review against `DS §12`; every user-visible string is escaped; every form has CSRF field; each page sets title/description via `head-meta`.

## Phase 5 — Auth & Account

- [x] **T5.1** `AuthController` + `login.php`/`register.php`: validation (email, password ≥ 8, confirm, name), register → auto-login, login with safe `next`, generic error + delay, logout (POST + CSRF), session `uid/email/name/role`.
- [x] **T5.2** `AccountController` + `account.php`, `account-orders.php`, `order-detail.php`: edit name/phone, paginated order history, owner-only order detail with items and status badge.
- [x] **T5.3** Header shows login/register or account menu (name, orders, admin link if admin, logout). Checkout prefill from profile.
- [x] **Gate 5:** lint; every account query filters by session uid; `next` validation present; logout is POST.

## Phase 6 — Admin Panel

- [x] **T6.1** Admin layout + `admin` middleware + sidebar nav (Dashboard, Products, Categories, Orders, Settings, View store, Logout).
- [x] **T6.2** Dashboard: 4 stat cards from `admin_stats` RPC, recent 8 orders, low-stock list (stock ≤ 5).
- [x] **T6.3** Products index: search, category filter, status filter, pagination (20), inline toggles for active/featured (POST), edit/delete actions, stock highlighting.
- [x] **T6.4** Product form (create/edit): all fields, validation, slug auto-generation + uniqueness, multi-image upload (≤ 5, ≤ 2 MB, finfo-validated) with remove and "make primary", price entered in major units and converted to cents, cache invalidation.
- [x] **T6.5** Product delete (confirm) — also deletes its images from Storage.
- [x] **T6.6** Categories CRUD (name, slug, description, sort order, active) with cache invalidation; block deleting is not required (products keep `category_id = null`).
- [x] **T6.7** Orders: list (status filter, search by number/email, pagination), detail (customer, address, items, totals, payment info), status update (allowed forward transitions only), payment status update, cancel via RPC with friendly error mapping.
- [x] **T6.8** Settings page: store name, currency code/symbol, flat shipping, free-shipping threshold, announcement, contact email, bank instructions → upsert + cache invalidation.
- [x] **Gate 6:** lint; every admin route uses `admin` middleware (grep route table); every POST has CSRF; uploads validated by content; no admin view echoes raw data.

## Phase 7 — Hardening, SEO, Performance, A11y (static review)

- [x] **T7.1** Security audit by grep + read: CSP header matches views (no inline code), cookie flags, CSRF coverage, redirect validation, no secrets in `public/` or `views/`.
- [x] **T7.2** SEO: `head-meta` (title, description, canonical, OG/Twitter), `SeoController::sitemap` (static pages + categories + active products, max 500 URLs), `robots.txt` (disallow `/admin`, `/cart`, `/checkout`, `/account`; sitemap link), noindex on cart/checkout/account/admin.
- [x] **T7.3** Performance: `width/height/lazy/decoding` on images, `fetchpriority` on LCP image, `preconnect` for fonts, asset versioning, caching for settings/categories/featured/newest in place.
- [x] **T7.4** Accessibility review by reading templates: labels, aria on drawer/menus, `alt`, heading order, focus management in `ui.js`.
- [x] **T7.5** Error handling review: every `Result->ok === false` path shows a friendly message; no raw exception output.
- [x] **Gate 7:** all Security Checklist items in `Rules.md` §6 ticked mentally with evidence from grep; lint passes.

## Phase 8 — Deployment Readiness & Docs

- [x] **T8.1** Finish `README.md`: prerequisites, Supabase setup steps (create project → SQL editor → run schema → seed → copy URL + keys), `.env` creation, local run (`php -S localhost:8000 -t public public/index.php`), create first admin, Render deploy steps (Docker, Free plan, env vars, health check), GitHub secret `APP_URL`, custom domain note, free-tier caveats (Render sleep/cold start, Supabase pause, 1 GB storage), troubleshooting.
- [x] **T8.2** Consistency check: env names, route table vs controllers, schema columns vs repository queries (grep for each column name used in `src/Repositories`).
- [x] **T8.3** Consolidate `Memory.md` → VERIFY-IN-QA list (every runtime-uncertain item).
- [x] **Gate 8:** full lint; `Memory.md` Status updated to "READY FOR QA"; print the H1 checklist for the human and **stop**.

---

## Phase 9 — FINAL QA (the only phase where running is allowed)

**T9.0 Human checkpoint H1 → wait for the human to say "ready".** Required: Supabase project created, `schema.sql` + `seed.sql` executed, `.env` filled, PHP 8.3 CLI available (or QA on a deployed URL).

**T9.1 Boot once:** `php -S localhost:8000 -t public public/index.php`. Open the app in one browser session. Run the whole checklist below, note each result as PASS/FAIL, **do not fix while testing**.

### QA Checklist
**Boot & shell**
- [ ] QA-01 `/health` returns ok; `/health?deep=1` reports Supabase ok.
- [ ] QA-02 Home renders: announcement, header, hero, categories, featured, new arrivals, footer (data from seed).
- [ ] QA-03 Theme toggle works and persists after reload; both themes readable.
- [ ] QA-04 Glass effect visible; no layout jank; fallback acceptable at 375 px width.

**Catalog**
- [ ] QA-05 `/shop` lists 12 per page, pagination works; sorting (4 options) changes order correctly.
- [ ] QA-06 Search finds a seeded product; empty search shows empty state.
- [ ] QA-07 Category filter and price range filter work and show chips; `/category/{slug}` works.
- [ ] QA-08 Product page: gallery/placeholder, price, compare-at + discount, stock states (incl. the out-of-stock product disables add-to-cart), related products, JSON-LD present.

**Cart & checkout**
- [ ] QA-09 Add to cart via JS: toast, badge bump, drawer shows item. With JS disabled: form add works and redirects.
- [ ] QA-10 Update qty (clamped to stock/10), remove, totals correct, free-shipping bar correct on both sides of the threshold.
- [ ] QA-11 Cart persists across reload and after server restart (encrypted cookie).
- [ ] QA-12 Guest checkout (COD): validation errors shown per field; success creates order, clears cart, shows order number.
- [ ] QA-13 Bank-transfer checkout shows the bank instructions on success.
- [ ] QA-14 Stock decremented in DB after order; ordering more than stock shows friendly error and creates **no** order.
- [ ] QA-15 Success page not accessible for another session's order number.

**Auth & account**
- [ ] QA-16 Register (valid), duplicate email error, short-password error; auto-login works.
- [ ] QA-17 Logout (POST) works; login with wrong password shows generic error; login with right password works; `next` redirect works; `next=//evil.com` is rejected.
- [ ] QA-18 Account page edits name/phone; order history lists the user's orders; another user's order number returns 404.

**Admin** (after H2: run `make_admin.sql`)
- [ ] QA-19 Non-admin gets 403/redirect on `/admin`; admin sees dashboard with sane stats.
- [ ] QA-20 Create product with 2 images (JPEG + PNG): appears on storefront immediately; invalid file type and > 2 MB rejected.
- [ ] QA-21 Edit product (price, stock, category, featured); change reflects on storefront within cache TTL (60 s) or immediately after cache forget.
- [ ] QA-22 Toggle active hides product (404 on its URL); delete removes product and its images.
- [ ] QA-23 Category create/edit/disable works and affects storefront.
- [ ] QA-24 Orders: list/filter/search; open order; status advances pending → confirmed → shipped → delivered; invalid transitions blocked.
- [ ] QA-25 Cancel a pending order → status cancelled and stock restored; cancelling a shipped order shows friendly error.
- [ ] QA-26 Settings change (store name, shipping fee, threshold, announcement) appears on the storefront and in cart math.

**Security & robustness**
- [ ] QA-27 POST without CSRF token → 419 page. Admin POST by customer → blocked.
- [ ] QA-28 View-source and network tab show no Supabase secret/publishable key, no inline scripts; CSP produces no console errors.
- [ ] QA-29 Unknown URL → friendly 404; forced error (bad Supabase URL in `.env` temporarily) → friendly 500 without details.
- [ ] QA-30 `sitemap.xml` and `robots.txt` correct.

**Responsive / quality**
- [ ] QA-31 Check at 360, 768, 1280 px: header/menu, shop filters sheet, PDP, cart, checkout, admin tables are usable.
- [ ] QA-32 Keyboard-only: skip link, header menu, drawer (focus trap + Esc), forms all operable; focus visible in both themes.
- [ ] QA-33 Reduced motion respected (emulate in devtools); page weight sane (< 300 KB excl. images).
- [ ] QA-34 Every item in `Memory.md` → VERIFY-IN-QA confirmed or fixed.

**T9.2 Fix batch:** collect all FAILs, fix them in one pass (edit, don't rewrite).
**T9.3 Re-test only the failed items** (max 2 rounds total). Leftovers → `Memory.md` → Known Issues.
**T9.4** Write the QA result summary (counts + known issues) in `Memory.md` → QA Log.

---

## Phase 10 — Release

- [ ] **T10.1** Final cleanup: remove debug code, confirm `.env` not tracked, lint passes, README accurate.
- [ ] **T10.2 (H3, human)** Push repo to GitHub → Render: New Web Service → Docker → Free → set env vars (`APP_URL`, `SUPABASE_URL`, `SUPABASE_PUBLISHABLE_KEY`, `SUPABASE_SECRET_KEY`; `APP_SECRET` auto-generated) → deploy.
- [ ] **T10.3 (human)** Set `APP_URL` to the final `onrender.com` URL (redeploy) and add GitHub secret `APP_URL`; run the `keepalive` workflow once manually.
- [ ] **T10.4** Post-deploy smoke (5 checks only): `/health?deep=1`, home, one product, add to cart, admin login.
- [ ] **T10.5** Set `Memory.md` Status = **COMPLETE**.
