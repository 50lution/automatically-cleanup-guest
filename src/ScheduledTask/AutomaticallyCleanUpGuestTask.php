<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class AutomaticallyCleanUpGuestTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 's50lution.clean_up_guest';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // 1 hour
    }
}
