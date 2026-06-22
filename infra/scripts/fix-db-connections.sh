#!/bin/bash
set -euo pipefail

echo "=== Step 1: Remove bad exportProcessor cron (spawns new process every minute) ==="
CURRENT=$(sudo crontab -u www-data -l 2>/dev/null || true)
echo "$CURRENT" | grep -v 'exportProcessor' | sudo crontab -u www-data -

echo "=== Step 2: Kill stuck exportProcessor and hung magento PHP processes ==="
# Kill export consumer processes first
sudo pkill -f 'queue:consumers:start exportProcessor' || true
sleep 2
# Kill any remaining hung magento CLI older than a few minutes if still too many
COUNT=$(ps aux | grep -E 'php.*magento' | grep -v grep | wc -l)
echo "Remaining magento php processes: $COUNT"
if [ "$COUNT" -gt 20 ]; then
  sudo pkill -f 'php.*magento/bin/magento' || true
  sleep 2
fi

echo "=== Step 3: Verify cron ==="
sudo crontab -u www-data -l

echo "=== Step 4: Test DB connection ==="
for i in 1 2 3 4 5; do
  if mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -e 'SELECT 1 AS ok;' 2>/dev/null; then
    echo "DB OK on attempt $i"
    break
  fi
  echo "DB still busy, waiting 5s (attempt $i)..."
  sleep 5
done

echo "=== Step 5: Flush cache ==="
sudo -u www-data php /var/www/magento/bin/magento cache:flush 2>&1 | tail -3

echo "=== Step 6: HTTP check ==="
curl -s -o /dev/null -w 'frontend=%{http_code}\n' https://stg.tyresonline.sa/
curl -s -o /dev/null -w 'admin=%{http_code}\n' https://stg.tyresonline.sa/tyadmin

echo "=== DONE ==="
