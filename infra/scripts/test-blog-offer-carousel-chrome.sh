#!/usr/bin/env bash
# Verify blog offer carousel images load (or show placeholder) using chrome-devtools.
set -euo pipefail

CDT="npx -y -p chrome-devtools-mcp@latest chrome-devtools"
BASE="${BASE:-https://stg.tyresonline.sa}"
LOCALE="${1:-en}"
BLOG_URL="${BASE}/${LOCALE}/blog/"

cleanup() {
  $CDT stop >/dev/null 2>&1 || true
}
trap cleanup EXIT

$CDT stop >/dev/null 2>&1 || true
$CDT start --headless true --chromeArg='--no-sandbox' --chromeArg='--disable-dev-shm-usage' --usageStatistics false >/dev/null
sleep 2

run_cdt() {
  # shellcheck disable=SC2086
  $CDT "$@" --output-format=json
}

echo "=== chrome-devtools test: blog offer carousel (${LOCALE}) ==="

run_cdt new_page "$BLOG_URL" --timeout 120000 >/dev/null
sleep 6

RESULT=$(run_cdt evaluate_script "async () => {
  const carousel = document.querySelector('.offer-slider');
  if (!carousel) {
    return { ok: false, reason: 'offer-slider not found' };
  }

  const imgs = Array.from(carousel.querySelectorAll('img'));
  const samples = imgs.slice(0, 5).map((img) => ({
    src: img.currentSrc || img.src || '',
    dataSrc: img.getAttribute('data-src') || '',
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    complete: img.complete,
  }));

  const loaded = await Promise.all(samples.map(async (item) => {
    const url = item.dataSrc || item.src;
    if (!url) return { ...item, httpOk: false, contentType: '' };
    try {
      const resp = await fetch(url, { method: 'HEAD', credentials: 'omit' });
      return {
        ...item,
        httpOk: resp.ok,
        contentType: resp.headers.get('content-type') || '',
      };
    } catch (e) {
      return { ...item, httpOk: false, contentType: '', error: String(e) };
    }
  }));

  const broken = loaded.filter((item) => {
    const url = item.dataSrc || item.src;
    if (!url || url.includes('blank.png')) return false;
    if (url.includes('content-placeholder')) return false;
    return !item.httpOk || (item.contentType && item.contentType.includes('text/html'));
  });

  return {
    ok: broken.length === 0,
    carouselFound: true,
    imageCount: imgs.length,
    brokenCount: broken.length,
    samples: loaded,
  };
}")

echo "$RESULT" | python3 -m json.tool 2>/dev/null || echo "$RESULT"

if echo "$RESULT" | grep -Eq '"ok"[[:space:]]*:[[:space:]]*true'; then
  echo "PASS"
  exit 0
fi

echo "FAIL"
exit 1
