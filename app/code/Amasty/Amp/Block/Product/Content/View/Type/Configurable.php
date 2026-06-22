<?php

declare(strict_types=1);

namespace Amasty\Amp\Block\Product\Content\View\Type;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as MagentoConfigurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;

class Configurable extends MagentoConfigurable
{
    public function isSwatchType(): bool
    {
        return $this->getData('swatchProvider')->isSwatchesEnable();
    }

    public function getSwatchesData(array $optionIds, Product $product, Attribute $attribute): array
    {
        $availableOptions = $this->helper->getOptions($product, $this->getAllowProducts());

        return array_intersect_key(
            $this->getData('swatchProvider')->getSwatchesData($optionIds),
            $availableOptions[$attribute->getAttributeId()]
        );
    }

    public function getSwatchPath(string $type, string $filename): string
    {
        return $this->getData('mediaHelper')->getSwatchAttributeImage($type, $filename);
    }
}
