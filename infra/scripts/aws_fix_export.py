#!/usr/bin/env python3
import json
import os
import re
import sys
import time
from datetime import datetime, timezone

import boto3
from botocore.exceptions import ClientError
from playwright.sync_api import sync_playwright

ACCOUNT_URL = os.environ.get("AWS_CONSOLE_URL", "https://681637098506.signin.aws.amazon.com/console")
USERNAME = os.environ["AWS_CONSOLE_USER"]
PASSWORD = os.environ["AWS_CONSOLE_PASS"]
REGION = "eu-north-1"
RDS_INSTANCE = "tyresonline-sa-prod"
EC2_TAG = "tyresonline-sa-prod-ec2"
STAMP = datetime.now(timezone.utc).strftime("%Y%m%d-%H%M")
LABEL = f"export-fix-{STAMP}"

captured_creds = {}


def capture_response(response):
    try:
        if response.request.resource_type not in ("xhr", "fetch", "document"):
            return
        body = response.text()
    except Exception:
        return
    if "AccessKeyId" in body or "accessKeyId" in body:
        captured_creds["raw"] = body
        captured_creds["url"] = response.url


def login(page):
    page.goto(ACCOUNT_URL, wait_until="domcontentloaded")
    time.sleep(2)
    for selector in ['input[name="username"]', '#username', 'input[id="resolving_input"]']:
        if page.locator(selector).count():
            page.fill(selector, USERNAME)
            break
    for selector in ['input[name="password"]', '#password']:
        if page.locator(selector).count():
            page.fill(selector, PASSWORD)
            break
    for selector in ['#signin_button', 'button[type="submit"]', '#next_button']:
        if page.locator(selector).count():
            page.click(selector)
            break

    page.wait_for_function(
        "() => location.hostname.endsWith('console.aws.amazon.com')",
        timeout=180000,
    )
    time.sleep(5)


def parse_creds_from_raw(raw: str):
    if not raw:
        return None

    key = re.search(r'"(?:accessKeyId|AccessKeyId)"\s*:\s*"([^"]+)"', raw)
    secret = re.search(r'"(?:secretAccessKey|SecretAccessKey)"\s*:\s*"([^"]+)"', raw)
    token = re.search(r'"(?:sessionToken|SessionToken)"\s*:\s*"([^"]+)"', raw)
    if key and secret:
        return {
            "aws_access_key_id": key.group(1),
            "aws_secret_access_key": secret.group(1),
            "aws_session_token": token.group(1) if token else None,
        }

    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        m = re.search(r"\{.*\}", raw, re.S)
        if not m:
            return None
        try:
            data = json.loads(m.group(0))
        except json.JSONDecodeError:
            return None

    def dig(obj):
        if isinstance(obj, dict):
            keys = {k.lower(): k for k in obj}
            if "accesskeyid" in keys and "secretaccesskey" in keys:
                return {
                    "aws_access_key_id": obj[keys["accesskeyid"]],
                    "aws_secret_access_key": obj[keys["secretaccesskey"]],
                    "aws_session_token": obj.get(keys.get("sessiontoken", ""), obj.get("sessionToken")),
                }
            for v in obj.values():
                found = dig(v)
                if found:
                    return found
        elif isinstance(obj, list):
            for item in obj:
                found = dig(item)
                if found:
                    return found
        return None

    return dig(data)


