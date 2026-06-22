<?php

declare(strict_types=1);

namespace Amasty\Amp\Plugin\Stockstatus\Catalog\Block\Product\ListProductPlugin;

use Amasty\Amp\Model\ConfigProvider;
use Amasty\Amp\Model\HtmlValidator;
use Amasty\Stockstatus\Plugin\Catalog\Block\Product\ListProductPlugin as StockstatusListProductPlugin;

/**
 * Class for resolve conflict of Amasty_Amp and Amasty_Stockstatus
 * When Amasty_Stockstatus enabled, Amasty_Amp removes product content HTML
 * because Amasty_Stockstatus is adding JavaScript user scripts to the content
 */
class ValidateHtmlInProductList
{
    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var HtmlValidator
     */
    private $validator;

    public function __construct(
        ConfigProvider $config,
        HtmlValidator $validator
    ) {
        $this->config = $config;
        $this->validator = $validator;
    }

    /**
     * @param StockstatusListProductPlugin $subject
     * @param string $result
     * @see \Amasty\Stockstatus\Plugin\Catalog\Block\Product\ListProductPlugin::afterToHtml
     * @return string
     */
    public function afterAfterToHtml(StockstatusListProductPlugin $subject, string $result): string
    {
        return $this->config->isAmpCategoryPage() ? $this->validator->getValidHtml($result) : $result;
    }
}
