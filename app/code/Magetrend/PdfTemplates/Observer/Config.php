<?php
/**
 * MB "Vienas bitas" (Magetrend.com)
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */

namespace Magetrend\PdfTemplates\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Module\Dir\ReverseResolver;
use Magento\Framework\Module\PackageInfoFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magetrend\PdfTemplates\Block\Adminhtml\Config\System\Config\Info;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface as Message;

/**
 * Config observer
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
class Config implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    public $curl;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    public $resourceConfig;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    public $cacheManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Message
     */
    public $message;

    /**
     * @var PackageInfoFactory
     */
    public $packageInfoFactory;

    /**
     * @var ReverseResolver
     */
    public $reverseResolver;


    /**
     * Config constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curl
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Message $message
     * @param PackageInfoFactory $packageInfoFactory
     * @param ReverseResolver $reverseResolver
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\HTTP\Client\CurlFactory $curl,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Message $message,
        \Magento\Framework\Module\PackageInfoFactory $packageInfoFactory,
        \Magento\Framework\Module\Dir\ReverseResolver $reverseResolver
    ) {
        $this->curl = $curl->create();
        $this->resourceConfig = $resourceConfig;
        $this->cacheManager = $cache;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->message = $message;
        $this->packageInfoFactory = $packageInfoFactory;
        $this->reverseResolver = $reverseResolver;
    }

    /**
     * Execute observer
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        switch ($observer->getEvent()->getName()) {
            case 'controller_action_predispatch_adminhtml_system_config_edit':
                return $this->executeLoad($observer);
            case 'admin_system_config_changed_section_pdftemplates':
                return $this->executeSave($observer);
        }
    }

    public function executeLoad(Observer $observer)
    {
        $reqest = $observer->getRequest();
        if ($reqest->getParam('section') != Info::CONFIG_NAMESPACE) {
            return;
        }

        $this->checkVersion();

        if ($this->message->getMessages()->getCount() > 0) {
            return;
        }

        $status = $this->get('L2NvbmZpZy9zdGF0dXM=');
        if (empty($this->getKey()) || (!empty($status) && $status != 1)) {
            $this->message->addErrorMessage(__(base64_decode(
                'VGhlIGxpY2Vuc2Uga2V5IGlzIG5vdCBpbnN0YWxsZWQsIGludmFsaWQgb3IgZXhwaXJlZC4gSW4gb3JkZXIgdG8gdXNlIHRoZSBleHRlbnNpb24sIHlvdSBuZWVkIHRvIG9idGFpbiBhbmQgaW5zdGFsbCBhIG5ldyB2YWxpZCBsaWNlbnNlIGtleS4='
            )));
            return;
        }

        $isActive = $this->scopeConfig->getValue(
            Info::XML_PATH_GENERAL,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        if (!$isActive) {
            $this->message->addNoticeMessage(__(base64_decode(
                'VGhlIGV4dGVuc2lvbiBpcyBkaXNhYmxlZC4gSXQgY2FuIGJlIGVuYWJsZWQgaGVyZTogR2VuZXJhbCBTZXR0aW5ncyA+IElzIEFjdGl2ZSA+IFllcw=='
            )));
        }
    }

    public function executeSave(Observer $observer)
    {
        $response = $this->validateConfig();
        if ($response) {
            $this->save('L291dHB1dC9kX2NvbmZpZw==', json_encode($response['output']));
            $this->save('L2NvbmZpZy9zdGF0dXM=', $response['status']);

            if ($response['status'] == 1) {
                $this->clean();
                return;
            }
        }

        $this->resourceConfig->saveConfig(Info::XML_PATH_GENERAL, 0, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->message->addError(base64_decode('VGhlIGxpY2Vuc2Uga2V5IGlzIG5vdCBpbnN0YWxsZWQsIGludmFsaWQgb3IgZXhwaXJlZC4gSW4gb3JkZXIgdG8gdXNlIHRoZSBleHRlbnNpb24sIHlvdSBuZWVkIHRvIG9idGFpbiBhbmQgaW5zdGFsbCBhIG5ldyB2YWxpZCBsaWNlbnNlIGtleS4='));
        $this->clean();
    }



    /**
     * Get all urls of stores
     * @return array
     */
    public function getUrlArray()
    {
        $stores = $this->storeManager->getStores();
        $urlArray = [];
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $urlArray[] = $this->scopeConfig->getValue(
                    'web/unsecure/base_url',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $store->getCode()
                );
            }
        }
        return $urlArray;
    }

    /**
     * Returns key
     * @return mixed
     */
    public function getKey()
    {
        return $this->get('L2xpY2Vuc2Uva2V5');
    }

    /**
     * @return null|string
     */
    public function getModuleName()
    {
        return $this->reverseResolver->getModuleName(dirname(__FILE__));
    }

    /**
     * @return bool|void
     */
    public function checkVersion()
    {
        $lastUpdate = $this->get('L2NvbmZpZy9sYXN0dXBkYXRl');
        if (!empty($lastUpdate) && time() < $lastUpdate + 86400) {
            return;
        }

        $moduleName = $this->getModuleName();
        $info = $this->packageInfoFactory->create();
        try {
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 1);
            $this->curl->setOption(CURLOPT_TIMEOUT, 3);
            //@codingStandardsIgnoreStart
            $this->curl->get(
                base64_decode('aHR0cHM6Ly93d3cubWFnZXRyZW5kLmNvbS9yZXN0L1YxL2NvbmZpZy9pbmZvLw==').$moduleName.'/'.$info->getVersion($moduleName),
            );

            if ($this->curl->getStatus() != 200) {
                return false;
            }

            $responseBody = $this->curl->getBody();
            $this->save('L291dHB1dC9hX3ZlcnNpb24=', $responseBody);
            $this->save('L2NvbmZpZy9sYXN0dXBkYXRl', time());
            $this->cacheManager->clean(['config']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool|array
     */
    private function validateConfig()
    {
        $moduleName = $this->getModuleName();
        $info = $this->packageInfoFactory->create();
        try {
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 1);
            $this->curl->setOption(CURLOPT_TIMEOUT, 3);
            $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            //@codingStandardsIgnoreStart
            $this->curl->post(
                base64_decode('aHR0cHM6Ly93d3cubWFnZXRyZW5kLmNvbS9yZXN0L1YxL2NvbmZpZy92YWxpZGF0ZS8='),
                json_encode(
                    [
                        'key' => $this->getKey(),
                        'url' => $this->getUrlArray(),
                        'module' => $moduleName,
                    ]
                )
            );

            if ($this->curl->getStatus() != 200) {
                return false;
            }
            //@codingStandardsIgnoreStart
            $response = json_decode($this->curl->getBody(), true);
            return $response[0];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $path
     * @param $value
     */
    public function save($path, $value)
    {
        $this->resourceConfig->saveConfig(
            Info::CONFIG_NAMESPACE . base64_decode($path),
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * @param $path
     * @return mixed
     */
    public function get($path)
    {
        return $this->scopeConfig->getValue(
            Info::CONFIG_NAMESPACE. base64_decode($path),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * Clean cache
     */
    public function clean()
    {
        $this->cacheManager->clean(['config', 'block_html']);
    }
}
