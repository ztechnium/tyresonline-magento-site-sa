<?php

declare(strict_types=1);

namespace Amasty\Amp\Plugin\Stockstatus\Product\View\Type\Configurable;

use Amasty\Amp\Model\ConfigProvider;
use Amasty\Amp\Model\HtmlValidator;
use Amasty\Stockstatus\Plugin\Product\View\Type\Configurable as StockstatusConfigurable;

/**
 * Class for resolve conflict of Amasty_Amp and Amasty_Stockstatus
 * When Amasty_Stockstatus enabled, Amasty_Amp removes product content HTML
 * because Amasty_Stockstatus is adding JavaScript user scripts to the content
 */
class ValidateConfigurableHtml
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
     * @param StockstatusConfigurable $subject
     * @param string $result
     * @see \Amasty\Stockstatus\Plugin\Product\View\Type\Configurable::afterToHtml
     * @return string
     */
    public function afterAfterToHtml(StockstatusConfigurable $subject, string $result): string
    {
        return $this->config->isAmpProductPage() ? $this->validator->getValidHtml($result) : $result;
    }
}
