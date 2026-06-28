#!/usr/bin/env python3
"""Add absolute bottom positioning to AR PLP quantity-buy-section (match EN)."""
from pathlib import Path

path = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline-ar/web/css/product-list.css")
text = path.read_text(encoding="utf-8")

old = """.products .product-list.grid-view .product-box.modern-layout .product-box-wrap .quantity-buy-section {
  margin-bottom: 0.6818181818rem;
  display: flex;
  justify-content: space-between;
  direction: ltr;
}"""

new = """.products .product-list.grid-view .product-box.modern-layout .product-box-wrap .quantity-buy-section {
  margin-bottom: 0.6818181818rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.4545454545rem;
  direction: ltr;
  position: absolute;
  bottom: 0;
  left: 8px;
  right: 8px;
  width: auto;
}"""

if old not in text:
    raise SystemExit("pattern not found in product-list.css")

path.write_text(text.replace(old, new, 1), encoding="utf-8")
print("patched", path)
