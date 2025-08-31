<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Command;

use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Util\ContextHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TopiPaymentIntegration\Action\SyncCatalogAction;
use TopiPaymentIntegration\CatalogSyncContext;

#[AsCommand('topi:catalog-sync:complete', 'Sync the catalog to topi', ['t:cs:c'])]
class CompleteCatalogImportCommand extends Command
{
    public function __construct(
        private readonly SyncCatalogAction $syncCatalogAction,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $syncContext = new CatalogSyncContext(
            false,
            $io->progressStart(...),
            $io->progressAdvance(...),
            static function (string $message) use ($io) {
                $io->progressFinish();
                $io->success($message);
            },
            static function (\Exception $exception) use ($io) {
                $io->error($exception->getMessage());
            },
        );

        $this->syncCatalogAction->execute(ContextHelper::createCliContext(), $syncContext);

        return 0;
    }
}
