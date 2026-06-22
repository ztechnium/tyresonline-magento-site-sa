<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */
namespace Hdweb\Booking\Block\Adminhtml;

class Items extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'items';
        $this->_headerText = __('Appointments');
        // $this->_addButtonLabel = __('Add New Item');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
