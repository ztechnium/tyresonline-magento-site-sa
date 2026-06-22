<?php

namespace MageWorx\OrderEditor\Api;

/**
 * Send webhook
 */
interface WebhookSenderInterface
{
    /**
     * @param Data\WebhookQueueEntityInterface $webhookQueueEntity
     * @return bool
     */
    public function send(\MageWorx\OrderEditor\Api\Data\WebhookQueueEntityInterface $webhookQueueEntity): bool;
}
