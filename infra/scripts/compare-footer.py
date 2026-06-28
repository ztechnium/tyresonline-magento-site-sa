#!/usr/bin/env python3
import re

for label, path in [("EN", "/tmp/en-tyres.html"), ("AR", "/tmp/ar-tyres.html")]:
    with open(path, encoding="utf-8", errors="ignore") as f:
        html = f.read()
    idx = html.find("footer-services")
    print(f"{label} footer-services at {idx}")
    if idx >= 0:
        print(html[idx : idx + 800])
    print(f"{label} priceslider refs: {html.count('priceslider')}")
    print(f"{label} fw-cdn: {html.count('fw-cdn')}")
    print("---")
