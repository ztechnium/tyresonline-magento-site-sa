#!/usr/bin/env bash
set -euo pipefail
ENV=/var/www/magento/app/etc/env.php
HOST=$(php -r "echo include '$ENV';" 2>/dev/null || python3 - <<'PY'
import re
text=open('/var/www/magento/app/etc/env.php').read()
for k in ['host','dbname','username','password']:
    m=re.search(r"'"+k+r"' => '([^']*)'", text)
    print(m.group(1) if m else '')
PY
)
# simpler parse
eval $(python3 - <<'PY'
import re
text=open('/var/www/magento/app/etc/env.php').read()
conn=re.search(r"'connection'\s*=>\s*\[(.*?)\]", text, re.S).group(1)
for k in ['host','dbname','username','password']:
    m=re.search(r"'"+k+r"'\s*=>\s*'([^']*)'", conn)
    print(f"{k}={m.group(1)}")
PY
)

mysql -h "$host" -u "$username" -p"$password" "$dbname" <<'SQL'
SELECT 'STORE CONFIG AR' AS section;
SELECT path, LEFT(value,120) AS value FROM core_config_data
WHERE scope='stores' AND scope_id=2
AND path IN ('general/store_information/name','design/head/default_title','design/footer/copyright','general/store_information/country_id');

SELECT 'STORE VIEWS' AS section;
SELECT store_id, code, name FROM store;

SELECT 'CMS HOME META' AS section;
SELECT page_id, identifier, LEFT(meta_title,100) AS meta_title FROM cms_page WHERE identifier='home';

SELECT 'CMS BLOCKS WITH UAE TEXT' AS section;
SELECT block_id, identifier, title FROM cms_block WHERE content LIKE '%الإمارات%' LIMIT 10;

SELECT 'CMS PAGES WITH UAE TEXT' AS section;
SELECT page_id, identifier, title FROM cms_page WHERE content LIKE '%الإمارات%' LIMIT 10;
SQL
