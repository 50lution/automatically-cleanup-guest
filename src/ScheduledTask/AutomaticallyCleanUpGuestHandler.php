<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\ScheduledTask;

use S50lution\AutomaticallyCleanUpGuest\Service\GuestCleanUpService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class AutomaticallyCleanUpGuestHandler extends ScheduledTaskHandler
{
    private GuestCleanUpService $guestCleanUpService;

    public function __construct(EntityRepository $scheduledTaskRepository, GuestCleanUpService $guestCleanUpService)
    {
        parent::__construct($scheduledTaskRepository);

        $this->guestCleanUpService = $guestCleanUpService;
    }

    public static function getHandledMessages(): iterable
    {
        return [AutomaticallyCleanUpGuestTask::class];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();

        $this->guestCleanUpService->removeGuestCustomers($context, true);
    }
}
