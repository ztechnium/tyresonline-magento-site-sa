<?php

namespace Hdweb\Salesperson\Controller\Adminhtml\Order;
use Magento\Store\Model\ScopeInterface;

class Assignsalesperson extends \Magento\Backend\App\Action
{
    protected $_order;
    protected $scopeConfig ;
    protected $addressRenderer;
    protected $authSession;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
          \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
          \Magento\Backend\Model\Auth\Session $authSession
        )
    {
            parent::__construct($context);
            $this->_order = $order; 
            $this->scopeConfig  = $scopeConfig;
            $this->addressRenderer = $addressRenderer;
            $this->authSession = $authSession;
    }
    public function execute()
    {

        $order_id = $this->getRequest()->getParam('order_id');
        $salesid = $this->getRequest()->getParam('salesid');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$user = $objectManager->create('Magento\User\Model\User');
		$userInfo = $user->load($salesid);
		$userName = $userInfo->getFirstname().' '.$userInfo->getLastname();
		
        $order = $this->_order->load($order_id);
               
        // // $this->orderSender->send($order, true);
              
        if ($userInfo->getUserErpExecutiveCode()) {
            $userErpExecutiveCode = $userInfo->getUserErpExecutiveCode();
        } else {
            $userErpExecutiveCode = '';    
        }
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('sales_order'); 
		$salesql = "Update " . $tableName . " Set sales_person_id = '". $salesid ."', erp_executive_code = '". $userErpExecutiveCode ."' where entity_id = ".$order_id;
        $connection->query($salesql);
        $adminUser = $this->authSession->getUser();
		$order->addStatusHistoryComment('Salesperson is assiged as '.$userName. ' - BY ' . $adminUser->getFirstname(). ' '.$adminUser->getLastname());
		$order->save();
		$this->messageManager->addSuccess(__('Salesperson has been assigned successfully.'));
		$this->_redirect('sales/order/view', array('order_id' => $order_id)); 
      

    }
    
}