<?php

namespace Hdweb\Installer\Controller\Adminhtml\Order;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;

class Sendemailtoinstaller extends \Magento\Backend\App\Action
{
    const NOTIFY_INSTALLER_TEMPLATE  = 'installer/general/admin_installer_email_template';
 
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
    protected $_filesystem;
    protected $fileFactory;
    protected $file;
    protected $dir;
    protected $orderStatusRepository;
    protected $authSession;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Ecomteck\StoreLocator\Model\Stores $pickupstores,
        \Hdweb\Core\Model\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $stateInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Directory\Model\Country $country,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderIdentity $identityContainer,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        \Magento\Backend\Model\Auth\Session $authSession

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
         $this->_filesystem     = $filesystem;
        $this->fileFactory     = $fileFactory;
        $this->file            = $file;
        $this->dir             = $dir;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->authSession = $authSession;
    }
    public function execute()
    {
        $order_id = $this->getRequest()->getParam('id');

        $admin_installer_date = $this->getRequest()->getParam('installer_date');

        $admin_installer_comment = $this->getRequest()->getParam('installer_comment');

        $order = $this->_order->load($order_id);
        
        $installer_id=$order->getPickupStore();

        if(isset($installer_id) && !empty($installer_id) ) {

         $pickupstoresData=$this->pickupstores->load($installer_id);
         $installer_email=$pickupstoresData->getEmail();
        
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
                $order->setIsnotifyinstaller(1);
                if ($order->getVinNumber()) {
                    $vehicleVinNo = $order->getVinNumber();
                } else {
                    $vehicleVinNo = '';
                }
				$baseUrl = $storeManager->getStore()->getBaseUrl();
				$installerTokenkey = md5($order->getIncrementId());
				$installationLink = $baseUrl.'installer/ajax/installationcomplete/order_id/'.$installerTokenkey;
                $templateVars = [
                'order' => $order,
                'order_id' => $order_id,
                'orderitem' => $order->getAllItems(),
                'billing' => $order->getBillingAddress(),
                'payment_html' => $paymentmethodTitle,//$this->getPaymentHtml($order),
                'store' => $order->getStore(),
                //'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                //'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'formattedShippingAddress' => $order->getCustomerName(),
                'formattedBillingAddress' => $order->getCustomerName(),
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
				'installation_link' 	  => $installationLink,	
                'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];
                //$pdfdownload = $this->saveworkorderpdf($templateVars);
                $pdfdownload = $this->createWorkOrderPDF($templateVars);

                $templateVars['pdfdownload'] = $pdfdownload['pdfdownload'];

                $email                       = $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);
                $name                        = $this->scopeConfig->getValue('trans_email/ident_support/name', ScopeInterface::SCOPE_STORE);
                $copy_to             = $this->scopeConfig->getValue('sales_email/order/copy_to', ScopeInterface::SCOPE_STORE);
                $from                = array('email' => $email, 'name' => $name);
                $receiveremail       = $pickupstoresData->getEmail();

                $storeEmailCopy = $pickupstoresData->getEmailCopy();
                $receiverEmailsArray = explode(',', $storeEmailCopy);
                array_push($receiverEmailsArray,$receiveremail);
                

                $inlineTranslation->suspend();


                $notifyInstallerTemplate=$this->scopeConfig->getValue(self::NOTIFY_INSTALLER_TEMPLATE, ScopeInterface::SCOPE_STORE);
                
                $transport = $_transportBuilder->setTemplateIdentifier($notifyInstallerTemplate)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($receiverEmailsArray)
                        ->addCc($copy_to)
                        ->addAttachment($pdfdownload['pdfData'], $pdfdownload['filename'], 'application/pdf')
                        ->getTransport();

                $transport->sendMessage();
                $inlineTranslation->resume();

                $this->messageManager->addSuccess(__('Email has been sent.'));
                /*$order->addStatusHistoryComment('Notify - Installer - ' . $admin_installer_date . ' - ' . $admin_installer_comment);*/
                $adminUser = $this->authSession->getUser();
                $comment = $order->addStatusHistoryComment(
                    'Notify - Installer - ' . $admin_installer_date . ' - ' . $admin_installer_comment . ' - BY ' . $adminUser->getFirstname(). ' '.$adminUser->getLastname()
                );
                try {
                    $orderHistory = $this->orderStatusRepository->save($comment);
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }


				$order->setIsNotifyInstaller($installerTokenkey);
                $order->save();
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            } else {
                $this->messageManager->addError(__('Please add installer email address.'));
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            }

       }else{
               $this->messageManager->addError(__('Installer not found for this order'));
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

    public function saveworkorderpdf($templateVars)
    {

        $pdf          = new \Zend_Pdf();
        $pdf->pages[] = new \Zend_Pdf_Page(\Zend_Pdf_Page::SIZE_A4);
        //$pdf->pages[] = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
        $page = $pdf->pages[0]; // this will get reference to the first page.
        //   $page1 = $pdf->pages[1]; // this will get reference to the first page.
        $style = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $width        = $page->getWidth();
        $hight        = $page->getHeight();
        $x            = 30;
        $pageTopalign = 850; //default PDF page height
        $this->y      = 850 - 170; //print table row from page top – 100px
        $style->setFont($font, 10);
        $page->setStyle($style);

        $imagePath = 'logo.png';

        $image = "";
        if ($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath)) {
            $image = \Zend_Pdf_Image::imageWithPath($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath));
        }
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

        $y1 = 800;
        $y2 = 830;
        $x1 = 20;
        $x2 = 150;

        $page->drawImage($image, $x1, $y1, $x2, $y2);
        //$font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES_BOLD);
        //        $page->drawRectangle(30, $this->y - 20, $page->getWidth() - 30, $this->y + 110, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText('New Work Order', $x, $this->y + 90, 'UTF-8');
        $order = $templateVars['order'];

        $orderid = $order->getIncrementId();
        // $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0)); //black
        // $page->setLineWidth(0.5);
        // $page->drawLine($x, $this->y + 70, 200, 100);
        $y3   = 740;
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Order #" . $orderid), $x, $y3, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Dear Installer,"), $x, $y3 - 20, 'UTF-8');
        $page->drawText(__("Thank you for accepting " . $pdfStoreName . " Work Order #" . $orderid), $x, $y3 - 35, 'UTF-8');
        $page->drawText(__("Please find the details below :"), $x, $y3 - 50, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Customer Details"), $x, $y3 - 80, 'UTF-8');

        $customername = $order->getCustomerName();
        $font         = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__($customername), $x, $y3 - 95, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Installer Information"), $x + 300, $y3 - 125, 'UTF-8');
        
      //  $installer_detail = unserialize($order->getInstallerDetail());
        //$installer_detail['name']="";
         $street           = $templateVars['installer_street'];
         $street           = wordwrap($street, 50, "&&");
         $street           = explode('&&', $street);
        //$street           = "75 Al Safa Street, Al Safa 75 Al Safa Street, Al Safa";
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText($templateVars['installer_name'], $x + 300, $y3 - 140, 'UTF-8');
        $streetbreak = 155;
        foreach ($street as $key => $value) {
            $page->drawText($value, $x + 300, $y3 - $streetbreak, 'UTF-8');
            $streetbreak += 15;
        }
        $streetbreak = $streetbreak;
        //  $page->drawTextBlock($street, 10, 600, 500, 500, Zend_Pdf_Page::ALIGN_LEFT);
        $page->drawText($templateVars['installer_city'], $x + 300, $y3 - $streetbreak, 'UTF-8');
        $streetbreak = $streetbreak + 15;
        $page->drawText($templateVars['installer_country'], $x + 300, $y3 - $streetbreak, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Vehicle Information"), $x, $y3 - 125, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Plate No.: " . $order->getPlate()), $x, $y3 - 140, 'UTF-8');
      //  $page->drawText(__("VIN No.: " . $templateVars['v_vin']), $x, $y3 - 155, 'UTF-8');  
        $page->drawText(__("Make: " . $order->getMake()), $x, $y3 - 155, 'UTF-8');
        $page->drawText(__("Model: " . $order->getModel()), $x, $y3 - 170, 'UTF-8');
        $page->drawText(__("Year: " . $order->getYear()), $x, $y3 - 185, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Delivery Date/Time :"), $x, $y3 - 215, 'UTF-8');

        $DeliveryDate=date('y-m-d',strtotime($order->getDeliveryDate()));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        //$page->drawText($DeliveryDate.' '.$order->getDeliveryComment(), $x + 100, $y3 - 215, 'UTF-8');
        $page->drawText($templateVars['admin_installer_date'], $x + 100, $y3 - 215, 'UTF-8');

        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Order Updates"), $x, $y3 - 235, 'UTF-8');
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText($templateVars['admin_installer_comment'], $x, $y3 - 260, 'UTF-8');

        $comments = str_split("", 120);// jig - $templateVars['admin_installer_comment']
        if (isset($comments[0])) {
            $page->drawText($comments[0], $x, $y3 - 250, 'UTF-8');
        }
        if (isset($comments[1])) {
            $page->drawText($comments[1], $x, $y3 - 262, 'UTF-8');
        }
        if (isset($comments[2])) {
            $page->drawText($comments[2], $x, $y3 - 274, 'UTF-8');
        }
        if (isset($comments[3])) {
            $page->drawText($comments[3], $x, $y3 - 286, 'UTF-8');
        }

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(216, 0, 0));
        //$page->setFillColor(new \Zend_Pdf_Color_Html('#4BCF2E'));
        $page->drawRectangle($x, $y3 - 300, $x + 520, $y3 - 320);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(216, 0, 0));
        //$page->setFillColor(new \Zend_Pdf_Color_Html('#4BCF2E'));
        $style->setFont($font, 10);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(255, 255, 255));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__('ITEM'), $x + 10, $y3 - 313, 'UTF-8');
        $page->drawText(__('QTY'), $x + 470, $y3 - 313, 'UTF-8');

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);

        $topy = 340;

        $ordersobj  = $templateVars['order'];
        $orderItems = $ordersobj->getAllItems();
        //$orderItems = array(array('name' => '1111', 'qty' => 3), array('name' => '1111', 'qty' => 3), array('name' => '1111', 'qty' => 3));
        foreach ($orderItems as $key => $item) {
            $name = $item->getName();
            $qty  = (int) $item->getQtyOrdered();
            // $name = $item['name'];
            // $qty  = $item['qty'];

            $page->drawText($name, $x + 10, $y3 - $topy, 'UTF-8');
            $page->drawText($qty, $x + 470, $y3 - $topy, 'UTF-8');
            $topy += 20;
        }
        $yy3  = $topy + 30;
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("Please note the following,"), $x, $y3 - $yy3, 'UTF-8');
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $yy3 = $yy3 + 20;
        $page->drawText(__("1. The tyres supplied to you under this work order must be fitted ONLY to the Vehicle bearing the license plate number and "), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 15;
        $page->drawText(__("description specified here. Please contact us if the customer insists on fitting these to another vehicle. "), $x, $y3 - $yy3, 'UTF-8');
        //$yy3 = $yy3 + 15;
        //$page->drawText(__(""), $x, $y3 - $yy3, 'UTF-8');

        $yy3 = $yy3 + 20;
        $page->drawText(__("2. Please refuse to accept delivery if the tyres being delivered by the courier are not the exact same specifications and DOT as listed here"), $x, $y3 - $yy3, 'UTF-8');
        //$yy3 = $yy3 + 15;
        //$page->drawText(__("."), $x, $y3 - $yy3, 'UTF-8');

        $yy3  = $yy3 + 20;
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 10);
        $page->setStyle($style);
        $page->drawText(__("3. The customer has paid for new tyres, delivery, installation, balancing and disposal of old tyres.You will have to charges the customer"), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 15;
        $page->drawText(__(" directly for Alignment or any other service you provide."), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 20;
        $page->drawText(__("4. The work order will not be considered complete unless the customer sign this work order and you email back a scanned copy to"), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 15;
        $page->drawText(__(" your " . $pdfStoreName . " contact."), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 30;
        $page->drawText(__("Please feel free to call us at " . $pdfStorePhone . " or Write to us at " . $pdfSalesEmail), $x, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 30;
        $page->drawText(__("Job Complete (Y/N):_________"), $x, $y3 - $yy3, 'UTF-8');
        $page->drawText(__("Remarks (if any):_____________"), $x + 300, $y3 - $yy3, 'UTF-8');
        $yy3 = $yy3 + 30;
        $page->drawText(__("Date & Time:_______________"), $x, $y3 - $yy3, 'UTF-8');
        $page->drawText(__("Customer Signature:__________"), $x + 300, $y3 - $yy3, 'UTF-8');

        $fileName = 'workorder1.pdf';
        $pdfData  = $pdf->render(); // Get PDF document as a string

        $isdownloadworkorder = $this->getRequest()->getParam('isdownloadworkorder');
        if ($isdownloadworkorder) {
            $this->fileFactory->create(
                $fileName,
                $pdf->render(),
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA, // this pdf will be saved in var directory with the name example.pdf
                'application/octet-stream'
            );
        } else {
            $workorderdir = $this->dir->getPath('media') . '/workorder';
            if (!file_exists($workorderdir)) {
                $this->file->mkdir($workorderdir);
            }
            $fileName      = $orderid . '_' . time() . '.pdf';
            $workorderpath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'workorder/' . $fileName;
            file_put_contents($workorderpath, $pdf->render());
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager  = $objectManager->create('Magento\Store\Model\StoreManagerInterface');
            $mediaUrl      = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $pdfdownload   = $mediaUrl . 'workorder/' . $fileName;
            $pdfinfo['filename']=$fileName;
            $pdfinfo['pdfData']=$pdfData;
            $pdfinfo['pdfdownload']=$pdfdownload;
            return $pdfinfo;

        }
    }

	public function createWorkOrderPDF($templateVars){
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
        <td width="50%" style="font-size: 9px; line-height: 1.5; text-align: right">شركة فارس الطرق<br />  الشرفية، مبنى بريجستون، الطابق الثاني<br />المملكة العربية السعودية، جدة، شارع فلسطين، الشرفية،<br />رقم الضريبة : 300196868700003<br />هاتف: <span dir="ltr" lang="en">800 244 0211</span><br /> موقع الكتروني: <span dir="ltr" lang="en">tyresonline.sa</span><br /></td></tr>
</table>
        <table cellspacing="0" cellpadding="2" border="0" width="100%">
			<tr><td height="20"></td></tr>
			<tr>
				<td style="font-size: 10px; font-weight: bold;">رقم الطلب  #<span dir="ltr" lang="en">'.$orderid.'</span></td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;"> شكرا لستقبالكم أمر الشغل من تايرزاونلاين رقم #<span dir="ltr" lang="en">'.$orderid.'</span><br />الرجاء العثور على التفاصيل أدناه :
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
			<td width="50%" style="font-size: 10px; font-weight: bold;">معلومات مركز الخدمة</td>
			</tr>
			<tr>
			<td width="50%" style="font-size: 9px; line-height: 1.7;">رقم اللوحة : '.$order->getPlate().'<br /> ماركة المركبة : '.$order->getMake().'<br />طراز المركبة : '.$order->getModel().'<br />سنة: '.$order->getYear().'<br /></td>
			<td width="50%" style="font-size: 9px; line-height: 1.7;">'.$installerNameAr.'<br />'.$installerStreetAr.'<br />'.$installerCityAr.'<br />السعودية</td>
			</tr>
		</table>
		<br />
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
			<tr>
				<td style="font-size: 10px; font-weight: bold;">تاريخ / وقت التسليم : <span style="font-weight: normal;">'.$deliveryTime.'</span></td>
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
				<td style="font-size: 10px; font-weight: bold;">يرجى ملاحظة ما يلي</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">1 .يتم تركيب الإطارات المذكورة بأمر الشغل للسيارة التي تحمل رقم اللوحة والوصف المحدد هنا. يرجى الاتصال بنا إذا أصر العميل على تركيبها في مركبة أخرى.</td>
			</tr>
			 <tr>
				<td style="font-size: 9px; line-height: 1.7;">2. يرجى رفض قبول التسليم إذا كانت الإطارات التي يتم تسليمها من قبل شركة الشحن ليست بالضبط نفس المواصفات و DOT كما هو مذكور هنا</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">3 .دفع العميل مقابل الإطارات الجديدة والتسليم والتركيب وترصيص والتخلص من الإطارات القديمة. سيتعين عليك تحميل العميل رسومًا مباشرة مقابل ميزان أذرعه أو أي خدمة أخرى تقدمها.</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">4 .لن يتم اعتبار أمر الشغل مكتملاً ما لم يوقع العميل على أمر الشغل هذا وأرسال منه نسخة ممسوحة ضوئيًا بالبريد الإلكتروني إلى <span dir="ltr" lang="en">'. $pdfStoreName.'</span>.</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">لا تتردد في الاتصال بنا على <span dir="ltr" lang="en">'.$pdfStorePhone.'</span> أو اكتب إلينا على  <span>'. $pdfSalesEmail.'</span></td>
			</tr>
		</table>

		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		   <tr><td height="25"></td></tr>
		</table>

		<table cellspacing="0" cellpadding="4" border="0" width="100%">
			<tr>
				<td style="font-size: 9px;">اكتملت الخدمة (نعم / لا): <span>________________</span></td>
				<td style="font-size: 9px;">ملاحظات (إن وجدت): <span>________________</span></td>
			</tr>
			<tr><td height="1"></td></tr>
			<tr>
				<td style="font-size: 9px;">التاريخ والوقت: <span>________________</span></td>
				<td style="font-size: 9px;">توقيع العميل: <span>________________</span></td>
			</tr>
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
				<td style="font-size: 10px; font-weight: bold;">Order #'.$orderid.'</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">Dear Installer,<br />Thank you for accepting '.$pdfStoreName.' Work Order #'.$orderid.'<br />Please find the details below :
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
				<td style="font-size: 10px; font-weight: bold;">Delivery Date/Time : <span style="font-weight: normal;">'.$deliveryTime.'</span></td>
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
				<td style="font-size: 9px; line-height: 1.7;">1. The tyres supplied to you under this work order must be fitted ONLY to the Vehicle bearing the license plate number and description specified here. Please contact us if the customer insists on fitting these to another vehicle.</td>
			</tr>
			 <tr>
				<td style="font-size: 9px; line-height: 1.7;">2. Please refuse to accept delivery if the tyres being delivered by the courier are not the exact same specifications and DOT as listed here</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">3. The customer has paid for new tyres, delivery, installation, balancing and disposal of old tyres.You will have to charges the customer directly for Alignment or any other service you provide.</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">4. The work order will not be considered complete unless the customer sign this work order and you email back a scanned copy to your '.$pdfStoreName.' contact.</td>
			</tr>
			<tr>
				<td style="font-size: 9px; line-height: 1.7;">Please feel free to call us at '.$pdfStorePhone.' or Write to us at '.$pdfSalesEmail.'</td>
			</tr>
		</table>

		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		   <tr><td height="20"></td></tr>
		</table>

		<table cellspacing="0" cellpadding="4" border="0" width="100%">
			<tr>
				<td style="font-size: 9px;">Job Complete (Y/N): <span>________________</span></td>
				<td style="font-size: 9px;">Remarks (if any): <span>________________</span></td>
			</tr>
			<tr><td height="1"></td></tr>
			<tr>
				<td style="font-size: 9px;">Date & Time: <span>________________</span></td>
				<td style="font-size: 9px;">Customer Signature: <span>________________</span></td>
			</tr>
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