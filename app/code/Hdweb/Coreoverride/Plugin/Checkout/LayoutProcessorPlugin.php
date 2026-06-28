<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\Checkout;

use Hdweb\Coreoverride\Model\Checkout\CheckoutJsLayoutNormalizer;
use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Ensure payment jsLayout nodes exist before LayoutProcessor runs.
 */
class LayoutProcessorPlugin
{
    public function aroundProcess(LayoutProcessor $subject, callable $proceed, array $jsLayout): array
    {
        return $proceed(CheckoutJsLayoutNormalizer::normalize($jsLayout));
    }
}
