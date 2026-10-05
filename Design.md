# Design System — GlassCart (Glassmorphism, Modern)

> The agent must build **all UI strictly from the tokens and component specs below**. Do not invent new colors, radii, shadows or fonts. If something is missing, extend the tokens here first and log it in `Memory.md`.

---

## 1. Design Principles

1. **Depth through translucency** — frosted glass panels floating over a soft aurora gradient.
2. **Readability beats effect** — text always sits on a glass surface that guarantees ≥ 4.5:1 contrast.
3. **Calm motion** — short, eased, purposeful; fully disabled for `prefers-reduced-motion`.
4. **Performance-aware glass** — few blurred layers, smaller blur on mobile, no nested blur.
5. **Clarity first for commerce** — price, stock, CTA and totals are always the most prominent element in their card.

---

## 2. Design Tokens (`public/assets/css/tokens.css`)

```css
:root {
  /* Fonts */
  --font-display: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
  --font-body: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;

  /* Brand */
  --brand-400: #818cf8;
  --brand-500: #6366f1;
  --brand-600: #4f46e5;
  --accent-cyan: #22d3ee;
  --accent-pink: #f472b6;
  --accent-amber: #fbbf24;
  --grad-brand: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
  --grad-brand-soft: linear-gradient(135deg, rgba(99,102,241,.16), rgba(236,72,153,.12));

  /* Status (solid, AA on white) */
  --success: #047857;
  --warning: #b45309;
  --danger:  #b91c1c;
  --info:    #0369a1;

  /* Surfaces — LIGHT */
  --bg-base: #eef1ff;
  --aurora-1: rgba(99,102,241,.38);
  --aurora-2: rgba(236,72,153,.26);
  --aurora-3: rgba(34,211,238,.30);
  --glass-subtle: rgba(255,255,255,.38);
  --glass:        rgba(255,255,255,.60);
  --glass-strong: rgba(255,255,255,.84);
  --glass-border: rgba(255,255,255,.72);
  --glass-edge:   inset 0 1px 0 rgba(255,255,255,.9);
  --line: rgba(15,23,42,.10);

  --text-1: #0f172a;
  --text-2: #334155;
  --text-3: #566074;
  --on-brand: #ffffff;
  --focus: #4f46e5;

  /* Shadows */
  --shadow-sm: 0 2px 8px rgba(30,41,99,.08);
  --shadow-md: 0 8px 30px rgba(30,41,99,.12);
  --shadow-lg: 0 20px 60px rgba(30,41,99,.20);

  /* Blur */
  --blur-sm: 10px;
  --blur-md: 18px;
  --blur-lg: 28px;

  /* Shape */
  --r-sm: 10px; --r-md: 16px; --r-lg: 24px; --r-xl: 32px; --r-pill: 999px;

  /* Spacing (4px base) */
  --s-1: 4px;  --s-2: 8px;  --s-3: 12px; --s-4: 16px; --s-5: 24px;
  --s-6: 32px; --s-7: 48px; --s-8: 64px; --s-9: 96px;

  /* Layout */
  --container: 1240px;
  --header-h: 72px;

  /* Motion */
  --ease: cubic-bezier(.2,.8,.2,1);
  --t-fast: 150ms; --t-base: 240ms; --t-slow: 420ms;

  /* Z-index */
  --z-header: 50; --z-drawer: 80; --z-modal: 90; --z-toast: 100;
}

[data-theme="dark"] {
  --bg-base: #0b1020;
  --aurora-1: rgba(99,102,241,.45);
  --aurora-2: rgba(236,72,153,.28);
  --aurora-3: rgba(34,211,238,.22);
  --glass-subtle: rgba(255,255,255,.05);
  --glass:        rgba(255,255,255,.08);
  --glass-strong: rgba(18,24,48,.82);
  --glass-border: rgba(255,255,255,.16);
  --glass-edge:   inset 0 1px 0 rgba(255,255,255,.14);
  --line: rgba(255,255,255,.12);

  --text-1: #f1f5f9;
  --text-2: #cbd5e1;
  --text-3: #9aa6bb;
  --success: #34d399; --warning: #fbbf24; --danger: #f87171; --info: #38bdf8;

  --shadow-sm: 0 2px 8px rgba(0,0,0,.35);
  --shadow-md: 0 8px 30px rgba(0,0,0,.45);
  --shadow-lg: 0 20px 60px rgba(0,0,0,.55);
}
```

