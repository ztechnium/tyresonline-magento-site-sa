#!/usr/bin/env bash
set -euo pipefail

# Validates ajax-navigation URL helpers and production list-mode ajax response.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
JS_FILE="$ROOT/app/design/frontend/Hditsol/tyresonline/MGS_Ajaxlayernavigation/web/js/ajax-navigation.js"
BASE="${BASE:-https://www.tyresonline.sa}"
LOCALE="${1:-en}"
URL="$BASE/$LOCALE/all-tyres/car-tyres.html"

echo "1) URL helper sanity check"
node <<NODE
const fs = require('fs');
const vm = require('vm');

const file = ${JS_FILE@Q};
const src = fs.readFileSync(file, 'utf8');
const widgetBody = src.match(/\\\$\\.widget\\('mage\\.ajaxnavigation', \\{([\\s\\S]*)\\}\\);\\s*return/s);
if (!widgetBody) {
  console.error('Could not parse ajax-navigation widget');
  process.exit(1);
}

const sandbox = {
  $: {
    param: (obj) => Object.keys(obj).map((k) => \`\${encodeURIComponent(k)}=\${encodeURIComponent(obj[k])}\`).join('&'),
  },
  window: { location: { origin: 'https://www.tyresonline.sa', pathname: '/en/all-tyres/car-tyres.html', search: '' } },
};
const ctx = vm.createContext(sandbox);
const code = \`const api = {\${widgetBody[1]}}; api;\`;
const api = vm.runInContext(code, ctx);

const broken = 'https://www.tyresonline.sa/en/all-tyres/car-tyres.html#';
const normalized = api.normalizeFilterUrl(broken);
const params = api.urlParams(broken);
params.product_list_mode = 'list';
const built = normalized.split('?')[0] + '?' + sandbox.$.param(params);

if (built.includes('#')) {
  console.error('FAIL: built URL still contains hash:', built);
  process.exit(1);
}
if (!built.includes('product_list_mode=list')) {
  console.error('FAIL: built URL missing list mode param:', built);
  process.exit(1);
}
console.log('PASS: built URL =', built);
NODE

echo "2) Production ajax list-mode response"
TMP_JSON="$(mktemp)"
curl -fsS -H "X-Requested-With: XMLHttpRequest" "$URL?product_list_mode=list&is_ajax=1" -o "$TMP_JSON"
python3 - <<'PY' "$TMP_JSON"
import json, sys
html = json.load(open(sys.argv[1])).get('list', '')
ok = ('products-list' in html) or ('class="list-view' in html)
print('PASS: list markup present' if ok else 'FAIL: list markup missing')
if not ok:
    sys.exit(2)
PY
rm -f "$TMP_JSON"

echo "3) Static URLs must not contain embedded newlines (breaks mage-init JSON)"
PAGE_HTML="$(mktemp)"
curl -fsS "$URL" -o "$PAGE_HTML"
if python3 - <<'PY' "$PAGE_HTML"
import sys
html = open(sys.argv[1]).read()
bad = [s for s in html.split("data-mage-init='") if '\n/' in s[:120] or 'version' in s[:30] and '\n' in s[:120]]
if bad:
    print('FAIL: embedded newline found in data-mage-init static URL')
    sys.exit(2)
print('PASS: no embedded newlines in static loader URLs')
PY
then
  :
else
  rm -f "$PAGE_HTML"
  exit 2
fi
rm -f "$PAGE_HTML"

echo "All checks passed for $URL"
