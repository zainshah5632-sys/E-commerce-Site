-- =====================================================================
-- GlassCart Payment System Migration — Run in Supabase SQL Editor
-- Supports: Easypaisa, JazzCash, Bank Transfer, Visa/Mastercard, COD
-- =====================================================================

-- 1. Update check constraints on orders table to support all payment methods
alter table public.orders drop constraint if exists orders_payment_method_check;
alter table public.orders add constraint orders_payment_method_check 
  check (payment_method in ('cod', 'bank_transfer', 'easypaisa', 'jazzcash', 'card'));

-- 2. Update payment_status check constraint to include 'rejected'
alter table public.orders drop constraint if exists orders_payment_status_check;
alter table public.orders add constraint orders_payment_status_check 
  check (payment_status in ('pending', 'paid', 'failed', 'rejected', 'refunded'));

-- 3. Add transaction_id, payment_receipt_url, and payment_details columns to orders
alter table public.orders add column if not exists transaction_id text;
alter table public.orders add column if not exists payment_receipt_url text;
alter table public.orders add column if not exists payment_details jsonb default '{}'::jsonb;

-- 4. Create payment_transactions audit table
create table if not exists public.payment_transactions (
  id               uuid primary key default gen_random_uuid(),
  order_id         uuid references public.orders(id) on delete cascade,
  order_number     text not null,
  payment_method   text not null,
  amount_cents     integer not null default 0,
  currency         text not null default 'USD',
  status           text not null default 'pending' check (status in ('pending', 'paid', 'failed', 'rejected', 'refunded')),
  transaction_id   text,
  receipt_url      text,
  gateway_response jsonb default '{}'::jsonb,
  admin_notes      text,
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create index if not exists payment_tx_order_idx on public.payment_transactions (order_id);
create index if not exists payment_tx_order_number_idx on public.payment_transactions (order_number);
create index if not exists payment_tx_status_idx on public.payment_transactions (status);

-- Enable RLS (Service role access for PHP backend)
alter table public.payment_transactions enable row level security;

-- 5. Updated place_order RPC supporting new payment rails
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

  -- Create initial payment transaction record
  insert into payment_transactions (order_id, order_number, payment_method, amount_cents, status)
  values (v_order_id, v_number, p_payment_method, v_total, 'pending');

  return jsonb_build_object('order_id', v_order_id, 'order_number', v_number, 'total_cents', v_total);
end $$;

grant execute on function public.place_order(uuid,text,text,text,jsonb,text,text,jsonb,integer) to service_role;
