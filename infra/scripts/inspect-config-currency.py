#!/usr/bin/env python3
import re
path = '/var/www/magento/app/etc/config.php'
with open(path, encoding='utf-8') as f:
    t = f.read()

for pat in [r"'currency' => \[(.*?)\],\s*'customer'", r"'country' => \[(.*?)\],\s*'locale'"]:
    m = re.search(pat, t, re.S)
    if m:
        print('--- match ---')
        print(m.group(1)[:1200])

for s in ["'base' => 'AED'", "'default' => 'AED'", "'allow' => 'AED'", "'default' => 'AE'"]:
    print(s, 'count', t.count(s))
