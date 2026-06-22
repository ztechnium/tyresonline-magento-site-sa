<?php

namespace Amasty\Coupons\Observer;

use Magento\Framework\Event\ObserverInterface;
use Amasty\Coupons\Model\DiscountCollector;
use Magento\Framework\Event\Observer;

/**
 * Reset coupon discount registry on event sales_quote_collect_totals_before
 */
class FlushCouponObserver implements ObserverInterface
{
    /**
     * @var DiscountCollector
     */
    protected $discountCollector;

    public function __construct(
        DiscountCollector $discountCollector
    ) {
        $this->discountCollector = $discountCollector;
    }

    /**
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $renderedCodes = $this->discountCollector->getCouponCodes();
		$quote = $observer->getEvent()->getQuote();
		/* $installerId = $quote->getPickupStore();
		if($renderedCodes){
			$totalwithDiscount = $quote->getBaseSubtotalWithDiscount() * 1.05;
			if (in_array("MATO22", $renderedCodes) && $installerId == 3 && $totalwithDiscount > 2000){	
				$mobilevanprice = 0;
				$quote->setFee($mobilevanprice);
				$quote->save();
			}else{
				if($installerId == 3){
					$mobilevanprice = 210;
					$quote->setFee($mobilevanprice);
					$quote->save();
				}
			}
		} */
        $this->discountCollector->flushAmount();
    }
}
