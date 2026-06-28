#Requires -Version 5.1
<#
.SYNOPSIS
  Phase 0 safety snapshots for KSA staging (EC2 AMI + RDS snapshot).

.EXAMPLE
  aws login
  cd infra/scripts
  .\create-ksa-snapshots.ps1
#>
param(
    [string]$Region = "eu-north-1",
    [string]$Ec2NameTag = "tyresonline-sa-prod-ec2",
    [string]$RdsInstance = "tyresonline-sa-prod",
    [string]$Label = "ksa-stg-before-enhancements"
)

$ErrorActionPreference = "Stop"
$stamp = Get-Date -Format "yyyyMMdd-HHmm"

aws sts get-caller-identity | Out-Null
if ($LASTEXITCODE -ne 0) { throw "Run 'aws login' first." }

$instanceId = aws ec2 describe-instances `
    --region $Region `
    --filters "Name=tag:Name,Values=$Ec2NameTag" "Name=instance-state-name,Values=running" `
    --query "Reservations[0].Instances[0].InstanceId" `
    --output text

if (-not $instanceId -or $instanceId -eq "None") {
    throw "Could not find running EC2 instance with tag Name=$Ec2NameTag"
}

$amiName = "$Label-ami-$stamp"
Write-Host "Creating AMI $amiName from $instanceId ..." -ForegroundColor Cyan
$amiId = aws ec2 create-image `
    --region $Region `
    --instance-id $instanceId `
    --name $amiName `
    --description "KSA staging safety snapshot before enhancements" `
    --no-reboot `
    --query ImageId `
    --output text
Write-Host "AMI: $amiId" -ForegroundColor Green

$dbSnap = "$Label-rds-$stamp"
Write-Host "Creating RDS snapshot $dbSnap ..." -ForegroundColor Cyan
aws rds create-db-snapshot `
    --region $Region `
    --db-instance-identifier $RdsInstance `
    --db-snapshot-identifier $dbSnap | Out-Null
Write-Host "RDS snapshot: $dbSnap" -ForegroundColor Green

Write-Host ""
Write-Host "=== Phase 0 snapshots started ===" -ForegroundColor Green
Write-Host "AMI: $amiId ($amiName)"
Write-Host "RDS: $dbSnap"
Write-Host "Check AWS console until both show 'available'."
