<?php

namespace Hdweb\Shippingform\Model\Plugin;

class AttributeMergerPlugin
{
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        if (array_key_exists('make', $result)) {
            $result['make']['additionalClasses'] = 'your_custom_class';
        }

        return $result;
    }
}
