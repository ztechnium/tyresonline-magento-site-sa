#!/bin/bash
set -euo pipefail
COOKIE=/tmp/oppwa_cookies.txt
USER='tech-support@tyresonline.com'
PASS='u&!H!9J+2Apd46='
KSA_ENTITY='8ac9a4c888e256440188f95dba3d5b97'

rm -f "$COOKIE"
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" 'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -o /tmp/login.html
CSRF=$(grep -oP 'name="_csrf" value="\K[^"]+' /tmp/login.html)
curl -sS -c "$COOKIE" -b "$COOKIE" -D /tmp/login_resp.hdr -X POST 'https://eu-prod.oppwa.com/sso/login' \
  -H 'Content-Type: application/x-www-form-urlencoded' -H 'Origin: https://eu-prod.oppwa.com' \
  -H 'Referer: https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -H 'User-Agent: Mozilla/5.0' \
  --data-urlencode "username=$USER" --data-urlencode "password=$PASS" --data-urlencode "_csrf=$CSRF" -o /tmp/login_post.html
AUTH_URL=$(grep -i '^location:' /tmp/login_resp.hdr | tail -1 | sed 's/location: //I' | tr -d '\r')
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" "$AUTH_URL" -o /tmp/entity_picker.html
curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" -X POST 'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' \
  -H 'Content-Type: application/x-www-form-urlencoded' -H 'Origin: https://eu-prod.oppwa.com' \
  -H 'Referer: https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -H 'User-Agent: Mozilla/5.0' \
  --data-urlencode "id=$KSA_ENTITY" -o /tmp/after_entity.html

for page in start main menu navigation leftMenu home dashboard; do
  curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
    "https://eu-prod.oppwa.com/bip/${page}.prc" -o "/tmp/${page}.html" 2>/dev/null || true
  if [ -f "/tmp/${page}.html" ]; then
    echo "=== ${page}.prc size $(wc -c < /tmp/${page}.html) ==="
    grep -oE '/bip/[a-zA-Z0-9_./-]+\.prc' "/tmp/${page}.html" | sort -u | head -40
    grep -ioE 'development|credential|token|entity|connector|channel|mada|visa|master|apple|payon|api' "/tmp/${page}.html" | sort -u | head -30
  fi
done

# Dump main.prc text
echo '=== main.prc text ==='
sed 's/<[^>]*>/\n/g' /tmp/main.html | grep -vi '^[[:space:]]*$' | head -120

# Search all discovered prc links from main
grep -oE '/bip/[a-zA-Z0-9_./-]+\.prc' /tmp/main.html | sort -u | while read -r path; do
  code=$(curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" -o /tmp/x.html -w '%{http_code}' "https://eu-prod.oppwa.com$path")
  echo "$code $path"
done
