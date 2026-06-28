# KSA Staging Test Cases — TyresOnline.sa

**Environment:** `https://stg.tyresonline.sa`  
**CDN:** `https://cdn.tyresonline.sa`  
**Stores:** English (`/en/` or default), Arabic (`/ar/`)  
**Last updated:** 2026-06-27

---

## How to use this document

| Column | Meaning |
|--------|---------|
| **ID** | Unique test case reference |
| **Type** | `UI` = layout/visual/translation · `FUNC` = behaviour/data flow |
| **Lang** | `EN` · `AR` · `Both` |
| **Priority** | `P0` blocker · `P1` critical · `P2` important · `P3` nice-to-have |
| **Auto** | Script that partially automates the case (if any) |

Run **P0/P1** on both languages before every release. Run full suite after infrastructure or theme changes.

---

## 1. Global & cross-cutting

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| G-01 | Site loads over HTTPS | FUNC | Both | P0 | Open homepage EN and AR | HTTP 200, valid SSL, no mixed-content warnings in browser console |
| G-02 | Language switcher EN → AR | FUNC | Both | P0 | From `/en/`, click language switcher (flag) | Redirects to equivalent AR URL; content in Arabic; `html` has `dir="rtl"` |
| G-03 | Language switcher AR → EN | FUNC | Both | P0 | From `/ar/`, switch to English | Redirects to EN URL; LTR layout; English copy |
| G-04 | Language switcher preserves page context | FUNC | Both | P1 | On PLP `/en/all-tyres/car-tyres.html`, switch to AR | Lands on `/ar/all-tyres/car-tyres.html` (or equivalent), not homepage |
| G-05 | Language flag images load | UI | Both | P1 | Inspect switcher flag `<img>` | Flag SVG loads (200); no broken image icon |
| G-06 | Cookie / session persistence | FUNC | Both | P1 | Add item to cart EN, switch to AR | Cart count preserved or clearly explained; no fatal errors |
| G-07 | Mobile responsive header | UI | Both | P1 | Resize to 375px width | Hamburger menu, cart icon, language switcher visible and usable |
| G-08 | Desktop header navigation | UI | Both | P1 | View at 1280px+ | Logo, main nav, search/tyre finder, cart, language switcher aligned |
| G-09 | Footer links load | UI | Both | P1 | Click footer sections (Why Tyres Online, Branded Tyres, Customer Support) | Accordions expand; links return 200 |
| G-10 | Google Reviews panel | UI | Both | P2 | Open reviews widget/panel in footer | iframe loads `/google-reviews.html`; Elfsight widget visible |
| G-11 | Chat widget lazy-load | FUNC | Both | P2 | Scroll or wait on page | Freshworks script loads once; chat bubble appears without blocking page |
| G-12 | No PHP fatal on key pages | FUNC | Both | P0 | Load home, PLP, PDP, cart, checkout | No white screen, no `Fatal error` / `CredisException` in HTML |
| G-13 | CSP allows KSA/CDN assets | FUNC | Both | P0 | Open DevTools → Console on PLP | No `blocked:csp` for `*.tyresonline.sa`, `cdn.tyresonline.sa`, fonts, images |
| G-14 | FPC does not break form_key | FUNC | Both | P0 | Hard refresh PLP → add to cart from browser | No “Invalid Form Key”; product added successfully |

**Auto:** `infra/scripts/benchmark-ksa-staging.sh`, `infra/scripts/validate-cdn-csp.sh`

---

