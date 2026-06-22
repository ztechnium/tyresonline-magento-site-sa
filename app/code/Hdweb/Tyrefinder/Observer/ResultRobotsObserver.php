<?php
namespace Hdweb\Tyrefinder\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;
use Magento\LayeredNavigation\Block\Navigation\State;
class ResultRobotsObserver implements ObserverInterface
{
    public $request;
    public $layoutFactory;
    public $layerResolver;
    public $stateFilter;

    public function __construct(
        Http $request,
        Config $layoutFactory,
        State $stateFilter,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver
    ) {
        $this->request = $request;
        $this->layoutFactory = $layoutFactory;
        $this->stateFilter = $stateFilter;
        $this->layerResolver = $layerResolver;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $fullActionName = $this->request->getFullActionName();
		$selectedFilters = $this->stateFilter->getActiveFilters();
		
        if ($fullActionName == "catalog_category_view" &&
            count($selectedFilters) > 0) {
            $this->layoutFactory->setRobots('NOINDEX,NOFOLLOW');
        }
    }
}