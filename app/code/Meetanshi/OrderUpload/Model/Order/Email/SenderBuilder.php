<?php

namespace Meetanshi\OrderUpload\Model\Order\Email;

use Meetanshi\OrderUpload\Helper\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\SenderBuilder as OrderSenderBuilder;
use Meetanshi\OrderUpload\Helper\Data as DataHelper;

/**
 * Class SenderBuilder
 * @package Meetanshi\OrderUpload\Model\Order\Email
 */
class SenderBuilder extends OrderSenderBuilder
{
    /**
     * SenderBuilder constructor.
     * @param Template $templateContainer
     * @param IdentityInterface $identityContainer
     * @param TransportBuilder $transportBuilder
     * @param DataHelper $helper
     */
    public function __construct(Template $templateContainer, IdentityInterface $identityContainer, TransportBuilder $transportBuilder, DataHelper $helper)
    {
        parent::__construct($templateContainer, $identityContainer, $transportBuilder);
        $this->helper = $helper;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function configureEmailTemplate()
    {
        $this->transportBuilder->setTemplateIdentifier($this->templateContainer->getTemplateId());
        $this->transportBuilder->setTemplateOptions($this->templateContainer->getTemplateOptions());
        $this->transportBuilder->setTemplateVars($this->templateContainer->getTemplateVars());
        $this->transportBuilder->setFrom($this->identityContainer->getEmailIdentity());
        $vars = $this->templateContainer->getTemplateVars();
        if (isset($vars['file_attach'])) {
            $fileAttach = $vars['file_attach'];
            if ($fileAttach == 1) {
                $pubMediaUrl = $this->helper->tempMediaPath();
                for ($i = 0; $i < $vars['total_file']; $i++) {
                    $fileName = $vars['file_name' . $i];
                    $filePath = $vars['file_path' . $i];
                    $filePath = $pubMediaUrl . 'orderupload' . $filePath;
                    $this->transportBuilder->addAttachment($filePath, $fileName);
                }
            }
        }
    }
}
