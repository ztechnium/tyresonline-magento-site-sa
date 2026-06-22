<?php

namespace Hdweb\Purchaseorder\Controller\Adminhtml\Create;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class Editsave extends \Magento\Backend\App\Action
{

    protected $resultPagee;
    protected $purchaseorder;
    protected $purchaseorderitem;
    protected $povendor;
    protected $order;
    protected $scopeConfig;
    protected $productRepository;
    protected $pricehelper;
    protected $_filesystem;
    protected $fileFactory;
    protected $authSession;
    protected $orderInterfaceFactory;
    protected $orderItemFactory;
    protected $pohelper;
    private $objectManager;
    protected $addressConfig;
    protected $ecomtechStoreLocator;
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $storeManager;
    protected $countryModel;

    public function __construct(
        Context $context, PageFactory $resultPageFactory,
        \Hdweb\Purchaseorder\Model\PurchaseorderFactory $purchaseorder,
        \Hdweb\Purchaseorder\Model\PurchaseorderitemFactory $purchaseorderitem,
        \Hdweb\Purchaseorder\Model\Povendor $povendor,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Pricing\Helper\Data $pricehelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderInterfaceFactory,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        Session $authSession,
        \Hdweb\Purchaseorder\Helper\Data $pohelper,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Ecomteck\StoreLocator\Model\Stores $ecomtechStoreLocator,
        \Hdweb\Core\Model\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Country $countryModel

    ) {
        parent::__construct($context);
        $this->resultPageFactory     = $resultPageFactory;
        $this->purchaseorder         = $purchaseorder;
        $this->purchaseorderitem     = $purchaseorderitem;
        $this->povendor              = $povendor;
        $this->order                 = $order;
        $this->scopeConfig           = $scopeConfig;
        $this->productRepository     = $productRepository;
        $this->pricehelper           = $pricehelper;
        $this->_filesystem           = $filesystem;
        $this->fileFactory           = $fileFactory;
        $this->authSession           = $authSession;
        $this->orderInterfaceFactory = $orderInterfaceFactory;
        $this->orderItemFactory      = $orderItemFactory;
        $this->pohelper              = $pohelper;
        $this->objectManager = $objectmanager;
        $this->addressConfig = $addressConfig;
        $this->ecomtechStoreLocator = $ecomtechStoreLocator;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->countryModel = $countryModel;
    }

