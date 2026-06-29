#!/usr/bin/env bash
# Test cart fitting date/time/checkbox visibility using chrome-devtools CLI.
set -euo pipefail

CDT="npx -y -p chrome-devtools-mcp@latest chrome-devtools"
BASE="https://stg.tyresonline.sa"
LOCALE="${1:-en}"
PRODUCT_URL="${BASE}/${LOCALE}/test-tyre-1-sar.html"
CART_URL="${BASE}/${LOCALE}/checkout/cart/"

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

echo "=== chrome-devtools test: ${LOCALE^^} ==="

run_cdt new_page "$PRODUCT_URL" --timeout 90000 >/dev/null
sleep 4

# Add product to cart
ADD_RESULT=$(run_cdt evaluate_script "async () => {
  const formKey = document.querySelector('input[name=form_key]')?.value;
  if (!formKey) return { ok: false, reason: 'no form_key' };
  const body = new URLSearchParams({ product: '6954', qty: '1', form_key: formKey });
  const resp = await fetch('/${LOCALE}/checkout/cart/add/', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
    credentials: 'include',
  });
  return { ok: resp.ok, status: resp.status };
}")
echo "add_to_cart: $ADD_RESULT"

run_cdt navigate_page --url "$CART_URL" --timeout 90000 >/dev/null
sleep 6

CONFIG_RESULT=$(run_cdt evaluate_script "() => ({
  hasConfig: typeof window.checkoutStoreLocatorConfig !== 'undefined',
  hideDatetimeRule: Array.from(document.styleSheets).some((sheet) => {
    try {
      return Array.from(sheet.cssRules || []).some((rule) =>
        rule.cssText && rule.cssText.includes('.storelocator .datetimesubmit{display: none')
      );
    } catch (e) { return false; }
  })
})")
echo "page_config: $CONFIG_RESULT"

# Open fitting modal
run_cdt evaluate_script "() => {
  const opener = document.querySelector('#cart-installer-location') ||
    document.querySelector('[data-bs-target=\"#mycartinstallerModal\"]');
  if (opener) opener.click();
  return { clicked: !!opener };
}" >/dev/null
sleep 3

# Wait for installers
for i in $(seq 1 30); do
  COUNT_JSON=$(run_cdt evaluate_script "() => document.querySelectorAll('#mycartinstallerModal .allInstaller .installer').length")
  COUNT=$(echo "$COUNT_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('result',{}).get('value', d.get('value',0)))" 2>/dev/null || echo 0)
  if [[ "$COUNT" -gt 0 ]]; then
    break
  fi
  sleep 1
done
echo "installer_count: $COUNT"

STATE=$(run_cdt evaluate_script "() => {
  const modal = document.querySelector('#mycartinstallerModal');
  const first = modal?.querySelector('.allInstaller .installer');
  const visible = (el) => {
    if (!el) return false;
    const cs = getComputedStyle(el);
    return cs.display !== 'none' && cs.visibility !== 'hidden' && el.offsetParent !== null;
  };
  const checkbox = first?.querySelector('.checkboxInstaller');
  const datetime = first?.querySelector('.datetimesubmit');
  return {
    installerCount: modal?.querySelectorAll('.allInstaller .installer').length || 0,
    checkboxFound: !!checkbox,
    checkboxVisible: visible(checkbox),
    datetimeFound: !!datetime,
    datetimeVisible: visible(datetime),
    dateInputFound: !!first?.querySelector('.storePickupdatepicker'),
    timeSelectFound: !!first?.querySelector('.storePickuptimepicker'),
  };
}")
echo "before_check: $STATE"

# Click Select Installer checkbox via evaluate (more reliable than snapshot uid)
run_cdt evaluate_script "() => {
  const cb = document.querySelector('#mycartinstallerModal .checkboxInstaller');
  if (!cb) return { clicked: false };
  cb.checked = true;
  cb.dispatchEvent(new Event('change', { bubbles: true }));
  return { clicked: true };
}" >/dev/null
sleep 1

AFTER=$(run_cdt evaluate_script "() => {
  const first = document.querySelector('#mycartinstallerModal .allInstaller .installer');
  const visible = (el) => {
    if (!el) return false;
    const cs = getComputedStyle(el);
    return cs.display !== 'none' && cs.visibility !== 'hidden' && el.offsetParent !== null;
  };
  const datetime = first?.querySelector('.datetimesubmit');
  return {
    datetimeFound: !!datetime,
    datetimeVisible: visible(datetime),
    dateInputFound: !!first?.querySelector('.storePickupdatepicker'),
    timeSelectFound: !!first?.querySelector('.storePickuptimepicker'),
  };
}")
echo "after_check: $AFTER"

SCREENSHOT="/tmp/fitting-devtools-${LOCALE}.png"
run_cdt take_screenshot --filePath "$SCREENSHOT" >/dev/null
echo "screenshot: $SCREENSHOT"

# Parse results
python3 - "$CONFIG_RESULT" "$STATE" "$AFTER" <<'PY'
import json, sys

def load(s):
    try:
        d = json.loads(s)
        return d.get('result', d).get('value', d.get('value', d))
    except Exception:
        return {}

config, before, after = map(load, sys.argv[1:4])
errors = []
if not config.get('hasConfig'):
    errors.append('checkoutStoreLocatorConfig missing')
if config.get('hideDatetimeRule'):
    errors.append('CSS still hides .datetimesubmit')
if before.get('installerCount', 0) == 0:
    errors.append('no installers rendered')
if not before.get('checkboxFound'):
    errors.append('Select Installer checkbox not found')
if not before.get('checkboxVisible'):
    errors.append('Select Installer checkbox not visible')
if not after.get('datetimeFound'):
    errors.append('date/time block missing after selecting installer')
if not after.get('dateInputFound'):
    errors.append('date picker missing after selecting installer')
if not after.get('timeSelectFound'):
    errors.append('time select missing after selecting installer')

if errors:
    print('RESULT: FAIL')
    for e in errors:
        print(' -', e)
    sys.exit(1)
print('RESULT: PASS')
PY
