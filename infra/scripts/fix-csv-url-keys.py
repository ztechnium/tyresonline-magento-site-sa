#!/usr/bin/env python3
import csv
import subprocess
from collections import Counter

SRC = '/var/www/magento/var/import/Master_sheet_tires_15_6_2026.csv'
OUT = '/var/www/magento/var/import/Master_sheet_tires_15_6_2026_fixed.csv'

existing = set()
try:
    out = subprocess.check_output([
        'mysql', '-h', 'tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com',
        '-u', 'magento', '-puxaYMIQRwEU0AFl1Ck69LJnH', 'tyresonline_sa', '-N', '-e',
        "SELECT DISTINCT REPLACE(request_path, '.html', '') FROM url_rewrite WHERE entity_type='product'"
    ], text=True, stderr=subprocess.DEVNULL)
    existing = {line.strip().lower() for line in out.splitlines() if line.strip()}
except Exception:
    pass

with open(SRC, encoding='utf-8-sig') as f:
    rows = list(csv.DictReader(f))
    fieldnames = rows[0].keys()

seen = Counter()
trim_fixed = 0
for row in rows:
    for k in row:
        v = row[k]
        if isinstance(v, str):
            nv = v.strip()
            if nv != v:
                row[k] = nv
                trim_fixed += 1
    base = (row.get('url_key') or row['sku']).strip()
    sku = row['sku'].strip()
    row['url_key'] = f"{base}-{sku.lower()}"

with open(OUT, 'w', newline='', encoding='utf-8') as o:
    w = csv.DictWriter(o, fieldnames=fieldnames)
    w.writeheader()
    w.writerows(rows)

print(f'Wrote {len(rows)} rows; trimmed fields: {trim_fixed}')
