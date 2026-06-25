<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ecomteck\StoreLocator\Controller\Js;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Template extends Action
{
    /**
     * @var string
     */
    const TEMPLATE_CONFIG_PATH = 'ecomteck_storelocator/template/%s';

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /** @var PageFactory */
    public $resultPageFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ResourceConnection */
    private $resource;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->resource = $resource;
    }

    /**
     * Load the page defined in view/frontend/layout/storelocator_index_index.xml
     *
     * @return void
     */
    public function execute()
    {
        $templateId = $this->getRequest()->getParam('template');
        $template = $this->getTemplate($templateId);
        $this->getResponse()
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8', true)
            ->setContent($template);
    }

    /**
     * @param string|null $templateId
     * @return string
     */
    protected function getTemplate($templateId)
    {
        $path = sprintf(static::TEMPLATE_CONFIG_PATH, $templateId);
        $template = (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);

        if ($this->isTemplateCorrupted($template)) {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $dbTemplate = $this->loadTemplateFromDb($path, $storeId);
            if ($dbTemplate !== '' && !$this->isTemplateCorrupted($dbTemplate)) {
                $template = $dbTemplate;
            }
        }

        return $template;
    }

    private function isTemplateCorrupted(string $template): bool
    {
        return (bool)preg_match('/[\x{2500}-\x{25FF}]/u', $template);
    }

    private function loadTemplateFromDb(string $path, int $storeId): string
    {
        $conn = $this->resource->getConnection();
        $value = (string)$conn->fetchOne(
            'SELECT value FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
            [$path, 'stores', $storeId]
        );

        if ($value === '') {
            $value = (string)$conn->fetchOne(
                'SELECT value FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
                [$path, 'default', 0]
            );
        }

        return $value;
    }
}
