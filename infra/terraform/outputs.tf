output "ec2_public_ip" {
  description = "Public IP of the KSA production app server"
  value       = aws_instance.sa_prod.public_ip
}

output "ec2_public_dns" {
  description = "Public DNS of the KSA production app server"
  value       = aws_instance.sa_prod.public_dns
}

output "ec2_instance_id" {
  value = aws_instance.sa_prod.id
}

output "rds_endpoint" {
  description = "RDS hostname (use in env.php)"
  value       = aws_db_instance.sa_prod.address
}

output "rds_port" {
  value = aws_db_instance.sa_prod.port
}

output "redis_endpoint" {
  description = "ElastiCache Redis hostname (use in env.php)"
  value       = aws_elasticache_cluster.sa_prod.cache_nodes[0].address
}

output "redis_port" {
  value = aws_elasticache_cluster.sa_prod.port
}

output "s3_media_bucket" {
  value = aws_s3_bucket.sa_prod_media.bucket
}

output "cdn_domain_name" {
  value = var.cdn_domain_name
}

output "cloudfront_domain_name" {
  description = "CNAME target for dnet.sa: cdn → this value"
  value       = aws_cloudfront_distribution.cdn.domain_name
}

output "cloudfront_distribution_id" {
  value = aws_cloudfront_distribution.cdn.id
}

output "acm_dns_validation" {
  description = "Add these CNAME records on dnet.sa BEFORE CloudFront finishes"
  value = {
    for dvo in aws_acm_certificate.cdn.domain_validation_options : dvo.domain_name => {
      name  = dvo.resource_record_name
      type  = dvo.resource_record_type
      value = dvo.resource_record_value
    }
  }
}

output "dnet_sa_dns_records" {
  description = "Exact records to add at https://dnet.sa for tyresonline.sa"
  value = <<-EOT
    === 1) ACM certificate validation (add first, wait ~5 min) ===
    Type: CNAME
    Host: ${try(tolist(aws_acm_certificate.cdn.domain_validation_options)[0].resource_record_name, "see acm_dns_validation")}
    Target: ${try(tolist(aws_acm_certificate.cdn.domain_validation_options)[0].resource_record_value, "see acm_dns_validation")}

    === 2) CDN hostname (add after CloudFront is Deployed) ===
    Type: CNAME
    Host: cdn.tyresonline.sa
    Target: ${aws_cloudfront_distribution.cdn.domain_name}

    === DO NOT CHANGE ===
    stg.tyresonline.sa  A  →  ${aws_instance.sa_prod.public_ip}
  EOT
}

output "vpc_id" {
  value = local.vpc_id
}

output "ssh_command" {
  value = "ssh -i staging-key-tyresonline.pem ubuntu@${aws_instance.sa_prod.public_dns}"
}

output "next_steps" {
  value = <<-EOT
    1. Point DNS for ${var.domain_name} to ${aws_instance.sa_prod.public_ip}
    2. Wait ~5 min for bootstrap to finish, then SSH and check /var/log/tyresonline-sa-bootstrap.log
    3. Run deploy_to_aws_sa_prod.ps1 to upload Magento code + DB
    4. Generate env.php with scripts/generate-env-php.php using terraform output values
  EOT
}
