-- =====================================================================
-- GlassCart Seed Data (Idempotent)
-- =====================================================================

-- Categories
insert into public.categories (id, name, slug, description, sort_order, is_active) values
  ('c1000000-0000-0000-0000-000000000001', 'Audio', 'audio', 'Premium acoustic transducers, studio headphones, and desktop monitors.', 1, true),
  ('c1000000-0000-0000-0000-000000000002', 'Wearables', 'wearables', 'Next-generation biometric timepieces and everyday optical accessories.', 2, true),
  ('c1000000-0000-0000-0000-000000000003', 'Desk & Home', 'desk-home', 'Sculptural desk lighting, acrylic stands, and workspace ergonomics.', 3, true),
  ('c1000000-0000-0000-0000-000000000004', 'Accessories', 'accessories', 'Braided power cables, aluminum hubs, and portable protective sleeves.', 4, true)
on conflict (slug) do nothing;

-- Products (12 items)
insert into public.products (
  id, category_id, name, slug, short_description, description,
  price_cents, compare_at_cents, sku, stock, images, is_active, is_featured
) values
  -- Audio
  (
    'a1000000-0000-0000-0000-000000000001',
    'c1000000-0000-0000-0000-000000000001',
    'Aura One Wireless Headphones',
    'aura-one-wireless-headphones',
    'Active noise-canceling studio cans with custom 40mm beryllium drivers.',
    'Engineered for discerning audiophiles and focused professionals alike. Aura One features adaptive spatial acoustic tuning, low-latency Bluetooth 5.3, and plush memory-foam ear cushions wrapped in breathable vegan leather for all-day listening.',
    24900,
    29900,
    'AUD-AURA-1',
    35,
    '{}',
    true,
    true
  ),
  (
    'a1000000-0000-0000-0000-000000000002',
    'c1000000-0000-0000-0000-000000000001',
    'Clarity Desktop Studio Monitors',
    'clarity-desktop-studio-monitors',
    'Pair of compact reference speakers with transparent acrylic waveguide.',
    'Delivers an uncolored flat-frequency profile that elevates nearfield production and critical listening. Each monitor features custom acoustic waveguides and discrete Class-D amplification for pristine high-fidelity sound.',
    18900,
    null,
    'AUD-CLAR-2',
    18,
    '{}',
    true,
    false
  ),
  (
    'a1000000-0000-0000-0000-000000000003',
    'c1000000-0000-0000-0000-000000000001',
    'Pulse True Wireless Earbuds',
    'pulse-true-wireless-earbuds',
    'Sweatproof in-ear buds with transparency mode and 32-hour battery life.',
    'Designed for movement and daily commute. Pulse earbuds seal comfortably with silicone contour tips and incorporate environmental beamforming microphones for crystal-clear voice transmission.',
    9900,
    12900,
    'AUD-PULS-3',
    42,
    '{}',
    true,
    false
  ),

  -- Wearables
  (
    'a1000000-0000-0000-0000-000000000004',
    'c1000000-0000-0000-0000-000000000002',
    'Vektor Chrono Smartwatch',
    'vektor-chrono-smartwatch',
    'Titanium unibody smartwatch with sapphire crystal display and health telemetry.',
    'Blending Scandinavian horology with modern health intelligence. Vektor monitors continuous heart rate variability, skin temperature, and sleep cycles while delivering seamless notifications with a 10-day battery reserve.',
    28900,
    null,
    'WEAR-VEK-1',
    25,
    '{}',
    true,
    true
  ),
  (
    'a1000000-0000-0000-0000-000000000005',
    'c1000000-0000-0000-0000-000000000002',
    'Lumen Blue-Light Frames',
    'lumen-blue-light-frames',
    'Ultralight acetate frames with anti-reflective visual wellness lenses.',
    'Alleviate digital eye strain during marathon creative sessions. Lumen frames feature precision optical filtration that blocks 420nm high-energy blue wavelengths without color distortion.',
    6500,
    null,
    'WEAR-LUM-2',
    50,
    '{}',
    true,
    false
  ),
  (
    'a1000000-0000-0000-0000-000000000006',
    'c1000000-0000-0000-0000-000000000002',
    'Apex Performance Fitness Ring',
    'apex-performance-fitness-ring',
    'Aerospace ceramic smart ring tracking recovery and athletic strain.',
    'A featherweight smart ring that disappears on your finger. Apex captures nocturnal metrics, resting vitals, and cardio recovery trends without distracting touchscreens or vibrations.',
    19900,
    null,
    'WEAR-APX-3',
    0, -- Stock 0 (out of stock testing item per T1.2)
    '{}',
    true,
    false
  ),

  -- Desk & Home
  (
    'a1000000-0000-0000-0000-000000000007',
    'c1000000-0000-0000-0000-000000000003',
    'Horizon Linear Desk Lamp',
    'horizon-linear-desk-lamp',
    'Sculpted aluminum task lamp with capacitive touch dimming and warm-to-cool LED.',
    'Illuminate your creative workspace with glare-free edge-lit optics. Horizon balances a weighted brushed aluminum cantilever base with infinitely variable color temperature presets from 2700K to 6500K.',
    14500,
    null,
    'DSK-HOR-1',
    20,
    '{}',
    true,
    true
  ),
  (
    'a1000000-0000-0000-0000-000000000008',
    'c1000000-0000-0000-0000-000000000003',
    'Prism Solid Acrylic Laptop Stand',
    'prism-solid-acrylic-laptop-stand',
    'Laser-polished transparent riser promoting optimal ergonomic eye height.',
    'Crafted from thick structural acrylic with polished chamfered bevels. Prism elevates any notebook up to 16 inches for improved posture and increased passive thermal airflow.',
    4900,
    null,
    'DSK-PRM-2',
    30,
    '{}',
    true,
    false
  ),
  (
    'a1000000-0000-0000-0000-000000000009',
    'c1000000-0000-0000-0000-000000000003',
    'Vertex Felt & Cork Desk Mat',
    'vertex-felt-cork-desk-mat',
    'Dual-surface reversible desk pad dampening keyboard resonance.',
    'Organize your desk surface in understated elegance. Vertex pairs high-density merino wool felt on one face with natural Portuguese non-slip cork on the other.',
    3800,
    null,
    'DSK-VTX-3',
    55,
    '{}',
    true,
    false
  ),

  -- Accessories
  (
    'a1000000-0000-0000-0000-000000000010',
    'c1000000-0000-0000-0000-000000000004',
    'Flux Braided Thunderbolt Cable (2m)',
    'flux-braided-thunderbolt-cable-2m',
    'Reinforced 40Gbps data transfer and 100W Power Delivery cable with silicone ties.',
    'Built with rugged ballistic nylon outer weave and CNC alloy housings. Flux ensures full Thunderbolt 4 data rates, dual 4K video bandwidth, and rapid high-wattage device charging.',
    2900,
    null,
    'ACC-FLX-1',
    60,
    '{}',
    true,
    false
  ),
  (
    'a1000000-0000-0000-0000-000000000011',
    'c1000000-0000-0000-0000-000000000004',
    'Kinetics Magnetic Cable Anchor',
    'kinetics-magnetic-cable-anchor',
    'Weighted steel desk puck with interchangeable silicone magnetic cord collars.',
    'Keep your charging and peripheral cables right where you need them. Kinetics anchors securely to any desktop via micro-suction tape and guides cables smoothly into place.',
    1900,
    null,
    'ACC-KIN-2',
    40,
    '{}',
    true,
    false
  ),
  (
    'a1000000-0000-0000-0000-000000000012',
    'c1000000-0000-0000-0000-000000000004',
    'Nexus Anodized Multi-Port Hub',
    'nexus-anodized-multi-port-hub',
    '7-in-1 compact USB-C hub supporting HDMI 4K60, UHS-II SD, and pass-through power.',
    'The quintessential travel companion for laptop creators. Nexus packs high-speed memory card readers, dual USB 3.2 Gen 2 ports, and 100W power delivery into an aluminum shell.',
    7900,
    null,
    'ACC-NEX-3',
    22,
    '{}',
    true,
    false
  )
on conflict (slug) do nothing;
