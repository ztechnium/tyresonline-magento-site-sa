#!/usr/bin/env python3
"""Browser test for cart fitting locations on KSA staging (EN + AR)."""
from __future__ import annotations

import json
import re
import sys
import time
from dataclasses import dataclass, field

from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeout


BASE = 'https://stg.tyresonline.sa'
PRODUCT_PATHS = {
    'en': '/en/test-tyre-1-sar.html',
    'ar': '/ar/test-tyre-1-sar.html',
}


@dataclass
class LocaleResult:
    locale: str
    errors: list[str] = field(default_factory=list)
    console_errors: list[str] = field(default_factory=list)
    stores_requests: list[dict] = field(default_factory=list)
    modal_found: bool = False
    modal_count: int = 0
    loader_visible: bool | None = None
    installer_count: int = 0
    config_found: bool = False
    cart_items: int = 0
    notes: list[str] = field(default_factory=list)


def extract_form_key(html: str) -> str:
    for pattern in (
        r'name="form_key"\s+type="hidden"\s+value="([^"]+)"',
        r'name="form_key"\s+value="([^"]+)"',
        r'"form_key":"([^"]+)"',
        r"form_key\\u0022:\\u0022([^\\]+)\\u0022",
    ):
        m = re.search(pattern, html)
        if m:
            return m.group(1)
    return ''


def add_product_to_cart(page, locale: str, result: LocaleResult) -> bool:
    selectors = [
        'form#product_addtocart_form button.tocart',
        'form[data-role="tocart-form"] button.tocart',
        '#product-addtocart-button',
        'button.tocart',
    ]
    for selector in selectors:
        btn = page.locator(selector).first
        try:
            if btn.count() and btn.is_visible():
                btn.click(timeout=10000)
                page.wait_for_timeout(4000)
                return True
        except Exception as exc:
            result.notes.append(f'click {selector} failed: {exc}')

    form_key = extract_form_key(page.content())
    if form_key:
        page.evaluate(
            """async ({ productId, formKey, locale }) => {
                const body = new URLSearchParams({ product: String(productId), qty: '1', form_key: formKey });
                const resp = await fetch(`/${locale}/checkout/cart/add/`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body,
                    credentials: 'include',
                });
                return resp.status;
            }""",
            {'productId': 6954, 'formKey': form_key, 'locale': locale},
        )
        page.wait_for_timeout(3000)
        return True
    return False


