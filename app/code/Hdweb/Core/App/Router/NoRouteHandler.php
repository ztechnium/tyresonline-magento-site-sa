<?php

namespace Hdweb\Core\App\Router;

class NoRouteHandler implements \Magento\Framework\App\Router\NoRouteHandlerInterface
{
    public function process(\Magento\Framework\App\RequestInterface $request)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();	
		$urlInterface = $objectManager->get('Magento\Framework\UrlInterface');
		$currentUrl = $urlInterface->getCurrentUrl();
		// URL to redirect to
		$response = $objectManager->get('Magento\Framework\App\ResponseInterface');
		$url = $urlInterface->getUrl('/').'all-tyres/car-tyres.html';
		if (strpos($currentUrl, "blog")) {
			$url = $urlInterface->getUrl('/').'blog';
		}
		$response->setRedirect($url, 301)->sendResponse();
		exit;
    }
}