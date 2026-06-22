#!/bin/bash
set -euo pipefail

COOKIE=/tmp/oppwa_cookies.txt
USER='tech-support@tyresonline.com'
PASS='u&!H!9J+2Apd46='

rm -f "$COOKIE"

# Step 1: login page
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
  'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -o /tmp/login.html
CSRF=$(grep -oP 'name="_csrf" value="\K[^"]+' /tmp/login.html)
echo "CSRF ok"

# Step 2: POST credentials (no -L yet)
curl -sS -c "$COOKIE" -b "$COOKIE" -D /tmp/login_resp.hdr \
  -X POST 'https://eu-prod.oppwa.com/sso/login' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Origin: https://eu-prod.oppwa.com' \
  -H 'Referer: https://eu-prod.oppwa.com/bip/root_oauthlogin.link' \
  -H 'User-Agent: Mozilla/5.0' \
  --data-urlencode "username=$USER" \
  --data-urlencode "password=$PASS" \
  --data-urlencode "_csrf=$CSRF" \
  -o /tmp/login_post.html

AUTH_URL=$(grep -i '^location:' /tmp/login_resp.hdr | tail -1 | sed 's/location: //I' | tr -d '\r')
echo "AUTH_URL=$AUTH_URL"

if [ -z "$AUTH_URL" ]; then
  echo "Login failed - no redirect"
  head -30 /tmp/login_post.html
  exit 1
fi

# Step 3: OAuth authorize (follow redirects into BIP)
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
  "$AUTH_URL" -o /tmp/after_oauth.html -D /tmp/oauth.hdr

echo "=== After OAuth (first 40 lines) ==="
head -40 /tmp/after_oauth.html

echo "=== OAuth response headers ==="
head -25 /tmp/oauth.hdr

# Step 4: open BIP home
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
  'https://eu-prod.oppwa.com/bip/start.prc' -o /tmp/bip_home.html

echo "=== BIP home size ==="
wc -c /tmp/bip_home.html

echo "=== Keywords in BIP home ==="
grep -iE 'entity|token|mada|visa|master|credential|merchant|tyres|saudi|ksa|sar|easy click|alma' /tmp/bip_home.html | head -40 || true

# Dump links for manual discovery
echo "=== Links in BIP home ==="
grep -oE 'href="[^"]+"' /tmp/bip_home.html | head -40 || true
