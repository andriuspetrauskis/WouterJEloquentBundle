<?php

/*
 * This file is part of the WouterJEloquentBundle package.
 *
 * (c) 2017 Wouter de Jong
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WouterJ\EloquentBundle\Command;

use Illuminate\Database\DatabaseManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WouterJ\EloquentBundle\Migrations\Migrator;

/**
 * @final
 * @internal
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class MigrateFreshCommand extends BaseMigrateCommand
{
    /** @var DatabaseManager */
    private $db;

    public function __construct(DatabaseManager $db, Migrator $migrator, $migrationPath, $kernelEnv)
    {
        parent::__construct($migrator, $migrationPath, $kernelEnv);

        $this->db = $db;
    }

    protected function configure()
    {
        $this->setName('eloquent:migrate:fresh')
            ->setDescription('Drop all tables and re-run all migrations.')
            ->setDefinition([
                new InputOption('database', null, InputOption::VALUE_REQUIRED, 'The database connection to use.'),
                new InputOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run in production.'),
                new InputOption('path', null, InputOption::VALUE_REQUIRED, 'The path of migrations files to be executed'),
                new InputOption('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'),
                new InputOption('seeder', null, InputOption::VALUE_REQUIRED, 'The class name of the root seeder.'),
            ])
        ;
    }

    protected function execute(InputInterface $i, OutputInterface $o)
    {
        $force = $i->getOption('force');
        if (!$force && !$this->askConfirmationInProd($i, $o)) {
            return;
        }

        $database = $i->getOption('database');
        $this->dropAllTables($database);

        $o->writeln('Dropped all tables successfully.');

        $this->call($o, 'eloquent:migrate', [
            '--database' => $database,
            '--force'    => $force,
            '--path'     => $i->getOption('path'),
        ]);

        if ($i->getOption('seed') || $i->getOption('seeder')) {
            $this->call($o, 'eloquent:seed', [
                '--database' => $database,
                '--class'    => $i->getOption('seeder') ?: 'DatabaseSeeder',
                '--force'    => $force,
            ]);
        }
    }

    private function dropAllTables($database)
    {
        $this->db->connection($database)
            ->getSchemaBuilder()
            ->dropAllTables();
    }
}
