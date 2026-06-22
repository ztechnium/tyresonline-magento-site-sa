#!/bin/bash
set -e
CSV=/var/www/magento/var/import/Master_sheet_tires_15_6_2026.csv
CHUNK_DIR=/var/www/magento/var/import/chunks
mkdir -p "$CHUNK_DIR"
HEADER=$(head -1 "$CSV")
tail -n +2 "$CSV" | split -l 200 - "$CHUNK_DIR/chunk_"
i=0
for part in "$CHUNK_DIR"/chunk_*; do
  i=$((i+1))
  out="$CHUNK_DIR/import_$i.csv"
  echo "$HEADER" > "$out"
  cat "$part" >> "$out"
done
rm -f "$CHUNK_DIR"/chunk_*
echo "Created $i chunk files"

TOTAL_BEFORE=$(mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -N -e 'SELECT COUNT(*) FROM catalog_product_entity;')
echo "Products before: $TOTAL_BEFORE"

for file in "$CHUNK_DIR"/import_*.csv; do
  name=$(basename "$file")
  echo "=== Importing $name ==="
  mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -e 'TRUNCATE importexport_importdata;'
  cd /var/www/magento
  sudo -u www-data php var/import/run-product-import.php "import/chunks/$name" 2>&1 | tail -4
done

TOTAL_AFTER=$(mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -N -e 'SELECT COUNT(*) FROM catalog_product_entity;')
echo "Products after: $TOTAL_AFTER"
