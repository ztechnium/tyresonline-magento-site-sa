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
namespace Ecomteck\OneStepCheckout\Controller\Index;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Index extends \Magento\Checkout\Controller\Onepage
{
    /**
     * Checkout helper
     *
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    private $configHelper;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ecomteck\OneStepCheckout\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ecomteck\OneStepCheckout\Helper\Config $configHelper
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
    }

    /**
     * One Step Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->configHelper->isEnabled()) {
            return $this->resultRedirectFactory->create()->setPath('checkout');
        }

        if (!$this->checkoutHelper->canOnepageCheckout()) {
            $this->messageManager->addErrorMessage(__('One-page checkout is turned off.'));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        if (!$this->_customerSession->isLoggedIn() && !$this->checkoutHelper->isAllowedGuestCheckout($quote)) {
            $this->messageManager->addErrorMessage(__('Guest checkout is disabled.'));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $currentUrl = $this->_url->getUrl('*/*/*', ['_secure' => true]);
        $this->_customerSession->setBeforeAuthUrl($currentUrl);
        $this->_customerSession->regenerateId();

        $this->checkoutSession->setCartWasUpdated(false);
        $this->getOnepage()->initCheckout();
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
