<?php

namespace Meetanshi\OrderUpload\Model\Checkout;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class PaymentInformationManagementPlugin
 * @package Meetanshi\OrderUpload\Model\Checkout
 */
class PaymentInformationManagementPlugin
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * PaymentInformationManagementPlugin constructor.
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param PaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $comment = null;
        $request = file_get_contents('php://input');
        $data = json_decode($request, true);
        if (isset($data['paymentMethod']['additional_data']['comments'])) {
            $orderComments = $data['paymentMethod']['additional_data']['comments'];
            if ($orderComments) {
                $comment = strip_tags($orderComments);
            }
        }
        $quote = $this->checkoutSession->getQuote();
        $quote->setOrderComment($comment);
        $quote->save();

        // run parent method and capture int $orderId
        $orderId = $proceed($cartId, $paymentMethod, $billingAddress);
        return $orderId;
    }
}
