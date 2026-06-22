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

curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
  "https://eu-prod.oppwa.com/bip/analysis.prc?id=${KSA_ENTITY}&type=MERCHANT&autosearch=false" -o /tmp/analysis.html
echo "analysis size $(wc -c < /tmp/analysis.html)"
grep -oE '8ac[0-9a-f]{30}' /tmp/analysis.html | sort -u
grep -ioE 'mada|visa|master|apple|channel|connector|entity.?id|access.?token|credential|OGFj[A-Za-z0-9=+/|_-]{10,}' /tmp/analysis.html | sort -u | head -30

# probe other top-level apps
for path in / /admin /backoffice /platform /merchant /bo /ui /portal /account /development; do
  code=$(curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" -o /tmp/root.html -w '%{http_code}' "https://eu-prod.oppwa.com${path}")
  title=$(grep -ioP '<title>\K[^<]+' /tmp/root.html 2>/dev/null | head -1)
  echo "$code $path => ${title:-no-title}"
done
