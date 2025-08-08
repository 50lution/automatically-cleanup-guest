<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\ScheduledTask;

use Psr\Log\LoggerInterface;
use S50lution\AutomaticallyCleanUpGuest\Service\GuestCleanUpService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: AutomaticallyCleanUpGuestTask::class)]
class AutomaticallyCleanUpGuestHandler extends ScheduledTaskHandler
{

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly GuestCleanUpService $guestCleanUpService
    )
    {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();

        $this->guestCleanUpService->removeGuestCustomers($context, true);
    }
}
