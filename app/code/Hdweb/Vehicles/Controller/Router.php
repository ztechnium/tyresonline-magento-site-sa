<?php

namespace Hdweb\Vehicles\Controller;

use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;

class Router implements RouterInterface
{
    protected $actionFactory;
    protected $eventManager;
    protected $response;
    protected $dispatched;
    protected $storeManager;


    public function __construct(
        ActionFactory $actionFactory,
        ResponseInterface $response,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager
    )
    {
      
        $this->actionFactory = $actionFactory;
        $this->response = $response;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
    }

    public function match(RequestInterface $request)
    {
        if (!$this->dispatched) {
            $urlKey = trim($request->getPathInfo(), '/');

            $origUrlKey = $urlKey;
            $condition = new DataObject(['url_key' => $urlKey, 'continue' => true]);

            if ($condition->getRedirectUrl()) {
                $this->response->setRedirect($condition->getRedirectUrl());
                $request->setDispatched(true);
          
                return $this->actionFactory->create(
                    'Magento\Framework\App\Action\Redirect',
                    ['request' => $request]
                );
            }
            if (!$condition->getContinue()) {
                return null;
            }

            //if ($urlKey == 'car-tyres') {
                if ($urlKey == 'car') {
                $request->setModuleName('vehicles')
                    ->setControllerName('vehicles')
                    ->setActionName('make')//;
                    ->setParam('route', $urlKey);
                $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                $this->dispatched = true;
                return $this->actionFactory->create(
                    'Magento\Framework\App\Action\Forward',
                    ['request' => $request]
                );
            }
            $identifiers = explode('/', $urlKey);
            //if($identifiers[0] == 'car-tyres' && $identifiers[1] != 'ajax'){
                if($identifiers[0] == 'car' && $identifiers[1] != 'ajax'){
                if (count($identifiers) == 2) {
                    $make = $identifiers[1];
                    $request->setModuleName('vehicles')
                        ->setControllerName('vehicles')
                        ->setActionName('model')
                        //->setParam('make', $make)//;
                        ->setParams(array(
                                        'make' => $make,
                                        'route' => $identifiers[0],
                                    ));
                    $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                    $this->dispatched = true;
                    return $this->actionFactory->create(
                        'Magento\Framework\App\Action\Forward',
                        ['request' => $request]
                    );  
                }elseif (count($identifiers) == 3) {
                    $make = $identifiers[1];
                    $model = $identifiers[2];
                    $request->setModuleName('vehicles')
                        ->setControllerName('vehicles')
                        ->setActionName('yeargeneration')
                        ->setParams(array(
                                        'make' => $make,
                                        'model' => $model,
                                    ));
                    $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                    $this->dispatched = true;
                    return $this->actionFactory->create(
                        'Magento\Framework\App\Action\Forward',
                        ['request' => $request]
                    );
                }
            }
        }
    }
}
