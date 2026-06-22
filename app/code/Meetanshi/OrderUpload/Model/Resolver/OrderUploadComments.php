<?php

namespace Meetanshi\OrderUpload\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Meetanshi\OrderUpload\Helper\Data as HelperData;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Class OrderUploadComments
 * @package Meetanshi\OrderUpload\Model\Resolver
 */
class OrderUploadComments implements ResolverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * OrderUploadComments constructor.
     * @param GetCartForUser $getCartForUser
     * @param HelperData $helper
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        HelperData $helper
    )
    {
        $this->getCartForUser = $getCartForUser;
        $this->helper = $helper;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if(!$this->helper->isEnabled()){
            throw new GraphQlNoSuchEntityException(__("Order Upload Extension is disable."));
        }
        try {
            $returnData = ['success' => false];
            if (!isset($args['input']['cart_id'])) {
                throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
            }
            if (!isset($args['input']['comments'])) {
                throw new GraphQlInputException(__('Required parameter "comments" is missing.'));
            }

            $maskedCartId = $args['input']['cart_id'];
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $quote = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
            $quote->setOrderComment($args['input']['comments']);
            $quote->save();

            $returnData = ['success' => true];
            return $returnData;
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        return $returnData;
    }
}
