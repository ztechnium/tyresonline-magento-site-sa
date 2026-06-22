<?php
$envFile = __DIR__ . '/../app/etc/env.php';
if (!file_exists($envFile)) {
    fwrite(STDERR, "env.php not found\n");
    exit(1);
}

$env = include $envFile;

$env['db']['connection']['default']['host'] = 'db';
$env['db']['connection']['default']['dbname'] = 'tyresonline_sa';
$env['db']['connection']['default']['username'] = 'magento';
$env['db']['connection']['default']['password'] = 'magento';

if (!isset($env['session'])) {
    $env['session'] = [];
}
$env['session']['save'] = 'redis';
$env['session']['redis']['host'] = 'redis';
$env['session']['redis']['port'] = '6379';
$env['session']['redis']['password'] = '';
$env['session']['redis']['database'] = '2';

if (!isset($env['cache']['frontend']['default'])) {
    $env['cache']['frontend']['default'] = [];
}
$env['cache']['frontend']['default']['backend'] = 'Cm_Cache_Backend_Redis';
$env['cache']['frontend']['default']['backend_options']['server'] = 'redis';
$env['cache']['frontend']['default']['backend_options']['port'] = '6379';
$env['cache']['frontend']['default']['backend_options']['database'] = '0';

if (!isset($env['cache']['frontend']['page_cache'])) {
    $env['cache']['frontend']['page_cache'] = [];
}
$env['cache']['frontend']['page_cache']['backend'] = 'Cm_Cache_Backend_Redis';
$env['cache']['frontend']['page_cache']['backend_options']['server'] = 'redis';
$env['cache']['frontend']['page_cache']['backend_options']['port'] = '6379';
$env['cache']['frontend']['page_cache']['backend_options']['database'] = '1';

if (!isset($env['queue'])) {
    $env['queue'] = [];
}
$env['queue']['consumers_wait_for_messages'] = 0;

if (!isset($env['MAGE_MODE'])) {
    $env['MAGE_MODE'] = 'developer';
}

$content = "<?php\nreturn " . var_export($env, true) . ";\n";
file_put_contents($envFile, $content);
echo "Updated app/etc/env.php for local Docker services.\n";
