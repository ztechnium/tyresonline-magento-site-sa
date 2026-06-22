#!/bin/bash
# EC2 user-data bootstrap for KSA production Magento (mirrors AE staging stack)
set -euo pipefail

LOG=/var/log/tyresonline-sa-bootstrap.log

exec > >(tee -a "$LOG") 2>&1
echo "=== KSA prod bootstrap started $(date -Is) domain=${domain_name} ==="

export DEBIAN_FRONTEND=noninteractive

apt-get update -y
apt-get install -y \
  apache2 \
  php \
  php-cli \
  php-mysql \
  php-gd \
  php-xml \
  php-curl \
  php-zip \
  php-bcmath \
  php-intl \
  php-mbstring \
  php-soap \
  php-xsl \
  php-opcache \
  unzip \
  curl \
  gnupg \
  apt-transport-https \
  composer \
  git \
  redis-tools

a2enmod rewrite headers ssl proxy proxy_http

mkdir -p /var/www/magento
chown -R ubuntu:ubuntu /var/www/magento

cat >/etc/apache2/sites-available/tyresonline-sa-prod.conf <<APACHE
<VirtualHost *:80>
    ServerName ${domain_name}
    ServerAlias tyresonline.sa
    DocumentRoot /var/www/magento/pub

    <Directory /var/www/magento/pub>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog $${APACHE_LOG_DIR}/magento-sa-error.log
    CustomLog $${APACHE_LOG_DIR}/magento-sa-access.log combined
</VirtualHost>
APACHE

a2dissite 000-default.conf || true
a2ensite tyresonline-sa-prod.conf
apache2ctl configtest
systemctl enable apache2
systemctl restart apache2

# OpenSearch on-box (same pattern as AE staging)
if ! command -v opensearch >/dev/null 2>&1; then
  curl -o /tmp/opensearch.deb https://artifacts.opensearch.org/releases/bundle/opensearch/2.11.1/opensearch-2.11.1-linux-x64.deb
  env OPENSEARCH_INITIAL_ADMIN_PASSWORD='TyresOnline#SaProd2026!' dpkg -i /tmp/opensearch.deb || true

  sed -i 's/plugins.security.disabled: false/plugins.security.disabled: true/' /etc/opensearch/opensearch.yml || true
  sed -i "s/cluster.name: .*/cluster.name: opensearch-sa-prod/" /etc/opensearch/opensearch.yml || true
  sed -i "s/network.host: .*/network.host: 127.0.0.1/" /etc/opensearch/opensearch.yml || true

  systemctl enable opensearch
  systemctl restart opensearch || true
fi

cat >/etc/cron.d/magento-sa <<'CRON'
*/5 * * * * ubuntu cd /var/www/magento && /usr/bin/php bin/magento cron:run 2>&1 | grep -v "Ran jobs by schedule" >> /var/www/magento/var/log/magento.cron.log
CRON
chmod 644 /etc/cron.d/magento-sa

echo "=== KSA prod bootstrap finished $(date -Is) ==="