## 2. Homepage — interface (UI)

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| H-EN-01 | Main banner CMS block | UI | EN | P0 | Open `/en/` | Hero banner renders; images load |
| H-AR-01 | Main banner CMS block | UI | AR | P0 | Open `/ar/` | Hero banner renders; Arabic text where translated |
| H-EN-02 | “Shop at TyresOnline” section | UI | EN | P1 | Scroll to 3-column boxes | Headings: Shop Tyres, Tyre Fitment, Buy Tyres; icons visible |
| H-AR-02 | “Shop at TyresOnline” section | UI | AR | P1 | Same section on AR home | Arabic translations; RTL text alignment |
| H-EN-03 | KSA map image | UI | EN | P0 | Scroll to “Tyres Anywhere in the KSA” | `ksa-map.png` loads (lazyload swaps from blank); red linear KSA map visible |
| H-AR-03 | KSA map image | UI | AR | P0 | Same on AR home | Map image loads via CDN/origin; not broken |
| H-EN-04 | Fitment centres counter | UI | EN | P2 | Below map | “50+ TYRE FITMENT CENTERS” visible |
| H-AR-04 | Fitment centres counter | UI | AR | P2 | Same on AR | Translated label; numerals readable in RTL |
| H-EN-05 | Welcome banner section | UI | EN | P1 | Scroll to welcome / tyre shop near me | Background image loads (webp/jpg fallback); shapes render |
| H-AR-05 | Welcome banner section | UI | AR | P1 | AR welcome section | Images load; city names (Riyadh, Jeddah, Dammam) translated |
| H-EN-06 | Car brands grid | UI | EN | P1 | Scroll to vehicle brands | Brand sprites visible; “ALL CAR BRANDS” CTA works |
| H-AR-06 | Car brands grid | UI | AR | P1 | AR brands section | Sprites aligned in RTL grid; link to `/ar/car` works |
| H-EN-07 | Blog slider CMS block | UI | EN | P2 | Bottom of homepage | `home_blog_slider` renders; thumbnails load |
| H-AR-07 | Blog slider CMS block | UI | AR | P2 | AR homepage blog block | Posts show Arabic titles where available |

**Asset URL to verify:** `https://cdn.tyresonline.sa/media/images/icon/ksa-map.png` → HTTP 200, `image/png`

---

## 3. Homepage — functionality (FUNC)

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| HF-01 | Shop Tyres opens tyre finder | FUNC | EN | P1 | Click “Shop Tyres” box | Tyre search / finder UI opens (modal or scroll) |
| HF-02 | Tyre Fitment link | FUNC | Both | P1 | Click “Tyre Fitment” | Navigates to store locator |
| HF-03 | Buy Tyres link | FUNC | Both | P1 | Click “Buy Tyres” | Navigates to car tyres PLP |
| HF-04 | Brand link (e.g. Toyota) | FUNC | Both | P2 | Click Toyota sprite | Opens `/car/toyota` (or `/ar/car/toyota`) |
| HF-05 | Lazyload images | FUNC | Both | P1 | Network tab: filter images | Below-fold images use `data-src`; load on scroll |

---

## 4. Catalog — PLP & PDP

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| C-EN-01 | Car tyres PLP loads | UI | EN | P0 | Open `/en/all-tyres/car-tyres.html` | Product grid; filters/sidebar; prices in SAR |
| C-AR-01 | Car tyres PLP loads | UI | AR | P0 | Open `/ar/all-tyres/car-tyres.html` | RTL layout; Arabic labels; products visible |
| C-EN-02 | Price slider / filters UI | UI | EN | P1 | Use price filter on PLP | Slider track visible (not `d-none` hidden); filter applies |
| C-AR-02 | Price slider / filters UI | UI | AR | P1 | Same on AR PLP | Slider works in RTL; labels aligned |
| C-EN-03 | Product image CDN | UI | EN | P0 | Inspect product image `src` | Points to `cdn.tyresonline.sa` or valid media URL; 200 |
| C-AR-03 | Product image CDN | UI | AR | P0 | AR PLP product image | Same; no broken images |
| C-EN-04 | Add to cart from PLP (Ajax) | FUNC | EN | P0 | Select qty 4 → Add to cart | Success message; mini-cart updates; no empty cart |
| C-AR-04 | Add to cart from PLP (Ajax) | FUNC | AR | P0 | Same on AR PLP | Same behaviour; Arabic success message |
| C-EN-05 | PDP loads | UI | EN | P0 | Open any in-stock PDP | Gallery, price, qty, add to cart, specs |
| C-AR-05 | PDP loads | UI | AR | P0 | Open same PDP in AR | RTL PDP; translated labels |
| C-EN-06 | PDP add to cart | FUNC | EN | P0 | Add qty 4 from PDP | Redirect or confirmation; cart has line item |
| C-AR-06 | PDP add to cart | FUNC | AR | P0 | Same in AR | Item in cart with correct SKU/qty |
| C-EN-07 | Out of stock handling | FUNC | Both | P2 | Open OOS product if available | Add disabled or clear OOS message |
| C-EN-08 | Tyre finder search | FUNC | Both | P1 | Search by size (e.g. 205/55 R16) | Results on PLP or no-results message |

