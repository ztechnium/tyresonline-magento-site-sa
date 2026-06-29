#!/usr/bin/env python3
"""Verify cart Summary totals render after Knockout init on staging."""
from __future__ import annotations

import json
import re
import sys

from playwright.sync_api import sync_playwright


BASE = 'https://stg.tyresonline.sa'
PRODUCT_ID = 4715


def extract_form_key(html: str) -> str:
    m = re.search(r'name="form_key"[^>]*value="([^"]+)"', html)
    return m.group(1) if m else ''


def main() -> int:
    result = {
        'checkoutConfig': False,
        'quoteData': False,
        'cartTotalsExists': False,
        'totalsRows': 0,
        'knockoutBound': False,
        'subtotalText': '',
        'grandTotalText': '',
        'consoleErrors': [],
        'pageErrors': [],
    }

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(ignore_https_errors=True)
        page = context.new_page()

        page.on('console', lambda msg: result['consoleErrors'].append(
            f'{msg.type}: {msg.text}'
        ) if msg.type in ('error', 'warning') else None)
        page.on('pageerror', lambda err: result['pageErrors'].append(str(err)))

        page.goto(f'{BASE}/en/checkout/cart/', wait_until='domcontentloaded', timeout=60000)
        form_key = extract_form_key(page.content())
        if form_key:
            page.request.post(
                f'{BASE}/en/checkout/cart/add/',
                form={'product': str(PRODUCT_ID), 'qty': '4', 'form_key': form_key},
            )

        page.goto(f'{BASE}/en/checkout/cart/?_={page.evaluate("Date.now()")}', wait_until='networkidle', timeout=90000)
        page.wait_for_timeout(3000)

        result.update(page.evaluate('''() => {
            const cfg = window.checkoutConfig || {};
            const el = document.querySelector('#cart-totals');
            const rows = el ? el.querySelectorAll('table.totals tr').length : 0;
            const sub = el ? (el.querySelector('.totals.sub .amount .price, tr.totals.sub .amount .price') || {}).textContent || '' : '';
            const grand = el ? (el.querySelector('.grand.totals .amount .price, tr.grand.totals .amount .price') || {}).textContent || '' : '';
            return {
                checkoutConfig: !!window.checkoutConfig,
                quoteData: !!(cfg.quoteData),
                cartTotalsExists: !!el,
                totalsRows: rows,
                knockoutBound: !!(el && el.querySelector('table.totals tr')),
                subtotalText: (sub || '').trim(),
                grandTotalText: (grand || '').trim(),
                cartTotalsHtmlLen: el ? el.innerHTML.length : 0,
                blockShippingDisplay: document.querySelector('#block-shipping') ? getComputedStyle(document.querySelector('#block-shipping')).display : 'missing',
            };
        }'''))

        browser.close()

    print(json.dumps(result, indent=2))
    ok = (
        result.get('checkoutConfig')
        and result.get('cartTotalsExists')
        and result.get('totalsRows', 0) > 0
        and (result.get('grandTotalText') or result.get('subtotalText'))
    )
    return 0 if ok else 1


if __name__ == '__main__':
    sys.exit(main())
