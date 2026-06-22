<?php
namespace Hdweb\Salesperson\Block\Adminhtml\User\Edit\Tab;

use Magento\Backend\Block\Widget\Form;

class Main extends \Magento\User\Block\User\Edit\Tab\Main
{
    /**
     * Prepare form fields
     *
     * @return Form
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();
        $model = $this->_coreRegistry->registry('permissions_user');
        $baseFieldset = $form->getElement('base_fieldset');
        $baseFieldset->addField(
            'user_erp_executive_code',
            'text',
            [
                'name' => 'user_erp_executive_code',
                'label' => __('ERP Executive Code'),
                'title' => __('ERP Executive Code'),
                'value' => $model->getUserErpExecutiveCode()
            ]
        );
        return $this;
    }
}