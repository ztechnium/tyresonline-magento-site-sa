<?php
declare(strict_types=1);

namespace Hdweb\Core\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixSmtpHostConfiguration implements DataPatchInterface
{
    private const BROKEN_HOST = 'mail.tyresonline.sa';
    private const SMTP_HOST = 'smtp.office365.com';
    private const SMTP_PORT = '587';
    private const SMTP_PROTOCOL = 'tls';

    private WriterInterface $configWriter;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply(): void
    {
        $currentHost = (string) $this->scopeConfig->getValue('smtp/configuration_option/host');
        if ($currentHost !== self::BROKEN_HOST) {
            return;
        }

        $this->configWriter->save('smtp/configuration_option/host', self::SMTP_HOST);
        $this->configWriter->save('smtp/configuration_option/port', self::SMTP_PORT);
        $this->configWriter->save('smtp/configuration_option/protocol', self::SMTP_PROTOCOL);
    }

    public static function getDependencies(): array
    {
        return [SetContactRecipientEmail::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
