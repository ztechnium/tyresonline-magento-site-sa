#!/bin/bash
set -euo pipefail

MAGENTO=/var/www/magento
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS='uxaYMIQRwEU0AFl1Ck69LJnH'
DB_NAME=tyresonline_sa

echo "=== Step 1: Create var/export ==="
sudo mkdir -p "$MAGENTO/var/export"
sudo chown www-data:www-data "$MAGENTO/var/export"
sudo chmod 775 "$MAGENTO/var/export"

echo "=== Step 2: Create export_history table if missing ==="
HAS_TABLE=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='export_history';")

if [ "$HAS_TABLE" = "0" ]; then
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'SQL'
CREATE TABLE IF NOT EXISTS export_history (
  id int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Export history ID',
  started_at timestamp NULL DEFAULT NULL COMMENT 'Started at',
  finished_at timestamp NULL DEFAULT NULL COMMENT 'Finished at',
  status varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'Status',
  file_name varchar(255) DEFAULT NULL COMMENT 'File name',
  type varchar(255) DEFAULT NULL COMMENT 'Type',
  entity varchar(255) DEFAULT NULL COMMENT 'Entity',
  export_filter text COMMENT 'Export filter',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Export history';
SQL
  echo "export_history table created"
else
  echo "export_history already exists"
fi

echo "=== Step 3: Install Magento cron for www-data ==="
sudo -u www-data php "$MAGENTO/bin/magento" cron:install 2>&1 || true

echo "=== Step 4: Add exportProcessor consumer to cron if missing ==="
CRON_FILE=$(sudo crontab -u www-data -l 2>/dev/null || true)
if echo "$CRON_FILE" | grep -q exportProcessor; then
  echo "exportProcessor already in cron"
else
  echo "Adding exportProcessor consumer cron entry"
  (echo "$CRON_FILE"; echo "* * * * * /usr/bin/php $MAGENTO/bin/magento queue:consumers:start exportProcessor --max-messages=100 >> $MAGENTO/var/log/export-consumer.log 2>&1") | sudo crontab -u www-data -
fi

echo "=== Step 5: Process any pending export queue messages now ==="
timeout 120 sudo -u www-data php "$MAGENTO/bin/magento" queue:consumers:start exportProcessor --max-messages=50 2>&1 || true

echo "=== Step 6: Check export_history ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
  "SELECT id, started_at, finished_at, status, file_name, entity FROM export_history ORDER BY id DESC LIMIT 10;" 2>/dev/null || true

echo "=== Step 7: Sample import error report ==="
head -2 "$MAGENTO/var/import_history/1781612187_Master_sheet_tires_error_report.csv" 2>/dev/null || true

echo "=== Step 8: Test CLI product export ==="
sudo -u www-data php "$MAGENTO/bin/magento" export:export \
  --entity=catalog_product \
  --file-format=csv \
  --file-name=test_export_cli.csv 2>&1 || true

ls -la "$MAGENTO/var/export/" 2>/dev/null || true

echo "=== DONE ==="
