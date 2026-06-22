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

curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" 'https://eu-prod.oppwa.com/bip/start.prc' -o /tmp/ksa_start.html
echo "SIZE $(wc -c < /tmp/ksa_start.html)"
grep -oE '/bip/[a-zA-Z0-9_./-]+\.prc' /tmp/ksa_start.html | sort -u
echo '---'
sed 's/<[^>]*>/\n/g' /tmp/ksa_start.html | grep -vi '^[[:space:]]*$' | head -100

# brute-force common credential endpoints
while read -r path; do
  [ -z "$path" ] && continue
  code=$(curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" -o /tmp/p.html -w '%{http_code}' "https://eu-prod.oppwa.com$path")
  if [ "$code" = "200" ]; then
    hits=$(grep -ioE 'entity.?id|access.?token|mada|visa|master|apple|OGFj[A-Za-z0-9=+/|_-]{10,}|8ac[0-9a-f]{30}' /tmp/p.html | head -5 | tr '\n' ' ')
    [ -n "$hits" ] && echo "HIT $path => $hits"
  fi
done <<'PATHS'
/bip/developmentOverview.prc
/bip/developmentDetails.prc
/bip/payonApiCredentialsOverview.prc
/bip/payonApiCredentialsList.prc
/bip/payonApiCredentialsDetails.prc
/bip/accessTokenOverview.prc
/bip/accessTokenList.prc
/bip/accessTokenDetails.prc
/bip/connectorOverview.prc
/bip/connectorList.prc
/bip/channelOverview.prc
/bip/channelList.prc
/bip/entityOverview.prc
/bip/entityList.prc
/bip/entityDetails.prc
/bip/merchantOverview.prc
/bip/merchantDetails.prc
/bip/merchantDetailsEdit.prc
/bip/transactionOverview.prc
PATHS
