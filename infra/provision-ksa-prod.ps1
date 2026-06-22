#Requires -Version 5.1
<#
.SYNOPSIS
  Provision KSA production infrastructure in eu-north-1 (clone of AE stack).

.EXAMPLE
  .\provision-ksa-prod.ps1
  .\provision-ksa-prod.ps1 -ApplyOnly
#>
param(
    [switch]$ApplyOnly,
    [switch]$SkipDeploy
)

$ErrorActionPreference = "Stop"
$InfraDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$TfDir = Join-Path $InfraDir "terraform"
$SaRoot = Split-Path -Parent $InfraDir
$Region = "eu-north-1"

Write-Host "=== TyresOnline KSA Production Infra ===" -ForegroundColor Cyan
Write-Host "Region: $Region`n"

if (-not $ApplyOnly) {
    Write-Host "[1/4] AWS auth check..." -ForegroundColor Cyan
    try {
        aws sts get-caller-identity --region $Region | Out-Null
    } catch {
        Write-Host "AWS session expired. Run: aws login" -ForegroundColor Red
        exit 1
    }

    Write-Host "[2/4] Discover AE reference resources..." -ForegroundColor Cyan
    & (Join-Path $InfraDir "scripts\discover-ae-network.ps1") -Region $Region

    if (-not (Test-Path (Join-Path $TfDir "terraform.tfvars"))) {
        Copy-Item (Join-Path $TfDir "terraform.tfvars.example") (Join-Path $TfDir "terraform.tfvars")
        Write-Host "Created terraform.tfvars — set db_password before continuing." -ForegroundColor Yellow
        exit 1
    }
}

Write-Host "[3/4] Terraform apply..." -ForegroundColor Cyan
Push-Location $TfDir
terraform init -input=false
terraform plan -out tfplan
terraform apply -auto-approve tfplan

$ec2Dns = terraform output -raw ec2_public_dns
$rdsHost = terraform output -raw rds_endpoint
$redisHost = terraform output -raw redis_endpoint
Pop-Location

Write-Host "[4/4] Generate production env.php..." -ForegroundColor Cyan
$envProd = Join-Path $SaRoot "app\etc\env.php.prod"
php (Join-Path $InfraDir "scripts\generate-env-php.php") `
    --source=(Join-Path $SaRoot "app\etc\env.php") `
    --output=$envProd `
    --rds-host=$rdsHost `
    --redis-host=$redisHost

Write-Host "`nProvisioned successfully." -ForegroundColor Green
Write-Host "EC2: $ec2Dns"
Write-Host "RDS: $rdsHost"
Write-Host "Redis: $redisHost"
Write-Host "env.php.prod: $envProd"

if (-not $SkipDeploy) {
    Write-Host "`nNext: run deploy_to_aws_sa_prod.ps1 -Ec2Host $ec2Dns" -ForegroundColor Yellow
}
