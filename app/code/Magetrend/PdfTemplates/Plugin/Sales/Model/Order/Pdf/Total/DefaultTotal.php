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

namespace Magetrend\PdfTemplates\Plugin\Sales\Model\Order\Pdf\Total;

/**
 * Default total model plugin class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class DefaultTotal
{
    public function afterGetFullTaxInfo($suject, $result)
    {
        if (!$result || !is_array($result)) {
            return $result;
        }

        foreach ($result as $key => $value) {
            $result[$key]['summary_field'] = 1;
        }

        return $result;
    }

    public function afterGetTotalsForDisplay($suject, $result)
    {
        if (!is_array($result) || empty($result)) {
            return $result;
        }

        $sourceField = $suject->getSourceField();
        if ($sourceField == 'grand_total') {
            foreach ($result as $key => $value) {
                $result[$key]['is_grand_total'] = 1;
            }
        }

        if (count($result) == 1) {
            $result[0]['source_field'] = $sourceField.'_0';
            return $result;
        }

        $i = 1;
        foreach ($result as $key => $value) {
            $result[$key]['source_field'] = $sourceField.'_'.$i;
            $i++;
        }

        return $result;
    }

    /**
     * Get title description from source
     *
     * @return mixed
     */
    public function aroundGetTitleDescription($subject, callable $proceed)
    {
        $source = $subject->getSource();
        if ($source instanceof \Magento\Sales\Model\Order && !$source->hasData('order')) {
            return $source->getData($subject->getTitleSourceField());
        }
        return $proceed();
    }
}