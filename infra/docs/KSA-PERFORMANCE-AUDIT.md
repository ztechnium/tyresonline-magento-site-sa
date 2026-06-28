# KSA Staging Performance Audit

**Date:** 2026-06-27  
**Site:** https://stg.tyresonline.sa  
**Script:** `infra/scripts/audit-performance-ksa.sh`

---

## Executive summary

The site **feels slow for two main reasons**:

1. **Full Page Cache (FPC) is not serving cached HTML** — every visit runs full Magento (~220ms server TTFB + 850KB–1MB HTML generated fresh).
2. **Heavy pages + distance** — PLP ships **853KB–1MB HTML** with **200+ images**; origin EC2 is in **eu-north-1 (Stockholm)**, far from KSA users. CDN helps CSS/images, but **HTML always comes from EU**.

Server CPU/RAM are healthy (load ~0.4, 12GB free). The bottleneck is **architecture + page weight**, not an overloaded box.

---

## Measurements

### Time to first byte (TTFB)

| Page | From EC2 (same region) | From external (user PC) |
|------|------------------------|---------------------------|
| Homepage | ~200ms | ~1.0s |
| PLP EN | ~220ms | **0.5–6.9s** (high variance) |
| PLP AR | ~220ms | ~0.5–1.1s |
| Cart | ~470ms | — |
| Store locator | ~200ms (warm) | — |

### HTML payload size

| Page | Size | Images in HTML | CDN refs |
|------|------|----------------|----------|
| Homepage | 249 KB | 34 | 95 |
| PLP EN | **853 KB** | **212** | 262 |
| PLP AR | **1.0 MB** | **412** | 498 |

### Static assets

| Asset | Size | CDN cache |
|-------|------|-----------|
| Merged CSS | **893 KB** | Hit after warm (~45ms TTFB) |
| Product image (sample) | 14 KB | Hit (~40ms) |
| KSA map PNG | 232 KB | Hit after warm |

---

## Root causes (ranked by impact)

### 1. FPC stores but never serves — **Critical**

```
Cache-Control: max-age=0, must-revalidate, no-cache, no-store
Set-Cookie: PHPSESSID=... (new session every request)
```

- FPC enabled in config; Redis db=4 has **~2,200 keys**
- Repeat requests same TTFB (~220ms) — **no cache hit**
- No `X-Magento-Cache-Debug: HIT` header
- **Effect:** Every catalog/home request bootstraps PHP, layout, blocks, DB queries

**Likely causes:** Session started on all pages; uncacheable block in layout; plugin forcing private/no-store headers (possibly from earlier form_key/FPC fix).

### 2. EC2 in EU, users in KSA — **High**

- Instance: `eu-north-1` (Stockholm)
- KSA → EU RTT typically **120–200ms+** per round trip
- HTML is **not on CloudFront** — only `/static/` and `/media/`
- **Effect:** Adds ~0.5–1s+ to perceived load even when server is “fast”

### 3. Oversized PLP HTML — **High**

- **853KB–1MB** single HTML document
- **212–412 `<img>` tags** in one response (lazyload helps after paint, but HTML download + parse still hurts)
- **57 inline `<script>` blocks**
- **Effect:** Slow first load, especially on mobile/4G

### 4. Heavy CSS bundle — **Medium**

- One merged CSS file: **893 KB**
- CDN caching works once warm; cold first visit still downloads ~900KB CSS

### 5. Third-party scripts — **Medium**

Detected on PLP:

| Service | Impact |
|---------|--------|
| Google Tag Manager | 5 references — blocks main thread |
| Freshworks chat (`uae.fw-cdn.com`) | Extra JS + network |
| Elfsight (reviews) | iframe/widget |
| WhatsApp | Multiple links |
| Metricool, Avada, Facebook | Tracking |

### 6. Magento optimisations disabled — **Medium**

| Setting | Current | Recommendation |
|---------|---------|----------------|
| `dev/js/enable_js_bundling` | **0** | Enable or use bundling tool |
| `dev/template/minify_html` | **0** | Enable |
| Mgt Varnish | Module on, **`isEnabled: no`** | Enable edge cache OR fix built-in FPC |
| Mgt cache warmer | **disabled** | Enable after FPC fix |

### 7. Minor

- Indexers `inventory`, `customer_grid`: **Reindex required** (unlikely main cause of slowness)
- `dev/grid/async_indexing`: 0

---

## What is working well

- Production mode enabled
- Redis session/cache connectivity OK
- CDN + CloudFront for static/media (Hit from cloudfront on warm requests)
- Server-side PHP TTFB ~200ms is acceptable **if FPC were serving**
- EC2 resources: load 0.48, 12GB RAM available

---

## Recommended fixes (priority order)

### Phase 1 — Quick wins (1–2 days)

| # | Action | Expected gain |
|---|--------|---------------|
| 1 | **Fix FPC serve** — diagnose why pages get `no-store` despite Redis keys; ensure catalog/home layouts are `cacheable="true"`; avoid starting PHP session on anonymous catalog pages | TTFB **220ms → &lt;50ms** on repeat views |
| 2 | **Enable HTML minification** — Admin → Developer → `Minify HTML` | 10–20% smaller HTML |
| 3 | **Defer third-party scripts** — GTM, chat, Elfsight after `load` or user interaction | Faster First Contentful Paint |
| 4 | **PLP pagination** — reduce products per page (e.g. 24 instead of 100+) | Smaller HTML, fewer img tags |

### Phase 2 — Infrastructure (before go-live)

| # | Action | Expected gain |
|---|--------|---------------|
| 5 | **Move prod EC2 to `me-south-1` (Bahrain)** or **CloudFront cache HTML** for anonymous pages | **−100–300ms** RTT for KSA users |
| 6 | **Enable Mgt Varnish** or **Varnish/CloudFront full-page cache** at edge | Edge TTFB &lt;100ms globally |
| 7 | **Enable cache warmer** for home, top PLPs, top PDPs | Warm FPC after deploy |

### Phase 3 — Deeper optimisation

| # | Action |
|---|--------|
| 8 | Enable JS bundling / reduce RequireJS waterfall |
| 9 | WebP product images + smaller PLP thumbnails |
| 10 | Audit Amasty PageSpeed settings (lazy load, defer JS) |
| 11 | Replace UAE Freshworks widget with KSA instance (remove cross-region call) |

---

## How to re-run audit

```bash
# On EC2
bash /tmp/audit-performance-ksa.sh 5

# FPC warm test
bash /tmp/audit-fpc-warm.sh

# From local machine
curl -w "ttfb=%{time_starttransfer}s total=%{time_total}s size=%{size_download}\n" -o NUL -sS https://stg.tyresonline.sa/all-tyres/car-tyres.html
```

---

## Verdict

| Layer | Status |
|-------|--------|
| Server hardware | OK |
| PHP/Magento backend speed | OK (~220ms uncached) |
| Full Page Cache | **Broken / not serving** |
| CDN (static/media) | OK |
| Page weight | **Too heavy** |
| Geography | **Wrong region for KSA** |
| Third-party JS | **Too many, too early** |

**Your slowness feeling is valid.** The biggest single fix is **making FPC actually serve cached pages**, followed by **reducing PLP weight** and **moving origin closer to KSA** for production.
