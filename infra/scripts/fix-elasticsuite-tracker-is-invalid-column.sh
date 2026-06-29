#!/bin/bash
set -euo pipefail

MAGENTO="${MAGENTO:-/var/www/magento}"
cd "$MAGENTO"

read_env() {
  php -r '$e = include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["'"$1"'"];'
}

DB_HOST="$(read_env host)"
DB_USER="$(read_env username)"
DB_PASS="$(read_env password)"
DB_NAME="$(read_env dbname)"

echo "=== Check elasticsuite_tracker_log_event.is_invalid column ==="
HAS_COLUMN=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
  "SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = '$DB_NAME'
     AND table_name = 'elasticsuite_tracker_log_event'
     AND column_name = 'is_invalid';")

if [ "$HAS_COLUMN" = "1" ]; then
  echo "Column is_invalid already exists"
else
  echo "Adding missing is_invalid column"
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'SQL'
ALTER TABLE elasticsuite_tracker_log_event
  ADD COLUMN is_invalid SMALLINT NOT NULL DEFAULT 0 COMMENT 'Has invalid data' AFTER data;
SQL

  HAS_INDEX=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
    "SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = '$DB_NAME'
       AND table_name = 'elasticsuite_tracker_log_event'
       AND index_name = 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID';")

  if [ "$HAS_INDEX" = "0" ]; then
    echo "Adding missing is_invalid index"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
      "ALTER TABLE elasticsuite_tracker_log_event ADD INDEX ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID (is_invalid);"
  fi
fi

echo "=== Apply declarative schema via setup:upgrade ==="
sudo -u www-data php "$MAGENTO/bin/magento" setup:upgrade --keep-generated 2>&1 | tail -20

echo "=== Verify invalid events query ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
  "SELECT COUNT(*) AS invalid_events FROM elasticsuite_tracker_log_event WHERE is_invalid = 1;"

echo "=== DONE ==="
