#Requires -Version 5.1
<#
.SYNOPSIS
  Deploy KSA Magento codebase to the production EC2 instance.

.EXAMPLE
  .\deploy_to_aws_sa_prod.ps1 -Ec2Host ec2-xx-xx.eu-north-1.compute.amazonaws.com
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$Ec2Host,

    [string]$Ec2User = "ubuntu",
    [string]$Domain = "www.tyresonline.sa",
    [string]$PemFile = "C:\Users\khhab\OneDrive\Desktop\projects\TyresOnline\tyresonline-server\staging-key-tyresonline.pem",
    [string]$LocalDir = "C:\Users\khhab\OneDrive\Desktop\projects\TyresOnline\tyresonline-sa",
    [string]$DbDump = "",
    [string]$EnvFile = ""
)

$ErrorActionPreference = "Stop"

if (-not $DbDump) { $DbDump = Join-Path $LocalDir "tyresonline_sa_backup.sql" }
if (-not $EnvFile) { $EnvFile = Join-Path $LocalDir "app\etc\env.php.prod" }

Write-Host "=== KSA Production Deploy ===" -ForegroundColor Cyan
Write-Host "Target: $Ec2Host ($Domain)`n"

if (-not (Test-Path $PemFile)) { throw "PEM not found: $PemFile" }
if (-not (Test-Path $DbDump)) {
    Write-Host "DB dump missing. Create with local Docker:" -ForegroundColor Yellow
    Write-Host "docker exec tyresonline-sa-db mysqldump -umagento -pmagento tyresonline_sa > tyresonline_sa_backup.sql"
    throw "Missing DB dump: $DbDump"
}
if (-not (Test-Path $EnvFile)) { throw "Missing env file: $EnvFile (run provision-ksa-prod.ps1 first)" }

ssh -i $PemFile -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 "${Ec2User}@${Ec2Host}" "echo SSH OK" | Out-Null

Write-Host "[1/5] Upload DB backup..." -ForegroundColor Cyan
scp -C -i $PemFile $DbDump "${Ec2User}@${Ec2Host}:/tmp/tyresonline_sa_backup.sql"

Write-Host "[2/5] Upload env.php..." -ForegroundColor Cyan
scp -C -i $PemFile $EnvFile "${Ec2User}@${Ec2Host}:/tmp/env.php.prod"

Write-Host "[3/5] Package and upload Magento code..." -ForegroundColor Cyan
$tarPath = Join-Path $env:TEMP "magento_sa_code.tar.gz"
if (Test-Path $tarPath) { Remove-Item $tarPath -Force }

$exclude = @(
    "--exclude=./var/*",
    "--exclude=./generated/*",
    "--exclude=./pub/media/*",
    "--exclude=./.git",
    "--exclude=./node_modules"
)

Push-Location $LocalDir
tar -czf $tarPath @exclude .
Pop-Location

scp -C -i $PemFile $tarPath "${Ec2User}@${Ec2Host}:/tmp/magento_sa_code.tar.gz"

Write-Host "[4/5] Remote extract + import..." -ForegroundColor Cyan
$remote = @'
set -e
sudo mkdir -p /var/www/magento
sudo tar -xzf /tmp/magento_sa_code.tar.gz -C /var/www/magento
sudo cp /tmp/env.php.prod /var/www/magento/app/etc/env.php
sudo chown -R ubuntu:www-data /var/www/magento
sudo find /var/www/magento/var /var/www/magento/generated /var/www/magento/pub/static -type d -exec chmod 775 {} + 2>/dev/null || true
sudo find /var/www/magento/var /var/www/magento/generated /var/www/magento/pub/static -type f -exec chmod 664 {} + 2>/dev/null || true

RDS_HOST=$(php -r '$e=include "/var/www/magento/app/etc/env.php"; echo $e["db"]["connection"]["default"]["host"];')
DB_NAME=$(php -r '$e=include "/var/www/magento/app/etc/env.php"; echo $e["db"]["connection"]["default"]["dbname"];')
DB_USER=$(php -r '$e=include "/var/www/magento/app/etc/env.php"; echo $e["db"]["connection"]["default"]["username"];')
DB_PASS=$(php -r '$e=include "/var/www/magento/app/etc/env.php"; echo $e["db"]["connection"]["default"]["password"];')

mysql -h "$RDS_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null
mysql -h "$RDS_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/tyresonline_sa_backup.sql
'@

ssh -i $PemFile "${Ec2User}@${Ec2Host}" $remote

Write-Host "[5/5] Magento build..." -ForegroundColor Cyan
$build = @'
set -e
cd /var/www/magento
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f en_US ar_SA
php bin/magento indexer:reindex
php bin/magento cache:flush
php bin/magento deploy:mode:set production
'@

ssh -i $PemFile "${Ec2User}@${Ec2Host}" $build

Write-Host "`nDeploy complete. Test: https://$Domain/" -ForegroundColor Green
