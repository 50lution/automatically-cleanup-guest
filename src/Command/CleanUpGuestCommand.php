<?php declare(strict_types=1);

namespace S50lution\AutomaticallyCleanUpGuest\Command;

use S50lution\AutomaticallyCleanUpGuest\Service\GuestCleanUpService;
use Shopware\Core\Content\Media\MediaException;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpGuestCommand extends Command
{
    private ShopwareStyle $io;
    private ?int $batchSize = null;
    private bool $isAsync;

    private GuestCleanUpService $guestCleanUpService;

    public function __construct(GuestCleanUpService $guestCleanUpService)
    {
        parent::__construct();

        $this->guestCleanUpService = $guestCleanUpService;
    }

    protected static $defaultName = 's50lution:clean-up-guest';

    protected function configure(): void
    {
        $this->setDescription('Automatically remove guest customers');

        $this->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Number of entities per iteration', '100')
            ->addOption(
                'async',
                'a',
                InputOption::VALUE_NONE,
                'Queue up batch jobs instead of deleting guests directly'
            )
        ;
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new ShopwareStyle($input, $output);
        $this->initializeCommand($input);

        $context = Context::createDefaultContext();

        $removedIds = $this->guestCleanUpService->removeGuestCustomers($context, $this->isAsync, $this->batchSize, $this->io);

        $this->io->success(sprintf('Removed %d guest customers.', count($removedIds)));

        // Exit code 0 for success
        return 0;
    }

    private function initializeCommand(InputInterface $input): void
    {
        $this->batchSize = $this->getBatchSizeFromInput($input);
        $this->isAsync = $input->getOption('async');
    }

    private function getBatchSizeFromInput(InputInterface $input): int
    {
        $rawInput = $input->getOption('batch-size');

        if (!is_numeric($rawInput)) {
            throw MediaException::invalidBatchSize();
        }

        return (int) $rawInput;
    }
}
