<?php
/**
 * Generate production env.php for KSA from Terraform outputs.
 *
 * Usage:
 *   php generate-env-php.php \
 *     --source=../../app/etc/env.php \
 *     --output=../../app/etc/env.php.prod \
 *     --rds-host=tyresonline-sa-prod.xxx.rds.amazonaws.com \
 *     --redis-host=tyresonline-sa-prod-redis.xxx.cache.amazonaws.com
 */

$options = getopt('', [
    'source:',
    'output:',
    'rds-host:',
    'redis-host:',
    'db-name::',
    'db-user::',
    'db-pass::',
]);

foreach (['source', 'output', 'rds-host', 'redis-host'] as $required) {
    if (empty($options[$required])) {
        fwrite(STDERR, "Missing --$required\n");
        exit(1);
    }
}

$env = include $options['source'];

$env['MAGE_MODE'] = 'production';
$env['db']['connection']['default']['host'] = $options['rds-host'];
$env['db']['connection']['default']['dbname'] = $options['db-name'] ?? 'tyresonline_sa';
$env['db']['connection']['default']['username'] = $options['db-user'] ?? 'magento';
$env['db']['connection']['default']['password'] = $options['db-pass'] ?? 'magento';

$redisHost = $options['redis-host'];

$env['session']['save'] = 'redis';
$env['session']['redis']['host'] = $redisHost;
$env['session']['redis']['port'] = '6379';
$env['session']['redis']['database'] = '2';

$env['cache']['frontend']['default']['backend'] = 'Cm_Cache_Backend_Redis';
$env['cache']['frontend']['default']['backend_options']['server'] = $redisHost;
$env['cache']['frontend']['default']['backend_options']['port'] = '6379';
$env['cache']['frontend']['default']['backend_options']['database'] = '3';

$env['cache']['frontend']['page_cache']['backend'] = 'Cm_Cache_Backend_Redis';
$env['cache']['frontend']['page_cache']['backend_options']['server'] = $redisHost;
$env['cache']['frontend']['page_cache']['backend_options']['port'] = '6379';
$env['cache']['frontend']['page_cache']['backend_options']['database'] = '4';

$env['system']['default']['catalog']['search']['engine'] = 'opensearch';
$env['system']['default']['catalog']['search']['opensearch_server_hostname'] = '127.0.0.1';
$env['system']['default']['catalog']['search']['opensearch_server_port'] = '9200';
$env['system']['default']['catalog']['search']['opensearch_index_prefix'] = 'satyresonline2';
$env['system']['default']['catalog']['search']['opensearch_enable_auth'] = 0;
$env['system']['default']['catalog']['search']['opensearch_server_timeout'] = 15;

$env['queue']['consumers_wait_for_messages'] = 1;
$env['downloadable_domains'] = ['www.tyresonline.sa'];

$content = "<?php\nreturn " . var_export($env, true) . ";\n";
file_put_contents($options['output'], $content);

echo "Wrote {$options['output']}\n";
