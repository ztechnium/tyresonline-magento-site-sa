#!/usr/bin/env python3
import urllib.request, re

checks = {
    'en': [
        'TYRE BRANDS WE TRUST | Best Tyres in KSA',
        'Quality tyre brands, anywhere in Saudi Arabia',
        'purchase tyres in Saudi Arabia',
        'cms-page-section',
        'text-center',
    ],
    'ar': [
        'كفرات من علامات تجارية نثق بها',
        'كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية',
        'موقع TyresOnline هو وجهتك الأولى',
        'لتسهيل عملية البحث عن الكفرات',
        'text-center',
    ],
    'en_bad': ['UAE', 'TyresOnline.ae', '7 Emirates', 'Dubai'],
}

for lang in ['en', 'ar']:
    html = urllib.request.urlopen(f'https://stg.tyresonline.sa/{lang}/all-tyre-brands').read().decode('utf-8', 'replace')
    body = html.split('class="all-brands"')[1].split('</body>')[0] if 'class="all-brands"' in html else html
    print(f'=== {lang} ===')
    for p in checks[lang]:
        print(f'  {p[:50]}: {"OK" if p in html else "MISSING"}')
    print(f'  إطارات in body: {body.count("إطارات")}')
    print(f'  كفرات in body: {body.count("كفرات")}')

html = urllib.request.urlopen('https://stg.tyresonline.sa/en/all-tyre-brands').read().decode('utf-8', 'replace')
body = html.split('class="all-brands"')[1] if 'class="all-brands"' in html else html
print('=== en_bad ===')
for p in checks['en_bad']:
    print(f'  {p}: {"FOUND" if p in body else "ok"}')
