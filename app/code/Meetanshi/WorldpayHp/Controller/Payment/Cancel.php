<?php

namespace Meetanshi\WorldpayHp\Controller\Payment;

use Meetanshi\WorldpayHp\Controller\Payment as WorldpayHpPayment;

/**
 * Class Cancel
 * @package Meetanshi\WorldpayHp\Controller\Payment
 */
class Cancel extends WorldpayHpPayment
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            $this->helper->logger("Cancel from worldpay", $params);
            if ($params['decision'] == 'CANCEL') {
                $orderIds = explode("-", $params['req_transaction_uuid']);
                $order = $this->orderFactory->create()->loadByIncrementId($orderIds[0]);
                $errorMsg = $params['message'];
                $payment = $order->getPayment();
                $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true,
                    $errorMsg);
                $payment->setStatus('DECLINED');
                $payment->setShouldCloseParentTransaction(1)->setIsTransactionClosed(1);
                $payment->save();
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->addStatusToHistory($order->getStatus(), $errorMsg);
                $order->save();
                $this->messageManager->addErrorMessage($errorMsg);
            }
            $this->checkoutSession->restoreQuote();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this->_redirect('checkout/cart');
    }
}
