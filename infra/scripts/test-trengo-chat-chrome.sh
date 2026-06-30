#!/usr/bin/env bash
# Verify Trengo chat close icon renders (Material Icons) instead of raw ligature text.
set -euo pipefail

CDT="npx -y -p chrome-devtools-mcp@latest chrome-devtools"
BASE="${BASE:-https://www.tyresonline.sa}"
LOCALE="${1:-en}"
PAGE_URL="${BASE}/${LOCALE}/all-tyres/car-tyres.html"

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

echo "=== chrome-devtools test: Trengo chat icon (${LOCALE}) ==="

run_cdt new_page "$PAGE_URL" --timeout 120000 >/dev/null
sleep 8

RESULT=$(run_cdt evaluate_script "async () => {
  const launcher = document.querySelector('#trengo-web-widget iframe.trengo-vue-iframe, .TrengoWidgetLauncher__iframe iframe');
  if (launcher) {
    launcher.click();
  }
  await new Promise((resolve) => setTimeout(resolve, 3500));

  const panel = document.querySelector('.TrengoWidgetPanel__iframe iframe');
  if (!panel || !panel.contentDocument) {
    return { ok: false, reason: 'chat-panel-missing' };
  }

  const doc = panel.contentDocument;
  const win = panel.contentWindow;
  const icon = doc.querySelector('i.material-icons');
  if (!icon) {
    return { ok: false, reason: 'material-icon-missing' };
  }

  const rawText = (icon.textContent || '').trim();
  const fontFamily = win.getComputedStyle(icon).fontFamily || '';
  let materialLoaded = false;
  try {
    materialLoaded = await win.document.fonts.load('24px \"Material Icons\"');
    materialLoaded = win.document.fonts.check('24px \"Material Icons\"');
  } catch (e) {
    materialLoaded = false;
  }

  const fontStatuses = [];
  for (const face of win.document.fonts) {
    if ((face.family || '').includes('Material')) {
      fontStatuses.push({ family: face.family, status: face.status });
    }
  }

  const ok = materialLoaded && rawText === 'keyboard_arrow_down' && /Material Icons/i.test(fontFamily);
  return {
    ok,
    rawText,
    fontFamily,
    materialLoaded,
    fontStatuses,
    panelTitle: doc.querySelector('.panel-title-heading')?.textContent?.trim() || '',
  };
}")

MSG=$(echo "$RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('message',''))")
PAYLOAD=$(python3 - <<'PY' "$MSG"
import json, sys
msg = sys.argv[1]
start = msg.index('{')
end = msg.rindex('}') + 1
print(msg[start:end])
PY
)

echo "$PAYLOAD" | python3 -m json.tool

OK=$(echo "$PAYLOAD" | python3 -c "import sys,json; print('true' if json.load(sys.stdin).get('ok') else 'false')")
if [ "$OK" = "true" ]; then
  echo "PASS: Trengo Material Icons font loaded for close button"
  exit 0
fi

echo "FAIL: Trengo chat icon still broken (see details above)"
exit 1
