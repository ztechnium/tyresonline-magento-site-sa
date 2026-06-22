param(
  [string]$PemPath = "C:\Users\khhab\Downloads\staging-key-tyresonline.pem",
  [string]$SshHost = "ubuntu@stg.tyresonline.ztechnium.com"
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$remotePhp = @'
$env = include "/var/www/magento/app/etc/env.php";
$db = $env["db"]["connection"]["default"];
echo $db["host"] . "|" . $db["dbname"] . "|" . $db["username"] . "|" . $db["password"];
'@

$dbInfo = ssh -i $PemPath $SshHost "php -r '$remotePhp'"
$parts = $dbInfo.Trim().Split('|')
if ($parts.Count -lt 4) {
  throw "Could not fetch DB credentials from staging env.php"
}

$dbHost = $parts[0]
$dbName = $parts[1]
$dbUser = $parts[2]
$dbPass = $parts[3]

Write-Host "Streaming DB dump from staging into local container..."
$dumpCmd = "mysqldump -h$dbHost -u$dbUser -p$dbPass --single-transaction --quick --routines --triggers --no-tablespaces --set-gtid-purged=OFF $dbName"

docker compose -f docker-compose.local.yml exec -T db mysql -uroot -proot -e "SET GLOBAL log_bin_trust_function_creators=1;"
ssh -i $PemPath $SshHost $dumpCmd |
  ForEach-Object { $_ -replace 'DEFINER=`[^`]+`@`[^`]+`', '' } |
  docker compose -f docker-compose.local.yml exec -T db mysql -umagento -pmagento tyresonline_sa

Write-Host "DB sync completed."