**Auto:** `infra/scripts/test-add-to-cart-internal.php`, `infra/scripts/test-uae-car-tyres-pages.php` (adapt URLs for KSA)

---

## 5. Cart & fitment selection

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| CT-01 | Cart page loads with items | UI | Both | P0 | Add product → open `/checkout/cart/` | Line items, subtotal, qty controls visible |
| CT-02 | Cart empty state | UI | Both | P2 | Open cart with no items | Empty cart message; CTA to shop |
| CT-03 | Update quantity | FUNC | Both | P1 | Change qty in cart → Update | Totals recalculate |
| CT-04 | Remove item | FUNC | Both | P1 | Remove line item | Item removed; cart empty or updated |
| CT-05 | Fitment / installer section visible | UI | Both | P0 | Cart with tyres | Installer / pickup / mail-order options shown |
| CT-06 | Select pickup store | FUNC | Both | P0 | Choose store, date, time → save | AJAX success; store name/address shown on cart |
| CT-07 | Mail-order / no fitting option | FUNC | Both | P1 | Select mail-order (`setfitment` fitment_installer=0) | Pickup fields cleared or mail-order path set |
| CT-08 | Proceed to checkout blocked without fitment | FUNC | Both | P1 | Skip fitment → Proceed | Validation message or redirect back to cart |
| CT-09 | Proceed to checkout with fitment | FUNC | Both | P0 | Complete fitment → Proceed | Checkout page loads (not empty white page) |
| CT-10 | Cart shipping block | UI | Both | P2 | Inspect cart page | No `Invalid block type: Cart\Shipping` errors in logs/HTML |

**Auto:** `infra/scripts/test-full-checkout-cycle.php` (steps 1–3), `infra/scripts/test-full-checkout-cycle.sh`

---

## 6. Checkout — guest purchase flow

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| CH-EN-01 | Checkout page renders | UI | EN | P0 | Cart with fitment → `/en/checkout/` | One-step checkout UI; `window.checkoutConfig` present |
| CH-AR-01 | Checkout page renders | UI | AR | P0 | Same flow in AR | RTL checkout; Arabic field labels |
| CH-EN-02 | Guest email field | FUNC | EN | P0 | Enter email | Accepted; no JS errors |
| CH-AR-02 | Guest email field | FUNC | AR | P0 | Arabic checkout email | Same |
| CH-EN-03 | Shipping address — KSA fields | FUNC | EN | P0 | Fill: Riyadh, SA, +966 phone | Region/city dropdowns show KSA data; validation passes |
| CH-AR-03 | Shipping address — KSA fields | FUNC | AR | P0 | Same in Arabic | Arabic city/area labels; phone +966 |
| CH-EN-04 | Shipping method store pickup | FUNC | EN | P0 | Select shipping | `storepickup` available; price shown (e.g. 15 SAR) |
| CH-AR-04 | Shipping method store pickup | FUNC | AR | P0 | AR checkout shipping | Same methods available |
| CH-EN-05 | Payment — Cash on Delivery | FUNC | EN | P0 | Select COD → Place order | Order confirmation; increment ID (e.g. `TO-…`) |
| CH-AR-05 | Payment — Cash on Delivery | FUNC | AR | P0 | COD in AR | Order placed; confirmation in Arabic |
| CH-EN-06 | Payment — HyperPay methods listed | UI | EN | P1 | View payment methods | Mada / Visa / etc. visible if enabled |
| CH-EN-07 | HyperPay live payment | FUNC | EN | P2 | Pay with test/live card | *Blocked until KSA HyperPay credentials configured* |
| CH-EN-08 | Order summary totals | UI | Both | P1 | Review totals on checkout | Subtotal + shipping + VAT = grand total in SAR |
| CH-EN-09 | Pickup data on order | FUNC | Both | P1 | After order, check Admin | `pickup_store`, `pickup_date`, `pickup_time` saved |
| CH-EN-10 | Registered customer checkout | FUNC | Both | P2 | Login → checkout | Saved addresses; place order |

