<?php

namespace Ecomteck\StorePickup\Plugin\Checkout\Session;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Prevent infinite loop when shipping carriers re-enter checkout session during collectTotals().
 */
class GetQuotePlugin
{
    /**
     * @param Session $subject
     * @param callable $proceed
     * @return CartInterface
     */
    public function aroundGetQuote(Session $subject, callable $proceed)
    {
        $reflection = new \ReflectionClass(Session::class);
        $isLoadingProperty = $reflection->getProperty('isLoading');
        $isLoadingProperty->setAccessible(true);
        $quoteProperty = $reflection->getProperty('_quote');
        $quoteProperty->setAccessible(true);

        if ($isLoadingProperty->getValue($subject)) {
            $quote = $quoteProperty->getValue($subject);
            if ($quote !== null) {
                return $quote;
            }
        }

        return $proceed();
    }
}
