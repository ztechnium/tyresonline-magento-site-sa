<?php

namespace Meetanshi\WorldpayHp\Block\Payment;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Info
 * @package Meetanshi\WorldpayHp\Block\Payment
 */
class Info extends ConfigurableInfo
{
    /**
     * @var string
     */
    protected $_template = 'Meetanshi_WorldpayHp::info.phtml';

    /**
     * @param string $field
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($field)
    {
        switch ($field) {
            case 'card_type_name':
                return __('Card Type');
            case 'transaction_id':
                return __('Transaction ID');
            case 'req_card_number':
                return __('Card Number');
            case 'req_card_expiry_date':
                return __('Card Expiry Date');
            case 'req_payment_method':
                return __('Payment Method');
            case 'method_title':
                return __('Title');
            default:
                return __($field);
                break;
        }
    }
}
