<?php

namespace Hdweb\Installer\Controller\Adminhtml\Order;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;


class Sendemailtocustomer extends \Magento\Backend\App\Action
{
   
  
    const NOTIFY_CUSTOMER_TEMPLATE  = 'installer/general/admin_notify_customer_email_template';

 
    protected $_order;
    protected $scopeConfig;
    protected $pickupstores;
    protected $transportBuilder;
    protected $stateInterface;
    protected $storeManagerInterface;
    protected $country;
    protected $addressRenderer;
    protected $paymentHelper;
    protected $identityContainer;
    protected $authSession;
    protected $_filesystem;
    protected $dir;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Ecomteck\StoreLocator\Model\Stores $pickupstores,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $stateInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Directory\Model\Country $country,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderIdentity $identityContainer,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\DirectoryList $dir

    ) {
        parent::__construct($context);
        $this->_order          = $order;
        $this->scopeConfig     = $scopeConfig;
        $this->pickupstores    = $pickupstores;
        $this->transportBuilder          = $transportBuilder;
        $this->stateInterface     = $stateInterface;
        $this->storeManagerInterface    = $storeManagerInterface;
        $this->country    = $country;
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->identityContainer = $identityContainer;
        $this->authSession = $authSession;
        $this->_filesystem     = $filesystem;
        $this->dir             = $dir;
    }
    public function execute()
    {

         $order_id = $this->getRequest()->getParam('order_id');

        $admin_installer_date = $this->getRequest()->getParam('installer_date');

        $admin_installer_comment = $this->getRequest()->getParam('installer_comment');

        $order = $this->_order->load($order_id);
        
        $installer_id=$order->getPickupStore();

        if(isset($installer_id) && !empty($installer_id) ) {

         $pickupstoresData=$this->pickupstores->load($installer_id);
         $installer_email=$order->getCustomerEmail();
        
            if (!empty($installer_email) && strpos($installer_email, '@') !== false) {
                $_transportBuilder = $this->transportBuilder;
                $inlineTranslation = $this->stateInterface;
                $storeManager      = $this->storeManagerInterface;
                $storeManager->setCurrentStore($order->getStore()->getId());
                $country = $this->country->load($pickupstoresData->getCountry())->getName();
                
                $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeManager->getStore()->getId());

                $payment            = $order->getPayment();
                $method             = $payment->getMethodInstance();
                $paymentmethodTitle = $method->getTitle();
                if ($order->getVinNumber()) {
                    $vehicleVinNo = $order->getVinNumber();
                } else {
                    $vehicleVinNo = '';
                }
               
                $templateVars = [
                'order' => $order,
                'order_id' => $order_id,
                'orderitem' => $order->getAllItems(),
                'billing' => $order->getBillingAddress(),
                'payment_html' => $paymentmethodTitle,//$this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'created_at_formatted' => $order->getCreatedAtFormatted(2),
                'admin_installer_date' => $admin_installer_date,
                'admin_installer_comment' => $admin_installer_comment,
                'installer_name'          => $pickupstoresData->getName(),
                'installer_name_ar'          => $pickupstoresData->getNameRtl(),
                'installer_street'        => $pickupstoresData->getAddress(),
                'installer_street_ar'        => $pickupstoresData->getAddressRtl(),
                'installer_city'          => $pickupstoresData->getCity(),
                'installer_city_ar'          => $pickupstoresData->getCityRtl(),
                'installer_region'        => $pickupstoresData->getRegion(),
                'installer_country'       => $country,
                'installer_managername'   => '',//$installer_detail['storemanager_name'],
                'installer_email'         => $pickupstoresData->getEmail(),
                'installer_phone'         => 'T: '.$pickupstoresData->getPhone(),
                'installer_location_map'  => $pickupstoresData->getExternalLink(),
                'vehicle_plate'           => $order->getPlate(),     
                'vehicle_vinno'           => $vehicleVinNo,     
                'vehicle_make'            => $order->getMake(),     
                'vehicle_model'           => $order->getModel(),     
                'vehicle_year'            => $order->getYear(),     
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => $order->getIsNotVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote(),
                    'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];

                $pdfdownload = $this->notifyCustomerPDF($templateVars);
                $templateVars['pdfdownload'] = $pdfdownload['pdfdownload'];

                $email                       = $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);
                $name                        = $this->scopeConfig->getValue('trans_email/ident_support/name', ScopeInterface::SCOPE_STORE);
                $copy_to             = $this->scopeConfig->getValue('sales_email/order/copy_to', ScopeInterface::SCOPE_STORE);
                $from                = array('email' => $email, 'name' => $name);
                $inlineTranslation->suspend();
                $receiveremail       = explode(',', $installer_email);
               
                $notifyInstallerTemplate=$this->scopeConfig->getValue(self::NOTIFY_CUSTOMER_TEMPLATE, ScopeInterface::SCOPE_STORE);

                $transport = $_transportBuilder->setTemplateIdentifier($notifyInstallerTemplate)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($installer_email)
                        ->addCc($copy_to)
                        ->addAttachment($pdfdownload['pdfData'], $pdfdownload['filename'], 'application/pdf')
                        ->getTransport();

                $transport->sendMessage();
                $inlineTranslation->resume();

                $this->messageManager->addSuccess(__('Email has been sent.'));
                $adminUser = $this->authSession->getUser();
                $order->addStatusHistoryComment('Notify - Customer - ' . $admin_installer_date . ' - ' . $admin_installer_comment . ' - BY ' . $adminUser->getFirstname(). ' '.$adminUser->getLastname());
                $order->save();
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            } else {
                $this->messageManager->addError(__('Please add customer email address.'));
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            }

       }else{
               $this->messageManager->addError(__('Customer not found for this order'));
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
         
       }     

    }
    
    protected function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    protected function getPaymentHtml($order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }

    public function notifyCustomerPDF($templateVars){
        // create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('TyresOnline');
        $pdf->setTitle('Tyresonline Order');
        $pdf->setSubject('Invoice');
        $pdf->setKeywords('TCPDF, PDF, example, test, guide');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(8, 8, 8, true); // set the margins 

        // set auto page breaks
        $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->setRTL(true);
        $pdf->setFont('dejavusans', '', 10);

        $tagvs = array('p' => array(0 => array('h' => 0, 'n' => 0), 1 => array('h' => 0, 'n'
        => 0)));

        $pdf->setHtmlVSpace($tagvs);

        // add a page
        $pdf->AddPage();
        
        $englishStoreId = 1;
        $arabicStoreId = 2;
        $this->storeManagerInterface->setCurrentStore($arabicStoreId);
        $pdflogoName = 'tyresonline-brand-logo-ar.png';
        $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'email-signature/'.$pdflogoName;
        
        $pdfSalesEmail = '';
        $salesEmail    = $this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE);
        if ($salesEmail != '') {
            $pdfSalesEmail = $salesEmail;
        }

        $pdfStoreName = '';
        $storeName    = $this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
        if ($storeName != '') {
            $pdfStoreName = $storeName;
        }

        $pdfStorePhone = '';
        $storePhone    = $this->scopeConfig->getValue('general/store_information/phone', ScopeInterface::SCOPE_STORE);
        if ($storePhone != '') {
            $pdfStorePhone = $storePhone;
        }
        $order = $templateVars['order'];
        $orderid = $order->getIncrementId();
        $customername = $order->getCustomerName();
        $installerName = $templateVars['installer_name'];
        $installerNameAr = $templateVars['installer_name_ar'];
        $installerStreet = $templateVars['installer_street'];
        $installerStreetAr = $templateVars['installer_street_ar'];
        $installerCity = $templateVars['installer_city'];
        $installerCityAr = $templateVars['installer_city_ar'];
        $installerRegion = $templateVars['installer_region'];
        $installerCountry = $templateVars['installer_country'];
        $deliveryTime = $templateVars['admin_installer_date'];
        $installerComment = $templateVars['admin_installer_comment'];
        $orderItems = $order->getAllItems();
        $itemtemplate   = '';
        foreach ($orderItems as $key => $item) {
            $name = $item->getName();
            $qty  = (int) $item->getQtyOrdered();
            $itemtemplate .= 
                '<tr>
                    <td width="80%" style="font-size: 9px; font-weight: bold; line-height: 1.7;">'.$name.'</td>
                    <td width="20%" style="font-size: 9px; font-weight: bold; line-height: 1.7; text-align: center">'.$qty.'</td>
                </tr>';
        }

        /* Start arabic version */
        
        // set some text to print
        $html = '<table cellspacing="0" cellpadding="2" border="0" width="100%" style="border-bottom: 1px solid #d70000;">
     <tr>
        <td width="50%" style="text-align: right">
            <img src="'.$imagePath.'" width="180" border="0">
        </td>
        <td width="50%" style="font-size: 9px; line-height: 1.5; text-align: right">شركة فارس الطرق<br />  الشرفية، مبنى بريجستون، الطابق الثاني<br />المملكة العربية السعودية، جدة، شارع فلسطين، الشرفية،<br /> رقم الضريبة : 300196868700003<br />هاتف: <span dir="ltr" lang="en">800 244 0211</span><br /> موقع الكتروني: <span dir="ltr" lang="en">tyresonline.sa</span><br /></td></tr>
