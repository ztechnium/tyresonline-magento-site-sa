<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Controller\Event;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Json\Helper\Data;
use Mageplaza\GoogleTagManager\Block\TagManager;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;

/**
 * Class Index
 * @package Mageplaza\GoogleTagManager\Controller\Event
 */
class Index extends Action
{
    /**
     * @var ForwardFactory
     */
    protected $forwardFactory;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var TagManager
     */
    protected $tagManager;

    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ForwardFactory $forwardFactory
     * @param Data $jsonHelper
     * @param TagManager $tagManager
     * @param HelperData $helper
     */
    public function __construct(
        Context $context,
        ForwardFactory $forwardFactory,
        Data $jsonHelper,
        TagManager $tagManager,
        HelperData $helper
    ) {
        $this->forwardFactory = $forwardFactory;
        $this->jsonHelper     = $jsonHelper;
        $this->tagManager     = $tagManager;
        $this->_helper        = $helper;

        parent::__construct($context);
    }

    public function execute()
    {
        $html          = '';
        $canShowEvents = $this->_helper->getShowEvents();

        if (!empty($data = $this->tagManager->getEventData())) {
            if ($this->getRequest()->getParam('customdata') == 'addtocart') {
                if (!empty($data['add']['pixel']) && in_array(Event::ADD_TO_CART, $canShowEvents)) {
                    $html .= "<script>fbq('track', 'AddToCart'," . $data['add']['pixel'] . ");</script>";
                }
                $this->tagManager->removeAddToCartData();
            }
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');

        $response->setContents(
            $this->jsonHelper->jsonEncode(
                [
                    'data' => $html
                ]
            )
        );

        return $response;
    }
}
