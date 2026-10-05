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
