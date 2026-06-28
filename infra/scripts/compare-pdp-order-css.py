#!/usr/bin/env python3
import re

paths = {
    'EN': '/var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US/css/product-details.min.css',
    'AR': '/var/www/magento/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/product-details.min.css',
}

classes = [
    'product-specs',
    'product-add-cart',
    'product-people-asked',
    'product-why-choose',
    'related-product-list',
    'product-info-main',
    'product.media',
    'product-addcart',
]

for theme, path in paths.items():
    css = open(path, encoding='utf-8', errors='ignore').read()
    print(f'=== {theme} ({len(css)} bytes) ===')
    for cls in classes:
        needle = f'.columns .{cls}'
        idx = css.find(needle)
        if idx < 0:
            print(f'  {cls}: NOT FOUND')
            continue
        snippet = css[idx:idx + 300]
        m = re.search(r'order:(\d+)', snippet)
        print(f'  {cls}: order={m.group(1) if m else "none"}')
