<?php
/**
 * MB "Vienas bitas" (Magetrend.com)
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */

namespace Magetrend\PdfTemplates\Helper;

class Total
{
    public $totalConfig;

    public $scopeConfig;

    public $moduleHelper;

    public $pdfConfig;

    public $defaultPdf;

    public $disableFullTaxSummary = false;

    /**
     * Total constructor.
     * @param \Magento\Sales\Model\Order\Pdf\Config $totalConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Data $moduleHelper
     * @param \Magento\Sales\Model\Order\Pdf\Config $pdfConfig
     * @param \Magetrend\PdfTemplates\Model\Sales\Order\Pdf $defaultPdf
     */
    public function __construct(
        \Magento\Sales\Model\Order\Pdf\Config $totalConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magetrend\PdfTemplates\Helper\Data $moduleHelper,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magetrend\PdfTemplates\Model\Sales\Order\Pdf $defaultPdf
    ) {
        $this->totalConfig = $totalConfig;
        $this->scopeConfig = $scopeConfig;
        $this->moduleHelper = $moduleHelper;
        $this->pdfConfig = $pdfConfig;
        $this->defaultPdf = $defaultPdf;
    }

    public function getOrderTotalData($attributes, $order, $source, $template = null)
    {
        $collectedTotals = [];
        if ($template) {
            $labelTranslate = $template->getTranslate();
        } else {
            $labelTranslate = [];
        }

        $totals = $this->defaultPdf->getTotalsList($source);
        if ($source instanceof \Magento\Quote\Model\Quote) {
            $source->collectTotals();
        }

        foreach ($totals as $total) {
            $totalConfigData = $total->getData();
            if (in_array($totalConfigData['source_field'], ['_', ''])) {
                $totalConfigData['source_field'] = hash('md5', $totalConfigData['title']);
            }

            $total->setOrder($order)
                ->setSource($source)
                ->setSourceField($totalConfigData['source_field']);

            if ($source instanceof \Magento\Quote\Model\Quote) {
                $total->setQuote($source);
            }

            if ($total->canDisplay()) {
                $subtotals = [];
                foreach ($total->getTotalsForDisplay() as $i => $totalData) {
                    if (!$this->canDisplay($totalData, $attributes, $source->getStoreId())) {
                        continue;
                    }
                    $collectedTotals[] = $totalData;
                }

            }
        }

        $totals = [];
        foreach ($collectedTotals as $totalData) {
            $sourceField = $totalData['source_field'];
            if (!isset($labelTranslate[$sourceField])) {
                $labelTranslate[$sourceField] = $this->getDefaultTotalLabel($sourceField, $source->getStoreId());
            }

            $totalLabel = $labelTranslate[$sourceField];
            if (isset($totalData['percent']) && !empty($totalData['percent'])) {
                $precision = 2;
                if (number_format($totalData['percent'], 2) == number_format($totalData['percent'], 0)) {
                    $precision = 0;
                }

                $totalLabel = (string)__(
                    $totalLabel,
                    number_format($totalData['percent'], $precision)
                );
            } elseif (in_array($sourceField, ['tax_amount_1', 'grand_total_2'])) {
                $totalLabel = $this->removePercentFromLabel($totalLabel);
            }

            $totalData['label'] = $totalLabel;
            $totals[] = $totalData;
        }

        $totals = $this->applyCustomSortOrder($totals, $order->getStoreId());
        usort($totals, [$this, 'sortTotalsList']);
        return $totals;
    }

    public function getQuoteTotalData($attributes, $quote, $template)
    {
        return $this->getOrderTotalData($attributes, $quote, $quote, $template);
    }

    public function getAvailableTotals()
    {
        $config = $this->totalConfig->getTotals();
        $totals = [];

        foreach ($config as $key => $total) {
            if (in_array($total['source_field'], ['_', ''])) {
                $total['source_field'] = hash('md5', $total['title']);
            }

            $total['source_field'] = $total['source_field'].'_0';
            $totals[$total['source_field']] = $total;
        }

        $totals = $this->addAdditionalSubtotal($totals);
        $totals = $this->addAdditionalShipping($totals);
        $totals = $this->addAdditionalTax($totals);
        $totals = $this->addAdditionalGrandTotal($totals);

        return $totals;
    }

