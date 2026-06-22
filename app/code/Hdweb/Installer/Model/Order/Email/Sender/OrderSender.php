<?php

namespace Hdweb\Installer\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{

    public function send(Order $order, $forceSyncMode = false, $iscreditcard = false)
    {

        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if ($payment == 'creditcard' && $iscreditcard == false) {
            return false;
        }

        $order->setSendEmail($this->identityContainer->isEnabled());

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            if ($this->checkAndSend($order)) {
                $order->setEmailSent(true);
                $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $order->setEmailSent(null);
            $this->orderResource->saveAttribute($order, 'email_sent');
        }

        $this->orderResource->saveAttribute($order, 'send_email');

        return false;

    }

    protected function getPaymentHtml(Order $order)
    {
        if ($order->getCcavenuepayTitle()) {
            return $order->getCcavenuepayTitle();
        } else {
            return $this->paymentHelper->getInfoBlockHtml(
                $order->getPayment(),
                $this->identityContainer->getStore()->getStoreId()
            );
        }
    }
}
