<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Amasty_PageSpeedOptimizer
*/

declare(strict_types=1);

namespace Amasty\PageSpeedOptimizer\Model\DiagnosticResult\ResourceModel;

use Amasty\PageSpeedOptimizer\Model\DiagnosticResult\DiagnosticResult as DiagnosticResultModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DiagnosticResult extends AbstractDb
{
    public const TABLE_NAME = 'amasty_page_speed_optimizer_diagnostic';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, DiagnosticResultModel::RESULT_ID);
    }
}
