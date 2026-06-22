<?php
namespace Hdweb\Core\Model\Checkout;

class LayoutProcessor
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;
    protected $hdwedcorehelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory $agreementCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Hdweb\Core\Helper\Data $hdwedcorehelper
    ) {
        $this->scopeConfig            = $context->getScopeConfig();
        $this->checkoutSession        = $checkoutSession;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->hdwedcorehelper        = $hdwedcorehelper;
    }
    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['city'] = [
            'component'  => 'Magento_Ui/js/form/element/select',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
                'id'          => 'city',
            ],
            'dataScope'  => 'shippingAddress.city',
            'label'      => __('City'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => ['required-entry' => true],
            'sortOrder'  => 250,
            'id'         => 'city',
            'options'    => $this->hdwedcorehelper->getCity(),
        ];


        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'] = '';
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region'] = '';

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region'] = [
            'component'  => 'Magento_Ui/js/form/element/select',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/select',
                'id'          => 'region',
            ],
            'dataScope'  => 'shippingAddress.region',
            'label'      => 'Area',
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => ['required-entry' => true],
            'sortOrder'  => 260,
            'id'         => 'region',
            'options'    => $this->hdwedcorehelper->getArea(),
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['company']['additionalClasses'] = 'company_field_div';
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['vat_id']['additionalClasses'] = 'vat_field_div';

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['vat_id']['label'] = __('TRN');

        /*$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['region']['additionalClasses'] = 'area_field_div';*/

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id']['additionalClasses'] = 'd-none';

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['config']['elementTmpl'] = 'Hdweb_Core/ui/form/element/phone-overwrite';

        return $jsLayout;
    }

}
