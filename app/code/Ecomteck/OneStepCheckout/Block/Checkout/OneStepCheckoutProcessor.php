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
namespace Ecomteck\OneStepCheckout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Module\Manager as ModuleManager;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Ui\Component\Form\AttributeMapper;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Ecomteck\OneStepCheckout\Helper\AddressFields as AddressFieldsHelper;

class OneStepCheckoutProcessor implements LayoutProcessorInterface
{
    const CONFIG_DISABLE_LOGIN_PATH = 'one_step_checkout/display/disable_login_popup';
    const CONFIG_DISABLE_FIELD_PATH = 'one_step_checkout/display/disable_%s';
    const CONFIG_MOVE_CART_ITEMS    = 'one_step_checkout/display/move_cart_items';
    const CONFIG_PATH_FIELD_ORDER   = 'one_step_checkout/field_order';
    const CONFIG_PATH_MOVE_BILLING_ADDRESS_BEFORE_SHIPPING_ADDRESS = 'one_step_checkout/display/move_billing_before_shipping';

    /**
     * Shipping address fields that can be disabled by backend configuration.
     *
     * @var array
     */
    const DISABLE_FIELDS = [
        'telephone',
        'company'
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $moduleManager;

    /**
     * @var AttributeMetadataDataProvider
     */
    public $attributeMetadataDataProvider;

    /**
     * @var AttributeMapper
     */
    public $attributeMapper;

    /**
     * @var AttributeMerger
     */
    public $merger;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var null
     */
    public $quote = null;

    protected $addressFieldsHelper;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;

    protected $request;
	
	protected $hdwebcorehelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ModuleManager $moduleManager,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        CheckoutSession $checkoutSession,
        AddressFieldsHelper $addressFieldsHelper,
        \Ecomteck\OneStepCheckout\Helper\Config $config,
        \Magento\Framework\App\Request\Http $request,
		\Hdweb\Core\Helper\Data $hdwebcorehelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->checkoutSession = $checkoutSession;
        $this->addressFieldsHelper = $addressFieldsHelper;
        $this->_config = $config;
        $this->request = $request;
		$this->hdwebcorehelper	= $hdwebcorehelper;
    }

    /**
     * Get Quote
     *
     * @return \Magento\Quote\Model\Quote|null
     */
    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Gets field order from config.
     *
     * @return array
     */
    private function getFieldOrder()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_FIELD_ORDER, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Disables authentication modal.
     *
     * @param array $jsLayout
     * @return array
     */
    private function disableAuthentication($jsLayout)
    {
        if ($this->scopeConfig->getValue(self::CONFIG_DISABLE_LOGIN_PATH, ScopeInterface::SCOPE_STORE)) {
            unset($jsLayout['components']['checkout']['children']['authentication']);
        }
        return $jsLayout;
    }

    /**
     * Changes cart items to be above totals in the cart summary.
     *
     * @param array $jsLayout
     * @return array
     */
    private function changeCartItemsSortOrder($jsLayout)
    {
        if ($this->scopeConfig->getValue(self::CONFIG_MOVE_CART_ITEMS, ScopeInterface::SCOPE_STORE)) {
            $jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['cart_items']
                ['sortOrder'] = 0;
        }
        return $jsLayout;
    }

