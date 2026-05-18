<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Command;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'letkode:form-schema:install',
    description: 'Run letkode/form-schema migrations',
)]
final class LetkodeFormSchemaInstallCommand extends Command
{
    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('LetkodeFormSchema — running migrations');

        try {
            $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();
            $statusCalculator = $this->dependencyFactory->getMigrationStatusCalculator();
            $migrator = $this->dependencyFactory->getMigrator();

            $storage = $this->dependencyFactory->getMetadataStorage();
            $storage->ensureInitialized();

            $availableMigrations = $this->dependencyFactory->getMigrationRepository()->getMigrations();

            $bundleMigrations = array_filter(
                $availableMigrations->getItems(),
                static fn ($migration) => str_starts_with(
                    $migration->getVersion()->__toString(),
                    'Letkode\\FormSchema\\Migrations\\'
                ),
            );

            if ([] === $bundleMigrations) {
                $io->success('No letkode/form-schema migrations to run.');

                return Command::SUCCESS;
            }

            $plan = $planCalculator->getPlanForVersions(
                array_map(static fn ($m) => $m->getVersion(), $bundleMigrations),
                Direction::UP,
            );

            if (0 === $plan->count()) {
                $io->success('letkode/form-schema migrations are already up to date.');

                return Command::SUCCESS;
            }

            $migratorConfig = new MigratorConfiguration()->setAllOrNothing(true);
            $migrator->migrate($plan, $migratorConfig);

            $io->success(\sprintf('Successfully executed %d letkode/form-schema migration(s).', $plan->count()));
        } catch (\Throwable $e) {
            $io->error(\sprintf('Migration failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
