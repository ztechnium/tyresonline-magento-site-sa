<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Controller\Adminhtml\Fix;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class ElasticsuiteTracker extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Backend::config';

    public function __construct(
        Context $context,
        private readonly ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('elasticsuite_tracker_log_event');

        try {
            if (!$connection->isTableExists($table)) {
                throw new \RuntimeException('Table elasticsuite_tracker_log_event was not found.');
            }

            if (!$connection->tableColumnExists($table, 'is_invalid')) {
                $connection->addColumn(
                    $table,
                    'is_invalid',
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => false,
                        'default' => '0',
                        'comment' => 'Has invalid data',
                        'after' => 'data',
                    ]
                );
            }

            if (!$this->indexExists($connection, $table, 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID')) {
                $connection->addIndex($table, 'ELASTICSUITE_TRACKER_LOG_EVENT_IS_INVALID', ['is_invalid']);
            }

            $count = (int) $connection->fetchOne(
                $connection->select()
                    ->from($table, ['count' => 'COUNT(*)'])
                    ->where('is_invalid = ?', 1)
            );

            $this->messageManager->addSuccessMessage(
                __('ElasticSuite tracker column is_invalid is ready. Invalid events in queue: %1.', $count)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Could not fix ElasticSuite tracker schema: %1', $e->getMessage())
            );
        }

        return $redirect->setPath('adminhtml/export/index');
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
}
