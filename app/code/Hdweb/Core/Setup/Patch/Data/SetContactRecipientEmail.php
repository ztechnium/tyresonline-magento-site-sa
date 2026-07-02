<?php
declare(strict_types=1);

namespace Hdweb\Core\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class SetContactRecipientEmail implements DataPatchInterface
{
    private WriterInterface $configWriter;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function apply(): void
    {
        $defaultRecipient = $this->scopeConfig->getValue('contact/email/recipient_email');
        if (empty($defaultRecipient)) {
            $generalEmail = $this->scopeConfig->getValue('trans_email/ident_general/email');
            if (!empty($generalEmail)) {
                $this->configWriter->save('contact/email/recipient_email', $generalEmail);
            }
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $recipient = $this->scopeConfig->getValue(
                'contact/email/recipient_email',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if (!empty($recipient)) {
                continue;
            }

            $generalEmail = $this->scopeConfig->getValue(
                'trans_email/ident_general/email',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?: $this->scopeConfig->getValue('trans_email/ident_general/email');

            if (!empty($generalEmail)) {
                $this->configWriter->save(
                    'contact/email/recipient_email',
                    $generalEmail,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
