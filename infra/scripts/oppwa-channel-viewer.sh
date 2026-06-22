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
  "https://eu-prod.oppwa.com/bip/changeviewer.prc?changeaggkey=true&id=${KSA_ENTITY}&type=MERCHANT" -o /tmp/changeviewer.html

echo "size $(wc -c < /tmp/changeviewer.html)"
grep -oE '8ac[0-9a-f]{30}' /tmp/changeviewer.html | sort -u
grep -ioE 'mada|visa|master|apple|channel|entity|token|access|credential|tyres|snb|ncb|cyber' /tmp/changeviewer.html | sort -u | head -40
sed 's/<[^>]*>/\n/g' /tmp/changeviewer.html | grep -vi '^[[:space:]]*$' | grep -iE 'channel|mada|visa|master|entity|token|tyres|snb|ncb' | head -40
