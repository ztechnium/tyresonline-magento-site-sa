#!/bin/bash
sudo -u www-data php /var/www/magento/bin/magento store:website:list
echo '--- env.php ---'
grep -i hyperpay /var/www/magento/app/etc/env.php || echo 'not in env.php'
echo '--- DB ---'
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa <<'SQL'
SELECT path, CASE WHEN path LIKE '%auth%' THEN LEFT(value,20) ELSE value END AS val, scope, scope_id
FROM core_config_data
WHERE path LIKE 'payment/hyperpay%' OR path LIKE 'payment/HyperPay%' OR path='payment/tabby_installments/active'
ORDER BY path, scope, scope_id;
SQL
