#!/bin/bash
# Import Ecomteck storelocator tables from AE staging into KSA RDS
set -eu

AE_HOST="${AE_HOST:-tyresonline-ae-stg-rds.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com}"
AE_DB="${AE_DB:-tyresonline_ae}"
SA_HOST="${SA_HOST:-tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com}"
SA_DB="${SA_DB:-tyresonline_sa}"
AE_ENV="${AE_ENV:-/var/www/magento/app/etc/env.php}"
SA_PASS="${SA_PASS:-uxaYMIQRwEU0AFl1Ck69LJnH}"
DUMP="/tmp/ecomteck_storelocator_$$.sql.gz"

AE_PASS=$(php -r '$e=include getenv("AE_ENV"); echo $e["db"]["connection"]["default"]["password"];')
export AE_ENV

TABLES=$(mysql -h "$AE_HOST" -u magento -p"$AE_PASS" "$AE_DB" -N -e "SHOW TABLES LIKE 'ecomteck%'")
if [ -z "$TABLES" ]; then
  echo "No ecomteck tables found on AE database"
  exit 1
fi

echo "Dumping tables from AE: $TABLES"
mysqldump -h "$AE_HOST" -u magento -p"$AE_PASS" "$AE_DB" $TABLES | gzip > "$DUMP"
echo "Dump size: $(du -h "$DUMP" | cut -f1)"

echo "Importing into KSA database..."
gunzip -c "$DUMP" | mysql -h "$SA_HOST" -u magento -p"$SA_PASS" "$SA_DB"

echo "Verifying..."
mysql -h "$SA_HOST" -u magento -p"$SA_PASS" "$SA_DB" -N -e \
  "SELECT COUNT(*) FROM ecomteck_storelocator_stores WHERE status=1"

rm -f "$DUMP"
echo "Done."
