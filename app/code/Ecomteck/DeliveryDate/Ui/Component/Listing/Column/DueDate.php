<?php
namespace Ecomteck\DeliveryDate\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
/**
 * Class DueDate
 * @package Ecomteck\DeliveryDate\Ui\Component\Listing\Column
 */
class DueDate extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Framework\View\Element\AbstractBlock
     */
    protected $viewFileUrl;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\View\Asset\Repository $viewFileUrl
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param array $components
     * @param array $data
     * 
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\View\Asset\Repository $viewFileUrl,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->viewFileUrl = $viewFileUrl;
        $this->_date = $date;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $current_date = $this->_date->gmtDate();
            $date1 = strtotime($current_date);
            foreach ($dataSource['data']['items'] as & $item) {
                $order = new \Magento\Framework\DataObject($item);
                $delivery_date = isset($order["delivery_date"])?$order["delivery_date"]:"";
                if($delivery_date && $delivery_date !== "0000-00-00 00:00:00"){
                    $date2 = strtotime($delivery_date);
                    $count_date = $delivery_date;
                    $dateDiff = $date2 - $date1;
                    $fullDays = floor($dateDiff/(60*60*24));
                    if($fullDays >= 0){
                        $item[$fieldName] = __("%1 days left",$fullDays);
                    }else {
                        $fullDays = -$fullDays;
                        $item[$fieldName] = __("Delay %1 days", $fullDays);
                    }
                }else {
                    $item[$fieldName] = "";
                }
            }
        }

        return $dataSource;
    }
}
