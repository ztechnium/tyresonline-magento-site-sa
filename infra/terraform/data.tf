data "aws_db_instance" "ae_reference" {
  db_instance_identifier = var.ae_reference_db_identifier
}

data "aws_db_subnet_group" "ae_db_subnet_group" {
  name = data.aws_db_instance.ae_reference.db_subnet_group
}

data "aws_subnet" "ae_db_subnets" {
  for_each = toset(data.aws_db_subnet_group.ae_db_subnet_group.subnet_ids)
  id       = each.value
}

locals {
  vpc_id = data.aws_subnet.ae_db_subnets[tolist(data.aws_db_subnet_group.ae_db_subnet_group.subnet_ids)[0]].vpc_id
}

data "aws_ami" "ubuntu_2404" {
  most_recent = true
  owners      = ["099720109477"]

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}
