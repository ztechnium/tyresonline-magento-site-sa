param(
  [string]$PemPath = "C:\Users\khhab\Downloads\staging-key-tyresonline.pem",
  [string]$SshHost = "ubuntu@stg.tyresonline.ztechnium.com"
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$mediaPath = Join-Path $projectRoot 'pub/media'

if (-not (Test-Path $mediaPath)) {
  New-Item -ItemType Directory -Path $mediaPath | Out-Null
}

Write-Host "Syncing media from staging..."
$cmd = "sudo tar -C /var/www/magento/pub/media --exclude='cache' --exclude='tmp' --exclude='catalog/product/cache' -cf - ."
ssh -i $PemPath $SshHost $cmd | tar -xf - -C $mediaPath
Write-Host "Media sync completed."
