<?php
namespace Hdweb\Booking\Controller\Index;

use Magento\Store\Model\ScopeInterface;

class Staroncar extends \Magento\Framework\App\Action\Action {
    const XML_PATH_EMAIL_RECIPIENT_NAME  = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
    const STAR_ON_CAR_QUOTE_EMAIL_TEMPLATE  = 'hdwebcore/general/staroncar_notify_email_template';

    protected $_inlineTranslation;
    protected $_transportBuilder;
    protected $_scopeConfig;
    protected $_logLoggerInterface;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Psr\Log\LoggerInterface $loggerInterface,
        array $data = []

    ) {
        $this->_inlineTranslation   = $inlineTranslation;
        $this->_transportBuilder    = $transportBuilder;
        $this->_scopeConfig         = $scopeConfig;
        $this->_logLoggerInterface  = $loggerInterface;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->messageManager       = $context->getMessageManager();

        parent::__construct($context);

    }

    public function execute() {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeid       = $storeManager->getStore()->getStoreId();

        $model = $objectManager->create('Hdweb\Booking\Model\Items');
        $post  = $this->getRequest()->getParams();
		//echo '<pre>';print_r($post);die;
        $model->setFname($post['name']);
        $model->setLname($post['lastname']);
        $model->setPhonenumber($post['phonenumber']);
        $model->setEmail($post['email']);
        $model->setMake($post['make']);
        $model->setModel($post['model']);
        $model->setYear($post['year']);
        $model->setMessage($post['message']);
        $model->setStatus('Pending');
        if(isset($post['service'])){
		   $service=implode(',', $post['service']);
		   $model->setService($service);           
        }
        $model->save();
        $model->setAppointmentIncrementId('TYO0000' . $model->getId());
        $model->save();
        
        try
        { 
            // Send Mail
            $this->_inlineTranslation->suspend();
            $emailTemplateId = $this->_scopeConfig->getValue(self::STAR_ON_CAR_QUOTE_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

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
			 
            $receiveemails = array_map('trim', $receiveemails);
					$transport = $this->_transportBuilder
					->setTemplateIdentifier($emailTemplateId)
					->setTemplateOptions(
						[
							'area'  => 'frontend',
							'store' => $storeid,
						]
					)
					->setTemplateVars([
						'name'           => $post['name'],
						'lname'          => $post['lastname'],
						'email'          => $post['email'],
						'service'        => implode(',', $post['service']),
						'phonenumber'    => $post['phonenumber'],
						'comments'       => $post['message'],
						'vehicle'        => $post['make'],
						'model'        	 => $post['model'],
						'year'        	 => $post['year'],
						'staroncar_id'   => $model->getAppointmentIncrementId(),

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
            // exit;
        }
        $this->_redirect('starlight-headliner-car-roof');
        return;
    }
}