    public function execute()
    {
        $data           = $this->_request->getParams();
        $submit_param   = $data['submit'];
        if (isset($submit_param) && $submit_param != 'delete') {
            if ($data['po_type'] == 'mpo') {
                $notAllowedSkus = [];
                $redirectBack = false;
                foreach ($data['item'] as $key => $poProductData) {
                    if ($poProductData['qty'] > $poProductData['allowedqty']) {
                        $order_incrementid = $data['orderreference_no'];
                        $order = $this->order->loadByIncrementId($order_incrementid);
                        $notAllowedSkus[] = $poProductData['sku'];
                        $redirectBack = true;       
                    }
                }
                if ($redirectBack) {
                    $notAllowedSkusString = implode(', ', $notAllowedSkus);
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $params = array('po_id' => $data['poid']);
                    $this->messageManager->addError(__('You are entering more than allowed qty for SKU - '.$notAllowedSkusString));
                    return $resultRedirect->setPath('purchaseorder/create/edit', $params);
                }
            }

            if ($data['po_type'] == 'fpo') {
                $notAllowedSkus = [];
                $redirectBack = false;
                foreach ($data['item'] as $key => $poProductData) {
                    if(isset($poProductData['qty']) && isset($poProductData['allowedqty'])){
                        if ($poProductData['qty'] > $poProductData['allowedqty']) {
                            $order_incrementid = $data['orderreference_no'];
                            $order = $this->order->loadByIncrementId($order_incrementid);
                            $notAllowedSkus[] = $poProductData['sku'];
                            $redirectBack = true;       
                        }
                    }
                }
                if ($redirectBack) {
                    $notAllowedSkusString = implode(', ', $notAllowedSkus);
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $params = array('po_id' => $data['poid']);
                    $this->messageManager->addError(__('You are entering more than allowed qty for SKU - '.$notAllowedSkusString));
                    return $resultRedirect->setPath('purchaseorder/create/edit', $params);
                }
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $submit_param   = $data['submit'];
        if (isset($submit_param) && $submit_param == 'pdf') {           
		$sendEmailpdf = $this->sendmailpdf();
            $this->messageManager->addSuccess(__('Purchase order email has been sent succesfully'));
            $params = array('po_id' => $data['poid']);
            return $resultRedirect->setPath('purchaseorder/create/edit', $params);
        } else if (isset($submit_param) && $submit_param == 'download') {
            $sendEmailpdf = $this->sendmailpdf();
            $this->messageManager->addSuccess(__('Purchasee order has been downloaded'));
            return $resultRedirect->setPath('*/*/grid');
        } else if (isset($submit_param) && $submit_param == 'delete') {
            if (isset($data['poid'])) {
                //
                $purchaseorder_model = $this->purchaseorder->create();
                $purchaseorder_model->load($data['poid'], 'id');
                $purchaseorder_model->delete();
                $ispurchaseorderdone = 0;
                if (isset($data['item']) && count($data['item']) > 0) {
                    $model                       = $this->purchaseorderitem->create();
                    $purchaseorderitemcollection = $this->purchaseorderitem->create()->getCollection()->addFieldToFilter('poid', $data['poid']);

                    foreach ($purchaseorderitemcollection as $modeldata) {
                        $ispurchaseorderdone = 1;
                        $model->load($modeldata['id'], 'id');
                        $model->delete();
                    }
                }

                if ($ispurchaseorderdone) {
                    $this->pohelper->savePoGrandTotal($data['orderreference_no']);
                }
                $this->messageManager->addSuccess(__('Your purchase order has been deleted succesfully'));
                return $resultRedirect->setPath('*/*/grid');
            }
        } else {

            if (isset($data['item']) && isset($data['poid'])) {

                if (isset($data['grandtotal']) && count($data['item']) > 0 && $data['grandtotal'] > 0) {
                    $itemtemplate        = "";
                    $ispurchaseorderdone = 0;

                    $purchaseorder_model = $this->purchaseorder->create();
                    $purchaseorder_model->load($data['poid'], 'id');
                    $purchaseorder_model->setPoreferenceNo($data['poreference_no']);
                    $purchaseorder_model->setOrderreferenceNo($data['orderreference_no']);
                    $vendorId = $data['vendor'];
                    $purchaseorder_model->setVendor($vendorId);
                    
                    $vendorCollection = $this->povendor->getCollection();
                    $vendorCollection->addFieldToFilter('id', array('eq' => $vendorId));
                    $vendorName = '';
                    if ($vendorCollection->getSize()) {
                        $vendorData = $vendorCollection->getFirstItem();
                        $vendorName = $vendorData->getName();
                    }
                    
                    $purchaseorder_model->setVendorName($vendorName);
                    $purchaseorder_model->setSubtotal($data['subtotal']);
                    $purchaseorder_model->setVat($data['vat']);
                    $purchaseorder_model->setGrandtotal($data['grandtotal']);
                    $purchaseorder_model->setComment($data['comment']);
                    $purchaseorder_model->setUpdateBy($this->authSession->getUser()->getId());
                    $purchaseorder_model->save();
                    $last_po_id = $purchaseorder_model->getId();

                    if (count($data['item']) > 0) {

                        $model = $this->purchaseorderitem->create();

                        $purchaseorderitemcollection = $this->purchaseorderitem->create()->getCollection()->addFieldToFilter('poid', $data['poid']);

                        foreach ($purchaseorderitemcollection as $modeldata) {

                            $model->load($modeldata['id'], 'id');
                            $model->delete();
                        }

                        foreach ($data['item'] as $key => $value) {

                            $CreatedAt    = date('Y-m-d h:i:s', time());
                            $product_objs = $this->productRepository->get($value['sku']);

                            $ispurchaseorderdone     = 1;
                            $purchaseorderitem_model = $this->purchaseorderitem->create();
                            $purchaseorderitem_model->setPoid($last_po_id);
                            $purchaseorderitem_model->setPoreferenceNo($data['poreference_no']);
                            $purchaseorderitem_model->setPoType($data['po_type']);
                            $purchaseorderitem_model->setSku($value['sku']);
                            $purchaseorderitem_model->setPrice($value['price']);
                            $purchaseorderitem_model->setQty($value['qty']);

                            $purchaseorderitem_model->setVendorId($vendorId);
                            $purchaseorderitem_model->setVendorName($vendorName);
                            $purchaseorderitem_model->setOrderId($data['orderreference_no']);
                            $purchaseorderitem_model->setCreatedAt($CreatedAt);
                            $purchaseorderitem_model->setTyreDescription($product_objs->getName());

                            $rowtotal = trim($value['price']) * (int) $value['qty'];
                            $rowtotal = number_format($rowtotal, 2);
                            $rowtotal = str_replace(',', '', $rowtotal);

                            $purchaseorderitem_model->setRowtotal($rowtotal);
                            $purchaseorderitem_model->save();
                            $purchaseorderitem_model->unsetData();

                            //for email template

                            $orderref   = $this->orderInterfaceFactory->create()->loadByIncrementId($data['orderreference_no']);
                            $orderitems = $this->orderItemFactory->create()->getCollection()->addFieldToFilter('order_id', array('eq' => $orderref->getEntityId()))->addFieldToFilter('sku', array('eq' => $value['sku']))->getFirstItem();

                            $itemtemplate .= '<tr>
                                                       <td colspan="3"><span style="padding-top:5px;font-weight:500;">
                                                       <br>' . $orderitems->getShortDescription() . '</span><br>
                                                          SKU: ' . $value['sku'] . ' </span>
                                                       </td>
                                                            <td style="text-align:center;font-size:14px;">' . $this->pricehelper->currency($value['price'], true, false) . '</td>
                                                            <td style="text-align:center;font-size:14px;">' . $value['qty'] . '</td>
                                                            <td style="text-align:center;font-size:14px;">
                                                                <span class="price">' . $this->pricehelper->currency($rowtotal, true, false) . '</span>
                                                            </td>
                                                  </tr>';
                        }
                    }

                    if ($ispurchaseorderdone) {
                        $this->pohelper->savePoGrandTotal($data['orderreference_no']);
                    }

                    /*email done */
                    $this->messageManager->addSuccess(__('Your purchase order has been edited successfully'));
                    
                    //return $resultRedirect->setPath('*/*/grid');
                    $params = array('po_id' => $data['poid']);
                    return $resultRedirect->setPath('purchaseorder/create/edit', $params);

                } else {
                    $this->messageManager->addError(__('Faild to create purchase order.'));
                    
                    return $resultRedirect->setPath('*/*/grid');
                }

            } else {
                /*email done */
                $this->messageManager->addError(__('No any product item found.'));
                
                return $resultRedirect->setPath('*/*/grid');
            }
        }
    }

    public function sendmailpdf()
    {

        /* Send email*/
        $data           = $this->_request->getParams();
        $resultRedirect = $this->resultRedirectFactory->create();
        $itemtemplate   = "";
        $model          = $this->purchaseorderitem->create();

        $purchaseorderitemcollection = $this->purchaseorderitem->create()->getCollection()->addFieldToFilter('poid', $data['poid']);
		$pdfItemTemplate   = "";
        foreach ($data['item'] as $key => $value) {
			$itemno      = $key + 1;
            $rowtotal = trim($value['price']) * (int) $value['qty'];
            $rowtotal = number_format($rowtotal, 2);
            $rowtotal = str_replace(',', '', $rowtotal);

            //for email template
            $product_obj = $this->productRepository->get($value['sku']);
            
            $itemtemplate .= '<tr>
                                                       <td colspan="3"><span style="padding-top:5px;font-weight:500;">
                                                       <br>' . $product_obj->getName() . '</span><br>
                                                          SKU: ' . $value['sku'] . ' </span>
                                                       </td>
                                                            <td style="text-align:center;font-size:14px;">' . $this->pricehelper->currency($value['price'], true, false) . '</td>
                                                            <td style="text-align:center;font-size:14px;">' . $value['qty'] . '</td>
                                                            <td style="text-align:center;font-size:14px;">
                                                                <span class="price">' . $this->pricehelper->currency($rowtotal, true, false) . '</span>
                                                            </td>
                                                  </tr>';
			$pdfItemTemplate .= '
			<tr>
				<td width="10%" style="font-size: 9px; line-height: 1.4; text-align: center">'.$itemno.'</td>
				<td width="40%" style="font-size: 9px; line-height: 1.4; text-align: center">'.$product_obj->getName().'<br />'.$value['sku'].'</td>
				<td width="10%" style="font-size: 9px; line-height: 1.4; text-align: center">'.$value['qty'].'</td>
				<td width="20%" style="font-size: 9px; line-height: 1.4; text-align: center">'.$this->pricehelper->currency($value['price'], true, false).'</td>
				<td width="20%" style="font-size: 9px; line-height: 1.4; text-align: center">'.$this->pricehelper->currency($rowtotal, true, false).'</td>
			</tr>';									  
        }
        /* Send email*/

        $vendorid   = $data['vendor'];
        $collection = $this->povendor->getCollection();
        $collection->addFieldToFilter('id', array('eq' => $vendorid));

        if ($collection->getSize()) {
            $vendor_data = $collection->getFirstItem();
            $vendor_name          = $vendor_data->getName();
            $vendor_contactperson = $vendor_data->getContactPerson();
            $vendor_email         = $vendor_data->getEmail();
            $vendor_copy_email    = $vendor_data->getEmailCopy();
            $vendor_phone         = $vendor_data->getPhone();
            $vendor_address       = $vendor_data->getAddress();
            $vendor_city          = $vendor_data->getCity();

            //$bill_to = $vendor_name . "<br>" . $vendor_address . "<br>" . $vendor_contactperson . "<br>Tel: " . $vendor_phone . "<br>Email: " . $vendor_email;

            $bill_to_name = $vendor_name;
            $billTelephone = $vendor_phone;
            $billEmail = $vendor_email;
            $billAddress = $vendor_address;
            $billContactPerson = $vendor_contactperson;

            $ordercomment = $data['comment'];

            $poreference_no = $data['poreference_no'];

            $order_incrementid = $data['orderreference_no'];
            $order = $this->order->loadByIncrementId($order_incrementid);
            
            $installer_id = $order->getPickupStore();   
			
				

            if($installer_id != 0){
                $installer_id = $installer_id;
            }else{
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            }
            $installerobj = $this->ecomtechStoreLocator->load($installer_id);

            $shipingAddress     = $order->getShippingAddress();
            $renderer           = $this->addressConfig->getFormatByCode('html')->getRenderer();
            $installer_country          = $this->countryModel->load($installerobj['country'])->getName();


            $installer_section = "<p>" . $installerobj['name'] . "<br>" . $installerobj['address'] ."<br>".$installerobj['city']. "<br>" . $installer_country . "<br> Phone: " . $installerobj['phone'] . "<br> Email: " . $installerobj['email'] . "<br> <a href='" . $installerobj->getExternalLink() . "'>Location Map</a> </p>";

            $installer_section_ar = "<p>" . $installerobj['name_rtl'] . "<br>" . $installerobj['address_rtl'] ."<br>".$installerobj['city_rtl']. "<br>السعودية<br> هاتف: " . $installerobj['phone'] . "<br> بريد الالكتروني: " . $installerobj['email'] . "<br> <a href='" . $installerobj->getExternalLink() . "'>موقع التركيب</a> </p>";
            
            $podate           = date("d/m/Y");
            $itemtable = '<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <thead class="thead-dark" style="background-color:#D80000;border-bottom-color:#D80000;color:white;">
                                          <tr>
                                                <th scope="col" colspan="3" style="font-weight: 500;text-align: left;font-size: 12px;padding:3px 9px;">ITEM</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">PRICE</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">QTY</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">TOTAL</th>
                                              </tr>
                                        </thead>
                                       <tbody>' . $itemtemplate . '</tbody>
                                        <tfoot class="order-totals">
                                                <tr class="subtotal" style="text-align: right;background: #fff;">
                                                    <td colspan="5" scope="row" style="background: #fff !important;">
                                                                    Sub Total
                                                    </td>
                                                    <td data-td="Sub Total" style="text-align: right;background: #fff !important;">
                                                             <span class="price">' . $this->pricehelper->currency($data['subtotal'], true, false) . '</span>
                                                    </td>
                                                </tr>

                                                <tr class="totals-tax">
                                                    <td colspan="5" scope="row" style="background: #fff !important;text-align:right;">
                                                                    VAT(15%)            </td>
                                                    <td data-th="VAT(15%)" style="background: #fff !important;text-align:right;">
                                                        <span class="price">' . $this->pricehelper->currency($data['vat'], true, false) . '</span>    </td>
                                                </tr>


                                              <tr class="grand_total" style="text-align: right;background: #fff;">
                                                     <td colspan="5" scope="row" style="background: #fff !important;">
                                                                Grand Total(Incl. VAT)
                                                     </td>
                                                    <td data-td="Grand Total(Incl. VAT)" style="text-align: right;background: #fff !important;">
                                                                <span class="price">' . $this->pricehelper->currency($data['grandtotal'], true, false) . '</span>
                                                     </td>
                                              </tr>
                                        </tfoot>
                            </table>';
                $itemtable_ar = '<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <thead class="thead-dark" style="background-color:#D80000;border-bottom-color:#D80000;color:white;">
                                          <tr>
                                                <th scope="col" colspan="3" style="font-weight: 500;text-align: right;font-size: 12px;padding:3px 9px;">السلعة</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">السعر</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">الكمية</th>
                                                <th scope="col" style="text-align:center;font-weight: 500;font-size: 12px;padding:3px 9px;">الإجمالي</th>
                                              </tr>
                                        </thead>
                                       <tbody>' . $itemtemplate . '</tbody>
                                        <tfoot class="order-totals">
                                                <tr class="subtotal" style="text-align: right;background: #fff;">
                                                    <td colspan="5" scope="row" style="background: #fff !important;">
                                                                    Sub Total
                                                    </td>
                                                    <td data-td="Sub Total" style="text-align: right;background: #fff !important;">
                                                             <span class="price">' . $this->pricehelper->currency($data['subtotal'], true, false) . '</span>
                                                    </td>
                                                </tr>

                                                <tr class="totals-tax">
                                                    <td colspan="5" scope="row" style="background: #fff !important;text-align:right;">
                                                                    VAT(15%)            </td>
                                                    <td data-th="VAT(15%)" style="background: #fff !important;text-align:right;">
                                                        <span class="price">' . $this->pricehelper->currency($data['vat'], true, false) . '</span>    </td>
                                                </tr>


                                              <tr class="grand_total" style="text-align: right;background: #fff;">
                                                     <td colspan="5" scope="row" style="background: #fff !important;">
                                                                Grand Total(Incl. VAT)
                                                     </td>
                                                    <td data-td="Grand Total(Incl. VAT)" style="text-align: right;background: #fff !important;">
                                                                <span class="price">' . $this->pricehelper->currency($data['grandtotal'], true, false) . '</span>
                                                     </td>
                                              </tr>
                                        </tfoot>
                            </table>';

                /* Create PDF */
				
				$englishStoreId = 1;
				$arabicStoreId = 2;
				$this->storeManager->setCurrentStore($arabicStoreId);
				
                $pdflogoName            = $this->scopeConfig->getValue('purchaseorder/general/po_logo_name', ScopeInterface::SCOPE_STORE);
                $pdfstoreName           = $this->scopeConfig->getValue('purchaseorder/general/po_store_name', ScopeInterface::SCOPE_STORE);
                $pdfStoreaddressStreet1 = $this->scopeConfig->getValue('purchaseorder/general/po_store_address_street1', ScopeInterface::SCOPE_STORE);
                $pdfStoreaddressStreet2 = $this->scopeConfig->getValue('purchaseorder/general/po_store_address_street2', ScopeInterface::SCOPE_STORE);
                $pdfTrnno               = $this->scopeConfig->getValue('purchaseorder/general/po_trn_no', ScopeInterface::SCOPE_STORE);
                $pdfPhoneno             = $this->scopeConfig->getValue('purchaseorder/general/po_phone_no', ScopeInterface::SCOPE_STORE);
                $pdfWebsiteName         = $this->scopeConfig->getValue('purchaseorder/general/po_website', ScopeInterface::SCOPE_STORE);
                $pdfContactPerson       = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person', ScopeInterface::SCOPE_STORE);
                $pdfContactPersonPhone  = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person_phone_no', ScopeInterface::SCOPE_STORE);
                $pdfContactPersonEmail  = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person_email', ScopeInterface::SCOPE_STORE);
                $pdfFileName            = $this->scopeConfig->getValue('purchaseorder/general/po_file_name', ScopeInterface::SCOPE_STORE);
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

                $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'logo.png';
                if ($pdflogoName != '') {
                    $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'email-signature/'.$pdflogoName;
                }

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

                /* $image = "";
                if ($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath)) {
                    $image = \Zend_Pdf_Image::imageWithPath($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath));
                } */
				
				/* Start Zend PDF */ 

                /* $y1 = 800;
                $y2 = 830;
                $x1 = 400;
                $x2 = 530;

                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $page->drawRectangle(30, $this->y - 20, $page->getWidth() - 30, $this->y + 110, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                $style->setFont($font, 10);
                $page->setStyle($style);
                $page->drawText(__($storeName), $x + 5, $this->y + 90, 'UTF-8');
                $style->setFont($font, 10);
                $page->setStyle($style);
                $page->drawText(__($storeAddress1), $x + 5, $this->y + 75, 'UTF-8');
                $page->drawText(__($storeAddress2), $x + 5, $this->y + 60, 'UTF-8');
                $page->drawText(__("TRN: " . $trnNo), $x + 5, $this->y + 45, 'UTF-8');
                $page->drawText(__("Phone: " . $phoneNo), $x + 5, $this->y + 30, 'UTF-8');
                $page->drawText(__("Website: " . $website), $x + 5, $this->y + 15, 'UTF-8');

                $page->drawText(__("PURCHASE ORDER"), $x + 350, $this->y + 90, 'UTF-8');
                $page->drawText(__("DATE"), $x + 350, $this->y + 75, 'UTF-8');
                $page->drawText(__("PO/ORDER #"), $x + 350, $this->y + 60, 'UTF-8');

                //Po value
                $page->drawText(date("d/m/Y"), $x + 450, $this->y + 75, 'UTF-8');
                $page->drawText($data['orderreference_no'], $x + 450, $this->y + 60, 'UTF-8');
                //$page->drawText($data['orderreference_no'], $x + 430, $this->y+10, 'UTF-8');

                // Vendor Detail
                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 30);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);
                $page->drawText(__('VENDOR'), $x + 5, $this->y - 18, 'UTF-8');
                $page->drawText(__('SHIP TO'), 300, $this->y - 18, 'UTF-8');

                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 140, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);

                $page->drawText($vendor_name, $x + 5, $this->y - 50, 'UTF-8');
                $page->drawText($vendor_address, $x + 5, $this->y - 70, 'UTF-8');
                $page->drawText('Phone: ' . $vendor_phone, $x + 5, $this->y - 90, 'UTF-8');
                $page->drawText('Email: ' . $vendor_email, $x + 5, $this->y - 110, 'UTF-8');
                
                $page->drawText($installerobj['name'], 300, $this->y - 50, 'UTF-8');
                $page->drawText($installerobj['address'], 300, $this->y - 70, 'UTF-8');              
                $page->drawText($installerobj['city'], 300, $this->y - 90, 'UTF-8');                
                $page->drawText('Phone: ' . $installerobj['phone'], 300, $this->y - 110, 'UTF-8');
                $page->drawText('Email: ' . $installerobj['email'], 300, $this->y - 130, 'UTF-8');                 

                $page->drawText(__('Comments: '), $x + 5, $this->y - 170, 'UTF-8');
                $page->drawText($data['comment'], 100, $this->y - 170, 'UTF-8');

                // iTems

                // Vendor Detail
                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->drawRectangle(30, $this->y - 200, $page->getWidth() - 30, $this->y - 220);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);
                $page->drawText(__('ITEM'), $x + 5, $this->y - 215, 'UTF-8');
                $page->drawText(__('DESCRIPTION'), 130, $this->y - 215, 'UTF-8');
                $page->drawText(__('QTY'), 360, $this->y - 215, 'UTF-8');
                $page->drawText(__('UNIT PRICE'), 410, $this->y - 215, 'UTF-8');
                $page->drawText(__('TOTAL'), 500, $this->y - 215, 'UTF-8');

                //ITEM VALUE
                $style->setFont($font, 10);
                $item_y = 245;
                foreach ($data['item'] as $key => $value) {
                    $itemno      = $key + 1;
                    $product_obj = $this->productRepository->get($value['sku']);
                    $rowtotal    = trim($value['price']) * (int) $value['qty'];
                    $rowtotal    = number_format($rowtotal, 2);
                    $rowtotal    = str_replace(',', '', $rowtotal);

                    $page->drawText($itemno, 40, $this->y - $item_y, 'UTF-8');
                    $page->drawText($product_obj->getName(), 80, $this->y - $item_y, 'UTF-8');
                    $page->drawText($product_obj->getSku(), 80, $this->y - $item_y-15, 'UTF-8');                    
                    $page->drawText($value['qty'], 360, $this->y - $item_y, 'UTF-8');
                    $page->drawText($this->pricehelper->currency($value['price'], true, false), 410, $this->y - $item_y, 'UTF-8');
                    $page->drawText($this->pricehelper->currency($rowtotal, true, false), 490, $this->y - $item_y, 'UTF-8');

                    $item_y += 30;
                }

                //subtotal

                $page->drawText(__('SUB TOTAL'), 400, $this->y - 350, 'UTF-8');
                $page->drawText($this->pricehelper->currency($data['subtotal'], true, false), 490, $this->y - 350, 'UTF-8');

                $page->drawText(__('TAX'), 400, $this->y - 380, 'UTF-8');
                $page->drawText($this->pricehelper->currency($data['vat'], true, false), 490, $this->y - 380, 'UTF-8');

                $page->drawText(__('GRAND TOTAL'), 400, $this->y - 410, 'UTF-8');
                $page->drawText($this->pricehelper->currency($data['grandtotal'], true, false), 490, $this->y - 410, 'UTF-8');

                // commment and instruction

                $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 140, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);

                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->drawRectangle(30, $this->y - 420, $page->getWidth() - 30, $this->y - 440);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);
                $page->drawText(__('COMMMENT OR SPECIAL INSTRUCTIONS'), 40, $this->y - 435, 'UTF-8');

                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->drawRectangle(30, $this->y - 440, $page->getWidth() - 30, $this->y - 510, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                $style->setFont($font, 10);

                $page->drawText(__('1. Please mention this Order number in your invoices for this order.'), 40, $this->y - 460, 'UTF-8');

                $page->drawText(__('2. Please notify us immediately if you are unable to ship as specified.'), 40, $this->y - 475, 'UTF-8');

                $page->drawText(__('3. The tyre/s to be supplied under this purchase order must comply with UAE law and with standards
                '), 40, $this->y - 490, 'UTF-8');

                $page->drawText(__('approved as per Gulf Technical Regulations by GSO.'), 50, $this->y - 505, 'UTF-8');

                // footer

                $page->drawText(__('If you have any questions about this purchase order, please contact'), 150, $this->y - 540, 'UTF-8');
                $page->drawText(__('[' . $contactPerson . ', ' . $contactPersonPhone . ', Email: ' . $contactPersonEmail . ']'), 150, $this->y - 560, 'UTF-8');

                $fileName = $pdfFile . $data['orderreference_no'] . '.pdf';
                $pdfData  = $pdf->render(); // Get PDF document as a string 
				
				*/
				/* End Zend PDF */ 
				
				
				
				// create new PDF document
				$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

				// set document information
				$pdf->SetCreator(PDF_CREATOR);
				$pdf->SetAuthor('TyresOnline');
				$pdf->setTitle('Tyresonline Purchase Order');
				$pdf->setSubject('Purchase Order');
				$pdf->setKeywords('Purchase, Order, Tyresonline');

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
				
				/* Start arabic version */
				
				// add a page
				$pdf->AddPage();
				// set some text to print
				$html = '<table cellspacing="0" cellpadding="2" border="0" width="100%" style="border-bottom: 1px solid #d70000;">
                <tr>
                <td width="50%" style="text-align: right">
                    <img src="'.$imagePath.'" width="180px" border="0">
                </td>
                <td width="50%" style="font-size: 8px; line-height: 1.5;">'.$storeName.'<br />'.$storeAddress1.'<br />'.$storeAddress2.'<br /> رقم الضريبة : '.$trnNo.'<br />هاتف: <span dir="ltr" lang="en">'.$phoneNo.'</span><br /> موقع الكتروني: <span dir="ltr" lang="en">'.$website.'</span><br /></td></tr>
                </table>
                <table cellspacing="0" cellpadding="2" border="0" width="100%">
					<tr><td height="20"></td></tr>
					<tr>
					<td style="font-size: 9px; line-height: 1.7;">أمر الشراء<br />تاريخ: '.date("d/m/Y").' <br />أمر الشراء / الأمر # : <span dir="ltr" lang="en">'.$data['orderreference_no'].'</span><br /></td>
					</tr>
				</table>
				<br />
				<table cellspacing="0" cellpadding="2" border="0" width="100%">
					<tr><td height="25"></td></tr>
					<tr>
					<td width="50%" style="font-size: 10px; font-weight: bold;">بائع</td>
					<td width="50%" style="font-size: 10px; font-weight: bold;">شحن إلى</td>
					</tr>
					<tr>
					<td width="50%" style="font-size: 9px; line-height: 1.7;">'.$vendor_name.'<br />'.$vendor_address.'<br />هاتف: <span dir="ltr" lang="en">'.$vendor_phone.'</span><br />بريد الالكتروني: <span dir="ltr" lang="en">'.$vendor_email.'</span><br /></td>
					<td width="50%" style="font-size: 9px; line-height: 1.7;">'.$installerobj['name_rtl'].'<br />'.$installerobj['address_rtl'].'<br />'.$installerobj['city_rtl'].'<br />هاتف: <span dir="ltr" lang="en">'.$installerobj['phone'].'</span><br />بريد الالكتروني: <span dir="ltr" lang="en">'.$installerobj['email'].'</span></td>
					</tr>
				</table>
				<table cellspacing="0" border="0" width="100%">
					<tr><td height="20"></td></tr>
					<tr>
						<td style="font-size: 9px;">التعليقات: '.$data['comment'].'</td>
					</tr>
				</table>
				<table cellspacing="0" cellpadding="2" border="0" width="100%">
				   <tr><td height="30"></td></tr>
				</table>

				<table cellspacing="0" cellpadding="4" border="1" width="100%">
					<tr>
						<th width="10%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center" >بند</th>
						<th width="40%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">وصف</th>
						<th width="10%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">الكمية</th>
						<th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">سعر الوحدة</th>
						<th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">إجمالي</th>
					</tr>
					'.$pdfItemTemplate.'
                        <tr> 
       <td width="60%" colspan="2" rowspan="3"></td>
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">لمجموع الفرعي </td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">'.$this->pricehelper->currency($data['subtotal'], true, false).'</td> 
    </tr>
    <tr> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">الضريبة</td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">'.$this->pricehelper->currency($data['vat'], true, false).'</td> 
    </tr>
    <tr> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; font-weight: bold; text-align: center">المجموع الإجمالي</td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; font-weight: bold; text-align: center">'.$this->pricehelper->currency($data['grandtotal'], true, false).'</td> 
    </tr>
				</table>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
   <tr><td height="30"></td></tr>
</table>
				<table cellspacing="0" cellpadding="4" border="0" width="100%">
					<tr>
						<td style="font-size: 10px; font-weight: bold;">تعليق أو تعليمات خاصة</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;">1. يرجى ذكر رقم الطلب هذا في فواتيرك لهذا الطلب</td>
					</tr>
					 <tr>
						<td style="font-size: 9px; line-height: 1.7;">2. يرجى إعلامنا على الفور إذا كنت غير قادر على الشحن كما هو محدد</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;">3. يجب أن يتوافق الإطار / الإطارات التي سيتم توريدها بموجب أمر الشراء هذا مع قانون المملكة العربية السعودية والمعايير المعتمدة وفقًا للوائح الفنية الخليجية من قبل هيئة التقييس الخليجية.</td>
					</tr>
				</table>

				<table cellspacing="0" cellpadding="2" border="0" width="100%">
				   <tr><td height="30"></td></tr>
				   <tr>
						<td style="font-size: 9px; line-height: 1.7;text-align: center">إذا كان لديك أي أسئلة حول طلب الشراء هذا ، يرجى الاتصال</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;text-align: center">['.$contactPerson.', '.$contactPersonPhone.', البريد الإلكتروني  '.$contactPersonEmail.']</td>
					</tr>
				</table>
				';
				// output the HTML content
				$pdf->writeHTML($html, true, false, true, false, '');
				
				/* End arabic version */
				
				/* Start english version */
				
				$this->storeManager->setCurrentStore($englishStoreId);
				
				$pdflogoName            = $this->scopeConfig->getValue('purchaseorder/general/po_logo_name', ScopeInterface::SCOPE_STORE);
                $pdfstoreName           = $this->scopeConfig->getValue('purchaseorder/general/po_store_name', ScopeInterface::SCOPE_STORE);
                $pdfStoreaddressStreet1 = $this->scopeConfig->getValue('purchaseorder/general/po_store_address_street1', ScopeInterface::SCOPE_STORE);
                $pdfStoreaddressStreet2 = $this->scopeConfig->getValue('purchaseorder/general/po_store_address_street2', ScopeInterface::SCOPE_STORE);
                $pdfTrnno               = $this->scopeConfig->getValue('purchaseorder/general/po_trn_no', ScopeInterface::SCOPE_STORE);
                $pdfPhoneno             = $this->scopeConfig->getValue('purchaseorder/general/po_phone_no', ScopeInterface::SCOPE_STORE);
                $pdfWebsiteName         = $this->scopeConfig->getValue('purchaseorder/general/po_website', ScopeInterface::SCOPE_STORE);
                $pdfContactPerson       = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person', ScopeInterface::SCOPE_STORE);
                $pdfContactPersonPhone  = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person_phone_no', ScopeInterface::SCOPE_STORE);
                $pdfContactPersonEmail  = $this->scopeConfig->getValue('purchaseorder/general/po_contact_person_email', ScopeInterface::SCOPE_STORE);
                $pdfFileName            = $this->scopeConfig->getValue('purchaseorder/general/po_file_name', ScopeInterface::SCOPE_STORE);
				
				$imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'logo.png';
                if ($pdflogoName != '') {
                    $imagePath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'email-signature/'.$pdflogoName;
                }
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

                /* $image = "";
                if ($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath)) {
                    $image = \Zend_Pdf_Image::imageWithPath($this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($imagePath));
                } */
				
				$pdf->AddPage();
				$pdf->setRTL(false);
				// set some text to print
				$html = '
                <table cellspacing="0" cellpadding="2" border="0" width="100%" style="border-bottom: 1px solid #d70000;">
                <tr>
                <td width="50%" style="text-align: left">
                <img src="'.$imagePath.'" width="300px" border="0">
                </td>
                <td width="50%" style="font-size: 8px; line-height: 1.5; text-align: right">'.$storeName.'<br />'.$storeAddress1.'<br />'.$storeAddress2.'<br /> VAT: '.$trnNo.'<br />Phone: '.$phoneNo.'<br /> Website: '.$website.'<br /></td></tr>
                </table>
                <table cellspacing="0" cellpadding="2" border="0" width="100%">
					<tr><td height="20"></td></tr>
					<tr>
					<td style="font-size: 9px; line-height: 1.7;">PURCHASE ORDER<br />DATE: '.date("d/m/Y").' <br />PO/ORDER # : '.$data['orderreference_no'].'<br /></td>
					</tr>
				</table>
				<br />
				<table cellspacing="0" cellpadding="2" border="0" width="100%">
					<tr><td height="25"></td></tr>
					<tr>
					<td width="50%" style="font-size: 10px; font-weight: bold;">VENDOR</td>
					<td width="50%" style="font-size: 10px; font-weight: bold;">SHIP TO</td>
					</tr>
					<tr>
					<td width="50%" style="font-size: 9px; line-height: 1.7;">'.$vendor_name.'<br />'.$vendor_address.'<br />Phone: '.$vendor_phone.'<br />Email: '.$vendor_email.'<br /></td>
					<td width="50%" style="font-size: 9px; line-height: 1.7;">'.$installerobj['name'].'<br />'.$installerobj['address'].'<br />'.$installerobj['city'].'<br />Phone: '.$installerobj['phone'].'<br />Email: '.$installerobj['email'].'</td>
					</tr>
				</table>
				<table cellspacing="0" border="0" width="100%">
					<tr><td height="20"></td></tr>
					<tr>
						<td style="font-size: 9px;">Comments: '.$data['comment'].'</td>
					</tr>
				</table>
				<table cellspacing="0" cellpadding="2" border="0" width="100%">
				   <tr><td height="30"></td></tr>
				</table>

				<table cellspacing="0" cellpadding="4" border="1" width="100%">
					<tr>
						<th width="10%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center" >ITEM</th>
						<th width="40%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">DESCRIPTION</th>
						<th width="10%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">QTY</th>
						<th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">UNIT PRICE</th>
						<th width="20%" style="font-size: 9px; font-weight: bold; background-color: #d70000; color: #fff; text-align: center">TOTAL</th>
					</tr>
					'.$pdfItemTemplate.'
                        <tr> 
       <td width="60%" colspan="2" rowspan="3"></td>
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">SUBTOTAL</td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">'.$this->pricehelper->currency($data['subtotal'], true, false).'</td> 
    </tr>
    <tr> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">VAT 15%</td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; text-align: center">'.$this->pricehelper->currency($data['vat'], true, false).'</td> 
    </tr>
    <tr> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; font-weight: bold; text-align: center">GRAND TOTAL</td> 
       <td width="20%" style="font-size: 9px; line-height: 1.7; font-weight: bold; text-align: center">'.$this->pricehelper->currency($data['grandtotal'], true, false).'</td> 
    </tr>
				</table>
                <table cellspacing="0" cellpadding="2" border="0" width="100%">
   <tr><td height="30"></td></tr>
</table>
				<table cellspacing="0" cellpadding="4" border="0" width="100%">
					<tr>
						<td style="font-size: 10px; font-weight: bold;">COMMMENT OR SPECIAL INSTRUCTIONS</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;">1. Please mention this Order number in your invoices for this order</td>
					</tr>
					 <tr>
						<td style="font-size: 9px; line-height: 1.7;">2. Please notify us immediately if you are unable to ship as specified</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;">3. The tyre/s to be supplied under this purchase order must comply with KSA law and with standards approved as per Gulf Technical Regulations by GSO.</td>
					</tr>
				</table>

				<table cellspacing="0" cellpadding="2" border="0" width="100%">
				   <tr><td height="30"></td></tr>
				   <tr>
						<td style="font-size: 9px; line-height: 1.7;text-align: center">If you have any questions about this purchase order, please contact</td>
					</tr>
					<tr>
						<td style="font-size: 9px; line-height: 1.7;text-align: center">['.$contactPerson.', '.$contactPersonPhone.', Email: '.$contactPersonEmail.']</td>
					</tr>
				</table>
				';
				$pdf->writeHTML($html, true, false, true, false, '');
				
				/* End english version */
				
				$fileName = $pdfFile . $data['orderreference_no'] . '.pdf';

                if ($data['submit'] == 'download') {
                    /* $this->fileFactory->create(
                        $fileName,
                        $pdf->render(),
                        \Magento\Framework\App\Filesystem\DirectoryList::MEDIA, // this pdf will be saved in var directory with the name example.pdf
                        'application/octet-stream'
                    ); */
					$pdf->Output($fileName, 'D');
                }

                if ($data['submit'] != 'download') {
                    /* Create PDF end*/
                    $fileName = $data['orderreference_no'] . '_' . time() . '.pdf';
                    $popath   = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'po/' . $fileName;
                    //file_put_contents($popath, $pdf->render());
					$pdf->Output($popath, 'F');
					$pdf_content = file_get_contents($popath);

                    $this->storeManager->setCurrentStore($order->getStore()->getId());
                    $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
                    $mediaUrl        = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $pdfdownload     = $mediaUrl . 'po/' . $fileName;
                    $templateVars    = array(
                        'poreference_no'    => $poreference_no,
                        'bill_to_name'           => $bill_to_name,
                        'bill_to_telephone'           => $billTelephone,
                        'bill_to_email'           => $billEmail,
                        'bill_to_address'           => $billAddress,
                        'bill_to_contact_person'           => $billContactPerson,
                        'ordercomment'      => $ordercomment,
                        'order_incrementid' => $order_incrementid,
                        'installer_section'           => $installer_section,
                        'installer_section_ar'           => $installer_section_ar,
                        'itemtable'         => $itemtable,
                        'itemtable_ar'         => $itemtable_ar,
                        'podate'            => $podate,
                        'pdfdownload'       => $pdfdownload,
                    );
                    $email = $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);
                    $name  = $this->scopeConfig->getValue('trans_email/ident_support/name', ScopeInterface::SCOPE_STORE);

                    $copy_to = $this->scopeConfig->getValue('sales_email/order/copy_to', ScopeInterface::SCOPE_STORE);

                    $from = array('email' => $email, 'name' => $name);
                    $this->inlineTranslation->suspend();
                    $emailTemplateId = $this->scopeConfig->getValue('purchaseorder/general/po_email_template_id', ScopeInterface::SCOPE_STORE);
                    if ($emailTemplateId != '') {
                        if ($vendor_data->getEmailCopy()) {
                            $vendor_copy_email = explode(',', $vendor_copy_email);
                            $transport = $this->transportBuilder->setTemplateIdentifier($emailTemplateId)
                                ->setTemplateOptions($templateOptions)
                                ->setTemplateVars($templateVars)
                                ->setFrom($from)
                                ->addTo($vendor_email) // $vendor_email
                                ->addBcc($vendor_copy_email)
                                ->addAttachment($pdf_content, $fileName, 'application/pdf')
                                ->getTransport();
                        } else {
                            $transport = $this->transportBuilder->setTemplateIdentifier($emailTemplateId)
                                ->setTemplateOptions($templateOptions)
                                ->setTemplateVars($templateVars)
                                ->setFrom($from)
                                ->addTo($vendor_email) // $vendor_email
                                ->getTransport();
                        }
                        
                        $transport->sendMessage();
                        $this->inlineTranslation->resume();
                    } else {
                        $this->messageManager->addError(__('Purchase Order Email Template not configured yet!.'));
                    }
                }
                $adminUser = $this->authSession->getUser();
                $orderPoComment = 'PO is sent via email to '.$vendor_name . ' - BY ' . $adminUser->getFirstname(). ' '.$adminUser->getLastname();
                $order->addStatusHistoryComment($orderPoComment);
                $order->save();
        }
    }

    public function getpdf()
    {

        $pdf          = new \Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
        $page         = $pdf->pages[0]; // this will get reference to the first page.
        $style        = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 15);
        $page->setStyle($style);
        $width        = $page->getWidth();
        $hight        = $page->getHeight();
        $x            = 30;
        $pageTopalign = 850; //default PDF page height
        $this->y      = 850 - 150; //print table row from page top – 100px
        //Draw table header row’s
        $style->setFont($font, 16);
        $page->setStyle($style);
        $page->drawRectangle(30, $this->y - 20, $page->getWidth() - 30, $this->y + 90, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $style->setFont($font, 15);
        $page->setStyle($style);

        $imagePath = 'logo.png';
        $image     = "";
        if ($this->_mediaDirectory->isFile($imagePath)) {
            $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));

        }

