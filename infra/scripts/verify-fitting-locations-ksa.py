#!/usr/bin/env python3
import urllib.request

checks = {
    'ar': [
        'متجر كفرات بالقرب مني - ابحث عن موقعك',
        'مواقع مناسبة لتركيب الكفرات معTYRESONLINE',
        'بالقرب مني',
        'في جميع أنحاء السعودية',
        'المملكة العربية السعودية',
    ],
    'en': [
        'Find Your Nearest TyresOnline Fitment Location in Saudi Arabia',
        'across the KSA',
        'Tyres Riyadh',
        'ALL OVER THE KSA',
    ],
    'en_bad': ['UAE', 'Dubai', '7 EMIRATES', 'across the UAE'],
}

for lang, phrases in checks.items():
    if lang == 'en_bad':
        url = 'https://stg.tyresonline.sa/en/storelocator'
        html = urllib.request.urlopen(url).read().decode('utf-8', 'replace')
        for p in phrases:
            # ignore nav URLs containing uae
            body = html.split('class="storelocator"')[1] if 'class="storelocator"' in html else html
            print(f'en_bad {p}:', 'FOUND' if p.lower() in body.lower() else 'ok')
        continue
    url = f'https://stg.tyresonline.sa/{lang}/storelocator'
    html = urllib.request.urlopen(url).read().decode('utf-8', 'replace')
    for p in phrases:
        print(f'{lang} {p[:40]}...:', 'OK' if p in html else 'MISSING')

# city list check
html = urllib.request.urlopen('https://stg.tyresonline.sa/ar/storelocator').read().decode('utf-8', 'replace')
import re
m = re.search(r'class="bottom position-relative.*?<p>(.*?)</p>', html, re.S)
if m:
    cities = re.sub(r'<[^>]+>', ' ', m.group(1))
    print('cities:', re.sub(r'\s+', ' ', cities).strip())
section = html[html.find('tyres-fitting-text'):html.find('installerShowMap')] if 'tyres-fitting-text' in html else html
print('إطارات in fitting section:', section.count('إطارات'))
print('كفرات in fitting section:', section.count('كفرات'))