    /**
     * Disables / reorders specific input fields in shipping address fieldset.
     *
     * @param array $jsLayout
     * @return array
     */
    private function modifyShippingFields($jsLayout)
    {
        $shippingFields = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
        foreach ($shippingFields as $fieldName => $shippingField) {
            $fieldConfig = $this->addressFieldsHelper->getConfigValue($fieldName);
            if (isset($fieldConfig['order'])) {
                if ($fieldConfig['order'] !='') {
                    $shippingFields[$fieldName]['sortOrder'] = $fieldConfig['order'];
                }
            }

            if (isset($fieldConfig['validation'])) {
                $validations = explode(',', $fieldConfig['validation']);
                if (empty($shippingFields[$fieldName]['validation'])) {
                    $shippingFields[$fieldName]['validation'] = [];
                }
                foreach ($validations as $validation) {
                    $shippingFields[$fieldName]['validation'][$validation] = 1;
                }
            }
        }

        foreach (self::DISABLE_FIELDS as $field) {
            $configPath = sprintf(self::CONFIG_DISABLE_FIELD_PATH, $field);
            if ($this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE)) {
                unset($shippingFields[$field]);
            }
        }
        return $jsLayout;
    }

    /**
     * Disables / reorders specific input fields in billing address fieldset.
     *
     * @param array $jsLayout
     * @return array
     */
    private function modifyBillingFields($jsLayout)
    {
        foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                 ['payment']['children']['payments-list']['children'] as $code => &$payment) {
            if (isset($payment['children']['form-fields'])) {
                $billingFields = &$payment['children']['form-fields']['children'];

                foreach ($billingFields as $fieldName => $billingField) {
                    $fieldConfig = $this->addressFieldsHelper->getConfigValue($fieldName);
                    if (isset($fieldConfig['order'])) {
                        if ($fieldConfig['order'] !='') {
                            $billingFields[$fieldName]['sortOrder'] = $fieldConfig['order'];
                        }
                    }
                    if (isset($fieldConfig['validation'])) {
                        $validations = explode(',', $fieldConfig['validation']);
                        if (empty($billingFields[$fieldName]['validation'])) {
                            $billingFields[$fieldName]['validation'] = [];
                        }
                        foreach ($validations as $validation) {
                            $billingFields[$fieldName]['validation'][$validation] = 1;
                        }
                    }
                }

                foreach (self::DISABLE_FIELDS as $field) {
                    $configPath = sprintf(self::CONFIG_DISABLE_FIELD_PATH, $field);
                    if ($this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE)) {
                        unset($billingFields[$field]);
                    }
                }
            }
        }
        return $jsLayout;
    }

    /**
     * Move Billing Address After Shipping Address
     *
     * @param array $jsLayout
     * @return array
     */
    private function moveBillingAddress($jsLayout)
    {
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset'])) {
            
            $jsLayout = $this->removeBillingAddressInPaymentMethod($jsLayout);

            $elements = $this->getAddressAttributes();

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['billing-address'] = $this->getCustomBillingAddressComponent($elements);
            
            $billingFields = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children'];
            
            foreach ($billingFields as $fieldName => $shippingField) {
                $fieldConfig = $this->addressFieldsHelper->getConfigValue($fieldName);
                if (isset($fieldConfig['order'])) {
                    if ($fieldConfig['order'] !='') {
                        $billingFields[$fieldName]['sortOrder'] = $fieldConfig['order'];
                    }
                }
                if (isset($fieldConfig['validation'])) {
                    $validations = explode(',', $fieldConfig['validation']);
                    if (empty($billingFields[$fieldName]['validation'])) {
                        $billingFields[$fieldName]['validation'] = [];
                    }
                    foreach ($validations as $validation) {
                        $billingFields[$fieldName]['validation'][$validation] = 1;
                    }
                }
            }

            foreach (self::DISABLE_FIELDS as $field) {
                $configPath = sprintf(self::CONFIG_DISABLE_FIELD_PATH, $field);
                if ($this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE)) {
                    unset($billingFields[$field]);
                }
            }
        }
        
        return $jsLayout;
    }

    private function removeBillingAddressInPaymentMethod($jsLayout)
    {
        unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children']);
        return $jsLayout;
    }

    /**
     * Get all visible address attribute
     *
     * @return array
     */
    private function getAddressAttributes()
    {
        /** @var \Magento\Eav\Api\Data\AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_register_address'
        );
        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            /*
            if ($attribute->getIsUserDefined()) {
                continue;
            }*/
            $elements[$code] = $this->attributeMapper->map($attribute);
            if (isset($elements[$code]['label'])) {
                $label = $elements[$code]['label'];
                $elements[$code]['label'] = __($label);
            }
            if ($attribute->getIsUserDefined()) {
                $elements[$code]['is_user_defined'] = true;
                if ($elements[$code]['formElement'] == 'checkbox' && $elements[$code]['dataType'] == 'boolean') {
                    $elements[$code]['formElement'] = 'select';
                }
            }
        }
        return $elements;
    }
    /**
     * Prepare billing address field for shipping step for physical product
     *
     * @param $elements
     * @return array
     */
    public function getCustomBillingAddressComponent($elements)
    {
        
        $fields = [
            'component' => 'Ecomteck_OneStepCheckout/js/view/billing-address',
            'displayArea' => 'billing-address',
            'provider' => 'checkoutProvider',
            'deps' => ['checkoutProvider'],
            'dataScopePrefix' => 'billingAddress',
            'children' => [
                'form-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'additional-fieldsets',
                    'children' => $this->merger->merge(
                        $elements,
                        'checkoutProvider',
                        'billingAddress',
                        [
                            'country_id' => [
                                'sortOrder' => 115,
                            ],
                            'region' => [
                                'visible' => false,
                            ],
                            'region_id' => [
                                'component' => 'Magento_Ui/js/form/element/region',
                                'config' => [
                                    'template' => 'ui/form/field',
                                    'elementTmpl' => 'ui/form/element/select',
                                    'customEntry' => 'billingAddress.region',
                                ],
                                'validation' => [
                                    'required-entry' => true,
                                ],
                                'filterBy' => [
                                    'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                    'field' => 'country_id',
                                ],
                            ],
                            'postcode' => [
                                'component' => 'Magento_Ui/js/form/element/post-code',
                                'validation' => [
                                    'required-entry' => true,
                                ],
                            ],
                            'company' => [
                                'validation' => [
                                    'min_text_length' => 0,
                                ],
                            ],
                            'fax' => [
                                'validation' => [
                                    'min_text_length' => 0,
                                ],
                            ],
                            'telephone' => [
                                'config' => [
                                    'tooltip' => [
                                        'description' => __('For delivery questions.'),
                                    ],
                                ],
                            ],
                        ]
                    ),
                ],
            ],
        ];
        foreach ($elements as $attributeCode => $attribute) {
            if (!empty($attribute['is_user_defined'])) {
                $fields['children']['form-fields']['children'][$attributeCode]['config']['customScope'] = 'billingAddress.custom_attributes';
                $fields['children']['form-fields']['children'][$attributeCode]['dataScope'] = 'billingAddress.custom_attributes.' . $attributeCode;

            }
        }
        return $fields;
    }

    /**
     * Disables / reorders specific input fields in billing address fieldset.
     *
     * @param array $jsLayout
     * @return array
     */
    private function removeProgressBar($jsLayout)
    {
        unset($jsLayout['components']['checkout']['children']['progressBar']);
        return $jsLayout;
    }

    /**
     * Remove checkout agreements in payment method section.
     *
     * @param array $jsLayout
     * @return array
     */
    private function removeCheckoutAgreements($jsLayout)
    {
        if(isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children'])){
            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                 ['payment']['children']['payments-list']['children'] as $code => &$payment) {  
                if (isset($payment['children']['agreements'])) {
                    unset($payment['children']['agreements']);
                }
            }
        }
        
        return $jsLayout;
    }

    /**
     * Changes steps sort order.
     *
     * @param array $jsLayout
     * @return array
     */
    private function changeStepsSortOrder($jsLayout)
    {
        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['sortOrder'] = 2;
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['sortOrder'] = 1;
        $jsLayout['components']['checkout']['children']['steps']['children']['summary-step']['sortOrder'] = 3;
        return $jsLayout;
    }

    /**
     * {@inheritdoc}
     */
    public function process($jsLayout)
    {
        if (!$this->_config->isEnabled()) {
            return $jsLayout;
        }

        $jsLayout = $this->disableAuthentication($jsLayout);
        $jsLayout = $this->modifyShippingFields($jsLayout);
        $jsLayout = $this->removeCheckoutAgreements($jsLayout);
        $jsLayout = $this->modifyBillingFields($jsLayout);

        $jsLayout = $this->moveBillingAddress($jsLayout);
        $jsLayout = $this->changeCartItemsSortOrder($jsLayout);
        $jsLayout = $this->changeStepsSortOrder($jsLayout);
		
		/*$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['custom-vehicle']['children']['vehicle-form-form-fields']['children']['vin_number'] = [
            'component'  => 'Magento_Ui/js/form/element/abstract',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
                'options'     => [],
                'id'          => 'vin_number',
            ],
            'dataScope'  => 'shippingAddress.vin_number',
            'label'      => __('VIN Number'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 1,
            'id'         => 'plate',
        ];*/
		
		$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['custom-vehicle']['children']['vehicle-form-form-fields']['children']['plate'] = [
            'component'  => 'Magento_Ui/js/form/element/abstract',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
                'options'     => [],
                'id'          => 'plate',
            ],
            'dataScope'  => 'shippingAddress.plate',
            'label'      => __('Vehicle Plate'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 2,
            'id'         => 'plate',
             'validation' => [
                "required-entry" => true,
            ], 
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['custom-vehicle']['children']['vehicle-form-form-fields']['children']['make'] = [
            'component'  => 'Magento_Ui/js/form/element/select',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
                'id'          => 'make',
            ],
            'dataScope'  => 'shippingAddress.make',
            'label'      => __('Make'),
            //  'caption' => 'Please select',
            // 'validation' => [
            //     "required-entry"    => true,
            //     "max_text_length"   => 255,
            //     "min_text_length"   => 1
            // ],
            // 'filterBy' => null,
            // 'customEntry' => null,
            // 'visible' => true,
            // 'focused' => false,

            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 3,
             'validation' => [
                "required-entry" => true,
            ], 
            'id'         => 'make',
            'additionalClasses' => 'vehicle-dropdown',
            'options'    => $this->hdwebcorehelper->getOnestepCheckoutVehcilelist(),
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['custom-vehicle']['children']['vehicle-form-form-fields']['children']['model'] = [
            'component'  => 'Magento_Ui/js/form/element/select',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
            ],
            'dataScope'  => 'shippingAddress.model',
            'label'      => __('Model'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 4,
            'id'         => 'model',
            'additionalClasses' => 'vehicle-dropdown',
             'validation' => [
                "required-entry" => true,
            ], 
            'options'    => [
                [
                    'value' => '',
                    'label' => __('Select Model'),
                ],
            ],
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['custom-vehicle']['children']['vehicle-form-form-fields']['children']['year'] = [
            'component'  => 'Magento_Ui/js/form/element/select',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
            ],
            'dataScope'  => 'shippingAddress.year',
            'label'      => __('Year'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 5,
            'id'         => 'year',
            'additionalClasses' => 'vehicle-dropdown',
             'validation' => [
                "required-entry" => true,
            ], 
            'options'    => [
                [
                    'value' => '',
                    'label' => __('Select Year'),
                ],
            ],
        ];
		
        return $jsLayout;
    }
}
