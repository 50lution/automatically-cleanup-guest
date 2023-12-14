<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\Service;

use S50lution\AutomaticallyCleanUpGuest\Message\CleanUpGuestsMessage;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\MessageBusInterface;

class GuestCleanUpService
{
    final public const DELETE_CUSTOMERS_BATCH_SIZE = 100;

    private ?ShopwareStyle $io;

    private EntityRepository $customerRepository;
    private SystemConfigService $systemConfigService;
    private MessageBusInterface $messageBus;

    public function __construct(
        EntityRepository $customerRepository,
        SystemConfigService $systemConfigService,
        MessageBusInterface $messageBus
    ) {
        $this->customerRepository = $customerRepository;
        $this->systemConfigService = $systemConfigService;
        $this->messageBus = $messageBus;
    }

    public function removeGuest(CustomerEntity $customer, Context $context): void
    {
        if (!$customer->getGuest()) {
           return;
        }

        $this->customerRepository->delete([['id' => $customer->getId()]], $context);
    }

    public function removeGuestCustomers(
        Context $context,
        bool $isAsync = false,
        int $batchSize = self::DELETE_CUSTOMERS_BATCH_SIZE,
        ?ShopwareStyle $io = null
    ): array
    {
        $this->io = $io;

        $maxLifeTime = $this->getUnusedGuestCustomerLifeTime();
        if (!$maxLifeTime) {
            if ($this->io instanceof ShopwareStyle) {
                $this->io->comment('No max life time set. Skipping...');
            }

            return [];
        }

        $criteria = $this->getUnusedCustomerCriteria($maxLifeTime);
        $criteria->setLimit($batchSize);

        $customerIterator = new RepositoryIterator($this->customerRepository, $context, $criteria);
        if (!$isAsync) {
            $this->removeSynchronous($customerIterator, $context);
        } else {
            $this->removeAsynchronous($customerIterator, $context);
        }

        $ids = $this->customerRepository->searchIds($criteria, $context)->getIds();
        if (empty($ids)) {
            return [];
        }

        $ids = \array_values(\array_map(static fn (string $id) => ['id' => $id], $ids));

        $this->customerRepository->delete($ids, $context);

        return $ids;
    }

    private function removeSynchronous(RepositoryIterator $customerIterator, Context $context): void
    {
        $totalCount = $customerIterator->getTotal();
        if ($this->io instanceof ShopwareStyle) {
            if ($totalCount === 0) {
                $this->io->info(sprintf('No guest customers found with created_at less than %s. This time based on core.loginRegistration.unusedGuestCustomerLifetime. Skipping...', $this->getUnusedGuestCustomerLifeTime()->format(Defaults::STORAGE_DATE_TIME_FORMAT)));
                return;
            }

            $this->io->comment(sprintf('Removing for %d guest. This may take some time...', $totalCount));
            $this->io->progressStart($totalCount);
        }

        $this->removeGuests($customerIterator, $context);

        if ($this->io instanceof ShopwareStyle) {
            $this->io->progressFinish();
        }
    }

    private function removeAsynchronous(RepositoryIterator $customerIterator, Context $context): void
    {
        if ($this->io instanceof ShopwareStyle) {
            $this->io->comment('Generating batch jobs...');
        }

        $batchCount = 0;
        while (($ids = $customerIterator->fetchIds()) !== null) {
            if (!empty($ids) && is_array($ids)) {
                $ids = \array_values(\array_map(static fn (string $id) => ['id' => $id], $ids));
                $msg = new CleanUpGuestsMessage($ids, $context);

                $this->messageBus->dispatch($msg);
                ++$batchCount;
            }
        }

        if ($this->io instanceof ShopwareStyle) {
            $this->io->success(sprintf('Generated %d Batch jobs!', $batchCount));
        }
    }

    private function removeGuests(RepositoryIterator $customerIterator, Context $context): void
    {
        while (($ids = $customerIterator->fetchIds()) !== null) {
            if (!empty($ids) && is_array($ids)) {
                $ids = \array_values(\array_map(static fn (string $id) => ['id' => $id], $ids));
                $this->customerRepository->delete($ids, $context);

                $this->io->progressAdvance(count($ids));
            }
        }
    }

    private function getUnusedCustomerCriteria(\DateTime $maxLifeTime): Criteria
    {
        $criteria = new Criteria();
        $criteria->setOffset(0);
        $criteria->addFilter(
            new AndFilter(
                [
                    new EqualsFilter('guest', true),
                    new RangeFilter(
                        'createdAt',
                        [
                            RangeFilter::LTE => $maxLifeTime->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                        ]
                    ),
                ]
            )
        );

        return $criteria;
    }

    private function getUnusedGuestCustomerLifeTime(): ?\DateTime
    {
        $maxLifeTime = $this->systemConfigService->getInt(
            'core.loginRegistration.unusedGuestCustomerLifetime'
        );

        if ($maxLifeTime <= 0) {
            return null;
        }

        return new \DateTime(\sprintf('- %d seconds', $maxLifeTime));
    }
}
