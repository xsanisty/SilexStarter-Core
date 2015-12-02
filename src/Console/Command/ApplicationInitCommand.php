<?php

namespace SilexStarter\Console\Command;

use Exception;
use InvalidArgumentException;
use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class ApplicationInitCommand extends Command
{
    protected $app;

    protected function configure()
    {
        $this
            ->setName('app:init')
            ->setDescription('Initialize the SilexStarter Application');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexStarter();
        $helper     = $this->getHelper('question');
        $question   = new ConfirmationQuestion('<info>This action will initiate the SilexStarter app from the scratch, do you want to continue?</info> <comment>[Y/n]</comment>');

        if ($helper->ask($input, $output, $question)) {
            $dbConfig = $this->populateDatabaseInfo($input, $output);
            $database = $app['capsule'];

            /**
             * Try initiate  database connection
             */
            try {
                $database->addConnection($dbConfig);
                $database->connection()->getDatabaseName();

                if (!$dbConfig['database'] || $dbConfig['database'] !== $database->connection()->getDatabaseName()) {
                    throw new InvalidArgumentException("Database is invalid");
                }

                if ($helper->ask($input, $output, new ConfirmationQuestion('<info>Database is connected, write the configuration?</info> <comment>[Y/n]</comment>'))) {
                    $app['config']->set('database.default', $dbConfig['driver']);
                    $app['config']->set('database.connections.'.$dbConfig['driver'], $dbConfig);
                    $app['config']->save('database');
                } else {
                    return;
                }

            } catch (Exception $e) {

                $output->writeln('<error>Error occured with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');

                if ($helper->ask($input, $output, new ConfirmationQuestion('Do you want to retry to reconfigure the database? <comment>[Y/n]</comment>'))) {
                    $this->execute($input, $output);
                } else {
                    return;
                }
            }

            /**
             * Try to migrate database structure
             */
            try {
                $output->writeln("\n<info>Since database is now connected, let's migrate the database structure</info>");

                $this->migrateDatabase($output);
            } catch (Exception $e) {

                $output->writeln('<error>Error occured while trying to migrate database structure:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');

                return ;
            }

            /**
             * Try to add new user
             */
            try {
                $output->writeln("\n<info>Since database table is now created, let's add new user</info>");

                $this->addDefaultUser($input, $output);
            } catch (Exception $e) {
                $output->writeln('<error>Error occured with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');

                if ($helper->ask($input, $output, new ConfirmationQuestion('Do you want to retry to add new user? <comment>[Y/n]</comment>'))) {
                    $this->addDefaultUser($input, $output);
                } else {
                    return;
                }
            }

            /**
             * Try to publish all assets
             */
            try {
                $output->writeln("\n<comment>Publishing assets...</comment>");

                $this->publishAssets($output);
            } catch (Exception $e) {
                $output->writeln('<error>Error occured with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');

                if ($helper->ask($input, $output, new ConfirmationQuestion('Do you want to retry to publish assets? <comment>[Y/n]</comment>'))) {
                    $this->publishAssets($output);
                }
            }

            $output->writeln("\n<info>Your application is now up, try to access it via your browser at ".$app['url_generator']->generate('admin.home')."</info>");

        }
    }

    /**
     * Get information for connecting to the database.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return array
     */
    protected function populateDatabaseInfo(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $output->writeln("<comment>This first step will try to initiate database connection\n</comment>");

        $dbType = $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('What kind of database server do you use? <comment>[mysql]</comment>', ['mysql', 'sqlite', 'pgsql', 'mssql'], 0)
        );

        if ('sqlite' == $dbType) {
            $dbName = $helper->ask(
                $input,
                $output,
                new Question('Where is the database location? ')
            );

            return [
                'driver'    => $dbType,
                'database'  => $dbName,
            ];
        }

        $dbHost = $helper->ask($input, $output, new Question('What is you database server address? <comment>[localhost]</comment> ', 'localhost'));
        $dbPort = $helper->ask($input, $output, new Question('What is the database port? ', ''));
        $dbName = $helper->ask($input, $output, new Question('What is the database name? ', ''));
        $dbUser = $helper->ask($input, $output, new Question('What is the username? <comment>[root]</comment> ', 'root'));
        $dbPass = $helper->ask($input, $output, (new Question('What is the password? ', ''))->setHidden(true));

        $config = [
            'driver'    => $dbType,
            'host'      => $dbHost,
            'database'  => $dbName,
            'username'  => $dbUser,
            'password'  => $dbPass,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ];

        if ('pgsql' == $dbType) {
            $dbSchema = $helper->ask($input, $output, new Question('What is you database schema? <comment>[public]</comment> ', 'public'));

            $config['schema'] = $dbSchema;
        }

        return $config;
    }

    /**
     * Add default user to the application
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function addDefaultUser(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('user:create');
        $command->execute($input, $output);
    }

    /**
     * Publishing default module assets
     *
     * @param  OutputInterface $output
     */
    protected function publishAssets(OutputInterface $output)
    {
        $command = $this->getApplication()->find('module:publish-asset');
        $input   = new ArrayInput(
            [
                'command'   => 'module:publish-asset',
                'module'    => ''
            ]
        );

        $command->run($input, $output);
    }

    /**
     * Migrating default database structure
     *
     * @param  OutputInterface $output
     */
    protected function migrateDatabase(OutputInterface $output)
    {
        $command = $this->getApplication()->find('migration:migrate');
        $input   = new ArrayInput(
            [
                'command'   => 'migration:migrate'
            ]
        );

        $command->run($input, $output);
    }
}
