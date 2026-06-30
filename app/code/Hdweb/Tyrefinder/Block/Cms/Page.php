<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Block\Cms;

use Hdweb\Tyrefinder\Helper\ProductImage as ProductImageHelper;
use MGS\Fbuilder\Block\Cms\Page as FbuilderCmsPage;

class Page extends FbuilderCmsPage
{
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Cms\Model\Page $page,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        \MGS\Fbuilder\Helper\Builder $panelHelper,
        private readonly ProductImageHelper $productImageHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $page,
            $filterProvider,
            $storeManager,
            $pageFactory,
            $pageConfig,
            $panelHelper,
            $data
        );
    }

    protected function _toHtml()
    {
        $html = (string) parent::_toHtml();
        if ($html === '' || $this->_panelHelper->acceptToUsePanel()) {
            return $html;
        }

        return $this->productImageHelper->normalizeContentHtmlImages($html);
    }

    /**
     * @param \Magento\Cms\Model\Page $page
     */
    protected function _addBreadcrumbs($page)
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl(),
                ]
            );

            $pageLabel = $this->resolveBreadcrumbPageLabel($page);
            $breadcrumbsBlock->addCrumb(
                'cms_page',
                [
                    'label' => $pageLabel,
                    'title' => $pageLabel,
                ]
            );
        }
    }

    private function resolveBreadcrumbPageLabel(\Magento\Cms\Model\Page $page): string
    {
        $heading = trim((string) $page->getContentHeading());
        if ($heading !== '') {
            return (string) __($heading);
        }

        $title = trim((string) $page->getTitle());
        if ($title !== '' && str_ends_with($title, ' - Arabic')) {
            $title = trim(substr($title, 0, -9));
        }

        return (string) __($title);
    }
}
