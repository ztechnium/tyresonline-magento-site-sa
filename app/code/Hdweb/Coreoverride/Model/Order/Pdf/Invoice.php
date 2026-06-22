<?php

namespace Hdweb\Coreoverride\Model\Order\Pdf;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use PHPQRCode\QRcode;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use TCPDF;
use TCPDF_FONTS;

class Invoice extends \Magento\Sales\Model\Order\Pdf\Invoice
{
	/**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Sales\Model\RtlTextHandler $rtlTextHandler,
        array $data = []
    ) {
        $this->_filesystem     = $filesystem;
        $this->_storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->_localeResolver = $localeResolver;
		$this->rtlTextHandler = $rtlTextHandler ?: Magento\Framework\App\ObjectManager::getInstance()->get(RtlTextHandler::class);
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $appEmulation,
            $data
        );
    }

    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));

        //columns headers
       // $lines[0][] = ['text' => __('Sr No.'), 'feed' => 35];

        $lines[0][] = ['text' => 'Products', 'feed' => 35];

        //$lines[0][] = ['text' => __('SKU'), 'feed' => 330, 'align' => 'right'];

        $lines[0][] = ['text' => 'Qty', 'feed' => 435, 'align' => 'right'];

        $lines[0][] = ['text' => 'Price', 'feed' => 370, 'align' => 'right'];

        $lines[0][] = ['text' => 'Tax', 'feed' => 495, 'align' => 'right'];

        $lines[0][] = ['text' => 'Subtotal', 'feed' => 565, 'align' => 'right'];

        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }
	
	/**
     * Insert order to pdf page.
     *
     * @param \Zend_Pdf_Page $page
     * @param \Magento\Sales\Model\Order $obj
     * @param bool $putOrderId
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        if ($obj instanceof \Magento\Sales\Model\Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
		
		$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
		$page->drawText('TAX INVOICE', 265, $top - 20, 'UTF-8');
		
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        //$page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        //$page->drawRectangle(25, $top, 570, $top - 55);
        //$page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates([25, $top, 570, $top - 55]);
        $this->_setFontRegular($page, 10);
		$top -=25;
        if ($putOrderId) {
            $page->drawText(__('Invoice # ') . $order->getRealOrderId(), 35, $top -= 20, 'UTF-8');
            $top +=15;
        }

        $top -=30;
        $page->drawText(
            __('Invoice Date: ') .
            $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            ),
            35,
            $top,
            'UTF-8'
        );
				
		/* if ($order->getSalesPersonId()){
			$this->insertSalesPerson($page, $order, $top -= 15);	
		} */		
		
