<?php
/**
 * MB "Vienas bitas" (Magetrend.com)
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */

namespace Magetrend\PdfTemplates\Model\Pdf;

/**
 * Abstract variable filter class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
abstract class Filter
{
    /**
     * @var array|null
     */
    protected $data = null;

    /**
     * @var \Magento\Sales\Model\AbstractModel
     */
    public $source;

    /**
     * @var \Magento\Sales\Model\Order
     */
    public $order = null;

    /**
     * @var \Magetrend\PdfTemplates\Helper\Data
     */
    public $moduleHelper;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    public $paymentHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    public $countryFactory;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    public $eventManager;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    public $dataObjectFactory;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    public $addressRenderer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    public $emulation;

    public $moduleRegistry;

    public $totalHelper;

    public $skipBillingFields = [
        'grand_total'
    ];

    /**
     * Returns entity data
     *
     * @return mixed
     */
    abstract public function getData();

    /**
     * Filter constructor.
     * @param \Magetrend\PdfTemplates\Helper\Data $moduleHelper
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magetrend\PdfTemplates\Helper\Data $moduleHelper,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magetrend\PdfTemplates\Model\Registry $moduleRegistry,
        \Magetrend\PdfTemplates\Helper\Total $totalHelper
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->paymentHelper = $paymentHelper;
        $this->objectManager = $objectManager;
        $this->countryFactory = $countryFactory;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->addressRenderer = $addressRenderer;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->moduleRegistry = $moduleRegistry;
        $this->totalHelper = $totalHelper;
    }

    /**
     * Replace variables to data from source object
     *
     * @param $source
     * @param $string
     * @return mixed
     */
    public function processFilter($source, $string)
    {
        $this->source = $source;
        $this->order = null;
        $variables = $this->getData();
        if (empty($variables)) {
            return $string;
        }

        foreach ($variables as $key => $value) {
            if (!is_string($value) && !empty($value)) {
                $value = '';
            }

            if (is_array($value) || is_null($value)) {
                $value = '';
            }

            $string = str_replace('{'.$key.'}', $value, $string);
        }
        return $string;
    }

    /**
     * Returns source object
     *
     * @return \Magento\Sales\Model\AbstractModel
     */
    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Returns order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->order == null) {
            $source = $this->getSource();
            if ($source instanceof \Magento\Sales\Model\Order) {
                $this->order = $source;
            } else {
                $this->order = $source->getOrder();
            }
        }
        return $this->order;
    }

    /**
     * Returns grand total
     *
     * @return string
     */
    public function getGrandTotal()
    {
        return $this->getOrder()->formatPriceTxt($this->getSource()->getGrandTotal());
    }

    /**
     * Returns grand total
     *
     * @return string
     */
    public function getDue()
    {
        $order = $this->getOrder();
        return $this->getOrder()->formatPriceTxt($order->getGrandTotal() - $order->getTotalInvoiced());
    }

    /**
     * Returns billing data
     *
     * @param $data
     * @return mixed
     */
    public function addBillingData($data)
    {
        $data['fullname'] = '';
        $data['company'] = '';
        $data['address'] = '';
        $data['region'] = '';
        $data['vat_id'] = '';
        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $source = $this->getSource();
        } else {
            $source = $this->getOrder();
        }

        $billingAddress = $source->getBillingAddress();
        $billingData = $billingAddress->getData();
        if (empty($billingData)) {
            return $data;
        }
        foreach ($billingData as $key => $value) {
            if (is_object($value) || in_array($key, $this->skipBillingFields)) {
                continue;
            }
            $data[$key] = $value;
        }

        $middleName = $billingAddress->getMiddlename();
        if (!empty($middleName)) {
            $middleName = ' '. $middleName;
        }

        $data['fullname'] = $billingAddress->getFirstname().$middleName.' '.$billingAddress->getLastname();

        if (isset($data['country_id']) && !empty($data['country_id'])) {
            $country = $this->countryFactory->create()->loadByCode($data['country_id']);
            $data['country'] = $country->getName();
        }

        $data['address'] = $this->getFormatedAddress($billingAddress);
        return $data;
    }

    /**
     * Returns billing data
     *
     * @param $data
     * @return mixed
     */
    public function addShippingData($data)
    {
        $data['s_fullname'] = '';
        $data['s_address'] = '';
        $data['s_region'] = '';
        $data['s_vat_id'] = '';
        $data['s_company'] = '';
        $source = $this->getSource();
        $shippingAddress = $source->getShippingAddress();
        if (!$shippingAddress) {
            return $data;
        }

        $shippingData = $shippingAddress->getData();
        if (empty($shippingData)) {
            return $data;
        }

        foreach ($shippingData as $key => $value) {
            if (is_object($value)) {
                continue;
            }
            $data['s_'.$key] = $value;
        }

        $middleName = $shippingAddress->getMiddlename();
        if (!empty($middleName)) {
            $middleName = ' '. $middleName;
        }
        $data['s_fullname'] = $shippingAddress->getFirstname().$middleName.' '.$shippingAddress->getLastname();

        if (isset($data['s_country_id']) && !empty($data['s_country_id'])) {
            $country = $this->countryFactory->create()->loadByCode($data['s_country_id']);
            $data['s_country'] = $country->getName();
        }

        $data['s_address'] = $this->getFormatedAddress($shippingAddress);

        return $data;
    }

    public function getFormatedAddress($address)
    {
        return $this->addressRenderer->format($address, 'pdf');
    }

    /**
     * Returns payment method information
     *
     * @param $data
     * @return mixed
     */
    public function addPaymentMethod($data)
    {
        $data['payment_method'] = '';
        $data['payment_additional'] = '';
        $data['payment_html'] = '';

        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $source = $this->getSource();
        } else {
            $source = $this->getOrder();
        }

        try {
            $payment = $source->getPayment();
            $method = $payment->getMethodInstance();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $data;
        }

        $methodTitle = $method->getTitle();
        $data['payment_method'] = htmlspecialchars($methodTitle);
        $paymentConfig = $this->moduleHelper->getPaymentConfig($payment->getMethod());
        if (isset($paymentConfig['renderer'])) {
            $data['payment_additional']  = $this->objectManager->get($paymentConfig['renderer'])
                ->setData([
                    'payment' => $payment,
                    'payment_instance' => $method,
                    'order' => $source
                ])
                ->getValue();
        }

        $emulatedStoreId = $this->storeManager->getStore()->getId();
        $this->emulation->stopEnvironmentEmulation();

        $paymentHtml = $this->paymentHelper->getInfoBlockHtml(
            $source->getPayment(),
            $emulatedStoreId
        );

        $this->emulation->startEnvironmentEmulation(
            $emulatedStoreId,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        $paymentHtml = str_replace(['<br>', '</br>', '<br/>', "\n"], '{br}', $paymentHtml);
        $paymentHtml = strip_tags($paymentHtml);
        $data['payment_html'] = $paymentHtml;
        return $data;
    }

    /**
     * Returns payment method information
     *
     * @param $data
     * @return mixed
     */
    public function addShippingMethod($data)
    {
        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $shippingDescription = $this->getSource()->getShippingDescription();
        } else {
            $shippingDescription = $this->getOrder()->getShippingDescription();
        }

        $data['shipping_method'] = htmlspecialchars($shippingDescription);
        return $data;
    }

    /**
     * Add comments
     *
     * @param $data
     * @return mixed
     */
    public function addComments($data)
    {
        $data['comment_label'] = '';
        $data['comment_text'] = '';

        $source = $this->getSource();

        if ($source instanceof \Magento\Sales\Model\Order) {
            $commentsCollection = $source->getStatusHistoryCollection();
        } else {
            $commentsCollection = $source->getCommentsCollection();
        }

        if (!$commentsCollection) {
            return $data;
        }

        if (!is_array($commentsCollection) && $commentsCollection->getSize() > 0) {
            $comments = $commentsCollection->getItems();
        } else {
            $comments = $commentsCollection;
        }

        if (!empty($comments)) {
            return $data;
        }

        $data['comment_label'] = (string)__(
            $this->moduleHelper->translate('notes', $this->moduleRegistry->getPdfStoreId())
        );
        foreach ($comments as $comment) {
            if ($comment->getData('is_visible_on_front') != 1) {
                continue;
            }

            else {$commentText = ''; }
            $commentText = $comment->getComment();
            $commentText = str_replace(["\n", '<br/>', '</br>', '<br>', '</p>'], '{br}', $commentText);
            $commentText = strip_tags($commentText);
            $data['comment_text'].=$commentText."{br} {br}";
        }

        return $data;
    }

    public function addTotals($data)
    {
        $order = $this->getOrder();
        $source = $this->getSource();
        $totals = $this->totalHelper->getOrderTotalData([], $order, $source);
        $data['total_shipping_amount'] = $order->formatPriceTxt(0.00);

        $possibleTotals = $this->totalHelper->getAvailableTotals();

        foreach ($possibleTotals as $total) {
            $data['total_'.$total['source_field']] = '';
        }

        foreach ($totals as $total) {
            $data['total_'.$total['source_field']] = $total['amount'];
        }

        return $data;
    }

    public function addAdditionalData($data, $type)
    {
        $dataObject = $this->dataObjectFactory
            ->create()
            ->setData($data);

        $this->eventManager->dispatch('magetrend_pdf_templates_add_additional_data', [
            'variable_list' => $dataObject,
            'source' => $this->getSource(),
            'order' => $this->getOrder()
        ]);
        $this->eventManager->dispatch('magetrend_pdf_templates_add_additional_data_'.$type, [
            'variable_list' => $dataObject,
            'source' => $this->getSource(),
            'order' => $this->getOrder()
        ]);
        $data = $dataObject->getData();

        return $data;
    }

    public function resetFilter()
    {
        $this->data = null;
    }
	
	/**
     * Returns vehicle data
     *
     * @param $data
     * @return mixed
     */
    public function addVehicleData($data)
    {
        $data['make'] = '';
        $data['model'] = '';
        $data['year'] = '';
        $data['plate'] = '';
        $data['vin_number'] = '';
        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $source = $this->getSource();
        } else {
            $source = $this->getOrder();
        }

        $make = $source->getMake();
		if (!empty($make)) {
			$data['make'] = $make;
		}
		$model = $source->getModel();
		if (!empty($model)) {
			$data['model'] = $model;
		}
		$year = $source->getYear();
		if (!empty($year)) {
			$data['year'] = $year;
		}
		$plate = $source->getPlate();
		if (!empty($plate)) {
			$data['plate'] = $plate;
		}
		$vin_number = $source->getVinNumber();
		if (!empty($vin_number)) {
			$data['vin_number'] = $vin_number;
		}

        return $data;
    }
	
	/**
     * Returns installer data
     *
     * @param $data
     * @return mixed
     */
    public function addInstallerData($data)
    {
        $data['installer_name'] = '';
        $data['installer_name_rtl'] = '';
        $data['installer_street'] = '';
        $data['installer_street_rtl'] = '';
        $data['installer_city'] = '';
        $data['installer_city_rtl'] = '';
        $data['installer_country'] = '';
        $data['installer_location_map'] = '';
        $data['pickup_date'] = '';
        $data['pickup_time'] = '';
        if ($this->getSource() instanceof \Magento\Quote\Model\Quote) {
            $source = $this->getSource();
        } else {
            $source = $this->getOrder();
        }
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$pickupstores = $objectManager->get('Ecomteck\StoreLocator\Model\Stores');
		$countryObj = $objectManager->get('Magento\Directory\Model\Country');
		$installer_id = $source->getPickupStore(); 
		$pickupstoresData = $pickupstores->load($installer_id);
        $country = $countryObj->load($pickupstoresData->getCountry())->getName();
		
        $installerName = $pickupstoresData->getName();
		if (!empty($installerName)) {
			$data['installer_name'] = $installerName;
		}
		$installerNameRTL = $pickupstoresData->getNameRtl();
		if (!empty($installerNameRTL)) {
			$data['installer_name_rtl'] = $installerNameRTL;
		}
		$installerStreet = $pickupstoresData->getAddress();
		if (!empty($installerStreet)) {
			$data['installer_street'] = $installerStreet;
		}
		$installerStreetRTL = $pickupstoresData->getAddressRtl();
		if (!empty($installerStreetRTL)) {
			$data['installer_street_rtl'] = $installerStreetRTL;
		}
		$installerCity = $pickupstoresData->getCity();
		if (!empty($installerCity)) {
			$data['installer_city'] = $installerCity;
		}
		$installerCityRTL = $pickupstoresData->getCityRtl();
		if (!empty($installerCityRTL)) {
			$data['installer_city_rtl'] = $installerCityRTL;
		}
		$installerCountry = $country;
		if (!empty($installerCountry)) {
			$data['installer_country'] = $installerCountry;
		}
		$installerLocationMap = $pickupstoresData->getExternalLink();
		if (!empty($installerLocationMap)) {
			$data['installer_location_map'] = $installerLocationMap;
		}
		$pickupDate = str_replace('00:00:00', '', $source->getPickupDate());
		if (!empty($pickupDate)) {
			$data['pickup_date'] = $pickupDate;
		}
		$pickupTime = $source->getPickupTime();
		if (!empty($pickupTime)) {
			$data['pickup_time'] = $pickupTime;
		}

        return $data;
    }
	
	/**
     * Returns store information data
     *
     * @param $data
     * @return mixed
     */
    public function addStoreInfoData($data)
    {
        $data['store_name'] = '';
        $data['store_phone'] = '';
        $data['store_country'] = '';
        $data['store_region'] = '';
        $data['store_postcode'] = '';
        $data['store_city'] = '';
        $data['store_street_line1'] = '';
        $data['store_street_line2'] = '';
        $data['store_vat_number'] = '';
        $data['store_url'] = '';
        $data['store_email'] = '';
		
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
		
		$emulatedStoreId = $this->storeManager->getStore()->getId();
        $this->emulation->stopEnvironmentEmulation();

		$storeName = $scopeConfig->getValue("general/store_information/name", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storePhone = $scopeConfig->getValue("general/store_information/phone", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeCountry = $scopeConfig->getValue("general/store_information/country_id", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeRegion = $scopeConfig->getValue("general/store_information/region_id", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storePostcode = $scopeConfig->getValue("general/store_information/postcode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeCity = $scopeConfig->getValue("general/store_information/city", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeStreetLine1 = $scopeConfig->getValue("general/store_information/street_line1", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeStreetLine2 = $scopeConfig->getValue("general/store_information/street_line2", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeVatNumber = $scopeConfig->getValue("general/store_information/merchant_vat_number", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeUrl = $scopeConfig->getValue("web/secure/base_url", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		$storeEmail = $scopeConfig->getValue("trans_email/ident_general/email", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $emulatedStoreId);
		
        $this->emulation->startEnvironmentEmulation(
            $emulatedStoreId,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
		
		
		if (!empty($storeName)) {
			$data['store_name'] = $storeName;
		}

		if (!empty($storePhone)) {
			$data['store_phone'] = $storePhone;
		}
		
		if (!empty($storeCountry)) {
			$data['store_country'] = $storeCountry;
		}
		
		if (!empty($storeRegion)) {
			$data['store_region'] = $storeRegion;
		}
		
		if (!empty($storePostcode)) {
			$data['store_postcode'] = $storePostcode;
		}
		
		if (!empty($storeCity)) {
			$data['store_city'] = $storeCity;
		}
		
		if (!empty($storeStreetLine1)) {
			$data['store_street_line1'] = $storeStreetLine1;
		}
		
		if (!empty($storeStreetLine2)) {
			$data['store_street_line2'] = $storeStreetLine2;
		}
		
		if (!empty($storeVatNumber)) {
			$data['store_vat_number'] = $storeVatNumber;
		}
		
		if (!empty($storeUrl)) {
			$storeUrl = rtrim($storeUrl, "/");
			$data['store_url'] = $storeUrl;
		}
		
		if (!empty($storeEmail)) {
			$data['store_email'] = $storeEmail;
		}

        return $data;
    }
}
