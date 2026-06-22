<?php
namespace Sparkminds\AutoTruncate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class TruncateImportData implements ObserverInterface
{
    protected $resource;
    protected $logger;

    public function __construct(ResourceConnection $resource, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('importexport_importdata');
        try {
            $this->logger->debug("Truncate for table start: $tableName");
            $connection->query("TRUNCATE TABLE importexport_importdata");
            $this->logger->debug("Truncate for table sucessfully");
        } catch (\Exception $e) {
            // Log exception if needed
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error truncating table: %1', $e->getMessage())
            );
        }
    }
}
