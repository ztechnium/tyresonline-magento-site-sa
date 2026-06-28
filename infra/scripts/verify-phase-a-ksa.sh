#!/usr/bin/env bash
set -euo pipefail
eval $(python3 - <<'PY'
import re
text=open('/var/www/magento/app/etc/env.php').read()
conn=re.search(r"'connection'\s*=>\s*\[(.*?)\]", text, re.S).group(1)
for k in ['host','dbname','username','password']:
    m=re.search(r"'"+k+r"'\s*=>\s*'([^']*)'", conn)
    print(f"{k}={m.group(1)}")
PY
)

check_url() {
  local url="$1"
  local label="$2"
  echo "=== $label ==="
  curl -s "$url" | grep -oE '.{0,30}الإمارات.{0,30}|.{0,30}TyresOnline\.ae.{0,30}|.{0,30}المملكة.{0,30}' | head -8 || echo "(no matches or fetch issue)"
}

check_url 'https://stg.tyresonline.sa/ar/' 'Homepage AR'
check_url 'https://stg.tyresonline.sa/ar/about-us' 'About Us AR'
check_url 'https://stg.tyresonline.sa/ar/storelocator' 'Fitting Locations AR'
check_url 'https://stg.tyresonline.sa/ar/all-tyre-brands' 'Tyre Brands AR'

echo "=== DB remaining UAE in key blocks ==="
mysql -h "$host" -u "$username" -p"$password" "$dbname" -N -e "
SELECT CONCAT('block ', block_id, ' ', identifier, ' uae=', (LENGTH(content)-LENGTH(REPLACE(content,'الإمارات','')))/CHAR_LENGTH('الإمارات'))
FROM cms_block WHERE block_id IN (18,37,32,35) ORDER BY block_id;
SELECT CONCAT('page ', page_id, ' ', identifier, ' uae=', (LENGTH(content)-LENGTH(REPLACE(content,'الإمارات','')))/CHAR_LENGTH('الإمارات'))
FROM cms_page WHERE identifier IN ('home','about-us') OR page_id IN (42,20);
"
