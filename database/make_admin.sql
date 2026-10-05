-- =====================================================================
-- GlassCart — Promote a User to Admin
-- =====================================================================
-- Run this query in the Supabase SQL Editor AFTER registering on the site
-- with the email you wish to grant administrative permissions to.

update public.profiles
set role = 'admin'
where id = (
  select id from auth.users
  where email = 'YOUR_EMAIL@example.com'
);
