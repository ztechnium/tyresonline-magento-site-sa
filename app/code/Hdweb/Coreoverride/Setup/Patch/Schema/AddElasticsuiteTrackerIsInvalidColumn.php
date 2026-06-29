<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddElasticsuiteTrackerIsInvalidColumn implements SchemaPatchInterface
{
    private const TABLE = 'elasticsuite_tracker_log_event';
    private const COLUMN = 'is_invalid';
    private const INDEX = 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $table = $this->moduleDataSetup->getTable(self::TABLE);

        if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, self::COLUMN)) {
            $connection->addColumn(
                $table,
                self::COLUMN,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Has invalid data',
                    'after' => 'data',
                ]
            );
        }

        if (
            $connection->isTableExists($table)
            && $connection->tableColumnExists($table, self::COLUMN)
            && !$this->indexExists($connection, $table, self::INDEX)
        ) {
            $connection->addIndex($table, self::INDEX, [self::COLUMN]);
        }

        $connection->endSetup();

        return $this;
    }

    private function indexExists(AdapterInterface $connection, string $table, string $indexName): bool
    {
        foreach ($connection->getIndexList($table) as $index) {
            if (($index['KEY_NAME'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
