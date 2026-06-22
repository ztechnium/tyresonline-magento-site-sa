#!/usr/bin/env python3
"""Apply KSA 15% VAT across Magento DB tables and locked config.php."""
path = '/var/www/magento/app/etc/config.php'
with open(path, encoding='utf-8') as f:
    content = f.read()

replacements = [
    ("'vat_percentage' => '5'", "'vat_percentage' => '15'"),
    (
        "'defaults' => [\n                    'country' => 'AE',",
        "'defaults' => [\n                    'country' => 'SA',",
    ),
    ("'vat' => '5'", "'vat' => '15'"),
    ("UAE VAT Rule", "KSA VAT Rule"),
    ("UAE VAT", "KSA VAT"),
]
for old, new in replacements:
    if old in content:
        n = content.count(old)
        content = content.replace(old, new)
        print(f'config.php: {old!r} -> {new!r} ({n}x)')
    else:
        print(f'config.php skip: {old!r}')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print('config.php done')
