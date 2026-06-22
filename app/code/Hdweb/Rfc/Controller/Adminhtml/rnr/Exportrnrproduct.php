<?php
namespace Hdweb\Rfc\Controller\Adminhtml\rnr;

class Exportrnrproduct extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;
    protected $_scopeConfig;
    protected $_order;
    protected $_product;
    protected $_storeManager;
    protected $_dir;
    protected $userFactory;
    protected $orderRepository;
    protected $customerRepositoryInterface;
    protected $_countryFactory;
	protected $_messageManager;
	protected $_filesystem;
	protected $_productRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order,
        \Magento\Catalog\Model\Product $product,
		\Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Framework\Filesystem $_filesystem
    ) {
        parent::__construct($context);
        $this->resultPageFactory           = $resultPageFactory;
        $this->_scopeConfig                = $scopeConfig;
        $this->_order                      = $order;
        $this->_product                    = $product;
		$this->_productRepository 		   = $productRepository;
        $this->_storeManager               = $storeManager;
        $this->_dir                        = $dir;
        $this->userFactory                 = $userFactory;
        $this->orderRepository             = $orderRepository;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
		$this->_countryFactory 			   = $countryFactory;
		$this->_messageManager    		   = $messageManager;
		$this->_filesystem        		   = $_filesystem;
    }
    public function execute()
    {
        $data           = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $order_id       = $this->getRequest()->getParam('order_id');
        $order          = $this->_order->load($order_id);
		$currencysymbol = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$currency 		= $currencysymbol->getStore()->getCurrentCurrencyCode();
		$responseRowData   = array();
        $responseRowData[] = array('Sku', 'STG Code', 'Product Name', 'Display Name', 'Description', 'Price', 'Cost', 'Origin', 'Color', 'Brand', 'Width', 'Profile', 'Rim', 'Load Index', 'Speed Index', 'Year', 'Runflat', 'Ordered Qty');
		foreach ($order->getAllVisibleItems() as $item) {
			$_product = $this->_productRepository->getById($item->getProductId());
			// $productionYear = $_product->getAttributeText('production_year');
			$qtyOrdered 	= $item->getQtyOrdered();
			$price      	= $item->getPrice();
			$sku			= $_product->getSku();
			$stgCode		= $_product->getStgCode();//$_product->getStgCode();
			$productName	= $_product->getName();
			$description	= $_product->getDescription();
			$displayName	= $_product->getDisplayname();
			$basePrice 		= $_product->getPrice();
			$price  		= $basePrice * (5/100);
			$finalPrice 	= $basePrice + $price;
			$pricewithTax 	= $currency.number_format($finalPrice, 2);
			$cost 			= $currency.number_format($_product->getCost(), 2);
			$origin 		= $_product->getResource()->getAttribute("origin")->getFrontend()->getValue($_product);
			$color 			= $_product->getResource()->getAttribute("color")->getFrontend()->getValue($_product);
			$brand 			= $_product->getResource()->getAttribute("brand")->getFrontend()->getValue($_product);
			$width			= $_product->getResource()->getAttribute("width")->getFrontend()->getValue($_product);
			$profile		= $_product->getResource()->getAttribute("height")->getFrontend()->getValue($_product);
			$rim			= $_product->getResource()->getAttribute("rin")->getFrontend()->getValue($_product);
			$load_index		= $_product->getResource()->getAttribute("load_index")->getFrontend()->getValue($_product);
			$speed_index	= $_product->getResource()->getAttribute("speed_index")->getFrontend()->getValue($_product);
			$productionYear	= $_product->getResource()->getAttribute("year")->getFrontend()->getValue($_product);
			$runflat		= $_product->getResource()->getAttribute("runflat")->getFrontend()->getValue($_product);
			
			$responseRowData[] = array($sku, $stgCode, $productName, $displayName, $description, $pricewithTax, $cost, $origin, $color, $brand, $width, $profile, $rim, $load_index, $speed_index, $productionYear, $runflat, $qtyOrdered);
		}
		
		if (count($responseRowData) > 1) {
			$this->_createcsvfile($responseRowData, $order_id);
		}
		$this->_messageManager->addSuccess(__('RNR order items template generated successfully.'));
		$this->_redirect('sales/order/view', array('order_id' => $order_id));
		
    }
	
	public function _createcsvfile($responseRow, $order_id)
    {
        if (count($responseRow) > 0) {

            $csvMediapath = $this->_filesystem->getDirectoryRead($this->_dir::MEDIA)->getAbsolutePath() . 'rnr_orderitems_templates/';
            if (!is_dir($csvMediapath)) {
                $csvMediapath = mkdir($csvMediapath);
            }
			
            $outputFile = $this->_filesystem->getDirectoryRead($this->_dir::MEDIA)->getAbsolutePath() . 'rnr_orderitems_templates/' . "RNR_Order_Items_Template_" . $order_id . ".csv";
	
            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }
			
			$file_name = "RNR_Order_Items_Template_" . $order_id . ".csv";
			$this->downloadOutputCSV($responseRow, $file_name);
			
        }
		
    }
	public function downloadOutputCSV($responseRow, $file_name) {
       # output headers so that the file is downloaded rather than displayed
        header("Content-Type: text/csv");
		header("Content-Transfer-Encoding: UTF-8");
        header("Content-Disposition: attachment; filename=$file_name");
        # Disable caching - HTTP 1.1
        header("Cache-Control: no-cache, no-store, must-revalidate");
        # Disable caching - HTTP 1.0
        header("Pragma: no-cache");
        # Disable caching - Proxies
        header("Expires: 0");
    
        # Start the ouput
        $output = fopen("php://output", "w");
        
         # Then loop through the rows
        foreach ($responseRow as $row) {
            # Add the rows to the body
            fputcsv($output, $row); // here you can change delimiter/enclosure
        }
        # Close the stream off
        fclose($output);
		exit;
    }
}