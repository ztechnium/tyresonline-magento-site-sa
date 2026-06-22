#Requires -Version 5.1
<#
.SYNOPSIS
  Discover AE staging AWS resources and print Terraform reference values.
#>
param(
    [string]$Region = "eu-north-1",
    [string]$AeDbId = "tyresonline-ae-stg-rds",
    [string]$AeRedisId = "tyresonline-ae-stg-redis"
)

$ErrorActionPreference = "Stop"

Write-Host "Checking AWS credentials..." -ForegroundColor Cyan
aws sts get-caller-identity --region $Region | Out-Null

Write-Host "`nAE RDS ($AeDbId):" -ForegroundColor Green
aws rds describe-db-instances `
    --region $Region `
    --db-instance-identifier $AeDbId `
    --query "DBInstances[0].{Class:DBInstanceClass,Engine:Engine,Version:EngineVersion,Storage:AllocatedStorage,SubnetGroup:DBSubnetGroup.DBSubnetGroupName,Vpc:DBSubnetGroup.VpcId}" `
    --output table

Write-Host "`nAE Redis ($AeRedisId):" -ForegroundColor Green
aws elasticache describe-cache-clusters `
    --region $Region `
    --cache-cluster-id $AeRedisId `
    --show-cache-node-info `
    --query "CacheClusters[0].{NodeType:CacheNodeType,Engine:Engine,Nodes:CacheNodes}" `
    --output table

Write-Host "`nAE EC2 instances (tyresonline):" -ForegroundColor Green
aws ec2 describe-instances `
    --region $Region `
    --filters "Name=tag:Project,Values=tyresonline" "Name=instance-state-name,Values=running" `
    --query "Reservations[].Instances[].{Id:InstanceId,Type:InstanceType,IP:PublicIpAddress,Name:Tags[?Key=='Name']|[0].Value}" `
    --output table

Write-Host "`nCopy values into infra/terraform/terraform.tfvars and run terraform apply." -ForegroundColor Yellow
