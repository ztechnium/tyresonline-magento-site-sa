#!/usr/bin/env bash
# Verify tyres PLP side filter hides loader after AJAX completes.
set -euo pipefail

CDT="npx -y -p chrome-devtools-mcp@latest chrome-devtools"
BASE="${BASE:-https://stg.tyresonline.sa}"
LOCALE="${1:-en}"
CATEGORY_URL="${BASE}/${LOCALE}/all-tyres/car-tyres.html"

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

echo "=== chrome-devtools test: tyres PLP filter loader (${LOCALE}) ==="

run_cdt new_page "$CATEGORY_URL" --timeout 120000 >/dev/null
sleep 8

RESULT=$(run_cdt evaluate_script "async () => {
  const filterLink = document.querySelector('.mgs-ajax-layer-item, .mgs-layered-checkbox');
  if (!filterLink) {
    return { ok: false, reason: 'No layered filter control found' };
  }

  const clickTarget = document.querySelector('.mgs-ajax-layer-item')
    || document.querySelector('.mgs-layered-checkbox');

  const jq = window.jQuery;
  const samples = [];
  const target = jq('.mgs-ajax-layer-item').first();
  if (target.length) {
    target.trigger('click');
  }

  for (let i = 0; i < 8; i++) {
    await new Promise((resolve) => setTimeout(resolve, 500));
    const mask = document.querySelector('.loading-mask');
    const loader = jq('[data-container=body]').data('mageLoader');
    samples.push({
      t: (i + 1) * 0.5,
      visibleMask: !!(mask && getComputedStyle(mask).display !== 'none' && getComputedStyle(mask).visibility !== 'hidden'),
      loaderStarted: loader ? loader.loaderStarted : null,
      ajaxLoading: document.body.classList.contains('ajax-loading'),
      filterInProgress: !!window.MGS_FILTER_IN_PROGRESS,
    });
  }

  const sawLoader = samples.some((s) => s.visibleMask || s.loaderStarted > 0 || s.filterInProgress);
  const final = samples[samples.length - 1];

  return {
    ok: sawLoader && !final.visibleMask && !final.ajaxLoading && final.loaderStarted === 0,
    sawLoader,
    final,
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
