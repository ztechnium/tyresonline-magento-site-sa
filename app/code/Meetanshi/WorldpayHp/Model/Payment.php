<?php

namespace Meetanshi\WorldpayHp\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Url;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Meetanshi\WorldpayHp\Block\Payment\Info;

/**
 * Class Payment
 * @package Meetanshi\WorldpayHp\Model
 */
class Payment extends AbstractMethod
{
    /**
     *
     */
    const CODE = 'worldpay_hp';
    /**
     * @var string
     */
    protected $_code = self::CODE;
    /**
     * @var string
     */
    protected $_infoBlockType = Info::class;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canCapture = false;
    /**
     * @var bool
     */
    protected $_canRefund = false;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var Url
     */
    protected $urlBuilder;

    /**
     * Payment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentHelper $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Url $urlBuilder
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Url $urlBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('worldpay_hp/payment/redirect', ['_secure' => true]);
    }
}
