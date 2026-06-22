<?php
namespace Hdweb\Core\Model\Checkout;

class CompositeConfigProvider extends \Magento\Checkout\Model\CompositeConfigProvider
{
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ?array $configProviders = null
    ) {
        if ($configProviders === null) {
            $configProviders = self::buildProviders($objectManager);
        }

        parent::__construct($configProviders);
    }

    private static function buildProviders(\Magento\Framework\ObjectManagerInterface $objectManager): array
    {
        $providers = [];
        $metadataFile = BP . '/generated/metadata/frontend.php';

        if (!is_readable($metadataFile)) {
            return $providers;
        }

        $metadata = include $metadataFile;
        $providerMap = $metadata['arguments']['Magento\\Checkout\\Model\\CompositeConfigProvider']['configProviders']['_vac_'] ?? [];

        foreach ($providerMap as $info) {
            $class = $info['_i_'] ?? null;
            if (!$class) {
                continue;
            }

            try {
                $providers[] = $objectManager->get($class);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $providers;
    }
}
