<?php

namespace Meetanshi\WorldpayHp\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Meetanshi\WorldpayHp\Controller\Payment as WorldpayHpPayment;

/**
 * Class Success
 * @package Meetanshi\WorldpayHp\Controller\Payment
 */
class Success extends WorldpayHpPayment implements CsrfAwareActionInterface
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->helper->logger("Success from worldpay", $params);
        if (is_array($params) && !empty($params)) {

            $decision = $params['decision'];

            $order = $this->orderFactory->create()->loadByIncrementId($params['req_reference_number']);
            $payment = $order->getPayment();

            if ($decision == 'ACCEPT') {

                if (array_key_exists('card_type_name', $params)) {
                    $cardType = $params['card_type_name'];
                    $payment->setAdditionalInformation('card_type_name', $cardType);
                }
                if (array_key_exists('transaction_id', $params)) {
                    $transactionID = $params['transaction_id'];
                    $payment->setAdditionalInformation('transaction_id', $transactionID);
                }
                if (array_key_exists('req_card_number', $params)) {
                    $rawAuthMessage = $params['req_card_number'];
                    $payment->setAdditionalInformation('req_card_number', $rawAuthMessage);
                }
                if (array_key_exists('req_card_expiry_date', $params)) {
                    $rawAuthCode = $params['req_card_expiry_date'];
                    $payment->setAdditionalInformation('req_card_expiry_date', $rawAuthCode);
                }
                if (array_key_exists('req_payment_method', $params)) {
                    $transStatus = $params['req_payment_method'];
                    $payment->setAdditionalInformation('req_payment_method', $transStatus);
                }

                $payment->setAdditionalInformation((array)$payment->getAdditionalInformation());
                $trans = $this->transactionBuilder;
                $transaction = $trans->setPayment($payment)->setOrder($order)->setTransactionId($transactionID)->setAdditionalInformation((array)$payment->getAdditionalInformation())->setFailSafe(true)->build(Transaction::TYPE_CAPTURE);
                $payment->addTransactionCommentsToOrder($transaction, 'Transaction is approved by the bank');
                $payment->setParentTransactionId(null);
                $payment->save();

                $this->orderSender->notify($order);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);

                $order->addStatusHistoryComment(__('Transaction is accept.'), Order::STATE_PROCESSING)->setIsCustomerNotified(true);
                $order->save();
                $transaction->save();

                if ($this->helper->isAutoInvoice()) {
                    if (!$order->canInvoice()) {
                        $order->addStatusHistoryComment('Sorry, Order cannot be invoiced.', false);
                    }
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    if (!$invoice) {
                        $order->addStatusHistoryComment('Can\'t generate the invoice right now.', false);
                    }

                    if (!$invoice->getTotalQty()) {
                        $order->addStatusHistoryComment('Can\'t generate an invoice without products.', false);
                    }
                    $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->getOrder()->setCustomerNoteNotify(true);
                    $invoice->getOrder()->setIsInProcess(true);
                    $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
                    $transactionSave->save();
                    try {
                        $this->invoiceSender->send($invoice);
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $order->addStatusHistoryComment('Can\'t send the invoice Email right now.', false);
                    }
                    $order->addStatusHistoryComment('Automatically Invoice Generated.', false);
                    $order->save();
                }
                return $this->_redirect('checkout/onepage/success');
            }

            if ($decision == 'DECLINE') {
                $errorMsg = __('Transaction was not decline. Please try again later');

                if (array_key_exists('card_type_name', $params)) {
                    $cardType = $params['card_type_name'];
                    $payment->setAdditionalInformation('card_type_name', $cardType);
                }
                if (array_key_exists('transaction_id', $params)) {
                    $transactionID = $params['transaction_id'];
                    $payment->setAdditionalInformation('transaction_id', $transactionID);
                }
                if (array_key_exists('req_card_number', $params)) {
                    $rawAuthMessage = $params['req_card_number'];
                    $payment->setAdditionalInformation('req_card_number', $rawAuthMessage);
                }
                if (array_key_exists('req_card_expiry_date', $params)) {
                    $rawAuthCode = $params['req_card_expiry_date'];
                    $payment->setAdditionalInformation('req_card_expiry_date', $rawAuthCode);
                }
                if (array_key_exists('req_payment_method', $params)) {
                    $transStatus = $params['req_payment_method'];
                    $payment->setAdditionalInformation('req_payment_method', $transStatus);
                }

                $payment->setAdditionalInformation((array)$payment->getAdditionalInformation());

                $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, 'Gateway has declined the payment.');
                $payment->setStatus('DECLINED');
                $payment->setShouldCloseParentTransaction(1)->setIsTransactionClosed(1);
                $payment->save();
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->addStatusToHistory($order->getStatus(), $errorMsg);
                $this->messageManager->addErrorMessage($errorMsg);
                $this->checkoutSession->restoreQuote();
                $order->save();
                return $this->_redirect('checkout/cart');
            }

            if ($decision == 'ERROR') {
                $errorMsg = __('Transaction was not decline. Please try again later');

                if (array_key_exists('message', $params)) {
                    $errorMsg = __($params['message']);
                }

                if (array_key_exists('card_type_name', $params)) {
                    $cardType = $params['card_type_name'];
                    $payment->setAdditionalInformation('card_type_name', $cardType);
                }
                if (array_key_exists('transaction_id', $params)) {
                    $transactionID = $params['transaction_id'];
                    $payment->setAdditionalInformation('transaction_id', $transactionID);
                }
                if (array_key_exists('req_card_number', $params)) {
                    $rawAuthMessage = $params['req_card_number'];
                    $payment->setAdditionalInformation('req_card_number', $rawAuthMessage);
                }
                if (array_key_exists('req_card_expiry_date', $params)) {
                    $rawAuthCode = $params['req_card_expiry_date'];
                    $payment->setAdditionalInformation('req_card_expiry_date', $rawAuthCode);
                }
                if (array_key_exists('req_payment_method', $params)) {
                    $transStatus = $params['req_payment_method'];
                    $payment->setAdditionalInformation('req_payment_method', $transStatus);
                }

                $payment->setAdditionalInformation((array)$payment->getAdditionalInformation());

                $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, $errorMsg);
                $payment->setStatus('DECLINED');
                $payment->setShouldCloseParentTransaction(1)->setIsTransactionClosed(1);
                $payment->save();
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->addStatusToHistory($order->getStatus(), $errorMsg);
                $this->messageManager->addErrorMessage($errorMsg);
                $this->checkoutSession->restoreQuote();
                $order->save();
                return $this->_redirect('checkout/cart');
            }

        }
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
