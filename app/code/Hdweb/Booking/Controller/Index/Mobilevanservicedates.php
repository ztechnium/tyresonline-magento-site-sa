<?php
namespace Hdweb\Booking\Controller\Index;

class Mobilevanservicedates extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
	protected $objectManager;

    public function __construct(\Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->resultJsonFactory        = $resultJsonFactory;
        $this->objectManager        = $objectManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $postData     = $this->getRequest()->getParams();
        $selectedDate = $postData['mobilevanservice_date']; //10-02-2022
		$timeZone = date_default_timezone_set('Asia/Dubai');
		$today = date('d-m-Y');
		$startHours = '11:00';
		$endHours = '18:00'; // 06:00 PM
		//$hourRange = array('09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM', '07:00 PM');
		$hourRange = array('11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM');
		$currentTimeHours = date('H:i');
		$selectHtml = '';
		$notAvailable = 'No slot available';
		
		if($today == $selectedDate){
			if(strtotime($currentTimeHours) >= strtotime($startHours) && strtotime($currentTimeHours) <= strtotime($endHours)){
				$timestamp = strtotime($currentTimeHours) + 60*60*2; //add 2 hours in current time
				$time = date('H:i', $timestamp);
				$times = explode(':', $time);
				$newTime = round(($times[0] * 60 + $times[1]) / 60) . ':00'; // rounding hours
				$startHours = $newTime;
				$startHours12 = date('h:i A', strtotime($startHours));
				$key = array_search($startHours12, $hourRange);
				if($key){
					$selectHtml .= '<option value="">' . __('Time') . '</option>';
					$position = $key - count($hourRange);
					$timeOptions = array_slice($hourRange, $position);
					foreach($timeOptions as $hours){
						$selectHtml .= '<option value="' . $hours . '">' . $hours . '</option>';
					}
				}else{
					$selectHtml .= '<option value="">' . $notAvailable . '</option>';
				}
				
			}else{
				if(strtotime($startHours) >= strtotime($currentTimeHours)){
					$selectHtml .= '<option value="">' . __('Time') . '</option>';
					foreach($hourRange as $hours){
						$selectHtml .= '<option value="' . $hours . '">' . $hours . '</option>';
					}
				}else{
					$selectHtml .= '<option value="">' . $notAvailable . '</option>';
				}
				
			}
		}else{
			$selectHtml .= '<option value="">' . __('Time') . '</option>';
			foreach($hourRange as $hours){
				$selectHtml .= '<option value="' . $hours . '">' . $hours . '</option>';
			}
		}
		
        $response         = array();
        $response['status']  = 'success';
        $response['request'] = $selectHtml;
        $resultJson          = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
