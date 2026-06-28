<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\Checkout;

use Hdweb\Coreoverride\Model\Checkout\CheckoutJsLayoutNormalizer;
use Magento\Checkout\Block\Onepage;
use ReflectionClass;

class OnepagePlugin
{
    public function aroundGetJsLayout(Onepage $subject, callable $proceed): string
    {
        $reflection = new ReflectionClass($subject);
        $property = $reflection->getProperty('jsLayout');
        $property->setAccessible(true);
        $jsLayout = $property->getValue($subject);
        if (!is_array($jsLayout)) {
            $jsLayout = [];
        }
        $property->setValue($subject, CheckoutJsLayoutNormalizer::normalize($jsLayout));

        return $proceed();
    }
}
