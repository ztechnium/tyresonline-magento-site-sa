#!/usr/bin/env bash
set -euo pipefail

URL="${1:-https://www.tyresonline.sa/en/all-tyres.html}"
EXPECTED_PHONE="${2:-9668001240192}"
OUT="/tmp/product-card-whatsapp-test.html"

curl -sL "$URL" -o "$OUT"

echo "=== Product card WhatsApp links ==="
python3 - <<'PY' "$OUT" "$EXPECTED_PHONE"
import re, sys
html = open(sys.argv[1]).read()
expected = sys.argv[2]
patterns = [
    r'class="[^"]*btn-whatsapp-oos[^"]*"[^>]*href="([^"]+)"',
    r'class="[^"]*whatsapp-qty[^"]*"[\s\S]*?href="([^"]+)"',
    r'href="(https://api\.whatsapp\.com/send\?phone=[^"]+)"[^>]*class="[^"]*btn-whatsapp-oos',
    r'href="(https://api\.whatsapp\.com/send\?phone=[^"]+)"[^>]*class="[^"]*whatsapp-qty',
]
links = []
for p in patterns:
    links.extend(re.findall(p, html, re.I))
links = list(dict.fromkeys(links))
if not links:
    print("No product-card WhatsApp links found (products may all be in stock).")
else:
    for link in links:
        print(link)
    old = [l for l in links if '97180025589737' in l]
    new = [l for l in links if expected in l]
    if old:
        print(f"FAIL: {len(old)} product-card link(s) still use UAE number")
        sys.exit(1)
    if new:
        print(f"PASS: {len(new)} product-card link(s) use KSA number {expected}")
    else:
        print("WARN: product-card WhatsApp links found but not with expected KSA number")
PY

if command -v google-chrome >/dev/null 2>&1; then
  SCREENSHOT="/tmp/product-card-whatsapp-plp.png"
  google-chrome --headless=new --disable-gpu --window-size=390,844 \
    --screenshot="$SCREENSHOT" "$URL" >/dev/null 2>&1 || true
  if [ -f "$SCREENSHOT" ]; then
    echo "Chrome mobile screenshot: $SCREENSHOT"
  fi
  DOM="/tmp/product-card-whatsapp-dom.html"
  google-chrome --headless=new --disable-gpu --dump-dom "$URL" > "$DOM" 2>/dev/null || true
  if [ -f "$DOM" ]; then
    CARD_LINKS=$(grep -oE 'href="https://api\.whatsapp\.com/send\?phone=[0-9]+[^"]*"' "$DOM" | grep -E 'btn-whatsapp|whatsapp-qty' || true)
    if [ -n "$CARD_LINKS" ]; then
      echo "Chrome DOM product-card WhatsApp hrefs:"
      echo "$CARD_LINKS"
    fi
  fi
fi

echo "Done."
