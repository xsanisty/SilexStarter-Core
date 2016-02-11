<?php

namespace SilexStarter\Console\Command;

use Exception;
use Illuminate\Suuport\Str;
use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCreateCommand extends Command
{
    protected function configure()
    {
        $this->setName('migration:create')
            ->setDescription('Create migration file for app or specific module')
            ->addArgument(
                'class-name',
                InputArgument::REQUIRED,
                'The migration class name'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'Create migration for specific module'
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_REQUIRED,
                'Specify table name for the migration'
            )
            ->addOption(
                'fields',
                'f',
                InputOption::VALUE_REQUIRED,
                'Define fields for the table [field_one:increments|field_two:integer|field_three:enum:val1,val2|field_for:float:8,2]',
                'id:increments,name:string'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app        = $this->getSilexStarter();
        $migrator   = $app['migrator'];
        $moduleMgr  = $app['module'];

        $tableName  = $input->getOption('table') ? $input->getOption('table') : 'table_name';
        $fields     = $input->getOption('fields');
        $tableFields= $this->buildTableDefinition($fields);

        $moduleId   = $input->getOption('module');
        $module     = $moduleId ? $moduleMgr->getModule($moduleId) : null;
        $moduleNs   = $module   ? "\nnamespace " . $moduleMgr->getModuleNamespace($moduleId) . "\Migration;\n" : '';

        if ($module && !$module->getResources()->migrations) {
            throw new Exception("Module '$moduleId' doesn't have migrations directory configured");
        }

        $className  = $input->getArgument('class-name');
        $timestamp  = date('Ymd_His_');
        $fileName   = "{$timestamp}{$className}.php";
        $classCode  = file_get_contents(__DIR__.'/stubs/migration.stub');
        $classCode  = str_replace(
            ['{{classNamespace}}', '{{className}}', '{{tableName}}', '{{tableDefinition}}'],
            [$moduleNs, $className, $tableName, $tableFields],
            $classCode
        );

        $targetDir  = ($moduleId)
                    ? $moduleMgr->getModulePath($moduleId) . $module->getResources()->migrations
                    : $migrator->getMigrationPath();

        $app['filesystem']->dumpFile($targetDir . '/' . $fileName, $classCode);

        $output->writeln('<info> - Migration created at ' . $targetDir . '/' . $fileName . '</info>');
    }

    /**
     * explode fields string definition into array.
     *
     * @param  string $fields fields string in format field_name[:type[,field_name[:type]]
     * @return array
     */
    protected function explodeFields($fields)
    {
        $fields = explode('+', $fields);

        foreach ($fields as $key => $field) {
            $fieldStruct = explode(':', $field);

            if (trim($fieldStruct[0])) {
                $fields[$key] = [
                    'name'  => trim($fieldStruct[0]),
                    'type'  => isset($fieldStruct[1]) ? Str::camel(trim($fieldStruct[1])) : 'text',
                    'option'=> isset($fieldStruct[2]) ? $fieldStruct[2] : null
                ];
            }
        }

        return $fields;
    }

    /**
     * Create table definition for the migration.
     */
    protected function buildTableDefinition($fields)
    {
        $fields      = $this->explodeFields($fields);
        $tableFields = array_map(
            function ($field) {
                switch ($field['type']) {
                    case 'char':
                    case 'string':
                        $param  = ', ' . $field['option'] ? $field['option'] : '255';
                        break;
                    case 'enum':
                        $enum   = explode(',', $field['option']);

                        foreach ($enum as $key => $value) {
                            $enum[$key] = '"' . addslashes($value) . '"';
                        }

                        $enum   = implode(', ', $enum);
                        $param  = $field['option'] ? ', [' . $enum . ']' : '';
                        break;
                    case 'float':
                    case 'decimal':
                    case 'double':
                        $param  = $field['option'] ? ', ' . $field['option'] : '';
                        break;
                    default:
                        $param  = '';
                        break;
                }

                return '$table->' . $field['type'] . '(\'' . $field['name'] . '\''. $param .');';
            },
            $fields
        );

        return implode("\n" . str_repeat(' ', 16), $tableFields);
    }
}
