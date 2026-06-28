#Requires -Version 5.1
<#
.SYNOPSIS
  Provision KSA CDN: ACM cert, CloudFront, S3 sync, Magento URLs.
  Prerequisite: aws login (account 681637098506)

.EXAMPLE
  cd infra
  aws login
  .\provision-cdn-ksa.ps1
#>
param(
    [string]$CdnDomain = "cdn.tyresonline.sa",
    [string]$OriginDomain = "stg.tyresonline.sa",
    [string]$Bucket = "tyresonline-sa-prod-media",
    [string]$TerraformDir = "$PSScriptRoot\terraform",
    [string]$SshKey = "C:\Users\khhab\OneDrive\Desktop\projects\TyresOnline\tyresonline-server\staging-key-tyresonline.pem",
    [string]$Ec2Host = "ubuntu@ec2-16-170-202-188.eu-north-1.compute.amazonaws.com",
    [switch]$SkipTerraform,
    [switch]$SkipMediaSync,
    [switch]$SkipMagento
)

$ErrorActionPreference = "Stop"

function Require-Aws {
    aws sts get-caller-identity | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Run 'aws login' first." }
}

Require-Aws

if (-not $SkipTerraform) {
    Write-Host "=== Step 1: ACM certificate (us-east-1) ===" -ForegroundColor Cyan
    Push-Location $TerraformDir
    terraform init -input=false | Out-Null
    terraform apply -auto-approve `
        -target=aws_acm_certificate.cdn `
        -target=aws_iam_role.ec2_s3_media `
        -target=aws_iam_role_policy.ec2_s3_media `
        -target=aws_iam_instance_profile.ec2_s3_media `
        -var="cdn_domain_name=$CdnDomain" `
        -var="cdn_origin_domain=$OriginDomain"

    Write-Host ""
    Write-Host "=== ADD THESE DNS RECORDS ON https://dnet.sa (tyresonline.sa zone) ===" -ForegroundColor Yellow
    terraform output -json acm_dns_validation | Write-Host
    Write-Host ""
    $reply = Read-Host "Press Enter after ACM validation CNAME is added on dnet.sa and ACM shows Issued (or wait 5 min)"

    Write-Host "=== Step 2: CloudFront + S3 policy + EC2 IAM ===" -ForegroundColor Cyan
    terraform apply -auto-approve `
        -var="cdn_domain_name=$CdnDomain" `
        -var="cdn_origin_domain=$OriginDomain"

    Write-Host ""
    Write-Host "=== ADD CDN CNAME ON dnet.sa ===" -ForegroundColor Yellow
    terraform output dnet_sa_dns_records
    Pop-Location
}

$cfDomain = terraform -chdir=$TerraformDir output -raw cloudfront_domain_name
Write-Host "CloudFront domain: $cfDomain" -ForegroundColor Green

if (-not $SkipMediaSync) {
    Write-Host "=== Step 3: Sync media to S3 ===" -ForegroundColor Cyan
    scp -i $SshKey -o StrictHostKeyChecking=no "$PSScriptRoot\scripts\sync-ksa-media-to-s3.sh" "${Ec2Host}:/tmp/sync-ksa-media-to-s3.sh"
    scp -i $SshKey -o StrictHostKeyChecking=no "$PSScriptRoot\scripts\configure-magento-cdn.php" "${Ec2Host}:/tmp/configure-magento-cdn.php"
    ssh -i $SshKey -o StrictHostKeyChecking=no $Ec2Host @"
chmod +x /tmp/sync-ksa-media-to-s3.sh
S3_BUCKET=$Bucket /tmp/sync-ksa-media-to-s3.sh
"@
}

if (-not $SkipMagento) {
    Write-Host "=== Step 4: Configure Magento CDN URLs ===" -ForegroundColor Cyan
    ssh -i $SshKey -o StrictHostKeyChecking=no $Ec2Host "cd /var/www/magento && sudo -u www-data php /tmp/configure-magento-cdn.php --cdn=https://$CdnDomain"
}

Write-Host ""
Write-Host "=== DONE ===" -ForegroundColor Green
Write-Host "Test: https://$CdnDomain/media/"
Write-Host "Test: https://$CdnDomain/static/version*/frontend/Hditsol/tyresonline/en_US/requirejs/require.min.js"
Write-Host "Site unchanged: https://$OriginDomain/en/"
