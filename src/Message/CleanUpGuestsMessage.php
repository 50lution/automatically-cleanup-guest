<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\Message;

use Shopware\Core\Framework\Context;

class CleanUpGuestsMessage
{
    private array $guestIds = [];

    private Context $context;

    public function __construct(array $guestIds, Context $context)
    {
        $this->guestIds = $guestIds;
        $this->context = $context;
    }

    public function getGuestIds(): array
    {
        return $this->guestIds;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
