#!/bin/bash
cd /var/www/magento
echo "=== onestepcheckout enabled ==="
sudo -u www-data php bin/magento config:show one_step_checkout/general/enabled 2>/dev/null || \
sudo -u www-data php bin/magento config:show ecomteck_onestepcheckout/general/enabled 2>/dev/null || \
echo "SELECT path,value FROM core_config_data WHERE path LIKE '%onestep%' OR path LIKE '%one_step%';" | mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa 2>/dev/null | grep -v Warning

echo "=== co_full main column ==="
grep -oP '(?<=column main">).{0,2000}' /tmp/co_full.html | head -c 2500

echo
echo "=== checkoutConfig in page ==="
grep -c checkoutConfig /tmp/co_full.html
grep checkoutConfig /tmp/co_full.html | head -c 200
