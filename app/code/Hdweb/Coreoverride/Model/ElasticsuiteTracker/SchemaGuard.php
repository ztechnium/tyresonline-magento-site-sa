<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Model\ElasticsuiteTracker;

use Magento\Framework\App\ResourceConnection;

class SchemaGuard
{
    private const TABLE = 'elasticsuite_tracker_log_event';
    private const COLUMN = 'is_invalid';

    private ?bool $hasColumn = null;

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    public function hasIsInvalidColumn(): bool
    {
        if ($this->hasColumn === null) {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName(self::TABLE);
            $this->hasColumn = $connection->isTableExists($table)
                && $connection->tableColumnExists($table, self::COLUMN);
        }

        return $this->hasColumn;
    }
}
