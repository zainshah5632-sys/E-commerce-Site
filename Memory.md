# Memory — Project State (living document)

> **Agent:** read this at the start of every session (Status + VERIFY-IN-QA are the important parts). **Update it at the end of every phase** (max 10 lines per update). Keep it short; don't duplicate what's in the other docs.

---

## 1. Status

| Field | Value |
|---|---|
| Project | GlassCart — PHP + Supabase e-commerce, glassmorphism UI |
| Current phase | Phase 8 — Complete |
| Last completed task | T8.3 |
| **NEXT** | **T9.0 (Human Checkpoint H1)** |
| Overall state | READY FOR QA *(→ IN QA → COMPLETE)* |
| Testing allowed? | **NO** (until Phase 9 checkpoint cleared — see Rules §2) |

---

## 2. Decisions Log (already made — do not revisit)

| ID | Decision | Reason |
|---|---|---|
| D-001 | PHP 8.3, no framework, no Composer | Simple deploy, zero dependency risk, easy for small models to follow |
| D-002 | Supabase accessed **server-side via REST (cURL)**, not PDO/pgsql | No extra PHP extensions, no IPv6/pooler issues, works with RLS-deny-all |
| D-003 | RLS enabled with **no policies**; PHP uses the secret (service) key | Browser can never read/write data directly |
| D-004 | **Stateless encrypted-cookie sessions** (libsodium) | Render free has no persistent disk and restarts often; zero DB round-trips for sessions |
| D-005 | Cart stored in cookie as `[{p,q}]`, prices always re-fetched | Never trust client prices; max 20 lines keeps cookie < 4 KB |
| D-006 | Atomic order placement via Postgres function `place_order` | Prevents overselling, one round-trip |
| D-007 | Registration via Supabase Admin API with `email_confirm: true` | Avoids free-tier auth email limits; no SMTP needed in MVP |
| D-008 | Money as integer cents; settings in `site_settings` table | No float bugs; owner edits config without deploys |
| D-009 | Payments: COD + bank transfer only | Free, no gateway dependency; gateway left as extension point |
| D-010 | Hosting: Render free (Docker `php:8.3-apache`); keep-alive via GitHub Actions cron every 3 days | Free; avoids Supabase 7-day inactivity pause |
| D-011 | Images in Supabase Storage bucket `product-images` (public read), 2 MB cap, no resizing | Free tier friendly; no GD dependency |
| D-012 | Strict CSP: no inline JS/CSS; theme-init.js is the only blocking script | Security + simple rules |
| D-013 | Glass design: tokens in `Design.md §2`; repeated items use `.glass--flat` (no blur) | Performance on low-end phones |
| D-014 | Testing only in Phase 9, one pass + max 2 fix rounds | Owner requirement: avoid repeated e2e testing |

*(Agent: append new decisions here as `D-0xx` when you must choose something unspecified.)*

---

## 3. Environment (names only — never store values here)

`APP_ENV` · `APP_URL` · `APP_SECRET` · `SUPABASE_URL` · `SUPABASE_PUBLISHABLE_KEY` · `SUPABASE_SECRET_KEY` · `SUPABASE_STORAGE_BUCKET`

Local dev: `.env` (gitignored). Render: dashboard env vars. GitHub: secret `APP_URL`.

---

## 4. Key Facts (quick reference)

- Routes: `Architecture.md §5` · Schema: `Architecture.md §7` · Tokens: `Design.md §2`.
- Order number format `ORD-YYMMDD-XXXXXX`; statuses `pending → confirmed → shipped → delivered` (+ `cancelled` from pending/confirmed).
- Cart limits: 20 lines, qty 1–10 (≤ stock). Page size: shop 12, admin 20.
- Cache TTL 60 s (settings, categories, featured, newest) in `sys_get_temp_dir()/gc_cache`.
- Free-tier caveats: Render sleeps after ~15 min idle (≈1 min cold start), no persistent disk; Supabase free pauses after ~7 days idle, 500 MB DB, 1 GB storage.

---

## 5. VERIFY-IN-QA (runtime-uncertain items — add as you go, check in Phase 9)

Pre-filled expectations to confirm:
- [ ] PostgREST `or=(name.ilike.*x*,short_description.ilike.*x*)` works with sanitized input.
- [ ] `Content-Range` header parsing gives correct totals with `Prefer: count=exact`.
- [ ] New `sb_secret_…` keys work with `apikey` header only (and legacy JWT keys with Bearer too).
- [ ] Admin create-user endpoint response shape and "already registered" error text/status.
- [ ] Password login response contains `user.id`; wrong password status is handled.
- [ ] Storage upload path/headers accepted; public URL loads in the browser.
- [ ] `place_order` RPC error messages (`OUT_OF_STOCK:*`) are surfaced in the REST error body and parsed correctly.
- [ ] Cookie session size stays < 4 KB with 20 cart lines + flash + csrf.
- [ ] `FallbackResource /index.php` serves static assets directly and routes everything else (Apache in Docker).
- [ ] CSP does not block Google Fonts or Supabase images; no console errors.

*(Agent: add more lines here instead of testing during build.)*

---

## 6. Known Issues

*(none yet — filled during Phase 9)*

---

## 7. Backlog / Ideas (not in scope — do not build)

*(Agent: park new ideas here. Post-MVP list is in `PRD.md §12`.)*

---

## 8. Phase Log

| Phase | Completed | Notes (≤ 3 lines) |
|---|---|---|
| 0 Scaffold | ☑ | Folder structure, config files, Docker, render.yaml, keepalive workflow. |
| 1 Database | ☑ | schema.sql, seed.sql (4 cats, 12 products, 1 stock 0), make_admin.sql. |
| 2 Core PHP | ☑ | PSR-4, Core classes, Supabase REST client, Repositories, Services, index.php. |
| 3 Design system | ☑ | CSS tokens, components, pages, SVGs, theme toggle, UI & cart JS. |
| 4 Storefront | ☑ | Home, Shop, PDP with schema.org, Cart, Checkout, Order success, static, error views. |
| 5 Auth & account | ☑ | Sign In, Sign Out (CSRF), Register, Account details, Paginated order history. |
| 6 Admin | ☑ | Dashboard with RPC stats, Products CRUD + upload, Categories, Orders, Settings. |
| 7 Hardening/SEO | ☑ | Security audit, CSP compliance, noindex tags, sitemap.xml, robots.txt. |
| 8 Deploy readiness | ☑ | Comprehensive README, env consistency check, H1 checklist prepared. |
| 9 Final QA | ☐ | |
| 10 Release | ☐ | |

---

## 9. QA Log (Phase 9 only)

| Round | PASS | FAIL | Notes |
|---|---|---|---|
| 1 | | | |
| 2 | | | |

Leftover failures → section 6.

---

## 10. Session Resume Protocol

1. Read `Rules.md` → this file (Status, VERIFY-IN-QA) → `Tasks.md` (first unchecked task).
2. Continue from **NEXT**. Don't re-read finished code unless a task requires it.
3. Before stopping (any reason): update Status → *Current phase*, *Last completed task*, **NEXT**.
