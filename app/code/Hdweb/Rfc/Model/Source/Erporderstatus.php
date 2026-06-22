<?php
namespace Hdweb\Rfc\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PrebookStatus
 */
class Erporderstatus implements OptionSourceInterface
{

    const ERP_ORDER_YES=1;
    const ERP_ORDER_NO=0;

    public static function getOptionArray()
    {
        return [
            self::ERP_ORDER_YES => __('Yes'),
            self::ERP_ORDER_NO => __('No')
        ];
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }
}