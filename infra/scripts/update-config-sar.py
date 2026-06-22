#!/usr/bin/env python3
"""Update locked KSA currency/country values in Magento config.php."""
path = '/var/www/magento/app/etc/config.php'
with open(path, encoding='utf-8') as f:
    content = f.read()

replacements = [
    ("'base' => 'AED'", "'base' => 'SAR'"),
    ("'default' => 'AED'", "'default' => 'SAR'"),
    ("'allow' => 'AED'", "'allow' => 'SAR'"),
    ("'default' => 'AE'", "'default' => 'SA'"),
    ("'allow' => 'AE'", "'allow' => 'SA'"),
    ("'optional_zip_countries' => 'AE'", "'optional_zip_countries' => 'SA'"),
    ("'country_id' => 'AE'", "'country_id' => 'SA'"),
    ("'timezone' => 'Asia/Dubai'", "'timezone' => 'Asia/Riyadh'"),
    ("'city' => 'Dubai'", "'city' => 'Riyadh'"),
    ("'currencycode' => 'AED'", "'currencycode' => 'SAR'"),
    ("'specificcountry' => 'AE'", "'specificcountry' => 'SA'"),
]

for old, new in replacements:
    if old in content:
        content = content.replace(old, new)
        print(f'updated: {old} -> {new}')
    else:
        print(f'skip (not found): {old}')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print('done')
