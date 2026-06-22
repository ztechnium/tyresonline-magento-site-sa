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
