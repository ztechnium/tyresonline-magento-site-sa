<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

// @codingStandardsIgnoreFile

namespace Hdweb\Booking\Block\Adminhtml\Items\Edit\Tab;


use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;



class Main extends Generic implements TabInterface
{

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_hdweb_booking_items');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);
        if ($model->getId()) {
            $fieldset->addField('appointment_id', 'hidden', ['name' => 'appointment_id']);
        }
        $fieldset->addField(
            'fname',
            'text',
            ['name' => 'fname', 'label' => __('First Name'), 'title' => __('First Name'), 'required' => true]
        );

        $fieldset->addField(
            'lname',
            'text',
            ['name' => 'lname', 'label' => __('Last Name'), 'title' => __('Last Name'), 'required' => true]
        );

        $fieldset->addField(
            'phonenumber',
            'text',
            ['name' => 'phonenumber', 'label' => __('Phone Number'), 'title' => __('Phone Number'), 'required' => true]
        );

        $fieldset->addField(
            'email',
            'text',
            ['name' => 'email', 'label' => __('Email'), 'title' => __('Email'), 'required' => true]
        );

        $fieldset->addField(
            'make',
            'text',
            ['name' => 'make', 'label' => __('Make'), 'title' => __('Make'), 'required' => true]
        );

        $fieldset->addField(
            'model',
            'text',
            ['name' => 'model', 'label' => __('Model'), 'title' => __('Model'), 'required' => true]
        );

        $fieldset->addField(
            'year',
            'text',
            ['name' => 'year', 'label' => __('Year'), 'title' => __('Year'), 'required' => true]
        );

        $fieldset->addField(
            'vin',
            'text',
            ['name' => 'vin', 'label' => __('Vin'), 'title' => __('Vin'), 'required' => true]
        );

        $fieldset->addField(
            'service',
            'text',
            ['name' => 'service', 'label' => __('Service'), 'title' => __('Service'), 'required' => true]
        );

        $fieldset->addField(
            'message',
            'text',
            ['name' => 'message', 'label' => __('Message'), 'title' => __('Message'), 'required' => true]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'values'    => array(
                            'Pending' => 'Pending',
                            'Confirmed'   => 'Confirmed',
                            'Complete'   => 'Complete',
                            'Canceled'   => 'Canceled',
                )
            ]
        );

        $fieldset->addField(
            'admincomment',
            'textarea',
            ['name' => 'admincomment', 'label' => __('Admin Comment'), 'title' => __('Admin Comment'), 'required' => true]
        );

        $fieldset->addField(
            'logs',
            'textarea',
            [
                'label' => __('Logs'),
                'title' => __('Logs'),
                'name' => 'logs',
                'required' => false,
                'readonly' => true
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
