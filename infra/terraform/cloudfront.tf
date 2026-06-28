# CDN: cdn.tyresonline.sa → CloudFront → S3 (/media) + EC2 (/static)
# ACM certificate must be in us-east-1 for CloudFront.

provider "aws" {
  alias  = "us_east_1"
  region = "us-east-1"
}

variable "cdn_domain_name" {
  type    = string
  default = "cdn.tyresonline.sa"
}

variable "cdn_origin_domain" {
  type    = string
  default = "stg.tyresonline.sa"
}

resource "aws_acm_certificate" "cdn" {
  provider          = aws.us_east_1
  domain_name       = var.cdn_domain_name
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }

  tags = merge(var.tags, { Name = "${var.project}-cdn-cert" })
}

resource "aws_acm_certificate_validation" "cdn" {
  provider        = aws.us_east_1
  certificate_arn = aws_acm_certificate.cdn.arn
}

resource "aws_cloudfront_response_headers_policy" "static_cors" {
  name    = "${var.project}-static-cors"
  comment = "CORS for Magento /static XHR from stg/prod tyresonline.sa"

  cors_config {
    access_control_allow_credentials = false

    access_control_allow_headers {
      items = ["*"]
    }

    access_control_allow_methods {
      items = ["GET", "HEAD", "OPTIONS"]
    }

    access_control_allow_origins {
      items = [
        "https://stg.tyresonline.sa",
        "https://www.tyresonline.sa",
        "https://tyresonline.sa",
      ]
    }

    access_control_max_age_sec = 86400
    origin_override            = true
  }
}

resource "aws_cloudfront_origin_access_control" "media" {
  name                              = "${var.project}-media-oac"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_cloudfront_distribution" "cdn" {
  enabled         = true
  is_ipv6_enabled = true
  comment         = "${var.project} CDN (media S3 + static EC2)"
  price_class     = "PriceClass_200"
  aliases         = [var.cdn_domain_name]
  http_version    = "http2and3"

  origin {
    domain_name = var.cdn_origin_domain
    origin_id   = "magento-ec2"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  origin {
    domain_name              = aws_s3_bucket.sa_prod_media.bucket_regional_domain_name
    origin_id                = "media-s3"
    origin_access_control_id = aws_cloudfront_origin_access_control.media.id
  }

  default_cache_behavior {
    target_origin_id       = "magento-ec2"
    viewer_protocol_policy = "redirect-to-https"
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cached_methods         = ["GET", "HEAD"]
    compress               = true

    forwarded_values {
      query_string = false
      cookies { forward = "none" }
    }

    min_ttl     = 0
    default_ttl = 3600
    max_ttl     = 86400
  }

  ordered_cache_behavior {
    path_pattern           = "/media/*"
    target_origin_id       = "media-s3"
    viewer_protocol_policy = "redirect-to-https"
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cached_methods         = ["GET", "HEAD"]
    compress               = true

    forwarded_values {
      query_string = false
      cookies { forward = "none" }
    }

    min_ttl     = 0
    default_ttl = 86400
    max_ttl     = 31536000
  }

  ordered_cache_behavior {
    path_pattern           = "/static/*"
    target_origin_id       = "magento-ec2"
    viewer_protocol_policy = "redirect-to-https"
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cached_methods         = ["GET", "HEAD"]
    compress               = true
    response_headers_policy_id = aws_cloudfront_response_headers_policy.static_cors.id

    forwarded_values {
      query_string = false
      cookies { forward = "none" }
      headers = ["Origin", "Access-Control-Request-Method", "Access-Control-Request-Headers"]
    }

    min_ttl     = 0
    default_ttl = 86400
    max_ttl     = 31536000
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn      = aws_acm_certificate_validation.cdn.certificate_arn
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1.2_2021"
  }

  tags = merge(var.tags, { Name = "${var.project}-cdn" })
}

resource "aws_s3_bucket_policy" "sa_prod_media_cdn" {
  bucket = aws_s3_bucket.sa_prod_media.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "AllowCloudFrontRead"
      Effect    = "Allow"
      Principal = { Service = "cloudfront.amazonaws.com" }
      Action    = "s3:GetObject"
      Resource  = "${aws_s3_bucket.sa_prod_media.arn}/*"
      Condition = {
        StringEquals = {
          "AWS:SourceArn" = aws_cloudfront_distribution.cdn.arn
        }
      }
    }]
  })

  depends_on = [aws_cloudfront_distribution.cdn]
}

resource "aws_iam_role" "ec2_s3_media" {
  name = "${var.project}-ec2-s3-media"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
      Action = "sts:AssumeRole"
    }]
  })

  tags = var.tags
}

resource "aws_iam_role_policy" "ec2_s3_media" {
  name = "${var.project}-ec2-s3-media-sync"
  role = aws_iam_role.ec2_s3_media.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = ["s3:ListBucket", "s3:GetObject", "s3:PutObject", "s3:DeleteObject"]
      Resource = [
        aws_s3_bucket.sa_prod_media.arn,
        "${aws_s3_bucket.sa_prod_media.arn}/*"
      ]
    }]
  })
}

resource "aws_iam_instance_profile" "ec2_s3_media" {
  name = "${var.project}-ec2-s3-media"
  role = aws_iam_role.ec2_s3_media.name
}
