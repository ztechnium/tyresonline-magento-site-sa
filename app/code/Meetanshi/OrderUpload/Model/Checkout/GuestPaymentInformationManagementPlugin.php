<?php

namespace Meetanshi\OrderUpload\Model\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class GuestPaymentInformationManagementPlugin
 * @package Meetanshi\OrderUpload\Model\Checkout
 */
class GuestPaymentInformationManagementPlugin
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * GuestPaymentInformationManagementPlugin constructor.
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param GuestPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
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

        $orderId = $proceed($cartId, $email, $paymentMethod, $billingAddress);
        return $orderId;
    }
}
