<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TopiPaymentIntegration\Action\SyncCatalogAction;
use TopiPaymentIntegration\CatalogSyncContext;
use TopiPaymentIntegration\Util\ContextHelper;

#[AsCommand('topi:catalog-sync:complete', 'Sync the catalog to topi', ['t:cs:c'])]
class CompleteCatalogImportCommand extends Command
{
    public function __construct(
        private readonly SyncCatalogAction $syncCatalogAction,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Override memory-limit')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Override batch-size')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($memoryLimit = $input->getOption('memory-limit')) {
            ini_set('memory_limit', $memoryLimit);
        }

        if ($batchSize = $input->getOption('batch-size')) {
            $batchSize = (int) $batchSize;
            if ($batchSize < 1) {
                throw new \InvalidArgumentException('Batch size must be greater than 0');
            }
        }

        $io = new SymfonyStyle($input, $output);
        $syncContext = new CatalogSyncContext(
            false,
            $batchSize,
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
