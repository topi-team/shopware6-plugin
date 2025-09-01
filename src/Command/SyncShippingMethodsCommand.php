<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TopiPaymentIntegration\Action\CreateShippingMethodsAction;
use TopiPaymentIntegration\Util\ContextHelper;

#[AsCommand('topi:shipping-methods:sync', 'Sync shipping methods to topi', ['t:sm:s'])]
class SyncShippingMethodsCommand extends Command
{
    public function __construct(
        private readonly CreateShippingMethodsAction $createShippingMethodsAction,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->createShippingMethodsAction->execute(ContextHelper::createCliContext());
        $io->success('Synced shipping methods');

        return 0;
    }
}
