from pathlib import Path

p = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml")
text = p.read_text()
log_line = '@file_put_contents("/tmp/render_mobile_errors.log", (string)$_product->getId() . ": " . $e->getMessage() . "\\n", FILE_APPEND);'
needle = "} catch (\\Throwable $e) {\n\t\t\t\t\t\t$productUrl = htmlspecialchars((string) $_product->getProductUrl()"
idx = text.find("function renderMobileProductItem")
if idx < 0:
    raise SystemExit("renderMobileProductItem not found")
catch_idx = text.find("} catch (\\Throwable $e) {", idx)
if catch_idx < 0:
    raise SystemExit("mobile catch not found")
insert_at = catch_idx + len("} catch (\\Throwable $e) {\n")
if log_line in text:
    print("already logged")
else:
    text = text[:insert_at] + "\t\t\t\t\t\t" + log_line + "\n" + text[insert_at:]
    p.write_text(text)
    print("mobile catch logging added")

# desktop catch
needle2 = "function renderProductItem"
idx2 = text.find(needle2)
catch2 = text.find("} catch (\\Throwable $e) {", idx2)
log_line2 = '@file_put_contents("/tmp/render_desktop_errors.log", (string)$_product->getId() . ": " . $e->getMessage() . "\\n", FILE_APPEND);'
insert2 = catch2 + len("} catch (\\Throwable $e) {\n")
if log_line2 not in text:
    text = text[:insert2] + "\t\t\t\t\t\t" + log_line2 + "\n" + text[insert2:]
    p.write_text(text)
    print("desktop catch logging added")