        $y1 = 800;
        $y2 = 830;
        $x1 = 400;
        $x2 = 530;

        $page->drawImage($image, $x1, $y1, $x2, $y2);

        $page->drawText(__("Tyres Vision"), $x + 5, $this->y + 70, 'UTF-8');
        $style->setFont($font, 12);
        $page->setStyle($style);
        $page->drawText(__("Aspin Commercial Tower,"), $x + 5, $this->y + 55, 'UTF-8');
        $page->drawText(__("20th floor, Sheikh Zayed Road, Dubai"), $x + 5, $this->y + 40, 'UTF-8');
        $page->drawText(__("Phone:01 234 5678"), $x + 5, $this->y + 25, 'UTF-8');
        $page->drawText(__("Website: www.tyresvision.com "), $x + 5, $this->y + 10, 'UTF-8');

        $page->drawText(__("PURCHASE ORDER"), $x + 350, $this->y + 70, 'UTF-8');
        $page->drawText(__("DATE"), $x + 350, $this->y + 50, 'UTF-8');
        $page->drawText(__("PO/ORDER #"), $x + 350, $this->y + 30, 'UTF-8');
        $page->drawText(__("TRN #"), $x + 350, $this->y + 10, 'UTF-8');

        //Po value
        $page->drawText(__("6/9/12"), $x + 430, $this->y + 50, 'UTF-8');
        $page->drawText($data['poreference_no'], $x + 430, $this->y + 30, 'UTF-8');
        $page->drawText($data['orderreference_no'], $x + 430, $this->y + 10, 'UTF-8');

