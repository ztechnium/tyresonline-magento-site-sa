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

namespace Magetrend\PdfTemplates\Plugin\Tax\Model\Sales\Pdf;

/**
 * Tax total model plugin class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Tax
{
    public function afterGetTotalsForDisplay($suject, $result)
    {
        if (!$result || !is_array($result)) {
            return $result;
        }

        if (count($result) == 1) {
            $result[0]['source_field'] = 'tax_amount_0';
            return $result;
        }

        /**
         * Remove tax summary rows without percentage
         * Find a row with percentage amount
         */
        $hasPercent = false;
        foreach ($result as $key => $value) {
            if ((isset($value['summary_field']) && $value['summary_field'] == 1)
                && (!isset($value['percent']) || empty($value['percent']))) {
                unset($result[$key]);
            }

            if (isset($value['percent']) && !empty($value['percent'])) {
               $hasPercent = true;
            }
        }

        foreach ($result as $key => $value) {
            $result[$key]['source_field'] = 'tax_amount_1';
            if ($hasPercent && (!isset($value['percent']) || empty($value['percent']))) {
                unset($result[$key]);
            }
        }

        return $result;
    }
}