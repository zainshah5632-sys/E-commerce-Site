# GlassCart

A modern, high-performance e-commerce web application built with PHP 8.3 and Supabase, featuring an aesthetic glassmorphism UI with light/dark theme support.

## Stack
- **Backend:** PHP 8.3 (Zero Composer dependencies, custom micro-core, PSR-4 autoloader)
- **Database & Auth:** Supabase (PostgreSQL, GoTrue Auth, Storage) accessed server-side via REST (cURL)
- **Frontend:** Vanilla CSS (Glassmorphism design system), Vanilla ES2020 JavaScript, semantic HTML5
- **Sessions:** Stateless encrypted cookies (`sodium_crypto_secretbox`)
- **Deployment:** Docker (`php:8.3-apache`) ready for Render Free Tier

---

## Key Features
- **Storefront:** Category browsing, live search with active filter chips, price range sliders, 4 sorting modes, responsive product gallery, stock indicators.
- **Cart & Checkout:** Encrypted-cookie cart (re-validated server-side), real-time stock clamping, free-shipping threshold tracker, Cash on Delivery (COD) & Direct Bank Transfer checkout.
- **Authentication:** Prioritized user sign in, registration with auto-login, secure sign out with CSRF protection, protected account dashboard with paginated order history and order tracking.
- **Admin Panel:** Real-time metrics overview (`admin_stats` RPC), product catalog management with multi-image upload to Supabase Storage, category management, order lifecycle management (forward status transitions and cancellations), and live store settings.
- **Design:** Liquid aurora background, glassmorphism cards (`.glass`, `.glass--strong`, `.glass--subtle`, `.glass--flat`), accessible color contrast, instant theme toggle.

---

## Prerequisites
- **PHP 8.3+** with `curl` and `sodium` extensions enabled (or **Docker**).
- A free **Supabase** account ([supabase.com](https://supabase.com)).

---

## Supabase Setup
1. Create a new Supabase project (e.g. `GlassCart`).
2. Open the **SQL Editor** in your Supabase Dashboard:
   - Run `database/schema.sql` to create all tables, indexes, triggers, functions, and RLS policies.
   - Run `database/seed.sql` to populate sample categories and products.
3. In **Project Settings → API**:
   - Copy **Project URL** (`SUPABASE_URL`).
   - Copy **anon / public key** (`SUPABASE_PUBLISHABLE_KEY`).
   - Copy **service_role key** (`SUPABASE_SECRET_KEY`).
4. Ensure the Storage bucket `product-images` exists and is set to **Public** (created automatically by `schema.sql`).

---

## Local Setup
1. Clone or copy the repository files into your local directory.
2. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
3. Generate a 64-character hex key for `APP_SECRET`:
   ```bash
   # In PHP CLI or bash:
   php -r "echo bin2hex(random_bytes(32));"
   ```
4. Fill in `.env` with your values:
   ```ini
   APP_ENV=local
   APP_URL=http://localhost:8000
   APP_SECRET=your_64_character_hex_key_here
   SUPABASE_URL=https://your-project-ref.supabase.co
   SUPABASE_PUBLISHABLE_KEY=your_supabase_anon_key
   SUPABASE_SECRET_KEY=your_supabase_service_role_key
   SUPABASE_STORAGE_BUCKET=product-images
   ```
5. Start the local PHP built-in server:
   ```bash
   php -S localhost:8000 -t public public/index.php
   ```
6. Visit `http://localhost:8000`.

---

## Creating Your First Admin Account
1. Register a new account via the storefront UI at `/register`.
2. Open the **SQL Editor** in Supabase and run:
   ```sql
   UPDATE public.profiles
   SET role = 'admin'
   WHERE id IN (
     SELECT id FROM auth.users WHERE email = 'your-email@example.com'
   );
   ```
3. Log out and log back in, or refresh your session. You can now access `/admin`.

---

## Deploying to Render (Free Tier)
1. Push your repository to **GitHub**.
2. Log in to [Render.com](https://render.com) and click **New → Web Service**.
3. Connect your GitHub repository.
4. Select **Docker** as the runtime environment.
5. Choose the **Free** instance type.
6. Under **Environment Variables**, add:
   - `APP_ENV` = `production`
   - `APP_URL` = `https://your-service-name.onrender.com`
   - `APP_SECRET` = *(generate a 64-character hex key)*
   - `SUPABASE_URL` = `https://your-project-ref.supabase.co`
   - `SUPABASE_PUBLISHABLE_KEY` = `your_anon_key`
   - `SUPABASE_SECRET_KEY` = `your_service_role_key`
   - `SUPABASE_STORAGE_BUCKET` = `product-images`
7. Click **Create Web Service**.
8. Verify health status at `https://your-service-name.onrender.com/health?deep=1`.

### Supabase Keepalive (Prevent Inactivity Pause)
Supabase Free Tier projects pause after 7 days of inactivity. To prevent this:
1. In your GitHub repository, go to **Settings → Secrets and variables → Actions**.
2. Add a repository secret named `APP_URL` with your deployed URL (e.g. `https://your-service-name.onrender.com`).
3. The included GitHub Action (`.github/workflows/keepalive.yml`) will ping `/health?deep=1` every 3 days.

---

## Free-Tier Caveats & Considerations
- **Render Free Spin-Down:** Web services on Render's free tier sleep after 15 minutes of inactivity. The first request after sleep may take 45–60 seconds (cold start).
- **Stateless Storage:** Render containers do not have persistent disk storage. GlassCart uses stateless encrypted cookies for sessions and cart data, and Supabase Storage for uploaded files.
- **Supabase Free Limits:** 500 MB database space and 1 GB file storage. Image uploads are restricted to 2 MB per file with automatic client and server validation.

---

## Troubleshooting
- **Session Decryption Errors / 500 on Login:** Verify that `APP_SECRET` is exactly 64 hex characters (32 bytes).
- **Supabase 401 / 403 API Errors:** Ensure `SUPABASE_SECRET_KEY` is the `service_role` key and not the anon key. If your secret key starts with `sb_secret_`, GlassCart automatically uses the appropriate header format.
- **Image Upload Failures:** Ensure the `product-images` bucket exists in Supabase Storage and has public read access enabled.
- **CSRF 419 Page Expired:** Ensure cookies are accepted in your browser and `SameSite=Lax` is permitted over your connection.
