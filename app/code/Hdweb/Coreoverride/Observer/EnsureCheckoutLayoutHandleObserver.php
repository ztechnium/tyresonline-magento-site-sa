<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Guarantees checkout_index_index layout handle is present on checkout pages.
 * Without it, onestepcheckout customizations reference checkout.root but the block is never defined.
 */
class EnsureCheckoutLayoutHandleObserver implements ObserverInterface
{
    private const CHECKOUT_ACTIONS = [
        'checkout_index_index',
        'onestepcheckout_index_index',
    ];

    public function execute(Observer $observer): void
    {
        $action = (string) $observer->getData('full_action_name');
        if (!in_array($action, self::CHECKOUT_ACTIONS, true)) {
            return;
        }

        $layout = $observer->getData('layout');
        if (!$layout) {
            return;
        }

        $update = $layout->getUpdate();
        $handles = $update->getHandles();
        if (!in_array('checkout_index_index', $handles, true)) {
            $update->addHandle('checkout_index_index');
        }
        if (!in_array('onestepcheckout', $handles, true)) {
            $update->addHandle('onestepcheckout');
        }
    }
}
