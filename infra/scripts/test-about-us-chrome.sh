#!/usr/bin/env bash
# Verify About Us CMS page images load (or show placeholder) using chrome-devtools.
set -euo pipefail

CDT="npx -y -p chrome-devtools-mcp@latest chrome-devtools"
BASE="${BASE:-https://stg.tyresonline.sa}"
LOCALE="${1:-en}"
PAGE_URL="${BASE}/${LOCALE}/about-us"

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

echo "=== chrome-devtools test: about-us page (${LOCALE}) ==="

run_cdt new_page "$PAGE_URL" --timeout 120000 >/dev/null
sleep 6

RESULT=$(run_cdt evaluate_script "async () => {
  const content = document.querySelector('.cms-page, .column.main, main, body');
  const root = content || document.body;
  const imgs = Array.from(root.querySelectorAll('img')).filter((img) => {
    const attrs = [
      img.getAttribute('src') || '',
      img.getAttribute('data-src') || '',
      img.currentSrc || '',
    ].join(' ');
    return /media|wysiwyg|about-us|whatsapp/i.test(attrs);
  });

  const samples = imgs.slice(0, 10).map((img) => ({
    alt: img.getAttribute('alt') || '',
    src: img.currentSrc || img.src || '',
    dataSrc: img.getAttribute('data-src') || '',
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    complete: img.complete,
  }));

  const checked = await Promise.all(samples.map(async (item) => {
    const url = item.dataSrc || item.src;
    if (!url || url.includes('blank.png')) {
      return { ...item, httpOk: true, contentType: 'skipped' };
    }
    try {
      const resp = await fetch(url, { method: 'HEAD', credentials: 'omit', redirect: 'follow' });
      return {
        ...item,
        httpOk: resp.ok,
        contentType: resp.headers.get('content-type') || '',
      };
    } catch (e) {
      return { ...item, httpOk: false, contentType: '', error: String(e) };
    }
  }));

  const broken = checked.filter((item) => {
    const url = item.dataSrc || item.src;
    if (!url || url.includes('blank.png')) return false;
    if (url.includes('content-placeholder')) return false;
    if (url.includes('tyresonline.ae')) return true;
    return !item.httpOk || (item.contentType && item.contentType.includes('text/html'));
  });

  return {
    ok: broken.length === 0,
    pageUrl: window.location.href,
    imageCount: imgs.length,
    brokenCount: broken.length,
    samples: checked,
  };
}")

echo "$RESULT" | python3 -m json.tool 2>/dev/null || echo "$RESULT"

if echo "$RESULT" | grep -Eq '"ok"[[:space:]]*:[[:space:]]*true'; then
  echo "PASS"
  exit 0
fi

echo "FAIL"
exit 1
