<?php
namespace Hdweb\Booking\Controller\Index;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;

class Oilservicepackenquiry extends \Magento\Framework\App\Action\Action {
    const XML_PATH_EMAIL_RECIPIENT_NAME  = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
	const NOTIFY_OILSERVICE_ENQUIRY_EMAIL_TEMPLATE  = 'hdwebcore/general/oil_service_enquiry_email_template';

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
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
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
        $this->fileFactory           	= $fileFactory;
		$this->date = $date;
		$this->_objectManager 			= $objectManager;
		$this->storeManager 			= $storeManager;
		$this->bookingModel 			= $bookingModel;

        parent::__construct($context);

    }

    public function execute() {
        $model = $this->bookingModel;
        $post  = $this->getRequest()->getParams();
        $model->setFname($post['name']);
        $model->setLname($post['lastname']);
        $model->setPhonenumber($post['phonenumber']);
        $model->setEmail($post['email']);
		$model->setMake($post['make']);
        $model->setModel($post['model']);
        $model->setYear($post['engine']);
        $model->setVin($post['vin']);
        $model->setStatus('Pending');
		$message = 'Via Email';
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
			
			$this->_inlineTranslation->suspend();
			$emailTemplateId = $this->_scopeConfig->getValue(self::NOTIFY_OILSERVICE_ENQUIRY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
			$receiveemails = array_map('trim', $receiveemails);
					$transport = $this->_transportBuilder
					->setTemplateIdentifier($emailTemplateId)
					->setTemplateOptions($templateOptions)
					->setTemplateVars([
						'name'           => $post['name'],
						'lname'          => $post['lastname'],
						'email'          => $post['email'],
						'make'           => $post['make'],
						'model'          => $post['model'],
						'engine'         => $post['engine'],
						'vin_number'     => $post['vin'],
						'service'        => $post['service'],
						'phonenumber'    => $post['phonenumber'],
						'service_id'   	 => $model->getAppointmentIncrementId(),
					])
					->setFrom($sender)
					->addTo($sentToEmail,$sentToName)
					->getTransport();

			$transport->sendMessage();
			$this->_inlineTranslation->resume();
			$this->messageManager->addSuccess('Email sent successfully');
			
        } catch (\Exception $e) {   
            $this->messageManager->addError($e->getMessage());
            $this->_logLoggerInterface->debug($e->getMessage());
        }
		if($serviceType == 1){
			$this->_redirect('contact?ref=oil-change'); //contact us page redirect
		}else{
			$this->_redirect('contact?ref=servicepacks');
		}
        return;
    }
}