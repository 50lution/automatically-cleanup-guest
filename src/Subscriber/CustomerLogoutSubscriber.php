<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\Subscriber;

use S50lution\AutomaticallyCleanUpGuest\Service\GuestCleanUpService;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerLogoutSubscriber implements EventSubscriberInterface
{
    private GuestCleanUpService $guestCleanUpService;

    public function __construct(GuestCleanUpService $guestCleanUpService)
    {
        $this->guestCleanUpService = $guestCleanUpService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLogoutEvent::class => 'onCustomerLogout',
        ];
    }

    public function onCustomerLogout(CustomerLogoutEvent $event): void
    {
        if (!$event->getCustomer()->getGuest()) {
            // Is not a guest, so we don't need to do anything
            return;
        }

        $this->guestCleanUpService->removeGuest($event->getCustomer(), $event->getContext());
    }
}
