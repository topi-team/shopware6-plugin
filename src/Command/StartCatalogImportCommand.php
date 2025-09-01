<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TopiPaymentIntegration\Action\SyncCatalogAction;
use TopiPaymentIntegration\CatalogSyncContext;
use TopiPaymentIntegration\Util\ContextHelper;

#[AsCommand('topi:catalog-sync:start', 'Start catalog sync to topi', ['t:cs:s'])]
class StartCatalogImportCommand extends Command
{
    public function __construct(
        private readonly SyncCatalogAction $syncCatalogAction,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->syncCatalogAction->execute(ContextHelper::createCliContext(), new CatalogSyncContext());
        $io->success('Catalog Sync runs in background');

        return 0;
    }
}
