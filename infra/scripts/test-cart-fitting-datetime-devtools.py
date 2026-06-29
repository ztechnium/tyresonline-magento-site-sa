#!/usr/bin/env python3
"""Browser test for cart fitting date/time/checkbox visibility using Chrome CDP.

Uses the same flow as chrome-devtools-mcp (navigate, snapshot DOM, evaluate_script).
"""
from __future__ import annotations

import json
import re
import sys
import time

from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeout

BASE = 'https://stg.tyresonline.sa'
PRODUCT_PATHS = {
    'en': '/en/test-tyre-1-sar.html',
    'ar': '/ar/test-tyre-1-sar.html',
}


def extract_form_key(html: str) -> str:
    for pattern in (
        r'name="form_key"\s+type="hidden"\s+value="([^"]+)"',
        r'name="form_key"\s+value="([^"]+)"',
        r'"form_key":"([^"]+)"',
    ):
        m = re.search(pattern, html)
        if m:
            return m.group(1)
    return ''


def add_product_to_cart(page, locale: str) -> bool:
    for selector in (
        'form#product_addtocart_form button.tocart',
        '#product-addtocart-button',
        'button.tocart',
    ):
        btn = page.locator(selector).first
        try:
            if btn.count() and btn.is_visible():
                btn.click(timeout=10000)
                page.wait_for_timeout(4000)
                return True
        except Exception:
            pass

    form_key = extract_form_key(page.content())
    if not form_key:
        return False
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


def evaluate_modal_state(page) -> dict:
    return page.evaluate(
        """() => {
            const modal = document.querySelector('#mycartinstallerModal');
            if (!modal) {
                return { error: 'modal missing' };
            }
            const style = document.querySelector('style');
            const hideRule = Array.from(document.styleSheets)
                .flatMap((sheet) => {
                    try {
                        return Array.from(sheet.cssRules || []);
                    } catch (e) {
                        return [];
                    }
                })
                .some((rule) => rule.cssText && rule.cssText.includes('.storelocator .datetimesubmit{display: none'));

            const installers = modal.querySelectorAll('.allInstaller .installer');
            const first = installers[0];
            const checkbox = first ? first.querySelector('.checkboxInstaller') : null;
            const datetime = first ? first.querySelector('.datetimesubmit') : null;
            const dateInput = first ? first.querySelector('.storePickupdatepicker') : null;
            const timeSelect = first ? first.querySelector('.storePickuptimepicker') : null;

            const visible = (el) => {
                if (!el) return false;
                const cs = window.getComputedStyle(el);
                return cs.display !== 'none' && cs.visibility !== 'hidden' && el.offsetParent !== null;
            };

            return {
                installerCount: installers.length,
                hideDatetimeCssRule: hideRule,
                checkboxFound: !!checkbox,
                checkboxVisible: visible(checkbox),
                checkboxLabel: checkbox ? (checkbox.closest('label')?.innerText || checkbox.parentElement?.innerText || '').trim() : '',
                datetimeFound: !!datetime,
                datetimeVisible: visible(datetime),
                dateInputFound: !!dateInput,
                timeSelectFound: !!timeSelect,
            };
        }"""
    )


def test_locale(page, locale: str) -> dict:
    result = {'locale': locale, 'errors': [], 'state': {}}
    page.goto(BASE + PRODUCT_PATHS[locale], wait_until='domcontentloaded', timeout=90000)
    page.wait_for_timeout(3000)

    if not add_product_to_cart(page, locale):
        result['errors'].append('Could not add product to cart')
        return result

    page.goto(f'{BASE}/{locale}/checkout/cart/', wait_until='domcontentloaded', timeout=90000)
    page.wait_for_timeout(5000)

    if 'checkoutStoreLocatorConfig' not in page.content():
        result['errors'].append('checkoutStoreLocatorConfig missing (defer_init cart block)')

    opener = page.locator('#cart-installer-location').first
    if opener.count() == 0:
        opener = page.locator('[data-bs-target="#mycartinstallerModal"]').first
    opener.click(timeout=10000)
    page.wait_for_timeout(2000)

    try:
        page.locator('#mycartinstallerModal').wait_for(state='visible', timeout=15000)
    except PlaywrightTimeout:
        result['errors'].append('Modal did not open')

    deadline = time.time() + 25
    while time.time() < deadline:
        count = page.locator('#mycartinstallerModal .allInstaller .installer').count()
        if count > 0:
            break
        page.wait_for_timeout(500)

    result['state'] = evaluate_modal_state(page)

    if result['state'].get('installerCount', 0) == 0:
        result['errors'].append('No installers rendered in modal')
    if result['state'].get('hideDatetimeCssRule'):
        result['errors'].append('CSS still hides .datetimesubmit (cartref/defer_init not applied)')
    if not result['state'].get('checkboxFound'):
        result['errors'].append('Select Installer checkbox not found in list template')
    if not result['state'].get('checkboxVisible'):
        result['errors'].append('Select Installer checkbox not visible')

    # Checkbox reveals datetime block
    checkbox = page.locator('#mycartinstallerModal .checkboxInstaller').first
    if checkbox.count():
        checkbox.check(force=True)
        page.wait_for_timeout(1000)
        after = evaluate_modal_state(page)
        result['stateAfterCheck'] = after
        if not after.get('datetimeFound'):
            result['errors'].append('Date/time block missing after selecting installer')
        if not after.get('dateInputFound'):
            result['errors'].append('Date picker input missing after selecting installer')
        if not after.get('timeSelectFound'):
            result['errors'].append('Time select missing after selecting installer')

    page.screenshot(path=f'/tmp/fitting-datetime-{locale}.png', full_page=False)
    result['screenshot'] = f'/tmp/fitting-datetime-{locale}.png'
    return result


def main() -> int:
    results = []
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=True,
            channel='chrome',
            args=['--no-sandbox', '--disable-dev-shm-usage', '--remote-debugging-port=9222'],
        )
        for locale in ('en', 'ar'):
            context = browser.new_context(viewport={'width': 1440, 'height': 900})
            page = context.new_page()
            print(f'\n=== chrome-devtools-style test: {locale.upper()} ===')
            try:
                r = test_locale(page, locale)
            except Exception as exc:
                r = {'locale': locale, 'errors': [f'Unhandled: {exc}'], 'state': {}}
            results.append(r)
            print(json.dumps(r, indent=2, ensure_ascii=False))
            context.close()
        browser.close()

    failed = [r for r in results if r.get('errors')]
    print('\nRESULT:', 'FAIL' if failed else 'PASS')
    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
