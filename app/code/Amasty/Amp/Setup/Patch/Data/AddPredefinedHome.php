<?php

declare(strict_types=1);

namespace Amasty\Amp\Setup\Patch\Data;

use Amasty\Amp\Block\Cms\Home\PredefinedContentFactory;
use Magento\Cms\Helper\Page;
use Magento\Cms\Model\GetPageByIdentifier;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class AddPredefinedHome implements DataPatchInterface
{
    /**
     * @var PredefinedContentFactory
     */
    private $predefinedContentFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetPageByIdentifier
     */
    private $pageByIdentifier;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var State
     */
    private $state;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PredefinedContentFactory $predefinedContentFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        GetPageByIdentifier $pageByIdentifier,
        PageRepository $pageRepository,
        LoggerInterface $logger,
        State $state
    ) {
        $this->predefinedContentFactory = $predefinedContentFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->pageByIdentifier = $pageByIdentifier;
        $this->pageRepository = $pageRepository;
        $this->state = $state;
        $this->logger = $logger;
    }

    public function apply(): self
    {
        try {
            $predefinedHtml = $this->state->emulateAreaCode(
                Area::AREA_FRONTEND,
                [$this, 'getContent'],
                []
            );
            $identifiers = [];

            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();
                $homeIdentifier = $this->scopeConfig->getValue(
                    Page::XML_PATH_HOME_PAGE,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );

                if (!in_array($homeIdentifier, $identifiers)) {
                    $homePage = $this->pageByIdentifier->execute($homeIdentifier, $storeId);
                    $homePage->setAmpContent($predefinedHtml);
                    $this->pageRepository->save($homePage);
                    $identifiers[] = $homeIdentifier;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    public function getContent(): string
    {
        return $this->predefinedContentFactory->create()->toHtml();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
