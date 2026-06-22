#!/bin/bash
set -e
MASTER=/var/www/magento/var/import/Master_sheet_tires_15_6_2026_fixed.csv
WORK=/var/www/magento/var/import
MYSQL=(mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa)

refresh_skus() {
  "${MYSQL[@]}" -N -e 'SELECT sku FROM catalog_product_entity' > /var/www/magento/var/import/db_skus.txt
}

make_batch() {
  python3 - <<'PY'
import csv
db=set(open('/var/www/magento/var/import/db_skus.txt').read().splitlines())
src='/var/www/magento/var/import/Master_sheet_tires_15_6_2026_fixed.csv'
out='/var/www/magento/var/import/batch.csv'
remaining=0
with open(src,encoding='utf-8-sig') as f:
    rows=list(csv.DictReader(f))
pending=[r for r in rows if r['sku'].strip() not in db]
remaining=len(pending)
batch=pending[:1]
with open(out,'w',newline='',encoding='utf-8') as o:
    if not batch:
        print('0')
    else:
        w=csv.DictWriter(o, fieldnames=rows[0].keys())
        w.writeheader(); w.writerows(batch)
        print(len(batch))
print(f'remaining={remaining}', file=__import__('sys').stderr)
PY
}

round=0
while true; do
  round=$((round+1))
  refresh_skus
  count=$(make_batch 2>/dev/null | tail -1)
  if [[ "$count" == "0" ]]; then
    echo "Done - no pending rows"
    break
  fi
  echo "=== Round $round: importing $count products ==="
  "${MYSQL[@]}" -e 'TRUNCATE importexport_importdata;'
  cd /var/www/magento
  sudo -u www-data php var/import/run-product-import.php import/batch.csv 2>&1 | grep -E 'Summary|Product count|ERR row'
  if [[ $round -ge 300 ]]; then
    echo "Safety stop at 50 rounds"
    break
  fi
done

"${MYSQL[@]}" -N -e 'SELECT COUNT(*) FROM catalog_product_entity;'
