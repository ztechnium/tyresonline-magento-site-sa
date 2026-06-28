#!/usr/bin/env python3
"""Generate privacy policy HTML from extracted docx text."""
from __future__ import annotations
import html
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
TXT = ROOT / 'privacy-policy-ksa.txt'

EN_SECTIONS = {
    'Consent to Collecting Information',
    'What Information We May Collect from You',
    'Information Usage',
    'Third-Party Data Sharing',
    'Data Retention',
    'Data Protection',
    'Your Rights',
    "Children's Privacy",
    'Use of Cookies',
    'International Data Transfers',
    'Changes to Our Privacy Policy',
}

AR_SECTIONS = {
    'الموافقة على جمع المعلومات',
    'المعلومات التي قد نجمعها من you',
    'المعلومات التي قد نجمعها منك',
    'استخدام المعلومات',
    'مشاركة البيانات مع أطراف ثالثة',
    'الاحتفاظ بالبيانات',
    'حماية البيانات',
    'حقوقك المضمونة بش regard معلوماتك',
    'حقوقك المضمونة بشأن معلوماتك',
    'خصوصية الأطفال',
    'استخدام الكوكيز "ملفات تعريف المتصفح"',
    'نقل البيانات الدولية',
    'تغييرات على سياسة الخصوصية الخاصة بنا',
}

EN_LIST_INTROS = {
    'Information collected by the aforementioned ways might be used in the following ways:',
    'Your information is critical to our business model, and we might share your information only as described below with other businesses that adhere to the Personal Data Protection Law issued by the Royal Decree No. (M/19):',
    'To protect your data from any unauthorized access, breaches, or leaks, we employ modern security measures to comply with the modern standards and the Personal Data Protection Law in Saudi Arabia. These measures include:',
    'We are committed to ensuring the privacy and protection of your information. In accordance with the Personal Data Protection Law, you have the following rights regarding your personal data:',
    'There is much information available to access on our website, during the normal interaction methods, such as:',
    'The types of cookies we use are:',
    'As a part of prioritizing the privacy and security of your personal data, including international data transfers, we will not disclose information to any party outside Saudi Arabia under these circumstances:',
}

AR_LIST_INTROS = {
    'قد تُستخدم المعلومات التي تم جمعها بالطرق المذكورة أعلاه بالطرق التالية:',
    'معلوماتك ضرورية لنموذج عملنا، وقد نشارك معلوماتك فقط كما هو موضح أدناه مع شركات أخرى تلتزم بقانون حماية البيانات الشخصية الصادر بموجب المرسوم الملكي رقم (م/19):',
    'لحماية بياناتك من أي وصول غير مصرح به، أو خروقات، أو تسريبات، نعتمد تدابير أمان حديثة للامتثال للمعايير الحديثة وقانون حماية البيانات الشخصية في المملكة العربية السعودية. تشمل هذه التدابير:',
    'نحن ملتزمون بضمان خصوصية وحماية معلوماتك. وفقًا لقانون حماية البيانات الشخصية، لديك الحقوق التالية فيما يتعلق ببياناتك الشخصية:',
    'هناك العديد من المعلومات المتاحة للوصول إليها على موقعنا، خلال طرق التفاعل العادية، مثل:',
    'أنواع الكوكيز التي نستخدمها هي:',
    'كجزء من إعطاء الأولوية لخصوصية وأمان بياناتك الشخصية، بما في ذلك نقل البيانات الدولية، لن نكشف عن المعلومات لأي طرف خارج المملكة العربية السعودية في ظل هذه الظروف:',
}


def split_en_ar(lines: list[str]) -> tuple[list[str], list[str]]:
    en, ar = [], []
    mode = 'en'
    for line in lines:
        if 'Arabic Version Below' in line:
            mode = 'ar'
            continue
        if mode == 'en':
            en.append(line)
        else:
            ar.append(line)
    return en, ar


def is_section(title: str, sections: set[str]) -> bool:
    t = title.strip()
    return t in sections or any(t.startswith(s[:20]) for s in sections if len(s) > 10)


