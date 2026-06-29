#!/usr/bin/env python3
"""Verify checkout page renders on staging."""
from __future__ import annotations

import json
import re
import sys
from datetime import UTC, datetime, timedelta

from playwright.sync_api import sync_playwright

BASE = 'https://stg.tyresonline.sa'
PRODUCT_ID = 4715


def extract_form_key(html: str) -> str:
    m = re.search(r'name="form_key"[^>]*value="([^"]+)"', html)
    return m.group(1) if m else ''


def save_installer(request, form_key: str) -> bool:
    pickup_date = (datetime.now(UTC) + timedelta(days=3)).strftime('%Y-%m-%d')
    payload = {
        'pickup_store': '1',
        'pickup_date': pickup_date,
        'pickup_time': '09:00 - 11:00',
        'form_key': form_key,
    }
    response = request.post(
        f'{BASE}/en/installer/ajax/savecartinstaller/',
        data=json.dumps(payload),
        headers={'Content-Type': 'application/json'},
    )
    try:
        body = response.json()
    except Exception:
        body = {}
    return response.ok and body.get('message') == 'success'


def main() -> int:
    result = {
        'checkoutDiv': False,
        'checkoutConfig': False,
        'shippingVisible': False,
        'paymentVisible': False,
        'summaryVisible': False,
        'installerSaved': False,
        'finalUrl': '',
        'pageErrors': [],
        'consoleErrors': [],
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_context(ignore_https_errors=True).new_page()
        page.on('pageerror', lambda e: result['pageErrors'].append(str(e)))

        page.goto(f'{BASE}/en/checkout/cart/', wait_until='domcontentloaded', timeout=60000)
        form_key = extract_form_key(page.content())
        if form_key:
            page.request.post(
                f'{BASE}/en/checkout/cart/add/',
                form={'product': str(PRODUCT_ID), 'qty': '4', 'form_key': form_key},
            )
            result['installerSaved'] = save_installer(page.request, form_key)

        page.goto(f'{BASE}/en/checkout/?_={page.evaluate("Date.now()")}', wait_until='domcontentloaded', timeout=120000)
        page.wait_for_timeout(5000)
        result['finalUrl'] = page.url

        result.update(page.evaluate('''() => {
            const checkout = document.querySelector('#checkout');
            const text = document.body ? document.body.innerText : '';
            return {
                checkoutDiv: !!checkout,
                checkoutConfig: !!window.checkoutConfig,
                shippingVisible: text.includes('Shipping') || !!document.querySelector('[data-role=shipping-address]'),
                paymentVisible: text.includes('Payment') || !!document.querySelector('.payment-method'),
                summaryVisible: !!document.querySelector('.opc-block-summary, .order-summary, .checkout-summary'),
                checkoutHtmlLen: checkout ? checkout.innerHTML.length : 0,
            };
        }'''))

        browser.close()

    print(json.dumps(result, indent=2))
    ok = (
        result.get('checkoutDiv')
        and result.get('checkoutConfig')
        and result.get('checkoutHtmlLen', 0) > 100
        and '/checkout/cart' not in result.get('finalUrl', '')
    )
    return 0 if ok else 1


if __name__ == '__main__':
    sys.exit(main())
