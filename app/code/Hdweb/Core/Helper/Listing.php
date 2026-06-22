<?php
namespace Hdweb\Core\Helper;

class Listing extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $optionFactory;

    protected $_attributeOptionCollection;

    protected $request;

    protected $productFactory;

    public function __construct(
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $attributeOptionCollection,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory
    ) {
        $this->optionFactory              = $optionFactory;
        $this->_attributeOptionCollection = $attributeOptionCollection;
        $this->request                    = $request;
        $this->productFactory             = $productFactory;
    }

    public function getRearsearchtext()
    {
        $width_rear_optionId  = $this->request->getParam('width_rear');
        $height_rear_optionId = $this->request->getParam('height_rear');
        $rim_rear_optionId    = $this->request->getParam('rim_rear');

        if (!empty($width_rear_optionId) && !empty($height_rear_optionId) && !empty($rim_rear_optionId)) {
            $poductReource = $this->productFactory->create();
            $attribute     = $poductReource->getAttribute('width');
            if ($attribute->usesSource()) {
                $width_rear_Text = $attribute->getSource()->getOptionText($width_rear_optionId);
            }

            $poductReource = $this->productFactory->create();
            $attribute     = $poductReource->getAttribute('height');
            if ($attribute->usesSource()) {
                $height_rear_Text = $attribute->getSource()->getOptionText($height_rear_optionId);
            }

            $poductReource = $this->productFactory->create();
            $attribute     = $poductReource->getAttribute('rim');
            if ($attribute->usesSource()) {
                $rim_rear_Text = $attribute->getSource()->getOptionText($rim_rear_optionId);
            }

            $rearsearchtext = '-' . $width_rear_Text . '/' . $height_rear_Text . ' R' . $rim_rear_Text;
            return $rearsearchtext;
        } else {
            return '';
        }
    }

}
