#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
CODE=$MAGENTO/app/code
THEME=$MAGENTO/app/design/frontend/Hditsol/tyresonline
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB_NAME=tyresonline_sa
API_KEY='e9f1c4181623dc389b0fafbde0928c0b'

echo '=== 1. Saudi phone prefix (+966) ==='
PHONE_TMPL=$CODE/Hdweb/Core/view/base/web/template/ui/form/element/phone-overwrite.html
sed -i 's/+971/+966/g' "$PHONE_TMPL"
sed -i "s/placeholder: '5[0-9x]*'/placeholder: '5xxxxxxxx'/g" "$PHONE_TMPL"
grep -E 'phone-label|placeholder' "$PHONE_TMPL"

echo '=== 2. Wheel-Size API key (Tyrefinder + Vehicles) ==='
for f in "$CODE/Hdweb/Tyrefinder/Helper/Data.php" "$CODE/Hdweb/Vehicles/Helper/Data.php"; do
  sed -i "s/const WHEEL_SEARCH_APIKEY = '';/const WHEEL_SEARCH_APIKEY = '${API_KEY}';/g" "$f"
  sed -i "s/const WHEEL_SEARCH_APIKEY = '[^']*';/const WHEEL_SEARCH_APIKEY = '${API_KEY}';/g" "$f"
done
grep WHEEL_SEARCH "$CODE/Hdweb/Tyrefinder/Helper/Data.php" | head -2

echo '=== 3. Clear cached vehicle makes (Redis) ==='
redis-cli DEL vehicle_makes_list 2>/dev/null || true

echo '=== 4. Saudi cities/areas in checkout_city_area ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'SQL'
TRUNCATE TABLE checkout_city_area;
INSERT INTO checkout_city_area (city, area) VALUES
('Riyadh','Al Olaya'),('Riyadh','Al Malaz'),('Riyadh','Al Narjis'),('Riyadh','Al Sahafa'),('Riyadh','Al Yasmin'),
('Riyadh','King Fahd District'),('Riyadh','Al Muruj'),('Riyadh','Al Rabwah'),('Riyadh','Al Wurud'),('Riyadh','King Abdullah District'),
('Jeddah','Al Hamra'),('Jeddah','Al Rawdah'),('Jeddah','Al Salamah'),('Jeddah','Al Shati'),('Jeddah','Al Andalus'),
('Jeddah','Al Zahra'),('Jeddah','Al Faisaliyah'),('Jeddah','Al Nawras'),('Jeddah','Obhur'),('Jeddah','Al Basateen'),
('Dammam','Al Faisaliyah'),('Dammam','Al Adamah'),('Dammam','Al Shati'),('Dammam','Al Rakah'),('Dammam','Al Aziziyah'),
('Alkhobar','Al Hamra'),('Alkhobar','Al Thuqbah'),('Alkhobar','Al Aqrabiyah'),('Alkhobar','Al Khobar Al Shamalia'),
('Makkah','Al Aziziyah'),('Makkah','Al Awali'),('Makkah','Al Shisha'),('Makkah','Al Kakiyyah'),
('Abha','Al Andalus'),('Abha','Al Mansak'),('Abha','Al Namas Road'),
('Hail','Al Naqrah'),('Hail','Al Muntazah'),
('Hassa','Al Hofuf'),('Hassa','Al Mubarraz'),
('Yanbu','Al Bahr'),('Yanbu','Al Nakheel');
SELECT city, COUNT(*) AS cnt FROM checkout_city_area GROUP BY city ORDER BY city;
SQL

