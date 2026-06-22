#!/usr/bin/env python3
"""Disable Tabby installments in Magento shared config.php."""
import sys

path = '/var/www/magento/app/etc/config.php'
with open(path, encoding='utf-8') as f:
    content = f.read()

old = "'tabby_installments' => [\n                    'active' => '1',"
new = "'tabby_installments' => [\n                    'active' => '0',"

if old not in content:
    print('pattern not found', file=sys.stderr)
    sys.exit(1)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content.replace(old, new, 1))

print('Tabby disabled in config.php')
