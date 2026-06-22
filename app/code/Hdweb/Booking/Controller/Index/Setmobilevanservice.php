<?php
namespace Hdweb\Booking\Controller\Index;

class Setmobilevanservice extends \Magento\Framework\App\Action\Action {
	
	protected $resultJsonFactory;
	protected $objectManager;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager

    ) {
        $this->resultJsonFactory    = $resultJsonFactory;
		$this->objectManager        = $objectManager;
        parent::__construct($context);

    }

    public function execute() {
		$post  = $this->getRequest()->getParams();
		$message = false;
		if(isset($post['mobilevanservice_address']) && isset($post['mobilevanservice_location_lat']) && isset($post['mobilevanservice_location_lng']) && isset($post['mobilevanservice_date']) && isset($post['mobilevanservice_time']) && isset($post['mobilevanservice_notes'])){
			$mobilevanserviceAddress = $post['mobilevanservice_address'];
			$mobilevanserviceLocation_lat = $post['mobilevanservice_location_lat'];
			$mobilevanserviceLocation_lng = $post['mobilevanservice_location_lng'];
			$mobilevanserviceDate = $post['mobilevanservice_date'];
			$mobilevanserviceTime = $post['mobilevanservice_time'];
			$mobilevanserviceNotes = $post['mobilevanservice_notes'];
			$mobileVanServiceData = array('mobilevanservice_address' => $mobilevanserviceAddress, 'mobilevanservice_location_lat' => $mobilevanserviceLocation_lat, 'mobilevanservice_location_lng' => $mobilevanserviceLocation_lng, 'mobilevanservice_date' => $mobilevanserviceDate, 'mobilevanservice_date' => $mobilevanserviceDate, 'mobilevanservice_time' => $mobilevanserviceTime, 'mobilevanservice_notes' => $mobilevanserviceNotes);
			$jsonData = json_encode($mobileVanServiceData);
			setcookie("mobilevanservice_data", $jsonData, time()+3600, '/');
			$message = true;
		}
		 $response = [
			'success' => $message,
		 ];
		 
		 /** @var \Magento\Framework\Controller\Result\Json $resultJson */
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }
}