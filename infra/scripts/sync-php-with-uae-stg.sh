#!/bin/bash
# Align KSA staging PHP runtime with UAE (Emirati) staging server.
# Both use PHP 8.3 + Apache mod_php; this syncs php.ini/opcache tuning.
set -euo pipefail

PHP_INI=/etc/php/8.3/apache2/php.ini
PHP_CLI_INI=/etc/php/8.3/cli/php.ini
OPCACHE_APACHE=/etc/php/8.3/apache2/conf.d/10-opcache.ini
OPCACHE_CLI=/etc/php/8.3/cli/conf.d/10-opcache.ini

echo "=== Before ==="
php -v | head -1
grep -E '^memory_limit|^max_execution_time|^error_reporting' "$PHP_INI" | head -5

echo "=== Pin PHP 8.3 ==="
update-alternatives --set php /usr/bin/php8.3 2>/dev/null || true
a2dismod php8.2 2>/dev/null || true
a2enmod php8.3 2>/dev/null || true

echo "=== Match UAE opcache drop-in ==="
cat >"$OPCACHE_APACHE" <<'INI'
; configuration for php opcache module (mirrors UAE staging)
; priority=10
zend_extension=opcache.so
opcache.jit=off
opcache.max_wasted_percentage=10
INI

# CLI may share the same opcache drop-in via symlink on Ubuntu 24.04.

patch_ini() {
  local file="$1"
  sed -i 's/^memory_limit = .*/memory_limit = 1536M/' "$file"
  sed -i 's/^max_execution_time = .*/max_execution_time = 600/' "$file"
  sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_STRICT/' "$file"
  if ! grep -q '^realpath_cache_size' "$file"; then
    echo 'realpath_cache_size = 32M' >> "$file"
  else
    sed -i 's/^realpath_cache_size = .*/realpath_cache_size = 32M/' "$file"
  fi
  if ! grep -q '^opcache.enable=' "$file"; then
    cat >>"$file" <<'INI'

[opcache]
opcache.enable=1
opcache.memory_consumption=512
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
INI
  fi
}

echo "=== Match UAE php.ini limits ==="
patch_ini "$PHP_INI"
patch_ini "$PHP_CLI_INI"

echo "=== Magento .user.ini (same as UAE) ==="
MAGENTO_USER_INI=/var/www/magento/.user.ini
cat >"$MAGENTO_USER_INI" <<'INI'
memory_limit = 756M
max_execution_time = 18000
session.auto_start = off
suhosin.session.cryptua = off
INI
chown www-data:www-data "$MAGENTO_USER_INI" 2>/dev/null || true

echo "=== Restart Apache ==="
apache2ctl configtest
systemctl restart apache2

echo "=== After ==="
php -v | head -1
/usr/sbin/php8.3 -i 2>/dev/null | grep -E 'memory_limit|max_execution_time' | head -2
grep -E '^memory_limit|^max_execution_time' "$PHP_INI" | head -3
echo DONE
