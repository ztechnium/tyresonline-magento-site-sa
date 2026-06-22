<?php

declare(strict_types=1);

namespace Amasty\Amp\Plugin\Theme\Controller\Result;

use Amasty\Amp\Model\ConfigProvider;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Theme\Controller\Result\AsyncCssPlugin;

class AsyncCssRemover
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    public function aroundAfterRenderResult(
        AsyncCssPlugin $subject,
        \Closure $proceed,
        Layout $subjectLayout,
        Layout $result,
        ResponseInterface $httpResponse
    ): Layout {
        if ($this->configProvider->isAmpUrl()) {
            return $result;
        }

        return $proceed($subjectLayout, $result, $httpResponse);
    }
}