</table>
        <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr><td height="15"></td></tr>
            <tr>
                <td style="font-size: 11px; font-weight: bold;">طلب تركيب جديد</td>
            </tr>
            <tr><td height="20"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">الترتيب #<span dir="ltr" lang="en">'.$orderid.'</span></td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">حضرة العميل،<br />شكراً لاختيارك  '.$pdfStoreName.', لقد تم تأكيد موعد طلبك  # <span dir="ltr" lang="en">'.$orderid.'</span><br />
                يرجى الاطلاع على التفاصيل التالية :
                </td>
            </tr>
            <tr><td height="20"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">تفاصيل العميل</td>
            </tr>
            <tr>
                <td style="font-size: 9px;">'.$customername.'</td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr><td height="20"></td></tr>
            <tr>
            <td width="50%" style="font-size: 10px; font-weight: bold;">معلومات المركبة</td>
            <td width="50%" style="font-size: 10px; font-weight: bold;">معلومات المثبت</td>
            </tr>
            <tr>
            <td width="50%" style="font-size: 9px; line-height: 1.7;">لوحة لا.: '.$order->getPlate().'<br />جعل: '.$order->getMake().'<br />الموديل: '.$order->getModel().'<br />سنة: '.$order->getYear().'<br /></td>
            <td width="50%" style="font-size: 9px; line-height: 1.7;">'.$installerNameAr.'<br />'.$installerStreetAr.'<br />'.$installerCityAr.'<br />السعودية</td>
            </tr>
        </table>
        <br />
        <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr>
                <td style="font-size: 10px; font-weight: bold;">تاريخ/ وقت الموعد : <span style="font-weight: normal;">'.$deliveryTime.'</span></td>
            </tr>
            <tr><td height="15"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">تحديثات الطلب</td>
            </tr>
            <tr>
                <td style="font-size: 9px;">'.$installerComment.'</td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="15"></td></tr>
        </table>

        <table cellspacing="0" cellpadding="4" border="1" width="100%">
            <tr>
                <th width="80%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff;">بند</th>
                <th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">الكمية</th>
            </tr>
            '.$itemtemplate.'
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="25"></td></tr>
        </table>

        <table cellspacing="0" cellpadding="4" border="0" width="100%">
            <tr>
                <td style="font-size: 10px; font-weight: bold;">يرجى الانتباه لما يلي،</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">1. ينبغي تركيب الإطارات التي تمّ إرسالها لك بموجب هذا الطلب على السيارة التي تحمل رقم لوحة الترخيص والوصف المحدّدَيْن هنا. يرجى الاتصال بخدمة العملاء إذا حدث أيّ تباين في هذا المجال.</td>
            </tr>
             <tr>
                <td style="font-size: 9px; line-height: 1.7;">2. يرجى عدم قبول استلام الإطارات التي يتولّى تركيبها مزوّد خدمة التركيب إذا لم تكن بالمواصفات ذاتها المدرجة هنا، والاتصال بنا على الفور.</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">3. يشمل السعر الذي دفعته كلفة الإطارات الجديدة وتوصيلها وتركيبها وضبط توازنها والتخلص من الإطارات القديمة. إذا طلبت أيّ خدمةٍ أخرى، مثل ضبط استقامة العجلات أو غيرها، فيتعيّن عليك دفع كلفتها مباشرةً لمزوّد خدمة التركيب. كما ننصحك بضبط استقامة عجلات سيارتك مرةً واحدة في السنة على الأقل.</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">لا تتردد في الاتصال بنا على <span dir="ltr" lang="en">'.$pdfStorePhone.'</span> أو اكتب إلينا على <span dir="ltr" lang="en">'. $pdfSalesEmail.'</span></td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="25"></td></tr>
        </table>
        ';
        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        /* End arabic version */

        /* Start english version */
        
        $pdf->AddPage();
        
        $this->storeManagerInterface->setCurrentStore($englishStoreId);
        $pdflogoName = 'tyresonline-brand-logo.png';
        $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'email-signature/'.$pdflogoName;
        
        $pdfSalesEmail = '';
        $salesEmail    = $this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE);
        if ($salesEmail != '') {
            $pdfSalesEmail = $salesEmail;
        }

        $pdfStoreName = '';
        $storeName    = $this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
        if ($storeName != '') {
            $pdfStoreName = $storeName;
        }

        $pdfStorePhone = '';
        $storePhone    = $this->scopeConfig->getValue('general/store_information/phone', ScopeInterface::SCOPE_STORE);
        if ($storePhone != '') {
            $pdfStorePhone = $storePhone;
        }
        
        // set some text to print
        $html = '<table cellspacing="0" cellpadding="2" border="0" width="100%" style="border-bottom: 1px solid #d70000;">
     <tr>
        <td width="50%" style="text-align: left">
            <img src="'.$imagePath.'" width="180" border="0">
        </td>
        <td width="50%" style="font-size: 8px; line-height: 1.5; text-align: right;">Fares Al Toruk Company Limited<br />Fourth Floor, Bridgestone Bldg. Al Sharafeyah,<br />Palestine St. Jeddah, KSA<br />VAT: 300196868700003<br />Phone: 800 244 0211<br /> Website: tyresonline.sa<br /></td></tr>
        </table>
          <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr><td height="20"></td></tr>
            <tr>
                <td style="font-size: 11px; font-weight: bold;">Order of Appointment</td>
            </tr>
            <tr><td height="20"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">Order #'.$orderid.'</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">Dear customer,<br />Thank you for choosing '.$pdfStoreName.', We have confirmed your appointment for Order #'.$orderid.'<br />Please find the details below :
                </td>
            </tr>
            <tr><td height="20"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">Customer Details</td>
            </tr>
            <tr>
                <td style="font-size: 9px;">'.$customername.'</td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr><td height="20"></td></tr>
            <tr>
            <td width="50%" style="font-size: 10px; font-weight: bold;">Vehicle Information</td>
            <td width="50%" style="font-size: 10px; font-weight: bold;">Installer Information</td>
            </tr>
            <tr>
            <td width="50%" style="font-size: 9px; line-height: 1.7;">Plate No.: '.$order->getPlate().'<br />Make: '.$order->getMake().'<br />Model: '.$order->getModel().'<br />Year: '.$order->getYear().'<br /></td>
            <td width="50%" style="font-size: 9px; line-height: 1.7;">'.$installerName.'<br />'.$installerStreet.'<br />'.$installerCity.'<br />'.$installerCountry.'</td>
            </tr>
        </table>
        <br />
        <table cellspacing="0" cellpadding="2" border="0" width="100%">
            <tr>
                <td style="font-size: 10px; font-weight: bold;">Appointment Date/Time : <span style="font-weight: normal;">'.$deliveryTime.'</span></td>
            </tr>
            <tr><td height="15"></td></tr>
            <tr>
                <td style="font-size: 10px; font-weight: bold;">Order Updates</td>
            </tr>
            <tr>
                <td style="font-size: 9px;">'.$installerComment.'</td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="20"></td></tr>
        </table>

        <table cellspacing="0" cellpadding="4" border="1" width="100%">
            <tr>
                <th width="80%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff;">ITEM</th>
                <th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">QTY</th>
            </tr>
            '.$itemtemplate.'
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="20"></td></tr>
        </table>

        <table cellspacing="0" cellpadding="4" border="0" width="100%">
            <tr>
                <td style="font-size: 10px; font-weight: bold;">Please note the following</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">1. The tyres supplied to you under this work order will be fitted ONLY to the Vehicle bearing the license plate number and the description specified here. Please contact Customer Service ifthere is a discrepancy here.</td>
            </tr>
             <tr>
                <td style="font-size: 9px; line-height: 1.7;">2. Please refuse to accept delivery if the tyres being installed by the installer are not the exact same specifications as listed here and call us immediately.</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">3. The price you have paid includes new tyres, delivery, installation, balancing and disposal of your old tyres. Any additional Service Including Alignment will have to be paid by you to the installer directly. We do recommend you align your wheel at least once a year.</td>
            </tr>
            <tr>
                <td style="font-size: 9px; line-height: 1.7;">Please feel free to call us at '.$pdfStorePhone.' or Write to us at '.$pdfSalesEmail.'</td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="2" border="0" width="100%">
           <tr><td height="20"></td></tr>
        </table>
        ';
        // output the HTML content
        $pdf->setRTL(false);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        /* End english version */
        
        $workorderdir = $this->dir->getPath('media') . '/workorder';
        if (!file_exists($workorderdir)) {
            $this->file->mkdir($workorderdir);
        }
        $fileName      = $orderid . '_' . time() . '.pdf';
        $workorderpath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'workorder/' . $fileName;
        $pdf->Output($workorderpath, 'F');
        $mediaUrl      = $this->storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $pdfdownload   = $mediaUrl . 'workorder/' . $fileName;
        $pdf_content = file_get_contents($workorderpath);
        $pdfinfo['filename'] = $fileName;
        $pdfinfo['pdfData'] = $pdf_content;
        $pdfinfo['pdfdownload'] = $pdfdownload;
        return $pdfinfo;
    }
  

}