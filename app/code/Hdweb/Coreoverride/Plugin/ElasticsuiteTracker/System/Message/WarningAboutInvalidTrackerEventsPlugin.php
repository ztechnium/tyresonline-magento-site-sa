<?php
declare(strict_types=1);

namespace Hdweb\Coreoverride\Plugin\ElasticsuiteTracker\System\Message;

use Smile\ElasticsuiteTracker\Model\System\Message\WarningAboutInvalidTrackerEvents;

class WarningAboutInvalidTrackerEventsPlugin
{
    public function aroundIsDisplayed(WarningAboutInvalidTrackerEvents $subject, callable $proceed): bool
    {
        try {
            return $proceed();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function aroundGetText(WarningAboutInvalidTrackerEvents $subject, callable $proceed): string
    {
        try {
            return $proceed();
        } catch (\Throwable $e) {
            return '';
        }
    }
}