Theme switching: `theme-init.js` (tiny, blocking, in `<head>`) sets `data-theme` on `<html>` from `localStorage('theme')` or `prefers-color-scheme`. Toggle button in the header flips it and persists. (Theme preference in localStorage is fine — it is not auth data.)

---

## 3. Background — "Aurora"

A fixed, GPU-cheap layer behind everything (no `filter: blur`, only gradients):

```css
body { background: var(--bg-base); color: var(--text-1); font-family: var(--font-body); }
body::before {
  content: ""; position: fixed; inset: 0; z-index: -1; pointer-events: none;
  background:
    radial-gradient(40vmax 40vmax at 12% 8%,  var(--aurora-1), transparent 60%),
    radial-gradient(45vmax 45vmax at 88% 18%, var(--aurora-2), transparent 60%),
    radial-gradient(50vmax 50vmax at 50% 105%, var(--aurora-3), transparent 60%);
  animation: aurora-drift 28s var(--ease) infinite alternate;
}
@keyframes aurora-drift { to { transform: translate3d(2%, -2%, 0) scale(1.06); } }
@media (prefers-reduced-motion: reduce) { body::before { animation: none; } }
```

---

## 4. The Glass Recipe

Three levels, one class each (`components.css`):

```css
.glass, .glass--strong, .glass--subtle {
  border: 1px solid var(--glass-border);
  box-shadow: var(--shadow-md), var(--glass-edge);
  -webkit-backdrop-filter: blur(var(--blur-md)) saturate(160%);
          backdrop-filter: blur(var(--blur-md)) saturate(160%);
  border-radius: var(--r-lg);
}
.glass         { background: var(--glass); }
.glass--strong { background: var(--glass-strong); }   /* forms, tables, admin, text-heavy */
.glass--subtle { background: var(--glass-subtle); box-shadow: var(--shadow-sm), var(--glass-edge); }

/* Flat variant for repeated items (cards in grids, table rows, cart lines): no blur, cheaper */
.glass--flat {
  -webkit-backdrop-filter: none;
          backdrop-filter: none;
}

/* Fallbacks */
@supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
  .glass, .glass--subtle { background: var(--glass-strong); }
}
@media (prefers-reduced-transparency: reduce) {
  .glass, .glass--subtle { background: var(--glass-strong); backdrop-filter: none; -webkit-backdrop-filter: none; }
}
@media (max-width: 640px) {
  :root { --blur-md: 12px; --blur-lg: 16px; }   /* cheaper on phones */
}
```

**Rules**
- ✅ Use `.glass--strong` for any block with long text, forms, tables or prices over busy areas.
- ✅ Product cards: `.glass` with an opaque image area.
- ❌ **Never nest** an element with `backdrop-filter` inside another element with `backdrop-filter` (breaks in Chromium). Inner elements use plain `rgba` backgrounds.
- ❌ Keep blurred elements on screen to a minimum (≈ 6). Header, hero cards, drawer, filters and footer may blur. **Repeated items (product cards in grids, table rows, cart lines) use `background: var(--glass)` + border + shadow but `backdrop-filter: none`** — add a `.glass--flat` modifier for this.
- ❌ No glass on tiny elements (badges, chips): use solid/gradient fills.
- ❌ No pure-white text on light glass; use tokens `--text-1/2/3`.

---

## 5. Typography

Google Fonts: `Plus Jakarta Sans` (600, 700, 800) + `Inter` (400, 500, 600), `display=swap`, with `preconnect`.

