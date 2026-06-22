import re
html = open('/tmp/checkout_body.html').read()
print('checkoutConfig', html.count('checkoutConfig'))
print('checkout_div', html.count('id="checkout"'))
print('checkout-messages', html.count('checkout-messages'))
print('onestepcheckout-page', 'onestepcheckout-page' in html)
m = re.search(r'class="column main">(.*?)</div>\s*</div>\s*</main>', html, re.S)
if m:
    chunk = re.sub(r'\s+', ' ', m.group(1))
    print('MAIN', chunk[:1000])
