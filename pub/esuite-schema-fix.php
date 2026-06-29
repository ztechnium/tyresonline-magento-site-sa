<?php
/**
 * One-time ElasticSuite tracker schema repair (remove after use).
 * Usage: /esuite-schema-fix.php?token=tyres-export-fix-48f5
 */
declare(strict_types=1);

$token = $_GET['token'] ?? '';
if (!hash_equals('tyres-export-fix-48f5', $token)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();
$table = $resource->getTableName('elasticsuite_tracker_log_event');

header('Content-Type: text/plain; charset=utf-8');

try {
    if (!$connection->isTableExists($table)) {
        throw new RuntimeException("Missing table: {$table}");
    }

    if (!$connection->tableColumnExists($table, 'is_invalid')) {
        $connection->addColumn(
            $table,
            'is_invalid',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Has invalid data',
                'after' => 'data',
            ]
        );
        echo "Added column is_invalid\n";
    } else {
        echo "Column is_invalid already exists\n";
    }

    $indexes = $connection->getIndexList($table);
    $hasIndex = false;
    foreach ($indexes as $index) {
        if (($index['KEY_NAME'] ?? '') === 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID') {
            $hasIndex = true;
            break;
        }
    }
    if (!$hasIndex) {
        $connection->addIndex($table, 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID', ['is_invalid']);
        echo "Added index ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID\n";
    }

    $count = (int) $connection->fetchOne(
        $connection->select()->from($table, ['c' => 'COUNT(*)'])->where('is_invalid = ?', 1)
    );
    echo "Invalid events: {$count}\n";
    echo "OK\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
