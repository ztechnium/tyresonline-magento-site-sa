#!/usr/bin/env python3
"""Set KSA car tyre category ID in config.php (overrides DB)."""
from pathlib import Path

config = Path('/var/www/magento/app/etc/config.php')
text = config.read_text()
backup = config.with_suffix('.php.bak-car-tyre-cat')
if not backup.exists():
    backup.write_text(text)
new_text = text.replace("car_tyre_category_id' => '4'", "car_tyre_category_id' => '1945'")
config.write_text(new_text)
print('car_tyre_category_id entries:', new_text.count("car_tyre_category_id' => '1945'"))
