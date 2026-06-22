#!/bin/bash
set -euo pipefail

MAGENTO=/var/www/magento
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS='uxaYMIQRwEU0AFl1Ck69LJnH'
DB_NAME=tyresonline_sa

echo "=== export dirs ==="
ls -la "$MAGENTO/var/" | grep -E 'export|import' || true

echo "=== export tables ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE '%export%';" 2>/dev/null || true

echo "=== export_history count ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='export_history';" 2>/dev/null || echo "query failed"

echo "=== export_history rows ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT * FROM export_history ORDER BY id DESC LIMIT 10;" 2>/dev/null || echo "export_history missing or empty"

echo "=== queue messages ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT topic_name, status, COUNT(*) c FROM queue_message GROUP BY topic_name, status ORDER BY c DESC LIMIT 20;" 2>/dev/null || echo "no queue_message table"

echo "=== import_history (recent) ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT history_id, started_at, finished_at, status, summary FROM import_history ORDER BY history_id DESC LIMIT 5;" 2>/dev/null || true

echo "=== cron www-data ==="
sudo crontab -u www-data -l 2>/dev/null || echo "no www-data crontab"

echo "=== magento cron status ==="
sudo -u www-data php "$MAGENTO/bin/magento cron:install 2>&1" || true
sudo crontab -u www-data -l 2>/dev/null || true

echo "=== setup db status ==="
sudo -u www-data php "$MAGENTO/bin/magento setup:db:status 2>&1" | head -30 || true

echo "=== export consumer test ==="
sudo -u www-data php "$MAGENTO/bin/magento queue:consumers:list 2>&1" | grep -i export || true
