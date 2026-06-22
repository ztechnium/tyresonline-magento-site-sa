#!/bin/bash
set -euo pipefail

COOKIE=/tmp/oppwa_cookies.txt
USER='tech-support@tyresonline.com'
PASS='u&!H!9J+2Apd46='
# KSA merchant from entity picker
KSA_ENTITY='8ac9a4c888e256440188f95dba3d5b97'
UAE_ENTITY='8ac9a4ce86c0b6e60186dacb08567778'

rm -f "$COOKIE"

login_and_select() {
  local entity_id="$1"
  local label="$2"

  rm -f "$COOKIE"
  curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
    'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' -o /tmp/login.html
  CSRF=$(grep -oP 'name="_csrf" value="\K[^"]+' /tmp/login.html)

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
  curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
    "$AUTH_URL" -o /tmp/entity_picker.html

  # Select merchant entity
  curl -sS -L -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
    -X POST 'https://eu-prod.oppwa.com/bip/root_oauthlogin.link' \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    -H 'Origin: https://eu-prod.oppwa.com' \
    -H 'Referer: https://eu-prod.oppwa.com/bip/root_oauthlogin.link' \
    -H 'User-Agent: Mozilla/5.0' \
    --data-urlencode "id=$entity_id" \
    -o /tmp/after_entity.html -D /tmp/entity.hdr

  echo "===== $label (entity $entity_id) ====="
  head -5 /tmp/entity.hdr
  wc -c /tmp/after_entity.html

  # Try dashboard + common credential pages
  for path in \
    '/bip/start.prc' \
    '/bip/merchantDetails.prc' \
    '/bip/merchantDetailsEdit.prc' \
    '/bip/connectorList.prc' \
    '/bip/channelList.prc' \
    '/bip/entityList.prc' \
    '/bip/developmentOverview.prc' \
    '/bip/payonApiCredentialsOverview.prc' \
    '/bip/payonApiCredentialsList.prc' \
    '/bip/accessTokenOverview.prc' \
    '/bip/accessTokenList.prc'
  do
    code=$(curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
      -o /tmp/page.html -w '%{http_code}' "https://eu-prod.oppwa.com${path}")
    if [ "$code" = "200" ]; then
      echo "--- $path (200) ---"
      grep -iE 'entity.?id|entityId|access.?token|token|mada|visa|master|apple|credential|8ac[0-9a-f]{30}|OGFj|payment|connector|channel' /tmp/page.html \
        | sed 's/<[^>]*>/ /g' | tr -s ' ' | head -25 || true
      grep -oE '8ac[0-9a-f]{30}' /tmp/page.html | sort -u | head -20 || true
      grep -oE 'OGFj[A-Za-z0-9=+/|_-]{10,}' /tmp/page.html | sort -u | head -5 || true
    fi
  done

  echo "=== Menu links from start.prc ==="
  curl -sS -A 'Mozilla/5.0' -c "$COOKIE" -b "$COOKIE" \
    'https://eu-prod.oppwa.com/bip/start.prc' -o /tmp/start.html
  grep -oE 'href="/bip/[^"]+"' /tmp/start.html | sort -u | head -60 || true
}

login_and_select "$KSA_ENTITY" "Tyresonline KSA"
login_and_select "$UAE_ENTITY" "Easy Click UAE"