    public function addAdditionalShipping($totals, $store = null)
    {
        $shipping = $totals['shipping_amount_0'];

        $totals['shipping_amount_1'] = $shipping;
        $totals['shipping_amount_1']['title'] = 'Shipping (Excl. Tax):';
        $totals['shipping_amount_1']['source_field'] = 'shipping_amount_1';

        $totals['shipping_amount_2'] = $shipping;
        $totals['shipping_amount_2']['title'] = 'Shipping (Incl. Tax):';
        $totals['shipping_amount_2']['source_field'] = 'shipping_amount_2';

        return $totals;
    }

    public function addAdditionalTax($totals, $store = null)
    {
        $taxAmount = $totals['tax_amount_0'];
        $totals['tax_amount_1'] = $taxAmount;
        $totals['tax_amount_1']['title'] = 'Tax (%1%) :';
        $totals['tax_amount_1']['source_field'] = 'tax_amount_1';

        return $totals;
    }

    public function addAdditionalSubtotal($totals, $store = null)
    {
        $subtotal = $totals['subtotal_0'];

        $totals['subtotal_1'] = $subtotal;
        $totals['subtotal_1']['title'] = 'Subtotal (Excl. Tax):';
        $totals['subtotal_1']['source_field'] = 'subtotal_1';

        $totals['subtotal_2'] = $subtotal;
        $totals['subtotal_2']['title'] = 'Subtotal (Incl. Tax):';
        $totals['subtotal_2']['source_field'] = 'subtotal_2';

        return $totals;
    }

    public function addAdditionalGrandTotal($totals, $store = null)
    {
        $grandTotal = $totals['grand_total_0'];

        $totals['grand_total_1'] = $grandTotal;
        $totals['grand_total_1']['title'] = 'Grand Total (Excl. Tax) :';
        $totals['grand_total_1']['source_field'] = 'grand_total_1';

        $totals['grand_total_2']['title'] = 'Grand Total Tax (%1%) :';
        $totals['grand_total_2']['source_field'] = 'grand_total_2';
        $totals['grand_total_2']['dummy_value'] = '$0.00';

        $totals['grand_total_3']['title'] = 'Grand Total Tax :';
        $totals['grand_total_3']['source_field'] = 'grand_total_3';
        $totals['grand_total_3']['dummy_value'] = '$0.00';

        $totals['grand_total_4'] = $grandTotal;
        $totals['grand_total_4']['title'] = 'Grand Total (Incl. Tax) :';
        $totals['grand_total_4']['source_field'] = 'grand_total_4';

        return $totals;
    }

    public function applyCustomSortOrder($totals, $store = null)
    {
        $customSortOrder = $this->moduleHelper->getTotalsSorting($store);
        if (empty($totals) || empty($customSortOrder)) {
            return $totals;
        }

        $i = 1;
        foreach ($totals as $key => $total) {
            if (isset($totals[$key]['sort_order'])) {
                continue;
            }

            $totals[$key]['sort_order'] = $i++;
        }

        foreach ($totals as $key => $total) {
            if (!isset($total['source_field'])) {
                continue;
            }

            $sourceField = $total['source_field'];
            if (!isset($customSortOrder[$sourceField])) {
                continue;
            }

            $totals[$key]['sort_order'] = $customSortOrder[$sourceField];
        }

        return $totals;
    }

    public function sortTotalsList($a, $b)
    {
        if (!isset($a['sort_order']) || !isset($b['sort_order'])) {
            return 0;
        }

        if ($a['sort_order'] == $b['sort_order']) {
            return 0;
        }

        return $a['sort_order'] > $b['sort_order'] ? 1 : -1;
    }

    public function canDisplay($totalData, $attributes, $store)
    {
        if (!isset($totalData['amount']) || empty($totalData['amount'])) {
            return false;
        }

        $hideList = $this->moduleHelper->getTotalsHideList($store);
        $sourceField = $totalData['source_field'];
        if (in_array($sourceField, $hideList)) {
            return false;
        }

        return true;
    }

    public function isTaxSummaryEnabled($storeId)
    {
        if ($this->disableFullTaxSummary) {
            return false;
        }

        return $this->moduleHelper->isTaxSummaryEnabled($storeId);
    }

    public function getDefaultTotalLabel($sourceField, $storeId)
    {
        $totals = $this->getAvailableTotals();
        if (empty($totals)) {
            return __($sourceField);
        }

        foreach ($totals as $total) {
            if ($total['source_field'] == $sourceField) {
                return $total['title'];
            }
        }

        return __($sourceField);
    }

    public function removePercentFromLabel($label)
    {
        return str_replace(
            [' (%1%)', ' %1%', '(%1%)', '%1%'],
            '',
            $label
        );
    }

}