**Auto:** `infra/scripts/test-full-checkout-cycle.php` (full flow, COD)

**Reference order (staging):** `TO-2706586309` — use as template for Admin verification.

---

## 7. Store locator & fitment centres

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| SL-01 | Store locator page loads | UI | Both | P1 | Open `/en/storelocator` and `/ar/storelocator` | Map/list of KSA centres |
| SL-02 | Store markers / list | UI | Both | P1 | Interact with map | Pins or list entries for KSA locations |
| SL-03 | Store detail | FUNC | Both | P2 | Click a store | Name, address, hours/service info |
| SL-04 | Link from homepage fitment box | FUNC | Both | P1 | Home → Tyre Fitment | Lands on store locator |

---

## 8. Static pages & content

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| SP-01 | Google Reviews standalone page | UI | Both | P2 | Open `/google-reviews.html` | Elfsight widget KSA ID loads |
| SP-02 | CMS pages (About, Contact, etc.) | UI | Both | P2 | Footer CMS links | 200; translated on AR |
| SP-03 | 404 page | UI | Both | P3 | Open invalid URL | Branded 404, not Apache default |

---

## 9. Assets, CDN & performance

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| A-01 | CDN CSS loads | FUNC | Both | P0 | PLP → inspect merged CSS | 200 from CDN or origin; styles applied |
| A-02 | CDN product image | FUNC | Both | P0 | Sample product image URL on CDN | HTTP 200; correct image |
| A-03 | KSA map on CDN | FUNC | Both | P0 | `cdn.tyresonline.sa/media/images/icon/ksa-map.png` | HTTP 200 |
| A-04 | Static version cache bust | FUNC | Both | P2 | Redeploy static → reload | New `version*` in static URLs |
| A-05 | Homepage TTFB | FUNC | Both | P2 | Run benchmark script | Homepage & PLP &lt; 3s external TTFB (staging target) |
| A-06 | CloudFront cache hit | FUNC | Both | P2 | Repeat CDN asset request | `X-Cache: Hit from cloudfront` on warm request |

**Auto:** `infra/scripts/benchmark-ksa-staging.sh`, `infra/scripts/validate-cdn-csp.sh`

---

## 10. Arabic-specific (RTL & i18n)

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| AR-01 | Document direction | UI | AR | P0 | View source on AR page | `<html lang="ar" dir="rtl">` (or equivalent) |
| AR-02 | Tajawal font applied | UI | AR | P1 | Computed styles on body | `font-family` includes Tajawal |
| AR-03 | Rubik font on EN only | UI | EN | P2 | EN body font | Rubik, not Tajawal-only |
| AR-04 | RTL header alignment | UI | AR | P1 | Header layout | Logo/nav mirrored correctly; no overlap |
| AR-05 | RTL checkout form | UI | AR | P0 | Checkout fields | Labels right-aligned; inputs usable |
| AR-06 | Arabic translations complete | UI | AR | P1 | Walk home → PLP → cart → checkout | No raw English UI strings on critical path |
| AR-07 | Numbers and currency | UI | AR | P2 | View prices | SAR amount readable (Western or Arabic numerals per locale) |
| AR-08 | AR footer CTA | UI | AR | P2 | Footer call-to-action | RTL styling; link works |

**Auto:** `infra/scripts/test-ar-translation.php`

---

## 11. English-specific

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| EN-01 | Default store locale | UI | EN | P1 | Open `/` or `/en/` | English copy; LTR |
| EN-02 | KSA copy (not UAE) | UI | EN | P0 | Homepage hero / map section | References **KSA**, not UAE/AED |
| EN-03 | Phone/country KSA | UI | EN | P1 | Checkout phone placeholder | +966 format hint |

---

