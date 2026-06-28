from pathlib import Path
p = Path('/var/www/magento/vendor/magento/module-page-cache/Model/Controller/Result/BuiltinPlugin.php')
t = p.read_text()
if 'ENTRY builtin' not in t:
    t = t.replace(
        'public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)',
        'public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)\n    {\n        file_put_contents(\'/tmp/builtin-plugin.log\', date(\'c\').\' ENTRY builtin\'.PHP_EOL, FILE_APPEND);'
    )
    t = t.replace(
        'public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)\n    {\n        file_put_contents(\'/tmp/builtin-plugin.log\', date(\'c\').\' ENTRY builtin\'.PHP_EOL, FILE_APPEND);\n    {',
        'public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)\n    {\n        file_put_contents(\'/tmp/builtin-plugin.log\', date(\'c\').\' ENTRY builtin\'.PHP_EOL, FILE_APPEND);'
    )
    p.write_text(t)
    print('entry patched')
else:
    print('already has entry')
