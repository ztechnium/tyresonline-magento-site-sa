#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
P1=$(openssl rand -base64 18 | tr -d '/+=' | head -c 14)
P2=$(openssl rand -base64 18 | tr -d '/+=' | head -c 14)
P1="${P1}Ty1!"
P2="${P2}Ty1!"

create_user() {
  local user=$1 email=$2 first=$3 last=$4 pass=$5
  if sudo -u www-data php "$MAGENTO/bin/magento" admin:user:create \
    --admin-user="$user" \
    --admin-password="$pass" \
    --admin-email="$email" \
    --admin-firstname="$first" \
    --admin-lastname="$last" 2>&1; then
    echo "CREATED $user ($email)"
    echo "PASSWORD_$user=$pass"
  else
    echo "FAILED_OR_EXISTS $user ($email)" >&2
  fi
}

create_user nour.abdelhamid nour.abdelhamid@tyresonline.com Nour Abdelhamid "$P1"
create_user mohamed.elbaz mohamed.elbaz@tyresonline.com Mohamed Elbaz "$P2"

echo "--- verify ---"
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -N -e \
  "SELECT u.username, u.email, u.is_active, r.role_name FROM admin_user u LEFT JOIN authorization_role r ON r.user_id=u.user_id AND r.role_type='U' WHERE u.email IN ('nour.abdelhamid@tyresonline.com','mohamed.elbaz@tyresonline.com');"
