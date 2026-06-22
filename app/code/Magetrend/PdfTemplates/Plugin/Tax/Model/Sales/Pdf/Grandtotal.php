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
 * Grandtotal total model plugin class
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Grandtotal
{
    public function afterGetTotalsForDisplay($subject, $result)
    {
        if (!$result || !is_array($result)) {
            return $result;
        }

        if (count($result) == 1) {
            $result[0]['source_field'] = 'grand_total_0';
            $result[0]['is_grand_total'] = 1;
            return $result;
        }

        /**
         * Remove tax summary rows without percentage
         * Find a row with percentage amount
         */
        $hasPercent = false;
        $isFullTaxSummaryEnabled = true;
        foreach ($result as $key => $value) {
            if ((isset($value['summary_field']) && $value['summary_field'] == 1)
                && (!isset($value['percent']) || empty($value['percent']))) {
                unset($result[$key]);
            }

           if (isset($value['percent']) && !empty($value['percent'])) {
               $hasPercent = true;
           }

           if (isset($value['summary_field']) && !empty($value['summary_field'])) {
                $isFullTaxSummaryEnabled = true;
           }
        }


        $lines = count($result);
        foreach ($result as $key => $value) {
            $result[$key]['is_grand_total'] = 1;
            //exclude first and last because they aren't tax rows
            if (!in_array($key, [0, $lines-1])) {
                if ($isFullTaxSummaryEnabled) {
                    $sourceField = 'grand_total_2';
                } else {
                    $sourceField = 'grand_total_3';
                }
                $result[$key]['source_field'] = $sourceField;

                if ($hasPercent && $sourceField == 'grand_total_2' && (!isset($value['percent']) || empty($value['percent']))) {
                    // Remove field without percentage if there is another one with percentage
                    unset($result[$key]);
                }
            }
        }

        $result[0]['source_field'] = 'grand_total_1';
        $result[$lines-1]['source_field'] = 'grand_total_4';
        return $result;
    }
}