## 12. Regression — known staging issues

| ID | Title | Type | Lang | P | Steps | Expected result |
|----|-------|------|------|---|-------|-----------------|
| R-01 | Add to cart PHP 8.3 fix | FUNC | Both | P0 | Add product via browser | No 500; cart saves (Installer helper properties) |
| R-02 | FPC stale form_key on PDP | FUNC | Both | P0 | Add from PDP after hard refresh | No “Invalid Form Key” |
| R-03 | Checkout empty page | UI | Both | P0 | Full flow to checkout | KO checkout renders; not blank `#checkout` |
| R-04 | MSI qty vs web add | FUNC | Both | P1 | Add qty 4 from PLP | If fails, verify salable qty in Admin ≥ 4 |
| R-05 | Indexers healthy | FUNC | Both | P2 | Admin → Indexers | `inventory`, `customer_grid` not “Reindex required” |

---

## 13. End-to-end scenarios (full journeys)

These combine multiple cases above into user stories. Execute **once per language** before go-live.

### E2E-EN-01 — Guest tyre purchase (English)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open `https://stg.tyresonline.sa/en/` | Homepage OK; KSA map visible |
| 2 | Navigate to Car Tyres PLP | Products listed |
| 3 | Add 4 tyres to cart | Success toast; cart count = 4 |
| 4 | Open cart | Item visible with SAR price |
| 5 | Select pickup store + date + time | Installer saved |
| 6 | Proceed to checkout | Checkout UI loads |
| 7 | Enter guest details (Riyadh, +966…) | Validation OK |
| 8 | Select store pickup + COD | Methods selected |
| 9 | Place order | Confirmation page; order in Admin |

### E2E-AR-01 — Guest tyre purchase (Arabic)

Same as E2E-EN-01 but start at `https://stg.tyresonline.sa/ar/` and verify RTL + Arabic strings at each step.

### E2E-EN-02 — Browse only (no purchase)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Home → Tyre finder → PLP → PDP | No errors |
| 2 | Switch EN → AR on PDP | Equivalent AR PDP |
| 3 | Store locator from header/footer | Map loads |

### E2E-EN-03 — CDN & media smoke

| Step | Action | Expected |
|------|--------|----------|
| 1 | Home, PLP, PDP | All CSS/images 200 |
| 2 | DevTools console | No CSP blocks |
| 3 | Verify ksa-map.png | Image visible on home |

---

## 14. Test execution checklist

```
Release smoke (both languages):
[ ] G-01 G-02 G-03 G-13 G-14
[ ] H-EN-03 H-AR-03 (KSA map)
[ ] C-EN-04 C-AR-04 (add to cart)
[ ] CT-06 CT-09 (fitment + checkout entry)
[ ] CH-EN-01 CH-AR-01 CH-EN-05 CH-AR-05 (checkout + COD)
[ ] E2E-EN-01 E2E-AR-01

Post-deploy CDN/media:
[ ] A-01 A-02 A-03
[ ] R-01 R-02 R-03
```

---

## 15. Automation mapping

| Script | Covers |
|--------|--------|
| `infra/scripts/test-full-checkout-cycle.php` | CT-05–09, CH-EN-03–05, CH-EN-09, E2E backend |
| `infra/scripts/test-full-checkout-cycle.sh` | HTTP session add-to-cart (R-02, C-EN-04 partial) |
| `infra/scripts/test-add-to-cart-internal.php` | C-EN-04, R-01 |
| `infra/scripts/benchmark-ksa-staging.sh` | A-05 |
| `infra/scripts/validate-cdn-csp.sh` | G-13, A-01–A-03 |
| `infra/scripts/test-ar-translation.php` | AR-06 (partial) |

Browser MCP / manual testing required for: language switcher, RTL layout, HyperPay UI, chat widget, and visual regression.

---

## 16. Out of scope / blocked on staging

- HyperPay KSA **live** card payments (CH-EN-07) — pending credentials
- Freshworks **KSA** widget ID — may still show UAE account
- Production DNS (`www.tyresonline.sa`) — not on AWS yet
- Email delivery verification — optional P2 if SMTP configured
