#!/usr/bin/env python3
"""Apply KSA car-tyres fixes and benchmark vs UAE."""
import subprocess
import urllib.request

MAGENTO = "/var/www/magento"

def run(cmd):
    print(f"+ {cmd}")
    return subprocess.run(cmd, shell=True, capture_output=True, text=True)

def bench(url, currency):
    with urllib.request.urlopen(url) as r:
        html = r.read().decode("utf-8", errors="replace")
    size = len(html.encode("utf-8"))
    prices = html.count(f"{currency} ")
    modern = html.count("product-box modern-layout")
    mobile = html.count("list-view-mobile")
    main = html.count("main-product-listing")
    grid = html.count("products-grid")
  # mid-page doctype (after first 1000 chars)
    mid_doctype = "<!doctype html>" in html[1000:].lower() or "<!DOCTYPE html>" in html[1000:]
    return {
        "size": size,
        "prices": prices,
        "modern": modern,
        "mobile": mobile,
        "main": main,
        "grid": grid,
        "mid_doctype": mid_doctype,
    }

print("=== Flushing all caches ===")
run(f"rm -rf {MAGENTO}/var/page_cache/* {MAGENTO}/var/view_preprocessed/* {MAGENTO}/var/cache/*")
run(f"cd {MAGENTO} && sudo -u www-data php bin/magento cache:flush")

print("\n=== KSA benchmark (cold) ===")
ksa = bench("https://stg.tyresonline.sa/en/all-tyres/car-tyres.html", "SAR")
for k, v in ksa.items():
    print(f"  {k}: {v}")

print("\n=== UAE benchmark ===")
uae = bench("https://stg.tyresonline.ae/en/all-tyres/car-tyres.html", "AED")
for k, v in uae.items():
    print(f"  {k}: {v}")

print("\n=== KSA benchmark (warm / FPC) ===")
ksa2 = bench("https://stg.tyresonline.sa/en/all-tyres/car-tyres.html", "SAR")
for k, v in ksa2.items():
    print(f"  {k}: {v}")
