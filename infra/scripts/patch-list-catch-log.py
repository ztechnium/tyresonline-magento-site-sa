#!/usr/bin/env python3
import re
from pathlib import Path

path = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml")
text = path.read_text()
text = re.sub(
    r'\t+@file_put_contents\([^\n]+\n',
    '',
    text,
)
log_line = "@file_put_contents('/var/www/magento/var/log/list-render-errors.log', $e->getMessage() . \"\\n\", FILE_APPEND);"
needle = "} catch (\\Throwable $e) {\n\t\t\t\t\t\t\t$productUrl = htmlspecialchars"
insert = "} catch (\\Throwable $e) {\n\t\t\t\t\t\t\t" + log_line + "\n\t\t\t\t\t\t\t$productUrl = htmlspecialchars"
count = text.count(needle)
text = text.replace(needle, insert)
path.write_text(text)
print("patched", count, "render catch blocks")
