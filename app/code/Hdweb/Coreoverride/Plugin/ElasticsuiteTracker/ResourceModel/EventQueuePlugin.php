<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\ElasticsuiteTracker\ResourceModel;

use Hdweb\Coreoverride\Model\ElasticsuiteTracker\SchemaGuard;
use Smile\ElasticsuiteTracker\Model\ResourceModel\EventQueue;

class EventQueuePlugin
{
    public function __construct(
        private readonly SchemaGuard $schemaGuard
    ) {
    }

    public function aroundGetEvents(EventQueue $subject, callable $proceed, $limit = null): array
    {
        if (!$this->schemaGuard->hasIsInvalidColumn()) {
            return [];
        }

        return $proceed($limit);
    }

    public function aroundGetInvalidEventsCount(EventQueue $subject, callable $proceed): int
    {
        if (!$this->schemaGuard->hasIsInvalidColumn()) {
            return 0;
        }

        return (int) $proceed();
    }

    public function aroundGetPendingEventsCount(EventQueue $subject, callable $proceed, $hours = 24): int
    {
        if (!$this->schemaGuard->hasIsInvalidColumn()) {
            return 0;
        }

        return (int) $proceed($hours);
    }

    public function aroundFlagInvalidEvents(EventQueue $subject, callable $proceed, array $eventIds): void
    {
        if (!$this->schemaGuard->hasIsInvalidColumn()) {
            return;
        }

        $proceed($eventIds);
    }

    public function aroundPurgeInvalidEvents(EventQueue $subject, callable $proceed, $delay = 3, $limit = null): void
    {
        if (!$this->schemaGuard->hasIsInvalidColumn()) {
            return;
        }

        $proceed($delay, $limit);
    }
}