        // Vendor Detail
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 30);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 14);
        $page->drawText(__('VENDOR'), 40, $this->y - 18, 'UTF-8');
        $page->drawText(__('SHIP TO:'), 300, $this->y - 18, 'UTF-8');

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 140, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 12);

        $page->drawText(__('Devvedra patel'), 40, $this->y - 50, 'UTF-8');
        $page->drawText(__('T:34343434243'), 40, $this->y - 70, 'UTF-8');
        $page->drawText(__('Email:test@gmail.com'), 40, $this->y - 90, 'UTF-8');

        $page->drawText(__('Devvedra patel'), 300, $this->y - 50, 'UTF-8');
        $page->drawText(__('Installer Address'), 300, $this->y - 70, 'UTF-8');
        $page->drawText(__('Installer Contact Person'), 300, $this->y - 90, 'UTF-8');
        $page->drawText(__('T:34343434243'), 300, $this->y - 110, 'UTF-8');

        $page->drawText(__('Comment:'), 40, $this->y - 170, 'UTF-8');
        $page->drawText(__('Test comment'), 100, $this->y - 170, 'UTF-8');

        // iTems

        // Vendor Detail
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(30, $this->y - 200, $page->getWidth() - 30, $this->y - 220);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 14);
        $page->drawText(__('ITEM #'), 40, $this->y - 215, 'UTF-8');
        $page->drawText(__('DESCRIPTION'), 130, $this->y - 215, 'UTF-8');
        $page->drawText(__('QTY'), 320, $this->y - 215, 'UTF-8');
        $page->drawText(__('UNIT PRICE'), 370, $this->y - 215, 'UTF-8');
        $page->drawText(__('TOTAL'), 500, $this->y - 215, 'UTF-8');

        //ITEM VALUE
        $style->setFont($font, 12);
        $page->drawText(__('1'), 40, $this->y - 245, 'UTF-8');
        $page->drawText(__('SP Sport Maxx 050+ '), 130, $this->y - 245, 'UTF-8');
        $page->drawText(__('2'), 330, $this->y - 245, 'UTF-8');
        $page->drawText(__('AED 1200'), 370, $this->y - 245, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 245, 'UTF-8');

        $page->drawText(__('2'), 40, $this->y - 270, 'UTF-8');
        $page->drawText(__('SP Sport Maxx 050+ '), 130, $this->y - 270, 'UTF-8');
        $page->drawText(__('2'), 330, $this->y - 270, 'UTF-8');
        $page->drawText(__('AED 1200'), 370, $this->y - 270, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 270, 'UTF-8');

        $page->drawText(__('3'), 40, $this->y - 300, 'UTF-8');
        $page->drawText(__('SP Sport Maxx 050+ '), 130, $this->y - 300, 'UTF-8');
        $page->drawText(__('2'), 330, $this->y - 300, 'UTF-8');
        $page->drawText(__('AED 1200'), 370, $this->y - 300, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 300, 'UTF-8');

        //subtotal

        $page->drawText(__('SUBTOTAL'), 400, $this->y - 330, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 330, 'UTF-8');

        $page->drawText(__('TAX'), 400, $this->y - 360, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 360, 'UTF-8');

        $page->drawText(__('GRAND TOTAL'), 400, $this->y - 390, 'UTF-8');
        $page->drawText(__('AED 3600'), 500, $this->y - 390, 'UTF-8');

        // commment and instruction

        $page->drawRectangle(30, $this->y, $page->getWidth() - 30, $this->y - 140, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 12);

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(30, $this->y - 420, $page->getWidth() - 30, $this->y - 440);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 14);
        $page->drawText(__('COMMMENT OR SPECIAL INSTRUCTIONS'), 40, $this->y - 435, 'UTF-8');

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->drawRectangle(30, $this->y - 440, $page->getWidth() - 30, $this->y - 510, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 12);

        $page->drawText(__('1. Please mention this Order number in your invoices for this order'), 40, $this->y - 460, 'UTF-8');

        $page->drawText(__('2. Please notify us immediately if you are unable to ship as specified'), 40, $this->y - 475, 'UTF-8');

        $page->drawText(__('3. The tyre/s to be supplied under this purchase order must comply with UAE law and with standards
            '), 40, $this->y - 490, 'UTF-8');

        $page->drawText(__('approved as Gulf Technical Regulations by GSO'), 50, $this->y - 505, 'UTF-8');

        // footer

        $page->drawText(__('If you have any questions about this purchase order, please contact'), 150, $this->y - 540, 'UTF-8');
        $page->drawText(__('[Mr. Devendra, 0543473401 or Email: devendra.it@live.com]'), 130, $this->y - 560, 'UTF-8');

        $fileName = 'example.pdf';

        $this->fileFactory->create(
            $fileName,
            $pdf->render(),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR, // this pdf will be saved in var directory with the name example.pdf
            'application/pdf'
        );
    }
}