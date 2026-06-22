#!/bin/bash
set -e
CSV=/var/www/magento/var/import/new_products_only.csv
CHUNK_DIR=/var/www/magento/var/import/new_chunks
mkdir -p "$CHUNK_DIR"
HEADER=$(head -1 "$CSV")
tail -n +2 "$CSV" | split -l 150 - "$CHUNK_DIR/chunk_"
i=0
for part in "$CHUNK_DIR"/chunk_*; do
  i=$((i+1))
  out="$CHUNK_DIR/new_$i.csv"
  echo "$HEADER" > "$out"
  cat "$part" >> "$out"
done
rm -f "$CHUNK_DIR"/chunk_*
echo "Chunks: $i"
for file in $(ls "$CHUNK_DIR"/new_*.csv | sort -V); do
  name=$(basename "$file")
  mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -e 'TRUNCATE importexport_importdata;'
  echo "=== $name ==="
  cd /var/www/magento
  sudo -u www-data php var/import/run-product-import.php "import/new_chunks/$name" 2>&1 | grep -E 'Summary|Product count|Invalid'
done
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -N -e 'SELECT COUNT(*) FROM catalog_product_entity; SELECT COUNT(*) FROM cataloginventory_stock_item;'
