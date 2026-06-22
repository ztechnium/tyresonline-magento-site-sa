variable "aws_region" {
  description = "AWS region (same as AE stack)"
  type        = string
  default     = "eu-north-1"
}

variable "project" {
  description = "Project slug used in resource names"
  type        = string
  default     = "tyresonline-sa-prod"
}

variable "ae_reference_db_identifier" {
  description = "Existing AE RDS instance to clone network placement from"
  type        = string
  default     = "tyresonline-ae-stg-rds"
}

variable "ec2_instance_type" {
  description = "EC2 size (AE staging runs 4 vCPU / 16 GB RAM)"
  type        = string
  default     = "t3.xlarge"
}

variable "ec2_key_name" {
  description = "Existing EC2 key pair name in AWS"
  type        = string
  default     = "staging-key-tyresonline"
}

variable "ec2_subnet_id" {
  description = "Public subnet for the app server (same as AE staging)"
  type        = string
  default     = "subnet-69bdae11"
}

variable "ec2_root_volume_gb" {
  type    = number
  default = 200
}

variable "db_instance_class" {
  type    = string
  default = "db.t3.medium"
}

variable "db_allocated_storage_gb" {
  type    = number
  default = 100
}

variable "db_name" {
  type    = string
  default = "tyresonline_sa"
}

variable "db_username" {
  type    = string
  default = "magento"
}

variable "db_password" {
  description = "RDS master password (override in terraform.tfvars, never commit)"
  type        = string
  sensitive   = true
}

variable "redis_node_type" {
  type    = string
  default = "cache.t3.micro"
}

variable "domain_name" {
  type    = string
  default = "www.tyresonline.sa"
}

variable "allowed_ssh_cidr" {
  description = "CIDR allowed to SSH to the app server"
  type        = string
  default     = "0.0.0.0/0"
}

variable "tags" {
  type = map(string)
  default = {
    Project     = "tyresonline"
    Market      = "sa"
    Environment = "production"
    ManagedBy   = "terraform"
  }
}
