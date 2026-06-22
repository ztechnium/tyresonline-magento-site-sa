<?php

namespace MGS\Brand\Controller\Adminhtml\Patternmanagement;

class MassDelete extends \Magento\Backend\App\Action
{
	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 * @return void
	 */
	public function execute()
	{
		$ids = $this->getRequest()->getParam('patternmanagement_id');
		if (!is_array($ids) || empty($ids)) {
			$this->messageManager->addErrorMessage(__('Please select pattern.'));
		} else {
			$numOfSuccess = 0;
			foreach ($ids as $id) {
				try {
					$cat = $this->_objectManager->create('MGS\Brand\Model\Patternmanagement')->load($id);
					$cat->delete();
					$numOfSuccess++;
				} catch (\Exception $e) {
					$this->messageManager->addErrorMessage(__('Cannot delete pattern with ID %1', $id));
				}
			}
			$this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $numOfSuccess));
		}

		$this->_redirect('*/*/');
	}
}
