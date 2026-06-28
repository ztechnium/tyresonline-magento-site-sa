from pathlib import Path
p = Path('/var/www/magento/vendor/magento/module-page-cache/Model/Controller/Result/BuiltinPlugin.php')
t = p.read_text()
needle = '$this->kernel->process($response);'
insert = '''file_put_contents('/tmp/builtin-plugin.log', date('c').' use='.(int)$usePlugin.' enabled='.(int)$this->config->isEnabled().' type='.$this->config->getType().' CC='.($response->getHeader('Cache-Control')?$response->getHeader('Cache-Control')->getFieldValue():'none').PHP_EOL, FILE_APPEND);
        $this->kernel->process($response);'''
if 'builtin-plugin.log' not in t:
    t = t.replace(needle, insert)
    p.write_text(t)
    print('patched')
else:
    print('already patched')