echo '=== 5. Enable Area dropdown on checkout ==='
LP=$CODE/Hdweb/Core/Model/Checkout/LayoutProcessor.php
python3 <<'PY'
from pathlib import Path
p = Path("/var/www/magento/app/code/Hdweb/Core/Model/Checkout/LayoutProcessor.php")
text = p.read_text()
# Uncomment region_id/region hide block
text = text.replace(
    "/*$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']\n        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'] = '';\n        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']\n        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region'] = '';*/",
    "$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']\n        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'] = '';\n        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']\n        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region'] = '';"
)
# Uncomment custom area select block
start = text.find("/*$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']\n        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region'] = [")
end = text.find("        ];*/", start)
if start != -1 and end != -1:
    block = text[start+3:end+9]
    text = text[:start] + block + text[end+10:]
p.write_text(text)
print("LayoutProcessor area field enabled")
PY

echo '=== 6. Enable city -> area AJAX on checkout ==='
CVF=$CODE/Hdweb/Shippingform/view/frontend/web/js/view/custom-vehicle-form.js
sed -i 's|/\*$(document).on('\''change'\'', "select\[name='\''city'\''\]", function() {|$(document).on('\''change'\'', "select[name='\''city'\''\]", function() {|' "$CVF"
sed -i 's|    });*/|    });|' "$CVF"

echo '=== 7. Harden vehicle model/year AJAX (null API response) ==='
for f in \
  "$CODE/Hdweb/Shippingform/Controller/Ajax/Getvehiclemodel.php" \
  "$CODE/Hdweb/Shippingform/Controller/Ajax/Getvehicleyear.php" \
  "$CODE/Hdweb/Tyrefinder/Controller/Ajax/Getmodel.php"
do
  [ -f "$f" ] || continue
  python3 - "$f" <<'PY'
import sys
from pathlib import Path
p = Path(sys.argv[1])
t = p.read_text()
if 'empty($WheelVehicleModel->data)' not in t and 'Getvehiclemodel' in p.name:
    t = t.replace(
        '$WheelVehicleModel =json_decode($WheelVehicleModel);\n\t\t\t\t\n                $modelSelectHtml',
        '$WheelVehicleModel = json_decode($WheelVehicleModel);\n\n                $modelSelectHtml'
    )
    t = t.replace(
        'if (count($WheelVehicleModel->data) > 0) {',
        'if ($WheelVehicleModel && !empty($WheelVehicleModel->data) && count($WheelVehicleModel->data) > 0) {'
    )
if 'empty($modelyear->data)' not in t and 'Getvehicleyear' in p.name:
    t = t.replace(
        'if (count($modelyear->data) > 0) {',
        'if ($modelyear && !empty($modelyear->data) && count($modelyear->data) > 0) {'
    )
if 'empty($WheelVehicleModel->data)' not in t and 'Getmodel' in p.name:
    t = t.replace(
        'if (count($WheelVehicleModel->data) > 0) {',
        'if ($WheelVehicleModel && !empty($WheelVehicleModel->data) && count($WheelVehicleModel->data) > 0) {'
    )
p.write_text(t)
print('patched', p.name)
PY
done

echo '=== 8. getArea() — placeholder only (areas load by city) ==='
CORE_HELPER=$CODE/Hdweb/Core/Helper/Data.php
python3 <<'PY'
from pathlib import Path
import re
p = Path("/var/www/magento/app/code/Hdweb/Core/Helper/Data.php")
t = p.read_text()
t = re.sub(
    r"public function getArea\(\)\s*\{.*?\n    \}",
    """public function getArea()
    {
        return [
            ['value' => '', 'label' => __('Select Area')],
        ];
    }""",
    t,
    count=1,
    flags=re.S,
)
p.write_text(t)
print("getArea updated")
PY

echo '=== 9. Magento cache + static deploy ==='
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
sudo rm -rf var/view_preprocessed/* pub/static/frontend/Hditsol/tyresonline/en_US/Hdweb_Core 2>/dev/null || true
sudo -u www-data php bin/magento setup:static-content:deploy -f en_US --theme Hditsol/tyresonline 2>&1 | tail -5

echo '=== 10. Quick API smoke test ==='
curl -s "https://api.wheel-size.com/v2/models/?user_key=${API_KEY}&make=toyota&region=medm" | head -c 120
echo
echo DONE
