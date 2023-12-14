<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\Message;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CleanUpGuestsHandler
{
    private EntityRepository $customerRepository;

    public function __construct(EntityRepository $customerRepository) {
        $this->customerRepository = $customerRepository;
    }

    public function __invoke(CleanUpGuestsMessage $message): void
    {
        if (empty($message->getGuestIds())) {
            return;
        }

        $this->customerRepository->delete($message->getGuestIds(), $message->getContext());
    }
}
