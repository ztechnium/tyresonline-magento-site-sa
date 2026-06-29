#!/usr/bin/env python3
import os
import sys
import time
from datetime import datetime, timezone

from playwright.sync_api import sync_playwright, expect

ACCOUNT_URL = os.environ.get("AWS_CONSOLE_URL", "https://681637098506.signin.aws.amazon.com/console")
USERNAME = os.environ["AWS_CONSOLE_USER"]
PASSWORD = os.environ["AWS_CONSOLE_PASS"]
REGION = "eu-north-1"
RDS_INSTANCE = "tyresonline-sa-prod"
EC2_INSTANCE_ID = "i-040966c4cd8824732"
STAMP = datetime.now(timezone.utc).strftime("%Y%m%d-%H%M")
LABEL = f"export-fix-{STAMP}"
FIX_CMD = (
    "cd /var/www/magento && git pull origin main || true && "
    "sudo -u www-data php bin/magento setup:upgrade --keep-generated && "
    "sudo -u www-data php bin/magento cache:flush && "
    "mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com "
    "-u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa "
    "-e \"SHOW COLUMNS FROM elasticsuite_tracker_log_event LIKE 'is_invalid';\""
)


def login(page):
    page.goto(ACCOUNT_URL, wait_until="domcontentloaded")
    time.sleep(2)
    page.locator('input[name="username"], #username').first.fill(USERNAME)
    page.locator('input[name="password"], #password').first.fill(PASSWORD)
    page.locator('#signin_button, button[type="submit"]').first.click()
    page.wait_for_function(
        "() => location.hostname.endsWith('console.aws.amazon.com')",
        timeout=180000,
    )
    time.sleep(5)


def create_rds_snapshot(page):
    page.goto(
        f"https://{REGION}.console.aws.amazon.com/rds/home?region={REGION}"
        f"#database:id={RDS_INSTANCE};is-cluster=false",
        wait_until="domcontentloaded",
    )
    time.sleep(12)
    page.get_by_role("button", name="Actions").click()
    time.sleep(2)
    page.get_by_role("menuitem", name="Take snapshot").click()
    time.sleep(3)
    page.locator('input[name="snapshotIdentifier"]').fill(f"{LABEL}-rds")
    page.get_by_role("button", name="Take snapshot").click()
    time.sleep(8)
    print("RDS snapshot submitted:", f"{LABEL}-rds")


def create_ec2_ami(page):
    page.goto(
        f"https://{REGION}.console.aws.amazon.com/ec2/home?region={REGION}"
        f"#InstanceDetails:instanceId={EC2_INSTANCE_ID}",
        wait_until="domcontentloaded",
    )
    time.sleep(12)
    page.get_by_role("button", name="Actions").click()
    time.sleep(2)
    page.get_by_role("menuitem", name="Image and templates").hover()
    time.sleep(1)
    page.get_by_role("menuitem", name="Create image").click()
    time.sleep(4)
    page.locator('#name, input[name="name"]').first.fill(f"{LABEL}-ami")
    page.get_by_role("button", name="Create image").click()
    time.sleep(8)
    print("EC2 AMI submitted:", f"{LABEL}-ami")


def run_ssm_shell(page):
    page.goto(
        f"https://{REGION}.console.aws.amazon.com/systems-manager/documents/"
        f"AWS-RunShellScript/run?region={REGION}",
        wait_until="domcontentloaded",
    )
    time.sleep(10)
    page.get_by_text(EC2_INSTANCE_ID, exact=False).click()
    time.sleep(2)
    page.locator('textarea').first.fill(FIX_CMD)
    page.get_by_role("button", name="Run").click()
    time.sleep(15)
    print("SSM shell command submitted")


def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(viewport={"width": 1920, "height": 1080})
        page.set_default_timeout(120000)
        login(page)
        print("Logged in")
        create_rds_snapshot(page)
        create_ec2_ami(page)
        try:
            run_ssm_shell(page)
        except Exception as exc:
            print("SSM UI automation failed:", exc)
            page.screenshot(path="/workspace/aws-ssm-fail.png", full_page=True)
        page.screenshot(path="/workspace/aws-done.png", full_page=True)
        browser.close()
    print("COMPLETE")


if __name__ == "__main__":
    main()
