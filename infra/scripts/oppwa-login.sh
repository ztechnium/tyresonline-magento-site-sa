#!/bin/bash
set -euo pipefail

COOKIE=/tmp/oppwa_cookies.txt
LOGIN_HTML=/tmp/oppwa_login.html
AFTER_LOGIN=/tmp/oppwa_after.html
USER='tech-support@tyresonline.com'
PASS='u&!H!9J+2Apd46='

rm -f "$COOKIE"

# Get login page + CSRF
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
  'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -o "$LOGIN_HTML"

CSRF=$(grep -oP 'name="_csrf" value="\K[^"]+' "$LOGIN_HTML" || true)
if [ -z "$CSRF" ]; then
  echo "ERROR: Could not extract CSRF token"
  exit 1
fi

echo "CSRF extracted, attempting login..."

# Login POST
curl -sS -c "$COOKIE" -b "$COOKIE" -L \
  -X POST 'https://eu-prod.oppwa.com/sso/login' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Origin: https://eu-prod.oppwa.com' \
  -H 'Referer: https://eu-prod.oppwa.com/bip/root_oauthlogin.link' \
  -H 'User-Agent: Mozilla/5.0' \
  --data-urlencode "username=$USER" \
  --data-urlencode "password=$PASS" \
  --data-urlencode "_csrf=$CSRF" \
  -o "$AFTER_LOGIN" -D /tmp/oppwa_headers.txt

echo "=== Response headers ==="
head -30 /tmp/oppwa_headers.txt

echo "=== Final URL content sample ==="
head -60 "$AFTER_LOGIN"

echo "=== Search for entity/token keywords ==="
grep -iE 'entity|token|merchant|mada|visa|master|credential|access' "$AFTER_LOGIN" | head -30 || true

# Try common BIP pages after login
for path in \
  '/bip/start.prc' \
  '/bip/merchantOverview.prc' \
  '/bip/entityOverview.prc' \
  '/bip/channelOverview.prc' \
  '/bip/connectorOverview.prc' \
  '/bip/development.prc' \
  '/bip/apiCredentials.prc' \
  '/bip/payonApiCredentials.prc'
do
  code=$(curl -sS -c "$COOKIE" -b "$COOKIE" -o /tmp/oppwa_page.html -w '%{http_code}' "https://eu-prod.oppwa.com${path}")
  echo "PATH $path => HTTP $code"
  if [ "$code" = "200" ]; then
    grep -iE 'entity|token|mada|visa|master|credential|access|tyres|saudi|ksa|sar' /tmp/oppwa_page.html | head -8 || true
  fi
done
