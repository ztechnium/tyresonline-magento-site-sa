param(
  [string]$DbDumpPath = "",
  [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot

Set-Location $projectRoot

if (-not (Test-Path "app/etc/env.php.local.bak")) {
  Copy-Item "app/etc/env.php" "app/etc/env.php.local.bak"
}

if (-not $SkipBuild) {
  docker compose -f docker-compose.local.yml up -d --build
} else {
  docker compose -f docker-compose.local.yml up -d
}

# Wait for DB
$ready = $false
for ($i = 0; $i -lt 60; $i++) {
  try {
    docker compose -f docker-compose.local.yml exec -T db mysqladmin ping -h 127.0.0.1 -uroot -proot | Out-Null
    $ready = $true
    break
  } catch {
    Start-Sleep -Seconds 2
  }
}

if (-not $ready) {
  throw "MySQL did not become ready in time."
}

# Apply local env wiring

docker compose -f docker-compose.local.yml exec -T php php scripts/localize-env.php

if ($DbDumpPath -and (Test-Path $DbDumpPath)) {
  Write-Host "Importing DB dump: $DbDumpPath"
  Get-Content -Path $DbDumpPath -Raw | docker compose -f docker-compose.local.yml exec -T db mysql -umagento -pmagento tyresonline_sa
}

# Magento baseline commands

docker compose -f docker-compose.local.yml exec -T php php bin/magento setup:upgrade

docker compose -f docker-compose.local.yml exec -T php php bin/magento cache:flush

Write-Host "Local setup completed. Open http://localhost:18081"
