#!/usr/bin/env bash
set -euo pipefail
URL_PUBLIC='https://stg.tyresonline.sa/ar/'
URL_ORIGIN='http://127.0.0.1/ar/'

check_html() {
  local label="$1" html="$2"
  echo "=== $label ==="
  for needle in \
    'الإمارات' 'TyresOnline.ae' 'أشتري كفرات أونلاين' 'تركيب الكفرات في جميع أنحاء السعودية' \
    'تضم شبكة شركائنا' 'المملكة العربية السعودية' 'TyresOnline.sa' 'أشتري إطارات أونلاين'
  do
    local c
    c=$(echo "$html" | grep -o "$needle" | wc -l | tr -d ' ')
    echo "  [$needle] count=$c"
  done
}

HTML_PUB=$(curl -s --max-time 60 "$URL_PUBLIC" || true)
HTML_ORG=$(curl -s --max-time 60 -H 'Host: stg.tyresonline.sa' "$URL_ORIGIN" || true)

check_html "PUBLIC $URL_PUBLIC" "$HTML_PUB"
echo
check_html "ORIGIN $URL_ORIGIN" "$HTML_ORG"

echo
echo "=== Sample visible headings (PUBLIC) ==="
echo "$HTML_PUB" | grep -oE '<h[1-4][^>]*>[^<]{5,120}' | head -12

echo
echo "=== DB block 18 snippet ==="
cd /var/www/magento
php -r "
require 'app/bootstrap.php';
\$c = Magento\Framework\App\Bootstrap::create(BP,\$_SERVER)->getObjectManager()->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
\$b18 = \$c->fetchOne('SELECT content FROM cms_block WHERE block_id=18');
echo substr(strip_tags(\$b18),0,400).PHP_EOL;
echo 'uae_hits='.((strlen(\$b18)-strlen(str_replace('الإمارات','',\$b18)))/strlen('الإمارات')).PHP_EOL;
"
