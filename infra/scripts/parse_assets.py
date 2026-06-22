import re
h = open("/tmp/new.html").read()
media_urls = re.findall(r'https://[^"\s<>]+', h)
media = [u for u in media_urls if '/media/' in u or '/static/' in u]
print("static urls:", sum(1 for u in media if '/static/' in u))
print("media urls:", sum(1 for u in media if '/media/' in u))
for u in sorted(set(media))[:25]:
    print(u)
