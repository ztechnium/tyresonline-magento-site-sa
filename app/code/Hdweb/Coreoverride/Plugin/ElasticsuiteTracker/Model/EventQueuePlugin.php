<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\ElasticsuiteTracker\Model;

use Smile\ElasticsuiteTracker\Model\EventQueue;

class EventQueuePlugin
{
    public function aroundGetInvalidEventsCount(EventQueue $subject, callable $proceed): int
    {
        try {
            return (int) $proceed();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