| Role | Size (fluid) | Weight | Font | Line-height |
|---|---|---|---|---|
| Display / Hero H1 | `clamp(2.25rem, 5vw, 4rem)` | 800 | display | 1.05 |
| H2 | `clamp(1.75rem, 3.2vw, 2.5rem)` | 700 | display | 1.15 |
| H3 | `1.25rem–1.5rem` | 700 | display | 1.25 |
| Body | `1rem` (16px) | 400 | body | 1.6 |
| Small / meta | `.875rem` | 500 | body | 1.5 |
| Price (card) | `1.125rem` | 700 | display | 1 |
| Price (PDP) | `clamp(1.75rem, 3vw, 2.25rem)` | 800 | display | 1 |
| Overline | `.75rem`, uppercase, letter-spacing `.08em` | 600 | body | 1 |

Gradient text (`.text-gradient`) allowed **only** for hero headline accent words and the logo.

---

## 6. Layout & Responsiveness

- Mobile-first. Breakpoints: `480px`, `768px`, `1024px`, `1280px`.
- `.container { width: min(100% - 2rem, var(--container)); margin-inline: auto; }`
- Section vertical rhythm: `--s-8` desktop / `--s-7` mobile.
- Product grid: 2 cols (<768) → 3 cols (≥768) → 4 cols (≥1100); gap `--s-4` mobile / `--s-5` desktop.
- Sticky glass header (`--header-h`), with `scroll-margin-top` on anchored sections.
- Touch targets ≥ 44×44 px.

---

## 7. Component Specs

