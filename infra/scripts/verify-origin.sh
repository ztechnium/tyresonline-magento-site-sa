#!/usr/bin/env bash
set -euo pipefail
check() {
  local path="$1"
  local label="$2"
  local html
  html=$(curl -s --max-time 45 -H 'Host: stg.tyresonline.sa' "http://127.0.0.1${path}")
  local ae uae ksa
  ae=$(echo "$html" | grep -c 'TyresOnline.ae' || true)
  uae=$(echo "$html" | grep -c 'الإمارات' || true)
  ksa=$(echo "$html" | grep -c 'المملكة' || true)
  echo "=== $label === ae=$ae uae=$uae ksa=$ksa"
  if [ "$ae" != "0" ]; then echo "$html" | grep -oE '.{0,35}TyresOnline\.ae.{0,35}' | head -3; fi
}
check '/ar/' 'Homepage AR'
check '/ar/about-us' 'About Us AR'
check '/ar/storelocator' 'Fitting Locations AR'
check '/ar/all-tyre-brands' 'Tyre Brands AR'
