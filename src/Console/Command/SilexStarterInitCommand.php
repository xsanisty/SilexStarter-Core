<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class SilexStarterInitCommand extends Command
{
    protected $app;

    protected function configure()
    {
        $this
            ->setName('silexstarter:init')
            ->setDescription('Initialize the SilexStarter project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexStarter();
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('This action will initiate the SilexStarter app from the scratch, do you want to continue? [Y/n]');

        if ($helper->ask($input, $output, $question)) {
            $dbConfig = $this->populateDatabaseInfo($input, $output);

            try {
                $app['db']->addConnection($dbConfig);
                $app['db']->connection()->getDatabaseName();

            } catch (\Exception $e) {

                $output->writeln('<error>Error occured with message:</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');

                if ($helper->ask($input, $output, new ConfirmationQuestion('Do you want to retry? [Y/n]'))) {
                    $this->execute($input, $output);
                }

            }
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

        $output->writeln('<info>This first step will try to initiate database connection</info>');

        $dbType = $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('What kind of database server do you use? [mysql]', ['mysql', 'sqlite', 'pgsql', 'mssql'], 0)
        );

        if ($dbType == 'sqlite') {
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

        $dbHost = $helper->ask(
            $input,
            $output,
            new Question('What is you database server address? [localhost] ', 'localhost')
        );

        $dbPort = $helper->ask(
            $input,
            $output,
            new Question('What is the database port? ', '')
        );

        $dbName = $helper->ask(
            $input,
            $output,
            new Question('What is the database name? ')
        );

        $dbUser = $helper->ask(
            $input,
            $output,
            new Question('What is the username? [root] ', 'root')
        );

        $dbPass = $helper->ask(
            $input,
            $output,
            (new Question('What is the password? ', ''))->setHidden(true)
        );

        return [
            'driver'    => $dbType,
            'host'      => $dbHost,
            'database'  => $dbName,
            'username'  => $dbUser,
            'password'  => $dbPass,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'schema'    => 'public',
        ];
    }
}