| Component | Spec |
|---|---|
| **Announcement bar** | Slim (36px) gradient (`--grad-brand`) strip, white text 13px, centered, from `settings('announcement')`. Hidden if empty. |
| **Header** | Sticky, `.glass`, height 72px. Left: logo (gradient mark + wordmark). Center (≥1024): nav links (Shop, Categories dropdown/anchors, About). Right: search (icon → expands inline input, on mobile opens full-width row), theme toggle, account icon, cart icon with count badge. Mobile: hamburger → glass slide-down menu. Active link = gradient underline. |
| **Buttons** | `.btn` base: height 44px, padding 0 20px, radius `--r-pill`, weight 600, transition `var(--t-base) var(--ease)`. `.btn--primary`: `--grad-brand`, white text, shadow `0 8px 24px rgba(99,102,241,.35)`, hover lift `-2px` + brighten. `.btn--glass`: `.glass--subtle` look + `--text-1`. `.btn--ghost`: transparent, hover `--glass-subtle`. `.btn--danger`: solid `--danger`. `:active` scale `.98`. Disabled: 50% opacity, no pointer events. Loading state: spinner replaces label, width preserved. |
| **Inputs** | Height 46px, radius `--r-md`, background `--glass-strong`, 1px `--line` border, label above (`.875rem`, 600). Focus: 2px `--focus` ring + soft glow. Error: `--danger` border + message below with icon. Textarea min-height 120px. Select with custom chevron. Checkbox/radio custom, brand-colored. |
| **Product card** | `.glass`, radius `--r-lg`, overflow hidden. Image area 4:5 (`aspect-ratio`), `object-fit: cover`, zoom `scale(1.05)` on hover (desktop). Badges top-left (Sale = pink gradient, New = cyan, Out of stock = neutral dark). Quick-add circular button bottom-right of image (appears on hover desktop, always visible on touch). Body: category overline, name (2-line clamp), price row (price bold + compare-at struck-through in `--text-3` + `-XX%` badge). Hover: lift `-6px`, shadow `--shadow-lg`. Entire card is one link; quick-add button sits above it. |
| **Category tile** | `.glass`, 1:1 or 4:3, large gradient initial/icon, name + item hint, hover gradient border glow. |
| **Hero** | Two-column (stack on mobile). Left: overline pill, H1 with `.text-gradient` accent word, subtext (max 52ch), two CTAs (primary "Shop now", glass "Browse categories"), trust row (3 small icon+text items). Right: layered floating glass cards (decorative, `aria-hidden`) showing featured product mini-card, rating chip, "Free shipping" chip; gentle float animation (`translateY` ±8px, 6s). |
| **Filters (shop)** | Desktop: left `.glass--strong` sidebar (260px, sticky): search, category list, price min/max, sort. Mobile: "Filters" button opens bottom sheet (glass strong, drag-less, close button, apply button). Active filters shown as removable chips above grid. |
| **Gallery (PDP)** | Main image `.glass` frame 1:1 (4:5 on mobile), thumbnails row below (scroll-snap), click swaps main image (JS), keyboard accessible. Without JS: first image only. |
| **Quantity stepper** | Pill with `−` / number input / `+`, 44px height, glass-strong. |
| **Mini-cart drawer** | Right-side panel width `min(420px, 100vw)`, `.glass--strong`, slides in (`translateX`), dimmed overlay `rgba(8,12,30,.45)`, focus trap, `Esc` closes, scroll-locked body. Lines: thumb, name, qty stepper, line total, remove. Footer: subtotal, free-shipping progress bar (gradient fill), buttons "View cart" (glass) + "Checkout" (primary). Empty state with illustration + "Start shopping". |
| **Cart page** | Desktop: two columns — lines list (`.glass--strong`) + sticky summary card. Mobile: stacked cards. Free-shipping progress bar at top of summary. |
| **Checkout** | Two columns: left form sections as numbered glass cards (Contact, Shipping, Payment, Notes), right sticky order summary. Payment methods as selectable radio cards (icon + title + description). Primary CTA full width: "Place order · $XX". Inline validation messages. |
| **Order success** | Centered glass card: animated check (SVG stroke draw), order number (copy-able), summary, bank instructions (if bank transfer), CTAs (Continue shopping / View my orders). |
| **Badges / chips** | Pill, 12px/600, solid or gradient fills: `new` cyan, `sale` pink gradient, `out` slate, status badges (pending=warning, confirmed=info, shipped=brand, delivered=success, cancelled=danger — soft tinted backgrounds with colored text, AA contrast). |
| **Toasts** | Top-right (bottom-center on mobile), `.glass--strong`, icon + text, auto-dismiss 4s, `role="status"`, slide+fade. Used for "Added to cart" etc. |
| **Skeletons** | Shimmer gradient for mini-cart loading only (keep minimal). |
| **Pagination** | Glass pill group, prev/next + numbered pages (max 7 shown with ellipsis), current page = primary gradient. |
| **Breadcrumbs** | Small, `--text-3`, chevron separators, last item `--text-1`. |
| **Tables** | Admin/orders: `.glass--strong` wrapper, sticky header row, zebra via `--glass-subtle`, row hover tint, responsive: horizontal scroll wrapper on mobile (cards for orders list < 640px optional). |
| **Footer** | `.glass` panel with 4 columns (brand blurb, shop links, company links, newsletter-style contact info), bottom bar with © and payment-method text chips. |
| **Empty states / 404 / 500** | Centered glass card, large gradient numeral/icon, short friendly copy, primary CTA home. |
| **Admin shell** | Left sidebar `.glass--strong` (240px, collapsible to icons < 1024, off-canvas on mobile), top bar with page title + user menu. Content cards use `.glass--strong` (higher opacity than storefront for data density). Stat cards: icon in gradient circle, big number, label, subtle delta text. Forms: two-column grid, sticky action bar at bottom (Save / Cancel). Image uploader: dashed glass dropzone, thumbnail grid with "Primary" star and remove (×). |

---

## 8. Page Wireframes (desktop)

