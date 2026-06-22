#!/usr/bin/env python3
import re, sys
h = open('/tmp/ct.html').read()
boxes = h.count('product-box')
proxes = h.count('Proxes Sport')
toyo_titles = re.findall(r'title="(Toyo[^"]+)"', h)
print('product-box count:', boxes)
print('Proxes Sport mentions:', proxes)
print('Toyo title attrs:', len(toyo_titles))
for t in toyo_titles[:20]:
    print(' -', t)
# sticky / mobile sections
for pat in ['sticky', 'mobile-product', 'compare', 'selected-size', 'tire-products']:
    print(pat, h.lower().count(pat))
