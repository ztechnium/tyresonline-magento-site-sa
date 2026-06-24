<?php

namespace Hdweb\Tyrefinder\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Productlisting extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_pricing;
    const SETFOUR = 4;
    const SETTWO = 2;
    const SETONE = 1;
    protected $ruleFactory;
    protected $_objectManager;
    protected $datetime;
    protected $_filesystem;
    protected $brandModel;
    protected $timezoneInterface;
    protected $productModel;
    protected $activeRules = null;
    protected $brandDetailsCache = [];


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\Helper\Data $pricing,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Filesystem $filesystem,
        \MGS\Brand\Model\Brand $brandModel,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Catalog\Model\Product $productModel

    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig  = $scopeConfig;
        $this->_pricing      = $pricing;
        $this->ruleFactory = $ruleFactory;
        $this->_objectManager = $objectManager;
        $this->datetime = $datetime;
        $this->_filesystem = $filesystem;
        $this->brandModel = $brandModel;
        $this->timezoneInterface = $timezoneInterface;
        $this->productModel = $productModel;
        parent::__construct($context);
    }

    public function getTyreSize($_product, $long = null)
    {
        $productAttributes = '';

        $width       = $this->getAttributeValue($_product, 'width');
        $height      = $this->getAttributeValue($_product, 'height');
        $rim         = $this->getAttributeValue($_product, 'rim');
        $load_index  = $_product->getLoadIndex();
        $speed_index = $this->getAttributeValue($_product, 'speed_index');
        $tyresize    = "";
        if (isset($width) && !empty($width)) {
            if ($height == '' || $height == 'None') {
                $tyresize = $width . " R" .  $rim . " " . $load_index . $speed_index;
            } else {
                $tyresize = $width . '/' . $height . " R" .  $rim . " " . $load_index . $speed_index;
            }
        }

        return $tyresize;
    }

    public function getAttributeValue($_product, $_attribute)
    {
        $_attributeId = $_product->getData($_attribute);
        $attr = $_product->getResource()->getAttribute($_attribute);
        $attributeValue = '';

        if ($attr && $attr->usesSource()) {
            $attributeValue = $attr->getSource()->getOptionText($_attributeId);
        } elseif (is_scalar($_attributeId)) {
            $attributeValue = (string) $_attributeId;
        }

        return $attributeValue;
    }

    /**
     * Resolve a product attribute label for the current store, falling back to admin labels.
     */
    public function getProductAttributeLabel($product, string $attributeCode): string
    {
        $attribute = $product->getResource()->getAttribute($attributeCode);
        if (!$attribute) {
            return '';
        }

        $value = trim((string)$attribute->getFrontend()->getValue($product));
        if ($value !== '' && strcasecmp($value, 'no') !== 0) {
            return $value;
        }

        $optionId = $product->getData($attributeCode);
        if ($optionId && $attribute->usesSource()) {
            $adminLabel = $attribute->getSource()->getOptionText($optionId);
            if ($adminLabel) {
                return trim((string)$adminLabel);
            }
        }

        $rawValue = $product->getResource()->getAttributeRawValue(
            (int)$product->getId(),
            $attributeCode,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );

        if ($rawValue && $attribute->usesSource()) {
            $defaultLabel = $attribute->getSource()->getOptionText($rawValue);
            if ($defaultLabel) {
                return trim((string)$defaultLabel);
            }
        }

        return is_scalar($rawValue) ? trim((string)$rawValue) : '';
    }

    public function translateSpecLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // Store-view labels may already be localized (e.g. Arabic option text).
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $value)) {
            return $value;
        }

        $translated = (string)__($value);
        return $translated !== '' ? $translated : $value;
    }

    public function getSet1price($_product)
    {
        $final_price = $_product->getPriceInfo()->getPrice('final_price')->getValue();
        $set1price   = $final_price * self::SETONE;
        $set1price   = $set1price + ($set1price * 0.05);
        //$set1price   = number_format($set1price, 2);
        $set1price   = number_format(round($set1price), 2, '.', '');
        // $set1price   = $this->_pricing->currency($set1price, true, false);
        return $set1price;
    }

    public function getSet2price($_product)
    {
        $final_price = $_product->getPriceInfo()->getPrice('final_price')->getValue();
        $set2price   = $final_price * self::SETTWO;
        $set2price   = $set2price + ($set2price * 0.05);
        $set2price   = number_format(round($set2price), 2, '.', '');
        //$set2price   = $this->_pricing->currency($set2price, true, false);
        return $set2price;
    }

    public function getSet4price($_product)
    {
        $final_price = $_product->getPriceInfo()->getPrice('final_price')->getValue();
        $set4price   = $final_price * self::SETFOUR;
        $set4price   = $set4price + ($set4price * 0.05);


        //$rulesId=$this->isAnyRuleExist($_product->getId());
        $rulesId = $this->isAnyRuleExist($_product);
        if (count($rulesId) > 0) {
            if ($rulesId[3] ==  4) {
                $discountPercentage = $rulesId[2] / 100;
                $set4price   = $set4price - ($set4price * $discountPercentage);
            }
        }
        $set4price   = number_format(round($set4price), 2, '.', '');
        //$set4price   = $this->_pricing->currency($set4price, true, false);

        return $set4price;
    }

    public function getVehcileImageUrl($_product, $vehicle_model)
    {
        $vehicle_model_value = $this->getAttributeValue($_product, $vehicle_model);
        $vehicle_model_value = strtolower($vehicle_model_value) . '-icon.svg';
        $imagepath           = 'vehicle_image/' . $vehicle_model_value;
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $imagepath;
    }

    public function getBrandDetails($manufacturerId)
    {
        if (!$manufacturerId || (is_array($manufacturerId) && empty($manufacturerId))) {
            return new \Magento\Framework\DataObject(['name' => '', 'image' => null]);
        }

        if (is_array($manufacturerId)) {
            $manufacturerId = reset($manufacturerId);
        }

        $cacheKey = (string) $manufacturerId;
        if (isset($this->brandDetailsCache[$cacheKey])) {
            return $this->brandDetailsCache[$cacheKey];
        }

        $brand = $this->brandModel;
        $brands = $brand->getCollection()->addFieldToFilter('option_id', ['eq' => $manufacturerId]);
        $brandItem = $brands->getFirstItem();

        if ($brandItem && $brandItem->getId()) {
            return $this->brandDetailsCache[$cacheKey] = $brandItem;
        }

        return $this->brandDetailsCache[$cacheKey] = new \Magento\Framework\DataObject(['name' => (string) $manufacturerId, 'image' => null]);
    }

    public function getBrandImageUrl($brand)
    {
        if (!is_object($brand) || !method_exists($brand, 'getImage')) {
            return false;
        }

        $image = $brand->getImage();
        if (!$image) {
            return false;
        }

        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $image;
    }

    public function isAnyRuleExist($product)
    {
        $currentStore = $this->_storeManager->getStore();
        $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $mediapathofferimage = $mediaUrl . "salesrule/offerimage/";
        $storeCode = $this->_storeManager->getStore()->getCode();
        if ($storeCode == 'ar') {
            $mediapathofferimage = $mediaUrl . "salesrule/offerimage/" . $storeCode . '/';
        }
        $currentDate = $this->datetime->date();
        $currentDate = $this->timezoneInterface->date($currentDate)->format('Y-m-d');
        if ($this->activeRules === null) {
            $this->activeRules = $this->ruleFactory->create()->getCollection()
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('coupon_type', 1)
                //->addFieldToFilter('rule_banner', ['neq' => 'NULL'])
                ->addFieldToFilter('from_date', ['lteq' => $currentDate])
                ->addFieldToFilter('to_date', ['gteq' => $currentDate]);
            $this->activeRules->getSelect()->order('sort_order DESC');
        }

        $item = $this->productModel;

        $imageColor = array();
        foreach ($this->activeRules as $rule) {
            $item->setProduct($product);
            $validate = $rule->getActions()->validate($item);
            if ($validate) {
                $imageColor[0] = '';
                $imageColor[1] = '';
                $imageColor[2] = '';
                $imageColor[3] = '';
                $imageColor[4] = '';
                $imageColor[5] = '';
                $imageColor[6] = '';

                if ($rule->getRuleBanner()) {
                    $imageColor[0] = $mediapathofferimage . $rule->getRuleBanner();
                }
                if ($rule->getRuleProductBanner()) {
                    $imageColor[1] = $mediapathofferimage . $rule->getRuleProductBanner();
                }
                if ($rule->getRuleProductBundleBanner()) {
                    $imageColor[2] = $mediapathofferimage . $rule->getRuleProductBundleBanner();
                }
                if ($rule->getRuleMobileBanner()) {
                    $imageColor[3] = $mediapathofferimage . $rule->getRuleMobileBanner();
                }

                $imageColor[4] = $rule->getColorText();
                $imageColor[5] = $rule->getDiscountAmount();
                $imageColor[6] = $rule->getDiscountStep();


                if ($rule->getDiscountStep() > 3 && $rule->getDiscountAmount() > 0) {
                    $discount_step_qty = $rule->getDiscountStep();
                    $module_qty = 4 % $discount_step_qty;
                    $discounted_qty = 4 - $module_qty;
                    $discount_amount_qty = $rule->getDiscountAmount();
                    //$set1price = $this->getSet1price($product);
                    $set1price = $product->getFinalPrice();
                    $set1price   = $set1price + ($set1price * 0.05);
                    $set1priceNumeric = str_replace(',', '', $set1price);
                    $set1priceNumeric   = number_format(round($set1priceNumeric), 2);
                    $set1priceNumericFinal = str_replace(',', '', $set1priceNumeric);
                    $pricefor_discount_item = ($discounted_qty * $set1priceNumericFinal  * $discount_amount_qty) / 100; //get percenatge
                    $offer_price_with_deducted_ammount = ($discounted_qty * $set1priceNumericFinal) - $pricefor_discount_item;
                    $pricefor_without_discount_item = $set1priceNumericFinal * $module_qty;
                    $offer_price = $offer_price_with_deducted_ammount + $pricefor_without_discount_item;

                    $imageColor[7] = round($offer_price);
                } else {
                    $imageColor[7] = '';
                }
                $imageColor[8] = $rule->getRuleId();
                $imageColor[9] = $rule->getName();
            }
        }
        return $imageColor;
    }
    public function getSet1priceWithoutCurrency($_product)
    {
        $final_price = $_product->getPriceInfo()->getPrice('final_price')->getValue();
        $set1price   = $final_price * self::SETONE;
        $set1price   = $set1price + ($set1price * 0.05);
        //$set1price   = $this->_pricing->currency($set1price, true, false);
        return round($set1price);
    }

    public function getVatIncPrice($price)
    {
        $set1price   = $price + ($price * 0.05);
        $set1price   = number_format(round($set1price), 2);
        // $set1price   = $this->_pricing->currency($set1price, true, false);
        return $set1price;
    }

    public function getSalebleQty($productId)
    {
        $websiteCode = $this->_storeManager->getWebsite()->getCode();
        $stockresolver = $this->_objectManager->get('Magento\InventorySalesApi\Api\StockResolverInterface');
        $salesChannelInterface = $this->_objectManager->get('Magento\InventorySalesApi\Api\Data\SalesChannelInterface');
        $stockDetails = $stockresolver->execute($salesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stockDetails->getStockId();
        $productFactory = $this->_objectManager->get('Magento\Catalog\Model\ProductFactory');
        $productDetails = $productFactory->create()->load($productId);
        $sku = $productDetails->getSku();
        $proType = $productDetails->getTypeId();
        $salebleqty = $this->_objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');

        if ($proType != 'configurable' && $proType != 'bundle' && $proType != 'grouped') {
            $stockQty = $salebleqty->execute($sku, $stockId);
            return $stockQty;
        } else {
            return '';
        }
    }

    public function getCustomerGroupName($customerGroupId)
    {
        $customerGroup = $this->_objectManager->get('Magento\Customer\Model\Group');
        $customerGroupDetails = $customerGroup->load($customerGroupId);
        $customerGroupName = $customerGroupDetails->getCustomerGroupCode();
        return $customerGroupName;
    }
}