def run_locale(page, locale: str) -> LocaleResult:
    result = LocaleResult(locale=locale)
    product_url = BASE + PRODUCT_PATHS[locale]

    def on_console(msg):
        if msg.type in ('error', 'warning'):
            text = msg.text
            lowered = text.lower()
            if any(x in lowered for x in ('favicon', 'snapchat', 'hotjar', 'csp', 'googlesyndication', 'doubleclick', 'material-icons', 'nibble')):
                return
            if 'store locator' in lowered or 'google maps' in lowered or 'storelocator' in lowered or 'installer' in lowered:
                result.console_errors.append(f'{msg.type}: {text}')

    def on_response(resp):
        if 'storelocator/ajax/stores' in resp.url:
            body = ''
            try:
                body = resp.text()
            except Exception:
                body = '<unreadable>'
            count = 0
            try:
                data = json.loads(body)
                if isinstance(data, list):
                    count = len(data)
            except Exception:
                pass
            result.stores_requests.append({
                'url': resp.url,
                'status': resp.status,
                'count': count,
                'preview': body[:300],
            })

    page.on('console', on_console)
    page.on('response', on_response)

    page.goto(product_url, wait_until='domcontentloaded', timeout=90000)
    page.wait_for_timeout(3000)

    if not add_product_to_cart(page, locale, result):
        result.errors.append('Could not add product to cart from PDP')
        return result

    cart_url = f'{BASE}/{locale}/checkout/cart/'
    page.goto(cart_url, wait_until='domcontentloaded', timeout=90000)
    page.wait_for_timeout(5000)

    html = page.content()
    result.cart_items = html.count('cart item') + html.count('class="item-info"')
    result.modal_count = html.count('id="mycartinstallerModal"')
    result.modal_found = result.modal_count > 0
    result.config_found = 'checkoutStoreLocatorConfig' in html

    if result.modal_count != 1:
        result.notes.append(f'Expected 1 fitting modal, found {result.modal_count}')

    if not result.modal_found:
        result.errors.append('Fitting location modal not found on cart page (cart may be empty)')
        page.screenshot(path=f'/tmp/fitting-{locale}-cart.png', full_page=True)
        result.notes.append(f'screenshot: /tmp/fitting-{locale}-cart.png')
        return result

    opener = page.locator('#cart-installer-location').first
    if opener.count() == 0:
        opener = page.locator('[data-bs-target="#mycartinstallerModal"]').first
    opener.click(timeout=10000)
    page.wait_for_timeout(2000)

    modal = page.locator('#mycartinstallerModal')
    try:
        modal.wait_for(state='visible', timeout=15000)
    except PlaywrightTimeout:
        result.errors.append('Modal did not become visible after click')

    deadline = time.time() + 25
    while time.time() < deadline:
        loader = page.locator('#mycartinstallerModal #installerLoading')
        installers = page.locator('#mycartinstallerModal .allInstaller .installer')
        loader_visible = loader.count() > 0 and loader.is_visible()
        installer_count = installers.count()
        if installer_count > 0:
            result.installer_count = installer_count
            result.loader_visible = loader_visible
            break
        if result.stores_requests and not loader_visible:
            break
        page.wait_for_timeout(500)

    loader = page.locator('#mycartinstallerModal #installerLoading')
    result.loader_visible = loader.count() > 0 and loader.is_visible()
    result.installer_count = page.locator('#mycartinstallerModal .allInstaller .installer').count()

    if result.installer_count == 0 and result.loader_visible:
        result.errors.append('Loader still visible and no installers rendered after 25s')
    elif result.installer_count == 0:
        if not result.stores_requests:
            result.errors.append('No stores AJAX request observed')
        else:
            last = result.stores_requests[-1]
            if last.get('count', 0) == 0:
                result.errors.append(f'Stores AJAX returned empty array (HTTP {last.get("status")})')
            else:
                result.errors.append(
                    f'Stores returned {last.get("count")} items but installer list not rendered'
                )

    page.screenshot(path=f'/tmp/fitting-{locale}-modal.png', full_page=False)
    result.notes.append(f'screenshot: /tmp/fitting-{locale}-modal.png')
    return result


def main() -> int:
    results: list[LocaleResult] = []
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=True,
            channel='chrome',
            args=['--no-sandbox', '--disable-dev-shm-usage'],
        )
        for locale in ('en', 'ar'):
            context = browser.new_context(
                viewport={'width': 1440, 'height': 900},
                locale='ar-SA' if locale == 'ar' else 'en-US',
                user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            )
            page = context.new_page()
            print(f'\n=== Testing {locale.upper()} ===')
            try:
                r = run_locale(page, locale)
            except Exception as e:
                r = LocaleResult(locale=locale, errors=[f'Unhandled exception: {e}'])
            results.append(r)
            print(f'cart_items_hint={r.cart_items} modal_count={r.modal_count} config_found={r.config_found}')
            print(f'installer_count={r.installer_count} loader_visible={r.loader_visible}')
            print(f'stores_requests={len(r.stores_requests)}')
            for req in r.stores_requests:
                print(f"  {req['status']} count={req['count']} {req['url']}")
            if r.console_errors:
                print('relevant_console_errors:')
                for err in r.console_errors[:10]:
                    print(f'  {err}')
            if r.errors:
                print('ERRORS:')
                for err in r.errors:
                    print(f'  {err}')
            if r.notes:
                for note in r.notes:
                    print(f'note: {note}')
            context.close()
        browser.close()

    failed = [r for r in results if r.errors]
    if failed:
        print('\nRESULT: FAIL')
        return 1
    print('\nRESULT: PASS')
    return 0


if __name__ == '__main__':
    sys.exit(main())
