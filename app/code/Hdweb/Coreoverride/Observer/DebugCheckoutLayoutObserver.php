<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DebugCheckoutLayoutObserver implements ObserverInterface
{
    private const CHECKOUT_ACTIONS = [
        'checkout_index_index',
        'onestepcheckout_index_index',
    ];

    public function __construct(
        private readonly Http $request
    ) {
    }

    public function execute(Observer $observer): void
    {
        $action = $this->request->getFullActionName();
        if (!in_array($action, self::CHECKOUT_ACTIONS, true)) {
            return;
        }

        $layout = $observer->getData('layout');
        if (!$layout) {
            return;
        }

        $contentChildren = $layout->getChildNames('content');
        $rootErr = '';
        $blockClass = 'null';
        $directLen = 0;
        if ($layout->hasElement('checkout.root')) {
            $block = $layout->getBlock('checkout.root');
            $blockClass = $block ? get_class($block) : 'missing';
            if ($block) {
                try {
                    $directLen = strlen($block->toHtml());
                } catch (\Throwable $e) {
                    $rootErr = $e->getMessage();
                }
            }
        }

        $line = date('c') . ' action=' . $action
            . ' has_root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no')
            . ' block=' . $blockClass
            . ' direct_len=' . $directLen
            . ' root_err=' . $rootErr
            . ' content_children=' . implode(',', $contentChildren)
            . PHP_EOL;

        @file_put_contents(BP . '/var/co_layout_debug.log', $line, FILE_APPEND);
    }
}
