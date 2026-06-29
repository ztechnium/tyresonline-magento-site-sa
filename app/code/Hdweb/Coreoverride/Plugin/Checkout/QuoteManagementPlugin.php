<?php

declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\Checkout;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class QuoteManagementPlugin
{
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository
    ) {
    }

    /**
     * Ensure the quote shipping method matches a collected rate before placing the order.
     *
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param int|string $cartId
     * @param mixed $paymentMethod
     * @return array
     */
    public function beforePlaceOrder($subject, $cartId, $paymentMethod = null): array
    {
        $quote = $this->quoteRepository->get((int) $cartId);
        if ($quote->isVirtual()) {
            return [$cartId, $paymentMethod];
        }

        $this->ensureValidShippingMethod($quote);

        return [$cartId, $paymentMethod];
    }

    private function ensureValidShippingMethod(Quote $quote): void
    {
        $address = $quote->getShippingAddress();
        if (!$address->getCountryId()) {
            return;
        }

        $address->setCollectShippingRates(true);
        $quote->collectTotals();

        $selectedMethod = (string) $address->getShippingMethod();
        $rates = $address->getAllShippingRates();
        if ($rates === []) {
            return;
        }

        foreach ($rates as $rate) {
            if ($rate->getCarrier() . '_' . $rate->getMethod() === $selectedMethod) {
                return;
            }
        }

        $firstRate = reset($rates);
        $address->setShippingMethod($firstRate->getCarrier() . '_' . $firstRate->getMethod());
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }
}
