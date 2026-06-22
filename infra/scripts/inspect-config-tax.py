#!/usr/bin/env python3
path = '/var/www/magento/app/etc/config.php'
needles = [
    'vat_percentage',
    'UAE VAT',
    'purchaseorder',
    "'defaults' =>",
    'cross_border',
]
with open(path, encoding='utf-8') as f:
    t = f.read()
for needle in needles:
    i = t.find(needle)
    if i != -1:
        print('---', needle, '---')
        print(t[max(0, i - 80): i + 160].replace('\n', ' '))