        $top -= 30;
		
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 285, $top - 25);
        $page->drawRectangle(285, $top, 570, $top - 25);

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($this->addressRenderer->format($order->getBillingAddress(), 'pdf'));

        /* Payment */
        $paymentInfo = $this->_paymentData->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress(
                $this->addressRenderer->format($order->getShippingAddress(), 'pdf')
            );
            $shippingMethod = $order->getShippingDescription();
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
        $page->drawText(__('Bill to:'), 35, $top - 15, 'UTF-8');

        /* if (!$order->getIsVirtual()) {
            $page->drawText(__('Ship to:'), 285, $top - 15, 'UTF-8');
        } else {
            $page->drawText(__('Payment Method:'), 285, $top - 15, 'UTF-8');
        } */
		
		$page->drawText(__('Vehicle Info:'), 295, $top - 15, 'UTF-8');
		
        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, $top - 25, 570, $top - 33 - $addressesHeight);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        foreach ($billingAddress as $value) {
            if ($value !== '') {
                $text = [];
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $text[] = $this->rtlTextHandler->reverseRtlText($_value);
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            /* foreach ($shippingAddress as $value) {
                if ($value !== '') {
                    $text = [];
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $text[] = $this->rtlTextHandler->reverseRtlText($_value);
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            } */
			
			if ($order->getPlate()) {
				$page->drawText('Plate :', 295, $this->y, 'UTF-8');
				$page->drawText($order->getPlate(), 330, $this->y, 'UTF-8');
			}
			$page->drawText('Make :', 295, $this->y -15, 'UTF-8');
			$page->drawText($order->getMake(), 330, $this->y -15, 'UTF-8');
			$page->drawText('Model :', 295, $this->y -30, 'UTF-8');
			$page->drawText($order->getModel(), 330, $this->y -30, 'UTF-8');
			$page->drawText('Year :', 295, $this->y -45, 'UTF-8');
			$page->drawText($order->getYear(), 330, $this->y -45, 'UTF-8');
			if ($order->getVinNumber()) {
				$page->drawText('VIN :', 295, $this->y -60, 'UTF-8');
				$page->drawText($order->getVinNumber(), 330, $this->y -60, 'UTF-8');
			}

            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;

            $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 285, $this->y - 25);
            $page->drawRectangle(285, $this->y, 570, $this->y - 25);

            $this->y -= 15;
            $this->_setFontBold($page, 12);
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
            $page->drawText(__('Payment Method:'), 35, $this->y, 'UTF-8');
            //$page->drawText(__('Shipping Method:'), 285, $this->y, 'UTF-8');
            $page->drawText(__('Installer Info:'), 295, $this->y, 'UTF-8');

            $this->y -= 10;
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments = $this->y - 15;
        } else {
            $yPayments = $addressesStartY;
            $paymentLeft = 295;
        }

        foreach ($payment as $value) {
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }

        /* if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->drawLine(25, $top - 25, 25, $yPayments);
            $page->drawLine(570, $top - 25, 570, $yPayments);
            $page->drawLine(25, $yPayments, 570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            $topMargin = 15;
            $methodStartY = $this->y;
            $this->y -= 15;

            foreach ($this->string->split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y -= 15;
            }

            $yShipments = $this->y;
            $totalShippingChargesText = "("
                . __('Total Shipping Charges')
                . " "
                . $order->formatPriceTxt($order->getShippingAmount())
                . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 10;

            $tracks = [];
            if ($shipment) {
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page, 9);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(__('Number'), 410, $yShipments - 7, 'UTF-8');

                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    $page->drawText($truncatedTitle, 292, $yShipments, 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments, 'UTF-8');
                    $yShipments -= $topMargin - 5;
                }
            } else {
                $yShipments -= $topMargin - 5;
            }

            $currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $methodStartY, 25, $currentY);
            //left
            $page->drawLine(25, $currentY, 570, $currentY);
            //bottom
            $page->drawLine(570, $currentY, 570, $methodStartY);
            //right

            $this->y = $currentY;
            $this->y -= 15;
        } */
		$topMargin = 15;
		$methodStartY = $this->y;
		$this->y -= 15;
		$yShipments = $this->y;
		$yShipments -= $topMargin + 40;
		if ($order->getPickupStore()){
			$this->insertInstallerInfo($page, $order, $yShipments);	
			//$page->drawText($order->getPickupStore(), 285, $this->y, 'UTF-8');
		}
		$currentY = min($yPayments, $yShipments);

		// replacement of Shipments-Payments rectangle block
		$page->drawLine(25, $methodStartY, 25, $currentY);
		//left
		$page->drawLine(25, $currentY, 570, $currentY);
		//bottom
		$page->drawLine(570, $currentY, 570, $methodStartY);
		//right

		$this->y = $currentY;
		$this->y -= 15;
		
    }

    public function getPDF($invoices = []) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $tcpdf->SetCreator(PDF_CREATOR);
        $tcpdf->SetAuthor('TyresOnline');
		$tcpdf->setTitle('Tyresonline Tax Invoice');
		$tcpdf->setSubject('Tax Invoice');
		$tcpdf->setKeywords('Tax Invoice, Tyresonline');

        $tcpdf->SetMargins(6, 4, 6);
        $tcpdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $tcpdf->SetHeaderMargin(0);
        $tcpdf->SetFooterMargin(0);
        $tcpdf->SetPrintHeader(false);
        $tcpdf->SetPrintFooter(false);


        $fontname = TCPDF_FONTS::addTTFfont(
            $this->_filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath('lib/internal/AmiriFont/Amiri-Regular.ttf'),
            '', 
            '', 
            14
            );

        $tcpdf->SetFont('times', '', 10);
        $billHtml = '';
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                /* start set english store id if arabic store */ 
                $invoiceStoreId = $invoice->getStoreId();
                if($invoice->getStoreId() == 2){
                    $invoiceStoreId = 1;
                }
                /* end set english store id if arabic store */ 
                $this->appEmulation->startEnvironmentEmulation(
                    //$invoice->getStoreId(),
                    $invoiceStoreId,
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    true
                );
                //$this->_storeManager->setCurrentStore($invoice->getStoreId());
                $this->_storeManager->setCurrentStore($invoiceStoreId);
            }
            $tcpdf->AddPage();
            $order = $invoice->getOrder();

            $pageWidth = $tcpdf->getPageWidth() - $tcpdf->getMargins()['left'] - $tcpdf->getMargins()['right'];
            $billingAddress = $this->_formatAddress($this->addressRenderer->format($order->getBillingAddress(), 'pdf'));

            foreach ($billingAddress as $value) {
                if ($value !== '') {
                    $text = [];
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $billHtml .= '<tr><td style="font-family: amiri-regular; height: 15px">'.strip_tags(ltrim($part)).'</td></tr>';
                    }
                }
            }
            $paymentInfo = $this->_paymentData->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
            $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
            $payment = explode('{{pdf_row_separator}}', $paymentInfo);
            foreach ($payment as $key => $value) {
                if (strip_tags(trim($value)) == '') {
                    unset($payment[$key]);
                }
            }
            reset($payment);
            
            $paymentHtml = '';
            foreach ($payment as $value) {
                if (trim($value) != '') {
                    //Printing "Payment Method" lines
                    $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $paymentHtml .= '<tr><td style="font-family: amiri-regular; height: 15px">'.strip_tags(trim($_value)).'</td></tr>';
                    }
                }
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $pickupstores = $objectManager->get('Ecomteck\StoreLocator\Model\Stores');
            $country = $objectManager->get('Magento\Directory\Model\Country');
            $installer_id = $order->getPickupStore();
            $pickupstoresData = $pickupstores->load($installer_id);
            $country = $country->load($pickupstoresData->getCountry())->getName();

            $installerInfoHtml = '<tr><td style="font-family: amiri-regular; height: 15px">'.$pickupstoresData->getName().'</td></tr>';
            $values = explode(
                "\n",
                'test'
            );
            foreach ($values as $value) {
                if ($value !== '') {
                    $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                    foreach ($this->string->split($value, 50, true, true) as $_value) {
                        $installerInfoHtml .= '<tr><td style="font-family: amiri-regular; height: 15px">'.trim(strip_tags($_value)).'</td></tr>';
                    }
                }
            }
            $installerInfoHtml .= '<tr><td style="font-family: amiri-regular; height: 15px">'.$pickupstoresData->getCity().'</td></tr>';
            $installerInfoHtml .= '<tr><td style="font-family: amiri-regular; height: 15px">'.$country.'</td></tr>';

            $itemsHtml = '';
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $itemName = htmlspecialchars($item->getName());
                $itemPrice = number_format($item->getPrice(), 2);
                $itemQty = (int)$item->getQty();
                $itemTax = number_format($item->getTaxAmount(), 2);
                $itemSubtotal = number_format($item->getRowTotalInclTax() - $item->getTaxAmount(), 2);

                $itemsHtml .= '
                <tr>
                    <td style="text-align: left; width: 50%">' . $itemName . '</td>
                    <td style="text-align: left; width: 15%">SAR ' . $itemPrice . '</td>
                    <td style="text-align: right; width: 5%">' . $itemQty . '</td>
                    <td style="text-align: right; width: 15%">SAR ' . $itemTax . '</td>
                    <td style="text-align: right; width: 15%">SAR ' . $itemSubtotal . '</td>
                </tr>';

            }
            $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'logo-pdf.png';
            $orderDate = $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            );
            $html = '
            <table cellpadding="4" cellspacing="0" style="width: 100%; border: none;">
                <tr>
                    <td style="width: 50%">
                        <img src="'.$imagePath.'" border="0">
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td>AL-MASSAR AL-AKBAR For Trading</td>
                            </tr>
                            <tr>
                                <td>2321, That Al-Nitaqain, Al-Sharafiyah</td>
                            </tr>
                            <tr>
                                <td>District, Jeddah, KSA</td>
                            </tr>
                            <tr>
                                <td>VAT: 312776098800003</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <p style="text-align: center;"><strong><h3>TAX INVOICE</h3></strong></p>

            <table>
                <tr>
                    <td>Invoice #'.$order->getRealOrderId().'</td>
                </tr>
                <tr>
                    <td>Invoice Date: '.$orderDate.'</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="height: 30px;"></td>
                </tr>
            </table>

            <table cellpadding="5" cellspacing="0" style="width: 100%; border: 0.5px solid gray;">
                <tr>
                    <td style="text-align: left; border: 0.5px solid gray; background-color: #e5e5e5; padding-left: 20px;"><h3>Bill to:</h3></td>
                    <td style="text-align: left; border: 0.5px solid gray; background-color: #e5e5e5; padding-left: 20px;"><h3>Vehicle Info:</h3></td>
                </tr>
                <tr>
                    <td>
                        <table>'.$billHtml.'</table>
                    </td>
                    <td>
                        <table style="font-family: amiri-regular;">
                            <tr>
                                <td style="height: 15px; width: 15%;">Plate:</td>
                                <td>'.$order->getPlate().'</td>
                            </tr>
                            <tr>
                                <td style="width: 15%; height: 15px;">Make:</td>
                                <td>'.$order->getMake().'</td>
                            </tr>
                            <tr>
                                <td style="width: 15%; height: 15px;">Model:</td>
                                <td>'.$order->getModel().'</td>
                            </tr>
                            <tr>
                                <td style="width: 15%; height: 15px;">Year:</td>
                                <td>'.$order->getYear().'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="height: 10px;"></td>
                </tr>
            </table>

            <table cellpadding="5" cellspacing="0" style="width: 100%; border: 0.5px solid gray;">
                <tr>
                    <td style="text-align: left; border: 0.5px solid gray; background-color: #e5e5e5; padding-left: 20px;"><h3>Payment Method:</h3></td>
                    <td style="text-align: left; border: 0.5px solid gray; background-color: #e5e5e5; padding-left: 20px;"><h3>Installer Info:</h3></td>
                </tr>
                <tr>
                    <td>
                        <table>'.$paymentHtml.'</table>
                    </td>
                    <td>
                        <table>'.$installerInfoHtml.'</table>
                    </td>
                </tr>
            </table>

            <div style="height: 10px;"></div>

            <table cellpadding="5" cellspacing="0" style="width: 100%; border: 1.5px solid gray;">
                <tr>
                    <td style="text-align: left; background-color: #e5e5e5; width: 50%;">Products</td>
                    <td style="text-align: left; background-color: #e5e5e5; width: 15%;">Price</td>
                    <td style="text-align: right; background-color: #e5e5e5; width: 5%;">Qty</td>
                    <td style="text-align: right; background-color: #e5e5e5; width: 15%;">Tax</td>
                    <td style="text-align: right; background-color: #e5e5e5; width: 15%;">Subtotal</td>
                </tr>
            </table>

            <table cellpadding="5" cellspacing="0" style="width: 100%;">'.$itemsHtml.'</table>

            <div style="height: 30px;"></div>

            <table style="text-align: right;">
                <tr>
                    <td style="width: 65%;"></td>
                    <td style="width: 20%;"><h4>Subtotal:</h4></td>
                    <td style="width: 15%;"><h4>SAR '.number_format($invoice->getSubtotal(), 2).'</h4></td>
                </tr>
                <tr>
                    <td style="width: 65%;"></td>
                    <td style="width: 20%;"><h4>Discount (SND95):</h4></td>
                    <td style="width: 15%;"><h4>-SAR '.number_format($invoice->getDiscountAmount(), 2).'</h4></td>
                </tr>
                <tr>
                    <td style="width: 65%;"></td>
                    <td style="width: 20%;"><h4>VAT (15%):</h4></td>
                    <td style="width: 15%;"><h4>SAR '.number_format($invoice->getTaxAmount(), 2).'</h4></td>
                </tr>
                <tr>
                    <td style="width: 65%;"></td>
                    <td style="width: 20%;"><h4>Grand Total:</h4></td>
                    <td style="width: 15%;"><h4>SAR '.number_format($invoice->getGrandTotal(), 2).'</h4></td>
                </tr>
            </table>
            ';
            $tcpdf->writeHTML($html, true, false, true, false, '');
        }
        $tcpdf->lastPage();
        ob_end_clean();
        $baseurl = $this->getDirPath();
        $filename = $baseurl . '/export/' . 'invoice' . date('Y-m-d_H-i-s') . '.pdf';
        return $tcpdf->Output($filename, 'D');
    }

    public function _getPdf($invoices = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
				/* start set english store id if arabic store */ 
				$invoiceStoreId = $invoice->getStoreId();
				if($invoice->getStoreId() == 2){
					$invoiceStoreId = 1;
				}
				/* end set english store id if arabic store */ 
                $this->appEmulation->startEnvironmentEmulation(
                    //$invoice->getStoreId(),
                    $invoiceStoreId,
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    true
                );
                //$this->_storeManager->setCurrentStore($invoice->getStoreId());
                $this->_storeManager->setCurrentStore($invoiceStoreId);
            }
            $page = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /*$this->insertQRCode(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                ));*/
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                )
            );
            /* Add document text and number */
            // $this->insertDocumentNumber($page, __('Invoice # ') . $invoice->getIncrementId());
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }

    /*QR code for ZATCA*/
    public function insertQRCode(&$page, $obj, $putOrderId = true)
    {
        $pdfstoreName           = $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $storeVatNumber           = $this->_scopeConfig->getValue('general/store_information/merchant_vat_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        if ($obj instanceof \Magento\Sales\Model\Order)
        {
            $shipment = null;
            $order = $obj;
        }
        elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment)
        {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
        /* Start QR Code */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            $invoice_id = $invoice->getIncrementId();
            $invoiceCreatedAt = $invoice->getCreatedAt();
            $invoiceTaxAmount = $invoice->getTaxAmount();
            $invoiceGrandTotal = $invoice->getGrandTotal();
        }
        $orderDate = $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            );

        $invoiceDate = date("Y-m-d\TH:i",strtotime($invoiceCreatedAt));

        $generatedString = GenerateQrCode::fromArray([
                            new Seller($pdfstoreName),
                            new TaxNumber($storeVatNumber),
                            new InvoiceDate($invoiceDate),
                            new InvoiceTotalAmount($invoiceGrandTotal),
                            new InvoiceTaxAmount($invoiceTaxAmount)
                        ])->render();
        
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $tempDir = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('invoiceQR/');
        $fileName = 'ord_'.$order->getRealOrderId().'.png';
        $data = $generatedString;
        $data = str_replace('data:image/png;base64,', '', $data);
        $data = str_replace(' ', '+', $data);
        $data = base64_decode($data);
        file_put_contents($tempDir.$fileName, $data);
        $pngAbsoluteFilePath = $tempDir.$fileName;
        $image = \Zend_Pdf_Image::imageWithPath($pngAbsoluteFilePath);
        
        /*width,height,*/
        //$page->drawImage($image,503,$top+10,573,$top+60);
		$page->drawImage($image,503,$top-10,573,$top-80);
    }

    /*Normal QR code*/
    public function insertQRCodeBkp(&$page, $obj, $putOrderId = true)
    {
        $pdfstoreName           = $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        if ($obj instanceof \Magento\Sales\Model\Order)
        {
            $shipment = null;
            $order = $obj;
        }
        elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment)
        {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
        /* Start QR Code */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            $invoice_id = $invoice->getIncrementId();
        }
        $orderDate = $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            );
        
        $orderGrandTotal = $currency.number_format($order->getGrandTotal(),2);
        $codeContents = "Invoice ID : #".$invoice_id.", "." Date: ".$orderDate.", "." Amount: ".$orderGrandTotal.", "." Customer Name: ".$order->getCustomerName().", "."Vendor Name: ".$pdfstoreName;
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');   
        $tempDir = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('invoiceQR/');
        $fileName = 'ord_'.$order->getRealOrderId().md5($codeContents).'.png';
 
        $pngAbsoluteFilePath = $tempDir.$fileName;
 
        $fileDriver = $objectManager->create('\Magento\Framework\Filesystem\Driver\File');
       
        if (!$fileDriver->isExists($pngAbsoluteFilePath))
        {
            QRcode::png($codeContents, $pngAbsoluteFilePath, 'L', 4, 2);
        } 
       
        $image = \Zend_Pdf_Image::imageWithPath($pngAbsoluteFilePath);
        
        /*width,height,*/
        $page->drawImage($image,503,$top+10,573,$top+60);
    }
	
	public function insertSalesPerson(&$page, $obj, $top)
    {
		if ($obj instanceof \Magento\Sales\Model\Order)
        {
            $shipment = null;
            $order = $obj;
        }
        elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment)
        {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$userFactory = $objectManager->get('Magento\User\Model\UserFactory');
		$user = $userFactory->create()->load($order->getSalesPersonId());
		$Firstname = $user->getFirstname();
		$Lastname = $user->getLastname();
		$salesPersonName = $Firstname.' '.$Lastname;
		
		$page->drawText(__('Sales Person: ') . $salesPersonName, 35, $top, 'UTF-8');
	}
	
	public function insertInstallerInfo(&$page, $obj, $yShipments)
    {
		if ($obj instanceof \Magento\Sales\Model\Order)
        {
            $shipment = null;
            $order = $obj;
        }
        elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment)
        {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$pickupstores = $objectManager->get('Ecomteck\StoreLocator\Model\Stores');
		$country = $objectManager->get('Magento\Directory\Model\Country');
		$installer_id = $order->getPickupStore();
		$pickupstoresData = $pickupstores->load($installer_id);
		$country = $country->load($pickupstoresData->getCountry())->getName();
		$page->drawText($pickupstoresData->getName(), 295, $yShipments +55, 'UTF-8');
		$page->drawText($pickupstoresData->getAddress(), 295, $yShipments +40, 'UTF-8');
		$page->drawText($pickupstoresData->getCity(), 295, $yShipments +25, 'UTF-8');
		$page->drawText($country, 295, $yShipments +10, 'UTF-8');
	}
}