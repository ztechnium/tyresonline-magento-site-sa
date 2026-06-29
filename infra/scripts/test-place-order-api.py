#!/usr/bin/env python3
"""Test guest place-order API on staging after shipping/installer setup."""
from __future__ import annotations

import json
import re
import sys
from datetime import UTC, datetime, timedelta

import requests

BASE = 'https://stg.tyresonline.sa/en'
PRODUCT_ID = 4715
SESSION = requests.Session()
SESSION.verify = False


def form_key(html: str) -> str:
    match = re.search(r'name="form_key"[^>]*value="([^"]+)"', html)
    if not match:
        raise RuntimeError('form_key not found')
    return match.group(1)


def main() -> int:
    cart_html = SESSION.get(f'{BASE}/checkout/cart/', timeout=60).text
    fk = form_key(cart_html)

    add = SESSION.post(
        f'{BASE}/checkout/cart/add/',
        data={'product': str(PRODUCT_ID), 'qty': '1', 'form_key': fk},
        timeout=60,
    )
    if add.status_code >= 400:
        print('add-to-cart failed', add.status_code)
        return 1

    pickup_date = (datetime.now(UTC) + timedelta(days=3)).strftime('%Y-%m-%d')
    installer = SESSION.post(
        f'{BASE}/installer/ajax/savecartinstaller/',
        json={
            'pickup_store': '1',
            'pickup_date': pickup_date,
            'pickup_time': '09:00 - 11:00',
            'form_key': fk,
        },
        timeout=60,
    )
    print('installer', installer.status_code, installer.text[:200])

    checkout_html = SESSION.get(f'{BASE}/checkout/', timeout=120).text
    if 'checkoutConfig' not in checkout_html:
        print('checkoutConfig missing')
        return 1

    config_match = re.search(r'window\.checkoutConfig\s*=\s*(\{.*?\});\s*</script>', checkout_html, re.S)
    if not config_match:
        print('could not parse checkoutConfig')
        return 1

    config = json.loads(config_match.group(1))
    quote_id = config.get('quoteData', {}).get('entity_id') or config.get('quoteId')
    print('quote_id', quote_id)

    shipping = config.get('selectedShippingMethod')
    print('selectedShippingMethod', shipping)

    guest_email = f'api-test-{int(datetime.now(UTC).timestamp())}@tyresonline.sa.test'
    payment_url = f'{BASE.replace("/en", "")}/rest/en/V1/guest-carts/{quote_id}/payment-information'

  # Use cash on delivery if available
    payload = {
        'email': guest_email,
        'paymentMethod': {'method': 'cashondelivery'},
        'billingAddress': {
            'firstname': 'API',
            'lastname': 'Test',
            'street': ['King Fahd Road'],
            'city': 'Riyadh',
            'postcode': '12345',
            'countryId': 'SA',
            'telephone': '+966501234567',
        },
    }

    response = SESSION.post(payment_url, json=payload, timeout=120)
    print('place-order status', response.status_code)
    print(response.text[:500])

    if response.status_code == 200:
        print('PLACE ORDER OK', response.text)
        return 0

    return 1


if __name__ == '__main__':
    sys.exit(main())
