# TyresOnline KSA Production Infrastructure

Mirrors the **AE staging stack** in **eu-north-1** (Stockholm), per decision to avoid Middle East regions.

## What gets created

| Resource | Name | Notes |
|----------|------|-------|
| EC2 | `tyresonline-sa-prod-ec2` | t3.xlarge, Ubuntu 24.04, Apache, PHP 8.3, OpenSearch on-box |
| RDS MySQL | `tyresonline-sa-prod` | Database `tyresonline_sa` |
| ElastiCache Redis | `tyresonline-sa-prod-redis` | Sessions DB 2, cache DB 3, page cache DB 4 |
| S3 | `tyresonline-sa-prod-media` | Media bucket |
| Security groups | EC2 / RDS / Redis | EC2-only access to data tier |

Network placement is cloned from existing AE resources:
- `tyresonline-ae-stg-rds`
- `tyresonline-ae-stg-redis`

## Prerequisites

1. **AWS CLI** authenticated (`aws login` or access keys)
2. **Terraform** >= 1.5
3. **SSH key** `staging-key-tyresonline.pem` already uploaded to AWS as key pair `staging-key-tyresonline`
4. **PHP** locally (for `generate-env-php.php`)

## Quick start

```powershell
cd C:\Users\khhab\OneDrive\Desktop\projects\TyresOnline\tyresonline-sa\infra

# 1) Authenticate
aws login

# 2) Provision AWS resources
.\provision-ksa-prod.ps1

# 3) Create DB dump from local SA Docker (if not exists)
cd ..
docker exec tyresonline-sa-db mysqldump -umagento -pmagento tyresonline_sa > tyresonline_sa_backup.sql

# 4) Deploy Magento
cd infra
.\deploy_to_aws_sa_prod.ps1 -Ec2Host <ec2-dns-from-terraform-output>

# 5) DNS + SSL
# Point www.tyresonline.sa to EC2 public IP
# SSH in and run: sudo certbot --apache -d www.tyresonline.sa -d tyresonline.sa
```

## Manual Terraform

```powershell
cd infra\terraform
copy terraform.tfvars.example terraform.tfvars
# Edit db_password

terraform init
terraform plan
terraform apply
terraform output
```

## Post-deploy checklist

- [ ] Update Magento base URLs to `https://www.tyresonline.sa/`
- [ ] Configure `static.tyresonline.sa` and `media.tyresonline.sa` (or S3)
- [ ] Set SAR currency and `Asia/Riyadh` timezone in admin
- [ ] Configure KSA payment gateways and shipping
- [ ] Verify OpenSearch indices (`satyresonline2_*`) and reindex
- [ ] Configure Cloudflare / WAF for production domain
- [ ] Restrict SSH CIDR in `terraform.tfvars` (`allowed_ssh_cidr`)

## Estimated monthly cost (eu-north-1)

| Service | Approx. |
|---------|---------|
| EC2 t3.xlarge | ~$120 |
| RDS db.t3.medium | ~$55 |
| ElastiCache cache.t3.micro | ~$12 |
| S3 + data transfer | ~$10–30 |
| **Total** | **~$200–220/mo** |

## Destroy (careful)

```powershell
cd infra\terraform
terraform destroy
```
