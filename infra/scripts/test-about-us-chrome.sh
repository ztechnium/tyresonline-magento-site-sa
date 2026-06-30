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
  const hero = document.querySelector('img[alt*=\"About TyresOnline\"], img.lazyload');
  const heroInfo = hero ? {
    alt: hero.getAttribute('alt') || '',
    src: hero.currentSrc || hero.src || '',
    dataSrc: hero.getAttribute('data-src') || '',
    naturalWidth: hero.naturalWidth,
    naturalHeight: hero.naturalHeight,
  } : null;

  const content = document.querySelector('.cms-page, .column.main, main, body');
  const root = content || document.body;
  const imgs = Array.from(root.querySelectorAll('img')).filter((img) => {
    const attrs = [
      img.getAttribute('src') || '',
      img.getAttribute('data-src') || '',
      img.currentSrc || '',
      img.getAttribute('alt') || '',
    ].join(' ');
    return /about|placeholder|wysiwyg|about-us/i.test(attrs);
  });

  const samples = imgs.slice(0, 10).map((img) => ({
    alt: img.getAttribute('alt') || '',
    src: img.currentSrc || img.src || '',
    dataSrc: img.getAttribute('data-src') || '',
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    complete: img.complete,
  }));

  const broken = samples.filter((item) => {
    const url = item.dataSrc || item.src;
    if (!url || url.includes('blank.png')) return false;
    if (url.includes('content-placeholder')) return false;
    if (url.includes('tyresonline.ae')) return true;
    if (/about-us\\/about-tyresonline/i.test(url)) return true;
    return false;
  });

  const heroOk = heroInfo
    ? (heroInfo.dataSrc || heroInfo.src).includes('content-placeholder')
      || (heroInfo.naturalWidth > 0 && heroInfo.naturalHeight > 0)
    : false;

  return {
    ok: broken.length === 0 && heroOk,
    pageUrl: window.location.href,
    heroOk,
    heroInfo,
    imageCount: imgs.length,
    brokenCount: broken.length,
    samples,
  };
}")

echo "$RESULT" | python3 -m json.tool 2>/dev/null || echo "$RESULT"

PASS=$(echo "$RESULT" | python3 -c "import json,sys,re
raw=sys.stdin.read()
try:
    data=json.loads(raw)
except json.JSONDecodeError:
    print('false')
    raise SystemExit(0)
msg=data.get('message','')
m=re.search(r'\"ok\"\\s*:\\s*(true|false)', msg)
print('true' if m and m.group(1)=='true' else 'false')
" 2>/dev/null || echo "false")

if [ "$PASS" = "true" ]; then
  echo "PASS"
  exit 0
fi

echo "FAIL"
exit 1
