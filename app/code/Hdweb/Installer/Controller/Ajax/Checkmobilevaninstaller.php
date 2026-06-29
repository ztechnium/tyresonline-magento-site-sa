<?php

declare(strict_types=1);

namespace Hdweb\Installer\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Checkmobilevaninstaller extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Json $json
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $message = 'success';

        try {
            $payload = $this->json->unserialize((string) $this->getRequest()->getContent());
            $pickupDate = trim((string) ($payload['pickup_date'] ?? ''));

            if ($pickupDate === '') {
                $message = 'fail';
            } else {
                $date = \DateTime::createFromFormat('m/d/Y', $pickupDate)
                    ?: \DateTime::createFromFormat('m/d/y', $pickupDate)
                    ?: \DateTime::createFromFormat('d-m-Y', $pickupDate);

                if (!$date || (int) $date->format('w') === 0) {
                    $message = 'fail';
                }
            }
        } catch (\Throwable) {
            $message = 'fail';
        }

        return $this->resultJsonFactory->create()->setData(['message' => $message]);
    }
}