```
HOME
┌ Announcement ───────────────────────────────────────────┐
│ [Header glass: logo  nav  ───────  🔍 ☾ 👤 🛒(2)]        │
│ HERO  ┌ H1 gradient ┐        ╔═ floating glass cards ═╗  │
│       │ sub · CTAs  │        ║  featured mini-card    ║  │
│       │ trust row   │        ╚════════════════════════╝  │
│ Categories  [tile][tile][tile][tile]                      │
│ Featured    [card][card][card][card]                      │
│ Value props [free ship][secure][easy returns]             │
│ New arrivals [card][card][card][card]                     │
│ Footer glass                                              │
└─────────────────────────────────────────────────────────┘

SHOP                               PRODUCT
┌ breadcrumbs + title + sort ─┐    ┌ breadcrumbs ───────────────┐
│ ┌Filters┐ ┌ grid 3–4 cols ┐ │    │ ┌ gallery ┐  Name           │
│ │glass  │ │ cards …       │ │    │ │ main    │  ★ price compare│
│ │sticky │ │ pagination    │ │    │ │ thumbs  │  stock · qty    │
│ └───────┘ └───────────────┘ │    │ └─────────┘  [Add to cart]  │
└─────────────────────────────┘    │ Description · Related grid  │
                                   └─────────────────────────────┘
CART / CHECKOUT
┌ lines/forms (glass strong) ─────────┐ ┌ sticky summary glass ┐
└─────────────────────────────────────┘ └──────────────────────┘
```

---

## 9. Motion

| Interaction | Spec |
|---|---|
| Hover lift (cards, buttons) | `transform: translateY(-4px…-6px)`, `var(--t-base) var(--ease)` |
| Press | `scale(.98)`, `var(--t-fast)` |
| Drawer / sheet | `translateX/Y` + overlay fade, `var(--t-slow) var(--ease)` |
| Add to cart | Button label → "Added ✓" (1.5 s), cart badge "bump" (scale 1 → 1.25 → 1), toast |
| Page entrance | Sections fade-up (`opacity` + `translateY(12px)`) via CSS only, **max 6 animated elements per view**, staggered 60 ms |
| Hero floats | 6 s ease-in-out infinite alternate, transform only |

Only animate `transform` and `opacity`. All animation off under `prefers-reduced-motion: reduce`.

---

## 10. Accessibility & Fallbacks (non-negotiable)

- Contrast ≥ 4.5:1 for text, ≥ 3:1 for UI borders/icons — verify with the tokens above (do not lighten `--text-3`).
- Visible focus: `:focus-visible { outline: 3px solid var(--focus); outline-offset: 2px; }` (also in dark mode).
- Skip link ("Skip to content") as first focusable element.
- Landmarks: `header`, `nav[aria-label]`, `main#main`, `footer`; one `h1` per page.
- Drawers/menus: `aria-expanded`, `aria-controls`, focus trap, `Esc`, return focus to trigger.
- Every `<img>` has `alt` (decorative → `alt=""`); icons inside buttons have `aria-label` on the button.
- Forms: `<label for>`, error text linked with `aria-describedby`, `autocomplete` attributes.
- Honor `prefers-reduced-motion`, `prefers-reduced-transparency`, and `@supports` fallback for backdrop-filter.
- Never rely on color alone for status — pair with text/icon.

---

## 11. Iconography, Imagery, Voice

- Icons: inline SVG, 24×24 viewBox, `stroke="currentColor"`, `stroke-width: 1.75`, round caps/joins (Lucide-style). Provide via `icon('name')` in `views/partials/icons.php` (cart, search, user, sun, moon, menu, x, trash, plus, minus, check, truck, shield, refresh, star, chevron-right/down, package, settings, home, tag, layers, alert, image, upload, logout).
- No external image dependencies. Missing product image → `placeholder.svg` (gradient + product initial rendered via CSS/SVG).
- Voice: friendly, concise, active. Buttons are verbs ("Add to cart", "Place order"). Errors explain the fix ("Enter a valid email address").

---

## 12. Code-Review Checklist (agent self-check at the end of Phase 3, 4, 5, 6)

- [ ] Only token variables used (no hard-coded hex outside `tokens.css`).
- [ ] No nested `backdrop-filter`; text-heavy areas use `.glass--strong`.
- [ ] Light **and** dark themes both defined for every new component.
- [ ] Reduced-motion / reduced-transparency / no-backdrop-filter fallbacks present.
- [ ] No inline `style`, `<script>` bodies or `on*` attributes (CSP).
- [ ] Mobile layout defined for every component (≥ 320 px).
- [ ] Focus states, labels, alt text, aria attributes present.
