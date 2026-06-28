from pathlib import Path
p = Path('/var/www/magento/vendor/magento/module-page-cache/Model/Controller/Result/BuiltinPlugin.php')
t = p.read_text()
old = "file_put_contents('/tmp/builtin-plugin.log', date('c').' ENTRY builtin'.PHP_EOL, FILE_APPEND);\n        $usePlugin = $this->registry->registry('use_page_cache_plugin');"
new = "$usePlugin = $this->registry->registry('use_page_cache_plugin');\n        file_put_contents('/tmp/builtin-plugin.log', date('c').' ENTRY use='.(int)$usePlugin.' en='.(int)$this->config->isEnabled().' type='.$this->config->getType().PHP_EOL, FILE_APPEND);"
if old in t:
    t = t.replace(old, new)
    p.write_text(t)
    print('updated')
else:
    print('pattern not found')