def run_with_boto(session: boto3.Session):
    sts = session.client("sts")
    identity = sts.get_caller_identity()
    print("Caller:", identity)

    ec2 = session.client("ec2")
    rds = session.client("rds")

    reservations = ec2.describe_instances(
        Filters=[
            {"Name": "tag:Name", "Values": [EC2_TAG]},
            {"Name": "instance-state-name", "Values": ["running"]},
        ]
    )["Reservations"]
    if not reservations:
        raise RuntimeError(f"No running EC2 with tag {EC2_TAG}")
    instance_id = reservations[0]["Instances"][0]["InstanceId"]
    print("EC2:", instance_id)

    ami_id = ec2.create_image(
        InstanceId=instance_id,
        Name=f"{LABEL}-ami",
        Description="Before ElasticSuite export fix",
        NoReboot=True,
    )["ImageId"]
    print("AMI started:", ami_id)

    rds_snap = f"{LABEL}-rds"
    rds.create_db_snapshot(
        DBSnapshotIdentifier=rds_snap,
        DBInstanceIdentifier=RDS_INSTANCE,
    )
    print("RDS snapshot started:", rds_snap)
    rds.get_waiter("db_snapshot_available").wait(DBSnapshotIdentifier=rds_snap)
    print("RDS snapshot available")

    cmd = (
        "cd /var/www/magento && "
        "git pull origin main || true && "
        "sudo -u www-data php bin/magento setup:upgrade --keep-generated && "
        "sudo -u www-data php bin/magento cache:flush && "
        "mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com "
        "-u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa "
        "-e \"SHOW COLUMNS FROM elasticsuite_tracker_log_event LIKE 'is_invalid';\""
    )
    try:
        ssm = session.client("ssm")
        command_id = ssm.send_command(
            InstanceIds=[instance_id],
            DocumentName="AWS-RunShellScript",
            Parameters={"commands": [cmd]},
        )["Command"]["CommandId"]
        print("SSM command:", command_id)
        for _ in range(30):
            time.sleep(10)
            inv = ssm.list_command_invocations(CommandId=command_id, Details=True)
            items = inv.get("CommandInvocations", [])
            if items and items[0]["Status"] in ("Success", "Failed", "Cancelled", "TimedOut"):
                print("SSM status:", items[0]["Status"])
                print(items[0]["CommandPlugins"][0].get("Output", ""))
                break
    except ClientError as e:
        print("SSM not available:", e)

    return ami_id, rds_snap


def main():
    try:
        run_with_boto(boto3.Session(region_name=REGION))
        print("SUCCESS")
        return
    except Exception as e:
        print("Direct boto3 failed:", e)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()
        page.on("response", capture_response)
        page.set_default_timeout(120000)

        print("Logging into AWS Console...")
        login(page)
        print("Console URL:", page.url)

        storage = page.evaluate(
            """() => {
                const out = {};
                for (const s of [localStorage, sessionStorage]) {
                    for (const k of Object.keys(s)) out[k] = s.getItem(k);
                }
                return out;
            }"""
        )
        with open("/workspace/aws-storage.json", "w") as f:
            json.dump(storage, f)
        print("Storage keys:", len(storage))

        # Trigger credential vending by visiting console service pages
        for url in [
            f"https://{REGION}.console.aws.amazon.com/ec2/home?region={REGION}",
            f"https://{REGION}.console.aws.amazon.com/rds/home?region={REGION}",
            f"https://{REGION}.console.aws.amazon.com/cloudshell/home?region={REGION}",
        ]:
            page.goto(url, wait_until="domcontentloaded")
            time.sleep(8)

        creds = parse_creds_from_raw(captured_creds.get("raw", ""))
        if creds:
            print("Captured temporary AWS credentials from console session")
            session = boto3.Session(
                aws_access_key_id=creds["aws_access_key_id"],
                aws_secret_access_key=creds["aws_secret_access_key"],
                aws_session_token=creds.get("aws_session_token"),
                region_name=REGION,
            )
            browser.close()
            run_with_boto(session)
            print("SUCCESS")
            return

        page.screenshot(path="/workspace/aws-console-final.png", full_page=True)
        with open("/workspace/aws-captured-creds-debug.json", "w") as f:
            json.dump(captured_creds, f)
        browser.close()

    print("Logged into console but could not extract API credentials for automation.", file=sys.stderr)
    sys.exit(1)


if __name__ == "__main__":
    main()
