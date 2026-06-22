<?php
namespace Hdweb\Booking\Controller\Index;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;

class Serviceemail extends \Magento\Framework\App\Action\Action {
    const XML_PATH_EMAIL_RECIPIENT_NAME  = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
	const NOTIFY_OILSERVICE_EMAIL_TEMPLATE  = 'hdwebcore/general/oilservice_email_template';

    protected $_inlineTranslation;
    protected $_transportBuilder;
    protected $_scopeConfig;
    protected $_logLoggerInterface;
	protected $pricehelper;
	protected $_filesystem;
	protected $fileFactory;
	public $date;
	protected $_objectManager;
	protected $storeManager;
	protected $bookingModel;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Hdweb\Core\Model\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Framework\Pricing\Helper\Data $pricehelper,
		\Magento\Framework\Filesystem $filesystem,
		\Magento\Framework\App\Response\Http\FileFactory $fileFactory,
		\Magento\Framework\Stdlib\DateTime\DateTime $date,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Hdweb\Booking\Model\Items $bookingModel,
        array $data = []

    ) {
        $this->_inlineTranslation   	= $inlineTranslation;
        $this->_transportBuilder    	= $transportBuilder;
        $this->_scopeConfig         	= $scopeConfig;
        $this->_logLoggerInterface  	= $loggerInterface;
        $this->resultForwardFactory 	= $resultForwardFactory;
        $this->messageManager       	= $context->getMessageManager();
		$this->pricehelper           	= $pricehelper;
		$this->_filesystem           	= $filesystem;
        $this->fileFactory          	= $fileFactory;
		$this->date = $date;
		$this->_objectManager 			= $objectManager;
		$this->storeManager 			= $storeManager;
		$this->bookingModel 			= $bookingModel;

        parent::__construct($context);

    }

    public function execute() {
		$_attributehelper = $this->_objectManager->create('Hdweb\Addattribute\Helper\Productdetails');
        $model = $this->bookingModel;
        $post  = $this->getRequest()->getParams();
		$oilformData = array();
		$serviceformData = array();
		$oilproductIds = array();
		$serviceproductIds = array();
		$product_oil_litre = '';
		$service_product_oil_litre = '';
		$discountPercentage = '';
		if(isset($post['oilserialize'])){
			parse_str($post['oilserialize'], $oilformData);
			//echo "<pre>";print_r($oilformData);
			$productIds = array_unique($oilformData['serviceproduct']);
			$oilproductIds = array_filter($productIds);
			$product_oil_litre = $oilformData['product_oil_litre'];
		}
		
		if(isset($post['servicepacksserialize'])){
			parse_str($post['servicepacksserialize'], $serviceformData);
			//echo "<pre>";print_r($serviceformData);
			$servproductIds = array_unique($serviceformData['serviceproduct']);
			$serviceproductIds = array_filter($servproductIds);
			$service_product_oil_litre = $serviceformData['product_oil_litre'];
			if(isset($serviceformData['package_type'])){
				if($serviceformData['package_type'] == 'Standard Service Pack'){
					$discountPercentage = 15;
				}
				if($serviceformData['package_type'] == 'Premium Service Pack'){
					$discountPercentage = 20;
				}
				if($serviceformData['package_type'] == 'Superb Service Pack'){
					$discountPercentage = 25;	
				}
			}
		}
		
        $model->setFname($post['name']);
        $model->setLname($post['lastname']);
        $model->setPhonenumber($post['phonenumber']);
        $model->setEmail($post['email']);
		$model->setMake($post['make']);
        $model->setModel($post['model']);
        $model->setYear($post['engine']);
        $model->setStatus('Pending');
		$message = 'Via Email';
		if ($post['submittype'] == 'download') {
			$message = 'Via Download';
		}
		$model->setMessage($message);
		$serviceType = 0;
        if(isset($post['service'])){
			$service = $post['service'];
		   if($service == 'oil'){
				$serviceType = 1;
		   }
		  $model->setService($service); 
        }

        $model->save();
        $model->setAppointmentIncrementId('TYO0000' . $model->getId());
        $model->save();
        // print_r($model->getData());die;
        // echo "<pre>";print_r($post); die();
        try
        { 
            // Send Mail
            
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $sender = [
                'name'  => $this->_scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->_scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ];

            $sentToEmail = $this->_scopeConfig->getValue('trans_email/ident_custom2/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $sentToName = $this->_scopeConfig->getValue('trans_email/ident_custom2/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $receiveemails[] = $sentToEmail;
            if (isset($post['email']) && !empty($post['email'])) {
                $receiveemails[] = $post['email'];
            }
			
			$vehicleInfo = ucfirst($post['make']).', '.ucfirst($post['model']).', '.$post['engine'];
			
			/* Create PDF */
				
			$pdfstoreName           = $this->_scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
			$pdfStoreaddressStreet1 = $this->_scopeConfig->getValue('purchaseorder/general/po_store_address_street1', ScopeInterface::SCOPE_STORE);
			$pdfStoreaddressStreet2 = $this->_scopeConfig->getValue('purchaseorder/general/po_store_address_street2', ScopeInterface::SCOPE_STORE);
			$pdfTrnno               = $this->_scopeConfig->getValue('purchaseorder/general/po_trn_no', ScopeInterface::SCOPE_STORE);
			$pdfPhoneno             = $this->_scopeConfig->getValue('purchaseorder/general/po_phone_no', ScopeInterface::SCOPE_STORE);
			$pdfWebsiteName         = $this->_scopeConfig->getValue('purchaseorder/general/po_website', ScopeInterface::SCOPE_STORE);
			$pdfContactPerson       = $this->_scopeConfig->getValue('purchaseorder/general/po_contact_person', ScopeInterface::SCOPE_STORE);
			$pdfContactPersonPhone  = $this->_scopeConfig->getValue('purchaseorder/general/po_contact_person_phone_no', ScopeInterface::SCOPE_STORE);
			$pdfContactPersonEmail  = $this->_scopeConfig->getValue('purchaseorder/general/po_contact_person_email', ScopeInterface::SCOPE_STORE);
			$pdfFileName            = $this->_scopeConfig->getValue('purchaseorder/general/po_file_name', ScopeInterface::SCOPE_STORE);
			
			$pdf                    = new \Zend_Pdf();
			$pdf->pages[]           = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
			$page                   = $pdf->pages[0]; // this will get reference to the first page.
			$style                  = new \Zend_Pdf_Style();
			$style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
			$font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
			$style->setFont($font, 15);
			$page->setStyle($style);
			$width        = $page->getWidth();
			$hight        = $page->getHeight();
			$x            = 30;
			$pageTopalign = 850; //default PDF page height
			$this->y      = 850 - 170; //print table row from page top – 100px
			//Draw table header row’s
			$style->setFont($font, 16);
			$page->setStyle($style);

			$imagePath = 'logo.png';

			$storeName          = '';
			$storeAddress1      = '';
			$storeAddress2      = '';
			$trnNo              = '';
			$phoneNo            = '';
			$website            = '';
			$contactPerson      = '';
			$contactPersonPhone = '';
			$contactPersonEmail = '';
			$pdfFile            = 'PO-';

			if ($pdfstoreName != '') {
				$storeName = $pdfstoreName;
			}

			if ($pdfStoreaddressStreet1 != '') {
				$storeAddress1 = $pdfStoreaddressStreet1;
			}

			if ($pdfStoreaddressStreet2 != '') {
				$storeAddress2 = $pdfStoreaddressStreet2;
			}

			if ($pdfTrnno != '') {
				$trnNo = $pdfTrnno;
			}
			if ($pdfPhoneno != '') {
				$phoneNo = $pdfPhoneno;
			}
			if ($pdfWebsiteName != '') {
				$website = $pdfWebsiteName;
			}
			if ($pdfContactPerson != '') {
				$contactPerson = $pdfContactPerson;
			}
			if ($pdfContactPersonPhone != '') {
				$contactPersonPhone = $pdfContactPersonPhone;
			}
			if ($pdfContactPersonEmail != '') {
				$contactPersonEmail = $pdfContactPersonEmail;
			}
			if ($pdfFileName != '') {
				$pdfFile = $pdfFileName;
			}
			
			$image = "";
			if ($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath)) {
				$image = \Zend_Pdf_Image::imageWithPath($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath));
			}

			$y1 = 820-43;
			$y2 = 820;
		   // $x1 = 86;
		   // $x2 = 341;
			$x1 = 25;
			$x2 = $x1 + 170;

			$page->drawImage($image, $x1, $y1, $x2, $y2);

			$style->setFont($font, 12);
			$page->setStyle($style);
			$page->drawText(__("Easy Click Tyres Trading LLC"), 375, $this->y + 130, 'UTF-8');
			$style->setFont($font, 10);
			$page->setStyle($style);
			$page->drawText(__("Office 223 Block C, Mardoof Bldg.,"), 375, $this->y + 115, 'UTF-8');
			$page->drawText(__("Al Safa 1, Sheikh Zayed Rd. Dubai"), 375, $this->y + 100, 'UTF-8');
			//$page->drawText(__($storeAddress2), 350, $this->y + 60, 'UTF-8');
			$page->drawText(__("Phone: 800255 89737"), 375, $this->y + 85, 'UTF-8');
			$page->drawText(__("Web: www.tyresonline.ae"), 375, $this->y + 70, 'UTF-8');
			
			$style->setFont($font, 12);
			$page->setStyle($style);
			
			// Vendor Detail
			$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
			$page->drawRectangle(30, $this->y, $page->getWidth() - 300, $this->y - 100, \Zend_Pdf_Page::SHAPE_DRAW_FILL);
			$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
			$style->setFont($font, 12);
			$page->drawText(__('QUOTE INFORMATION'), $x + 15, $this->y - 18, 'UTF-8');
			
			$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
			$page->drawRectangle(300, $this->y, $page->getWidth() - 30, $this->y - 100, \Zend_Pdf_Page::SHAPE_DRAW_FILL);
			$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
			$style->setFont($font, 8);
			
			$page->drawText(__('CUSTOMER INFORMATION'), 315, $this->y - 18, 'UTF-8');
			
			$page->drawText('Date: ' . date("d/m/Y"), $x + 15, $this->y - 40, 'UTF-8');
			$page->drawText('Service No: ' . $model->getAppointmentIncrementId(), $x + 15, $this->y - 55, 'UTF-8');
			//$page->drawText('Quote By: ' . $post['name'], $x + 15, $this->y - 70, 'UTF-8');
			
			$page->drawText($post['name'], 315, $this->y - 40, 'UTF-8');
			$page->drawText('Phone: ' . $post['phonenumber'], 315, $this->y - 55, 'UTF-8');
			$page->drawText('Email: ' . $post['email'], 315, $this->y - 70, 'UTF-8');
			$page->drawText('Vehicle: ' . $vehicleInfo, 315, $this->y - 85, 'UTF-8');
			
			
			/* Start Oil Change Items */
			if(count($oilproductIds) > 0){
				// iTems
				$this->y += 70;
				$style->setFont($font, 14);
				$page->drawText(__('OIL CHANGE'), 30, $this->y - 190, 'UTF-8');
				// Vendor Detail
				$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
				$page->drawRectangle(30, $this->y - 200, $page->getWidth() - 30, $this->y - 220);
				$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
				$style->setFont($font, 14);
				$page->drawText(__('ITEM'), $x + 5, $this->y - 215, 'UTF-8');
				$page->drawText(__('PRICE'), 360, $this->y - 215, 'UTF-8');
				$page->drawText(__('QTY'), 440, $this->y - 215, 'UTF-8');
			   // $page->drawText(__('UNIT PRICE'), 410, $this->y - 215, 'UTF-8');
				$page->drawText(__('TOTAL'), 500, $this->y - 215, 'UTF-8');

				//ITEM VALUE
				$style->setFont($font, 12);
				$item_y = 245;
				$qty = 1;
				$subTotal = 0;
				foreach($oilproductIds as $key => $productId){
					$product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($productId);
					$price =  $product->getPrice(); //number_format($finalPricewithtax, 2);
					$optionsPrice = array();
					$customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
					$oilPerLitre = 1;
					if($product_oil_litre != ''){
						$oilPerLitre = $product_oil_litre;
					}
					foreach ($customOptions as $o) {
						foreach ($o->getValues() as $value) {
							if($value->getTitle() != $oilPerLitre){
								continue;
							}else{
								$optionsPrice[] = $value['price'];
							}
						}
					}
					if(count($optionsPrice) > 0){
						$optionPricevalue =  $optionsPrice[0];
						$totalPrice = $optionPricevalue + $product->getPrice();
						/* $multiplyPrice = $totalPrice * 0.05;
						$customOptionPrice = $totalPrice + $multiplyPrice; */
						$price = number_format($totalPrice, 2);
					}
					
					$itemno      = $key + 1;
					$rowtotal    = $price * (int) $qty;
					$rowtotal    = number_format($rowtotal, 2);
					$rowtotal    = str_replace(',', '', $rowtotal);
					$subTotal+= $price;

					$page->drawText($product->getName(), 33, $this->y - $item_y, 'UTF-8');
					//$page->drawText($cartitemsData->getName(), 80, $this->y - $item_y, 'UTF-8');
					$page->drawText($this->pricehelper->currency($price, true, false), 355, $this->y - $item_y, 'UTF-8');
					$page->drawText($qty, 445, $this->y - $item_y, 'UTF-8');
				   // $page->drawText($this->pricehelper->currency($cartitemsData->getPrice(), true, false), 410, $this->y - $item_y, 'UTF-8');
					$page->drawText($this->pricehelper->currency($rowtotal, true, false), 490, $this->y - $item_y, 'UTF-8');

					$item_y += 15;
				}

				//subtotal
				$taxAmount = $subTotal * 0.15;
				$taxAmount = number_format($taxAmount, 2);
				$grandTotalPrice = $subTotal + $taxAmount;
				$grandTotal = number_format($grandTotalPrice, 2);
				$grandTotal = str_replace(',', '', $grandTotal);
				
				
				$style->setFont($font, 10);
				$page->drawText(__('Subtotal'), 360, ($this->y - $item_y)-10, 'UTF-8');
				$page->drawText($this->pricehelper->currency($subTotal, true, false), 490, ($this->y - $item_y)-10, 'UTF-8');

				$page->drawText(__('VAT(15%)'), 360, ($this->y - $item_y)-30, 'UTF-8');
				$page->drawText($this->pricehelper->currency($taxAmount, true, false), 490, ($this->y - $item_y)-30, 'UTF-8');

				$page->drawText(__('Grand Total(Incl. VAT)'), 360, ($this->y - $item_y)-50, 'UTF-8');
				$page->drawText($this->pricehelper->currency($grandTotal, true, false), 490, ($this->y - $item_y)-50, 'UTF-8');
			}
			/* End Oil Change Items */
			
			/* Start Service Packs Items */
			if(count($serviceproductIds) > 0){
				// iTems
				$this->y -= 250;
				$style->setFont($font, 14);
				$page->drawText(__('SERVICE PACKS'), 30, $this->y - 190, 'UTF-8');
				// Vendor Detail
				$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
				$page->drawRectangle(30, $this->y - 200, $page->getWidth() - 30, $this->y - 220);
				$page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
				$style->setFont($font, 14);
				$page->drawText(__('ITEM'), $x + 5, $this->y - 215, 'UTF-8');
				$page->drawText(__('PRICE'), 360, $this->y - 215, 'UTF-8');
				$page->drawText(__('QTY'), 440, $this->y - 215, 'UTF-8');
			   // $page->drawText(__('UNIT PRICE'), 410, $this->y - 215, 'UTF-8');
				$page->drawText(__('TOTAL'), 500, $this->y - 215, 'UTF-8');

				//ITEM VALUE
				$style->setFont($font, 12);
				$item_y = 245;
				$qty = 1;
				$subTotal = 0;
				foreach($serviceproductIds as $key => $productId){
					$product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($productId);
					$price =  $product->getPrice(); //number_format($finalPricewithtax, 2);
					$optionsPrice = array();
					$customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
					$oilPerLitre = 1;
					if($service_product_oil_litre != ''){
						$oilPerLitre = $service_product_oil_litre;
					}
					foreach ($customOptions as $o) {
						foreach ($o->getValues() as $value) {
							if($value->getTitle() != $oilPerLitre){
								continue;
							}else{
								$optionsPrice[] = $value['price'];
							}
						}
					}
					if(count($optionsPrice) > 0){
						$optionPricevalue =  $optionsPrice[0];
						$totalPrice = $optionPricevalue + $product->getPrice();
						/* $multiplyPrice = $totalPrice * 0.05;
						$customOptionPrice = $totalPrice + $multiplyPrice; */
						$price = number_format($totalPrice, 2);
					}
					
					$itemno      = $key + 1;
					$rowtotal    = $price * (int) $qty;
					$rowtotal    = number_format($rowtotal, 2);
					$rowtotal    = str_replace(',', '', $rowtotal);
					$subTotal+= $price;

					$page->drawText($product->getName(), 33, $this->y - $item_y, 'UTF-8');
					//$page->drawText($cartitemsData->getName(), 80, $this->y - $item_y, 'UTF-8');
					$page->drawText($this->pricehelper->currency($price, true, false), 355, $this->y - $item_y, 'UTF-8');
					$page->drawText($qty, 445, $this->y - $item_y, 'UTF-8');
				   // $page->drawText($this->pricehelper->currency($cartitemsData->getPrice(), true, false), 410, $this->y - $item_y, 'UTF-8');
					$page->drawText($this->pricehelper->currency($rowtotal, true, false), 490, $this->y - $item_y, 'UTF-8');

					$item_y += 15;
				}

				//subtotal
				$discounted = ($subTotal * $discountPercentage / 100);
				$discountAmount = number_format($discounted, 2);
				$taxAmount = $subTotal - $discountAmount;
				$taxAmount = $taxAmount * 0.15;
				$taxAmount = number_format($taxAmount, 2);
				$grandTotalPrice = ($subTotal + $taxAmount) - $discountAmount;
				$grandTotal = number_format($grandTotalPrice, 2);
				$grandTotal = str_replace(',', '', $grandTotal);
				
				$style->setFont($font, 10);
				$page->drawText(__('Subtotal'), 360, ($this->y - $item_y)-10, 'UTF-8');
				$page->drawText($this->pricehelper->currency($subTotal, true, false), 490, ($this->y - $item_y)-10, 'UTF-8');
				
				$page->drawText(__('Discount'), 360, ($this->y - $item_y)-30, 'UTF-8');
				$page->drawText($this->pricehelper->currency($discountAmount, true, false), 490, ($this->y - $item_y)-30, 'UTF-8');
				

				$page->drawText(__('VAT(15%)'), 360, ($this->y - $item_y)-50, 'UTF-8');
				$page->drawText($this->pricehelper->currency($taxAmount, true, false), 490, ($this->y - $item_y)-50, 'UTF-8');

				$page->drawText(__('Grand Total(Incl. VAT)'), 360, ($this->y - $item_y)-70, 'UTF-8');
				$page->drawText($this->pricehelper->currency($grandTotal, true, false), 490, ($this->y - $item_y)-70, 'UTF-8');
			}
			/* End Service Packs Items */

			$fileName = 'Service-Download-'.$model->getAppointmentIncrementId().'.pdf';
			$pdfData  = $pdf->render(); // Get PDF document as a string
			if ($post['submittype'] == 'download') {
				$this->fileFactory->create(
					$fileName,
					$pdf->render(),
					\Magento\Framework\App\Filesystem\DirectoryList::MEDIA, // this pdf will be saved in var directory with the name example.pdf
					'application/octet-stream'
				);

			}
			
			/* Create PDF end*/
			if ($post['submittype'] != 'download') {
				$fileName = 'Service-Email-'.$model->getAppointmentIncrementId().'.pdf';
				$popath   = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'po/' . $fileName;
				file_put_contents($popath, $pdf->render());
				$mediaUrl        = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
				$pdfdownload     = $mediaUrl . 'po/' . $fileName;
				$this->_inlineTranslation->suspend();
				$emailTemplateId = $this->_scopeConfig->getValue(self::NOTIFY_OILSERVICE_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				$receiveemails = array_map('trim', $receiveemails);
				$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
				$templateVars    = array(
					'name'           => $post['name'],
					'lname'          => $post['lastname'],
					'email'          => $post['email'],
					'make'           => $post['make'],
					'model'          => $post['model'],
					'engine'         => $post['engine'],
					'service'        => $post['service'],
					'phonenumber'    => $post['phonenumber'],
					'service_id'   	 => $model->getAppointmentIncrementId(),
					'pdfdownload'    => $pdfdownload,
				);				
				$transport = $this->_transportBuilder->setTemplateIdentifier($emailTemplateId)
							->setTemplateOptions($templateOptions)
							->setTemplateVars($templateVars)
							->setFrom($sender)
							->addTo($post['email'])
							->addAttachment($pdfData, $fileName, 'application/pdf')
							->getTransport();

				$transport->sendMessage();
				$this->_inlineTranslation->resume();
				$this->messageManager->addSuccess('Email sent successfully');
			}	
			
        } catch (\Exception $e) {   
            $this->messageManager->addError($e->getMessage());
            $this->_logLoggerInterface->debug($e->getMessage());
            // exit;
        }
		
		if($serviceType == 1){
			$this->_redirect('oil-change');
		}else{
			$this->_redirect('servicepacks');
		}
        return;
    }
}