<?php
/**
 * Ecomteck
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Ecomteck.com license that is
 * available through the world-wide-web at this URL:
 * https://ecomteck.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Ecomteck
 * @package     Ecomteck_OneStepCheckout
 * @copyright   Copyright (c) 2018 Ecomteck (https://ecomteck.com/)
 * @license     https://ecomteck.com/LICENSE.txt
 */
namespace Ecomteck\OneStepCheckout\Helper;

use Magento\Framework\App\ObjectManager;
use Ecomteck\Core\Lib\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use Magento\Customer\Model\AttributeMetadataDataProvider;

/**
 * MinSaleQty value manipulation helper
 */
class AddressFields
{
    const XML_CONFIG_PATH_ADDRESS_FIELD_CONFIG = 'one_step_checkout/field_config/config';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var array
     */
    private $addressFieldsCache = [];

    /**
     * @var AttributeMetadataDataProvider
     */
    public $attributeMetadataDataProvider;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Math\Random $mathRandom,
        Json $serializer = null,
        AttributeMetadataDataProvider $attributeMetadataDataProvider
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
    }

    /**
     * Retrieve fixed qty value
     *
     * @param int|float|string|null $order
     * @return float|null
     */
    protected function fixOrder($order)
    {
        return !empty($order) ? (float) $order : 0;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    protected function serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (float) $value;
            return (string) $data;
        } elseif (is_array($value)) {
            $data = [];
            foreach ($value as $field => $order) {
                if (!array_key_exists($field, $data)) {
                    $data[$field] = $this->fixOrder($order);
                }
            }

            return $this->serializer->serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('field', $row)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $field => $order) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['field' => $field, 'order' => $this->fixOrder($order)];
        }
        return $result;
    }

    /**
     * Decode value from used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('field', $row)
            ) {
                continue;
            }
            $field = $row['field'];
            $order = $this->fixOrder($row['order']);
            $result[$field] = $row;
        }
        return $result;
    }

    /**
     * Retrieve order value from config
     *
     * @param int $field
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($field, $store = null)
    {
        $key = $field . '-' . $store;
        if (!isset($this->addressFieldsCache[$key])) {
            $value = $this->scopeConfig->getValue(
                self::XML_CONFIG_PATH_ADDRESS_FIELD_CONFIG,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );
            $value = $this->unserializeValue($value);
            
            if ($this->isEncodedArrayFieldValue($value)) {
                $value = $this->decodeArrayFieldValue($value);
            }
            
            $result = null;
            if ($value) {
                foreach ($value as $f => $data) {
                    if ($field == $f) {
                        $result = $data;
                        break;
                    }
                }
            }
            
            $this->addressFieldsCache[$key] = $result;
        }
        return $this->addressFieldsCache[$key];
    }

    /**
     * Make value readable by \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param string|array $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);
        return $value;
    }

    public function getAddressFields()
    {
        $elements = [];
        /** @var \Magento\Eav\Api\Data\AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );
        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            $elements[$code] = $attribute->getFrontendLabel();
        }
        return $elements;
        /*
        return [
            'firstname' => __('First Name'),
            'middlename' => __('Middle Name'),
            'lastname' => __('Last Name'),
            'company' => __('Company'),
            'street' => __('Street'),
            'company' => __('Company'),
            'city' => __('City'),
            'region_id' => __('Region'),
            'postcode' => __('Postal Code'),
            'country_id' => __('Country'),
            'telephone' => __('Telephone'),
            'vat_id' => __('VAT ID')
        ];*/
    }
}