def build_body(lines: list[str], sections: set[str], list_intros: set[str]) -> str:
    out: list[str] = []
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        i += 1
        if not line:
            continue
        if is_section(line, sections):
            out.append(f'<p><strong>{html.escape(line)}</strong></p>')
            continue
        if line in list_intros:
            out.append(f'<p><strong>{html.escape(line)}</strong></p>')
            continue
        # Collect bullet-style lines (international transfers, cookie types, rights, info collection)
        if line.startswith('If it ') or line.startswith('Right to ') or line.startswith('Essential Cookies'):
            items = [line]
            while i < len(lines):
                nxt = lines[i].strip()
                if not nxt:
                    i += 1
                    break
                if is_section(nxt, sections) or nxt in list_intros:
                    break
                if (
                    nxt.startswith('If it ')
                    or nxt.startswith('Right to ')
                    or 'Cookies:' in nxt
                    or nxt.startswith('الحق في ')
                    or nxt.startswith('الكوكيز ')
                    or nxt.startswith('إذا كانت ')
                    or nxt.startswith('معلوماتك، ')
                    or nxt.startswith('الطلبات ')
                    or nxt.startswith('إعدادات ')
                ):
                    items.append(nxt)
                    i += 1
                    continue
                break
            out.append('<ul>')
            for item in items:
                out.append(f'  <li>{html.escape(item)}</li>')
            out.append('</ul>')
            continue
        if line.startswith('Information that you provide') or line.startswith('Information required') or line.startswith('To provide you') or line.startswith('To carry out') or line.startswith('To ensure') or line.startswith('To notify'):
            items = [line]
            while i < len(lines):
                nxt = lines[i].strip()
                if not nxt:
                    i += 1
                    break
                if is_section(nxt, sections) or nxt in list_intros:
                    break
                if (
                    nxt.startswith('Information ')
                    or nxt.startswith('To provide ')
                    or nxt.startswith('To carry ')
                    or nxt.startswith('To ensure ')
                    or nxt.startswith('To notify ')
                    or nxt.startswith('لتزويدك ')
                    or nxt.startswith('لتنفيذ ')
                    or nxt.startswith('لتقديم ')
                    or nxt.startswith('لضمان ')
                    or nxt.startswith('لإعلامك ')
                    or nxt.startswith('المعلومات المطلوبة')
                ):
                    items.append(nxt)
                    i += 1
                    continue
                break
            out.append('<ul>')
            for item in items:
                out.append(f'  <li>{html.escape(item)}</li>')
            out.append('</ul>')
            continue
        if line.startswith('المعلومات التي تقدمها'):
            items = [line]
            while i < len(lines):
                nxt = lines[i].strip()
                if not nxt:
                    i += 1
                    break
                if is_section(nxt, sections) or nxt in list_intros:
                    break
                if nxt.startswith('المعلومات ') or nxt.startswith(' سجلات') or nxt.startswith('سجلات'):
                    items.append(nxt.lstrip())
                    i += 1
                    continue
                break
            out.append('<ul>')
            for item in items:
                out.append(f'  <li>{html.escape(item)}</li>')
            out.append('</ul>')
            continue
        if line.startswith('Firewall protection:') or line.startswith('Our business associates:') or line.startswith('Promotional Offers') or line.startswith('Business transfers:') or line.startswith('Protection of our website'):
            out.append(f'<p>{html.escape(line)}</p>')
            continue
        if line.startswith('حماية الجدار') or line.startswith('تشفير SSL') or line.startswith('مراقبة النشاط') or line.startswith('التحكم الصارم') or line.startswith('شركاؤنا') or line.startswith('العروض') or line.startswith('تحويلات') or line.startswith('حماية موقعنا'):
            out.append(f'<p>{html.escape(line)}</p>')
            continue
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


def main() -> None:
    lines = [ln.rstrip() for ln in TXT.read_text(encoding='utf-8').splitlines()]
    en_lines, ar_lines = split_en_ar(lines)
    en_lines = en_lines[1:]  # drop doc title; page uses h1 Privacy Policy
    ar_lines = ar_lines[1:]  # drop Arabic doc title

    en_html = wrap_page('Privacy Policy', build_body(en_lines, EN_SECTIONS, EN_LIST_INTROS), True)
    ar_html = wrap_page('سياسة الخصوصية', build_body(ar_lines, AR_SECTIONS, AR_LIST_INTROS), False)

    (ROOT / 'privacy-policy-en.html').write_text(en_html, encoding='utf-8')
    (ROOT / 'privacy-policy-ar.html').write_text(ar_html, encoding='utf-8')
    print('EN', len(en_html), 'AR', len(ar_html))


if __name__ == '__main__':
    main()
