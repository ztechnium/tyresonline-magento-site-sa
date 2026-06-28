resource "aws_security_group" "sa_prod_ec2" {
  name        = "${var.project}-ec2-sg"
  description = "KSA production Magento app server"
  vpc_id      = local.vpc_id

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = [var.allowed_ssh_cidr]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, { Name = "${var.project}-ec2-sg" })
}

resource "aws_security_group" "sa_prod_rds" {
  name        = "${var.project}-rds-sg"
  description = "KSA production RDS MySQL"
  vpc_id      = local.vpc_id

  ingress {
    description     = "MySQL from app server"
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.sa_prod_ec2.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, { Name = "${var.project}-rds-sg" })
}

resource "aws_security_group" "sa_prod_redis" {
  name        = "${var.project}-redis-sg"
  description = "KSA production ElastiCache Redis"
  vpc_id      = local.vpc_id

  ingress {
    description     = "Redis from app server"
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.sa_prod_ec2.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(var.tags, { Name = "${var.project}-redis-sg" })
}

resource "aws_db_subnet_group" "sa_prod" {
  name       = "${var.project}-db-subnet"
  subnet_ids = data.aws_db_subnet_group.ae_db_subnet_group.subnet_ids

  tags = merge(var.tags, { Name = "${var.project}-db-subnet" })
}

resource "aws_db_instance" "sa_prod" {
  identifier = var.project

  engine         = "mysql"
  engine_version = data.aws_db_instance.ae_reference.engine_version
  instance_class = var.db_instance_class

  allocated_storage     = var.db_allocated_storage_gb
  max_allocated_storage = var.db_allocated_storage_gb * 2
  storage_type          = "gp3"
  storage_encrypted     = true

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  vpc_security_group_ids = [aws_security_group.sa_prod_rds.id]
  db_subnet_group_name   = aws_db_subnet_group.sa_prod.name

  backup_retention_period = 7
  backup_window           = "03:00-04:00"
  maintenance_window      = "sun:04:00-sun:05:00"

  skip_final_snapshot       = false
  final_snapshot_identifier = "${var.project}-final-snapshot"
  deletion_protection       = true
  publicly_accessible       = false
  multi_az                  = false

  tags = merge(var.tags, { Name = var.project })
}

resource "aws_elasticache_subnet_group" "sa_prod" {
  name       = "${var.project}-redis-subnet"
  subnet_ids = data.aws_db_subnet_group.ae_db_subnet_group.subnet_ids

  tags = merge(var.tags, { Name = "${var.project}-redis-subnet" })
}

resource "aws_elasticache_cluster" "sa_prod" {
  cluster_id           = "${var.project}-redis"
  engine               = "redis"
  node_type            = var.redis_node_type
  num_cache_nodes      = 1
  port                 = 6379
  parameter_group_name = "default.redis7"

  subnet_group_name  = aws_elasticache_subnet_group.sa_prod.name
  security_group_ids = [aws_security_group.sa_prod_redis.id]

  tags = merge(var.tags, { Name = "${var.project}-redis" })
}

resource "aws_instance" "sa_prod" {
  ami                         = data.aws_ami.ubuntu_2404.id
  instance_type               = var.ec2_instance_type
  key_name                    = var.ec2_key_name
  subnet_id                   = var.ec2_subnet_id
  associate_public_ip_address = true
  vpc_security_group_ids      = [aws_security_group.sa_prod_ec2.id]

  root_block_device {
    volume_size = var.ec2_root_volume_gb
    volume_type = "gp3"
    encrypted   = true
  }

  user_data = templatefile("${path.module}/../scripts/bootstrap-ec2-sa-prod.sh", {
    domain_name = var.domain_name
  })

  tags = merge(var.tags, {
    Name = "${var.project}-ec2"
  })

  lifecycle {
    ignore_changes = [user_data, ami]
  }
}

# IAM profile for S3 media sync is created in cloudfront.tf.
# Attach manually after apply (provider has no association resource in v5):
#   aws ec2 associate-iam-instance-profile --instance-id <id> --iam-instance-profile Name=tyresonline-sa-prod-ec2-s3-media

resource "aws_s3_bucket" "sa_prod_media" {
  bucket = "${var.project}-media"

  tags = merge(var.tags, { Name = "${var.project}-media" })
}

resource "aws_s3_bucket_versioning" "sa_prod_media" {
  bucket = aws_s3_bucket.sa_prod_media.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "sa_prod_media" {
  bucket = aws_s3_bucket.sa_prod_media.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}
