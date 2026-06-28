#!/usr/bin/env python3
"""Generate terms & conditions HTML from extracted docx text (Arabic) and EN template."""
from __future__ import annotations
import html
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
TXT = ROOT / 'terms-conditions-ksa.txt'

AR_MAJOR = {
    '1. أحكام البيع',
    '2. أحكام العروض',
    '3. أحكام تسعير السلع',
    '4. شروط الدفع',
    '5. توصيل المنتجات والخدمات',
    '6. أحكام المخاطر والملكية',
    '7. شروط الإرجاع',
    '8. أحكام المسؤولية القانونية',
    '9. الشروط العامة',
}

KSA_REPLACEMENTS_AR = [
    ('ضريبة القيمة المضافة القياسية في دولة الإمارات العربية المتحدة منذ 1 يناير 2018. لهذا السبب، يُفرض معدّل الضريبة القياسي البالغ 5%',
     'ضريبة القيمة المضافة في المملكة العربية السعودية. يُفرض معدّل الضريبة القياسي البالغ 15%'),
    ('تخضع الشروط والأحكام لقانون دولة الإمارات العربية المتحدة وسلطتها القضائية، ويوافق العميل على الخضوع للسلطة القضائية غير الحصرية في دولة الإمارات العربية المتحدة.',
     'تخضع الشروط والأحكام لقوانين المملكة العربية السعودية وسلطتها القضائية، ويوافق العميل على الخضوع للسلطة القضائية غير الحصرية في المملكة العربية السعودية.'),
    ('بطاقات الائتمان وبطاقات الخصم بالدرهم الإماراتي',
     'بطاقات الائتمان وبطاقات الخصم بالريال السعودي'),
    ('بموجب قانون دولة الإمارات العربية المتحدة، لا يجوز لـ TyresOnline.sa التعامل مع البلدان الخاضعة لعقوبات مكتب مراقبة الأصول الأجنبية',
     'وفقًا للأنظمة المعمول بها في المملكة العربية السعودية، لا يجوز لـ TyresOnline.sa التعامل مع البلدان أو الجهات الخاضعة للعقوبات الدولية'),
]

KSA_REPLACEMENTS_EN = [
    ('3.3 The standard tax of Value Added Tax (VAT) has been introduced in UAE since 1 January 2018. Due to the VAT introduction, all the goods and services supplied to the customer will be charged on standard rate of 5%. Products sold on TyresOnline.sa will be charged VAT wherever required.',
     '3.3 Value Added Tax (VAT) applies in the Kingdom of Saudi Arabia. The standard rate of 15% is charged on goods and services supplied to the customer where applicable. Products sold on TyresOnline.sa will be charged VAT wherever required.'),
    ('The terms and conditions are governed by the law and jurisdiction of United Arab Emirates and the customer agrees to submit to the non-exclusive jurisdiction of UAE.',
     'The terms and conditions are governed by the laws and jurisdiction of the Kingdom of Saudi Arabia and the customer agrees to submit to the non-exclusive jurisdiction of the Kingdom of Saudi Arabia.'),
    ('TyresOnline.sa accepts from online bank modes including credit card and debit cards in AED currency.',
     'TyresOnline.sa accepts online payment modes including credit and debit cards in SAR currency.'),
    ('As per the law of UAE, TyresOnline.sa shall NOT deal with or offer any products or services to OFAC sanctioned countries',
     'In accordance with applicable laws in the Kingdom of Saudi Arabia, TyresOnline.sa shall NOT deal with or offer any products or services to sanctioned countries or entities'),
    ('Buy Tyres Online Dubai', 'Buy Tyres Online Saudi Arabia'),
    ('Best Online Tyre Shop UAE', 'Best Online Tyre Shop KSA'),
    ('Car Tyres Dubai', 'Car Tyres Saudi Arabia'),
    ('Tyres Dubai', 'Tyres Saudi Arabia'),
]


def ksa_fix(text: str, replacements: list[tuple[str, str]]) -> str:
    for old, new in replacements:
        text = text.replace(old, new)
    text = text.replace('TyresOnline.ae', 'TyresOnline.sa')
    text = text.replace('tyresonline.ae', 'tyresonline.sa')
    text = text.replace('United Arab Emirates', 'Kingdom of Saudi Arabia')
    text = text.replace('UAE', 'Kingdom of Saudi Arabia')
    return text


def is_major_section(line: str) -> bool:
    return line.strip() in AR_MAJOR


def is_subclause(line: str) -> bool:
    return bool(re.match(r'^\d+(?:\.\d+)+\s', line.strip()))


def build_ar_body(lines: list[str]) -> str:
    out: list[str] = []
    for raw in lines:
        line = ksa_fix(raw.strip(), KSA_REPLACEMENTS_AR)
        if not line:
            continue
        if is_major_section(line):
            out.append(f'<p><strong style="font-size: 20px;">{html.escape(line)}</strong></p>')
        elif is_subclause(line):
            out.append(f'<p>{html.escape(line)}</p>')
        else:
            out.append(f'<p>{html.escape(line)}</p>')
    return '\n'.join(out)


def wrap_page(title: str, body: str, uppercase: bool) -> str:
    cls = 'section-title text-center text-uppercase' if uppercase else 'section-title text-center'
    return f'''<div class="cms-page-section">
<div class="container custom-width-1170">
<div class="{cls}">
<h1>{html.escape(title)}</h1>
</div>
<div class="text-block">
{body}
</div>
</div>
</div>'''


def strip_pagebuilder(content: str) -> str:
    content = html.unescape(content)
    content = re.sub(r'^<div data-content-type="html"[^>]*>', '', content)
    content = re.sub(r'</div>\s*$', '', content)
    content = content.replace('```html\n', '').replace('```', '')
    return content.strip()


def extract_text_block(en_raw: str) -> str:
    en_raw = strip_pagebuilder(en_raw)
    m = re.search(r'<div class="text-block">(.*)</div>\s*</div>\s*</div>', en_raw, re.S)
    if not m:
        return en_raw
    return m.group(1).strip()


def main() -> None:
    lines = [ln.rstrip() for ln in TXT.read_text(encoding='utf-8').splitlines()]
    title_ar = lines[0]
    ar_body = build_ar_body(lines[1:])
    ar_html = wrap_page(title_ar, ar_body, False)

    en_template = ROOT / 'terms-25-current.html'
    if en_template.exists():
        body = ksa_fix(extract_text_block(en_template.read_text(encoding='utf-8')), KSA_REPLACEMENTS_EN)
        en_html = wrap_page('Terms & Conditions for Sale of Tyres', body, True)
    else:
        en_html = ar_html

    (ROOT / 'terms-conditions-ar.html').write_text(ar_html, encoding='utf-8')
    (ROOT / 'terms-conditions-en.html').write_text(en_html, encoding='utf-8')
    print('AR', len(ar_html), 'EN', len(en_html))


if __name__ == '__main__':
    main()
