#Requires -Version 5.1
<#
.SYNOPSIS
  Provision KSA production using AWS CLI only (no Terraform required).
#>
param(
    [string]$Region = "eu-north-1",
    [string]$Project = "tyresonline-sa-prod",
    [string]$AeDbId = "tyresonline-ae-stg-rds",
    [string]$KeyName = "staging-key-tyresonline",
    [string]$DbPassword = "",
    [string]$InstanceType = "t3.xlarge",
    [string]$DbClass = "db.t3.medium"
)

$ErrorActionPreference = "Stop"
aws sts get-caller-identity --region $Region | Out-Null

if (-not $DbPassword) {
    $DbPassword = Read-Host "RDS password for magento user" -AsSecureString
    $DbPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($DbPassword))
}

Write-Host "Discovering AE network from $AeDbId..." -ForegroundColor Cyan
$aeDb = aws rds describe-db-instances --region $Region --db-instance-identifier $AeDbId --output json | ConvertFrom-Json
$subnetGroup = $aeDb.DBInstances[0].DBSubnetGroup.DBSubnetGroupName
$vpcId = $aeDb.DBInstances[0].DBSubnetGroup.VpcId
$engineVersion = $aeDb.DBInstances[0].EngineVersion
$paramGroup = $aeDb.DBInstances[0].DBParameterGroups[0].DBParameterGroupName
$subnetIds = ($aeDb.DBInstances[0].DBSubnetGroup.Subnets | ForEach-Object { $_.SubnetIdentifier }) -join ","

Write-Host "VPC=$vpcId Subnets=$subnetIds" -ForegroundColor Green

function New-Sg {
    param($Name, $Desc, $IngressRules)
    $sgId = aws ec2 create-security-group --region $Region --group-name $Name --description $Desc --vpc-id $vpcId --query GroupId --output text
    foreach ($rule in $IngressRules) {
        aws ec2 authorize-security-group-ingress --region $Region --group-id $sgId @rule | Out-Null
    }
    aws ec2 authorize-security-group-egress --region $Region --group-id $sgId --ip-permissions "IpProtocol=-1,IpRanges=[{CidrIp=0.0.0.0/0}]" 2>$null | Out-Null
    return $sgId
}

Write-Host "Creating security groups..." -ForegroundColor Cyan
$ec2Sg = New-Sg "$Project-ec2-sg" "KSA prod EC2" @(
    @{ IpPermissions = "IpProtocol=tcp,FromPort=80,ToPort=80,IpRanges=[{CidrIp=0.0.0.0/0}]" },
    @{ IpPermissions = "IpProtocol=tcp,FromPort=443,ToPort=443,IpRanges=[{CidrIp=0.0.0.0/0}]" },
    @{ IpPermissions = "IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges=[{CidrIp=0.0.0.0/0}]" }
)
$rdsSg = New-Sg "$Project-rds-sg" "KSA prod RDS" @(
    @{ IpPermissions = "IpProtocol=tcp,FromPort=3306,ToPort=3306,UserIdGroupPairs=[{GroupId=$ec2Sg}]" }
)
$redisSg = New-Sg "$Project-redis-sg" "KSA prod Redis" @(
    @{ IpPermissions = "IpProtocol=tcp,FromPort=6379,ToPort=6379,UserIdGroupPairs=[{GroupId=$ec2Sg}]" }
)

Write-Host "Creating RDS $Project..." -ForegroundColor Cyan
aws rds create-db-instance `
    --region $Region `
    --db-instance-identifier $Project `
    --db-instance-class $DbClass `
    --engine mysql `
    --engine-version $engineVersion `
    --master-username magento `
    --master-user-password $DbPassword `
    --allocated-storage 100 `
    --storage-type gp3 `
    --db-name tyresonline_sa `
    --vpc-security-group-ids $rdsSg `
    --db-subnet-group-name $subnetGroup `
    --backup-retention-period 7 `
    --no-publicly-accessible `
    --db-parameter-group-name $paramGroup | Out-Null

Write-Host "Creating ElastiCache ${Project}-redis..." -ForegroundColor Cyan
$redisSubnet = aws elasticache describe-cache-subnet-groups --region $Region --query "CacheSubnetGroups[?VpcId=='$vpcId']|[0].CacheSubnetGroupName" --output text
if ($redisSubnet -eq "None" -or -not $redisSubnet) {
    aws elasticache create-cache-subnet-group --region $Region --cache-subnet-group-name "$Project-redis-subnet" --cache-subnet-group-description "KSA prod redis" --subnet-ids $subnetIds.Split(",") | Out-Null
    $redisSubnet = "$Project-redis-subnet"
}

aws elasticache create-cache-cluster `
    --region $Region `
    --cache-cluster-id "${Project}-redis" `
    --engine redis `
    --cache-node-type cache.t3.micro `
    --num-cache-nodes 1 `
    --cache-subnet-group-name $redisSubnet `
    --security-group-ids $redisSg | Out-Null

Write-Host "Launching EC2..." -ForegroundColor Cyan
$ami = aws ec2 describe-images --region $Region --owners 099720109477 `
    --filters "Name=name,Values=ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*" "Name=state,Values=available" `
    --query "sort_by(Images,&CreationDate)[-1].ImageId" --output text

$bootstrap = Get-Content (Join-Path $PSScriptRoot "bootstrap-ec2-sa-prod.sh") -Raw
$bootstrap = $bootstrap -replace '\$\{domain_name\}', 'www.tyresonline.sa'
$bootstrapB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($bootstrap))

$instanceId = aws ec2 run-instances --region $Region `
    --image-id $ami `
    --instance-type $InstanceType `
    --key-name $KeyName `
    --security-group-ids $ec2Sg `
    --subnet-id $($subnetIds.Split(",")[0]) `
    --block-device-mappings "DeviceName=/dev/sda1,Ebs={VolumeSize=200,VolumeType=gp3,DeleteOnTermination=true}" `
    --user-data $bootstrapB64 `
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=${Project}-ec2},{Key=Project,Value=tyresonline},{Key=Market,Value=sa},{Key=Environment,Value=production}]" `
    --query Instances[0].InstanceId --output text

aws ec2 create-tags --region $Region --resources $instanceId --tags Key=Name,Value="${Project}-ec2" | Out-Null
aws ec2 wait instance-running --region $Region --instance-ids $instanceId
$publicDns = aws ec2 describe-instances --region $Region --instance-ids $instanceId --query "Reservations[0].Instances[0].PublicDnsName" --output text
$publicIp = aws ec2 describe-instances --region $Region --instance-ids $instanceId --query "Reservations[0].Instances[0].PublicIpAddress" --output text

Write-Host "`nProvision started. Resources may take 10-15 min to become available." -ForegroundColor Green
Write-Host "EC2: $publicDns ($publicIp)"
Write-Host "RDS: $Project (wait for available)"
Write-Host "Redis: ${Project}-redis (wait for available)"
Write-Host "`nWhen ready, run deploy_to_aws_sa_prod.ps1 -Ec2Host $publicDns"
