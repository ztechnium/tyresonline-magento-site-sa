<?php

namespace Meetanshi\OrderUpload\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload\CollectionFactory;
use Meetanshi\OrderUpload\Helper\Data;

/**
 * Class OrderUploadCustomerCollection
 * @package Meetanshi\OrderUpload\Model\Resolver
 */
class OrderUploadCustomerCollection implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * OrderUploadCustomerCollection constructor.
     * @param CollectionFactory $collectionFactory
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Data $helper,
        array $data = []
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null)
    {

        if(!$this->helper->isEnabled()){
            throw new GraphQlAuthorizationException(__("Order Upload extension is not enable."));
        }
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        $customerId = $context->getUserId();

        try {
            return $this->getWhatsappContactData($customerId);
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }
        return [];
    }

    /**
     * @param $customerId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWhatsappContactData($customerId)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);

        $allData = [];
        foreach ($collection as $item) {
            $allData[] = [
                "id" => $item->getId(),
                "order_id" => $item->getorder_id(),
                "customer_id" => $item->getcustomer_id(),
                "file_name" => $item->getfile_name(),
                "file_path" => $this->helper->pubMediaPath() . $item->getfile_path(),
                "comment" => $item->getcomment(),
                "visible_customer_account" => $item->getvisible_customer_account(),
                "updated_at" => $item->getupdated_at(),
                "created_at" => $item->getcreated_at(),
            ];
        }

        $data['allOrderUploadCustomer'] = $allData;
        return $data;
    }

}