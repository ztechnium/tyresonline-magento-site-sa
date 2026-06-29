#!/bin/bash
# Phase 0 safety + fix for ElasticSuite is_invalid export error on KSA staging.
#
# Prerequisites (run from your laptop or a bastion with access):
#   - aws CLI configured (aws sts get-caller-identity works)
#   - SSH key: staging-key-tyresonline.pem
#   - EC2 security group allows SSH from your IP
#
# Usage:
#   export AWS_REGION=eu-north-1
#   export EC2_HOST=ec2-16-170-225-52.eu-north-1.compute.amazonaws.com
#   export SSH_KEY=~/staging-key-tyresonline.pem
#   bash infra/scripts/fix-export-with-snapshots.sh

set -euo pipefail

AWS_REGION="${AWS_REGION:-eu-north-1}"
EC2_NAME_TAG="${EC2_NAME_TAG:-tyresonline-sa-prod-ec2}"
RDS_INSTANCE="${RDS_INSTANCE:-tyresonline-sa-prod}"
EC2_HOST="${EC2_HOST:-ec2-16-170-202-188.eu-north-1.compute.amazonaws.com}"
SSH_USER="${SSH_USER:-ubuntu}"
SSH_KEY="${SSH_KEY:-staging-key-tyresonline.pem}"
MAGENTO="${MAGENTO:-/var/www/magento}"
LABEL="${LABEL:-ksa-stg-before-export-fix}"
STAMP="$(date +%Y%m%d-%H%M)"
AMI_NAME="${LABEL}-ami-${STAMP}"
RDS_SNAP="${LABEL}-rds-${STAMP}"

die() { echo "ERROR: $*" >&2; exit 1; }

command -v aws >/dev/null || die "aws CLI not found. Install and run 'aws configure' or 'aws login'."
[[ -f "$SSH_KEY" ]] || die "SSH key not found: $SSH_KEY"

echo "=== 0. Verify AWS identity ==="
aws sts get-caller-identity --region "$AWS_REGION"

echo "=== 1. Resolve EC2 instance ==="
INSTANCE_ID="$(aws ec2 describe-instances \
  --region "$AWS_REGION" \
  --filters "Name=tag:Name,Values=${EC2_NAME_TAG}" "Name=instance-state-name,Values=running" \
  --query "Reservations[0].Instances[0].InstanceId" \
  --output text)"
[[ -n "$INSTANCE_ID" && "$INSTANCE_ID" != "None" ]] || die "Running EC2 not found for tag Name=${EC2_NAME_TAG}"
echo "EC2 instance: $INSTANCE_ID"

echo "=== 2. Create EC2 AMI snapshot (no reboot) ==="
AMI_ID="$(aws ec2 create-image \
  --region "$AWS_REGION" \
  --instance-id "$INSTANCE_ID" \
  --name "$AMI_NAME" \
  --description "Safety snapshot before ElasticSuite is_invalid export fix" \
  --no-reboot \
  --query ImageId \
  --output text)"
echo "AMI started: $AMI_ID ($AMI_NAME)"

echo "=== 3. Create RDS snapshot ==="
aws rds create-db-snapshot \
  --region "$AWS_REGION" \
  --db-instance-identifier "$RDS_INSTANCE" \
  --db-snapshot-identifier "$RDS_SNAP" >/dev/null
echo "RDS snapshot started: $RDS_SNAP"

echo "=== 4. Wait for RDS snapshot (available) ==="
aws rds wait db-snapshot-available \
  --region "$AWS_REGION" \
  --db-snapshot-identifier "$RDS_SNAP"
echo "RDS snapshot available: $RDS_SNAP"

echo "=== 5. Table-level DB backup on server ==="
REMOTE_BACKUP="/tmp/elasticsuite_tracker_log_event_${STAMP}.sql"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new "${SSH_USER}@${EC2_HOST}" bash -s <<REMOTE
set -euo pipefail
cd "$MAGENTO"
read_env() { php -r '\$e=include "app/etc/env.php"; echo \$e["db"]["connection"]["default"]["'"'\$1'"'"];'; }
DB_HOST="\$(read_env host)"
DB_USER="\$(read_env username)"
DB_PASS="\$(read_env password)"
DB_NAME="\$(read_env dbname)"
mysqldump -h "\$DB_HOST" -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME" elasticsuite_tracker_log_event > "$REMOTE_BACKUP"
ls -lh "$REMOTE_BACKUP"
REMOTE
echo "Table backup: $REMOTE_BACKUP (on EC2)"

echo "=== 6. Apply fix (setup:upgrade, fallback ALTER, verify) ==="
ssh -i "$SSH_KEY" "${SSH_USER}@${EC2_HOST}" bash -s <<'REMOTE'
set -euo pipefail
MAGENTO=/var/www/magento
cd "$MAGENTO"

read_env() { php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["'"$1"'"];'; }
DB_HOST="$(read_env host)"
DB_USER="$(read_env username)"
DB_PASS="$(read_env password)"
DB_NAME="$(read_env dbname)"

has_column() {
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema='$DB_NAME'
       AND table_name='elasticsuite_tracker_log_event'
       AND column_name='is_invalid';"
}

echo "--- setup:upgrade ---"
sudo -u www-data php bin/magento setup:upgrade --keep-generated 2>&1 | tail -30

if [[ "$(has_column)" != "1" ]]; then
  echo "--- column still missing; applying ALTER TABLE fallback ---"
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'SQL'
ALTER TABLE elasticsuite_tracker_log_event
  ADD COLUMN is_invalid SMALLINT NOT NULL DEFAULT 0 COMMENT 'Has invalid data' AFTER data;
SQL
fi

HAS_INDEX=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
  "SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema='$DB_NAME'
     AND table_name='elasticsuite_tracker_log_event'
     AND index_name='ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID';")
if [[ "$HAS_INDEX" == "0" ]]; then
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
    "ALTER TABLE elasticsuite_tracker_log_event ADD INDEX ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID (is_invalid);"
fi

echo "--- verify exact failing query ---"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
  "SELECT COUNT(*) AS count FROM elasticsuite_tracker_log_event WHERE is_invalid = 1;"

echo "--- cache flush ---"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -5

if [[ "$(has_column)" == "1" ]]; then
  echo "FIX_OK: is_invalid column exists"
else
  echo "FIX_FAILED: is_invalid column still missing" >&2
  exit 1
fi
REMOTE

echo ""
echo "=== DONE ==="
echo "AMI:  $AMI_ID ($AMI_NAME)"
echo "RDS:  $RDS_SNAP"
echo "Next: reload Admin -> System -> Export and confirm the blue SQL error is gone."
