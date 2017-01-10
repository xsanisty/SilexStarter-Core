<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Suuport\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Exception;

class GenerateScaffoldingCommand extends Command
{
    protected $input;
    protected $output;
    protected $app;
    protected $mode;
    protected $module;
    protected $moduleManager;
    protected $filesystem;
    protected $entity;
    protected $basePath;
    protected $resources;
    protected $generated = [];
    protected $fields = [];

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('scaffold')
            ->setDescription('Generate basic crud scaffolding')
            ->addArgument(
                'entity-name',
                InputArgument::REQUIRED,
                'The table or entity name as the base of the scaffolding'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'Create scaffolding for specific module'
            )
            ->addOption(
                'migrate',
                'g',
                InputOption::VALUE_NONE,
                'Run the migration after all file is generated'
            )
            ->addOption(
                'mode',
                'd',
                InputOption::VALUE_REQUIRED,
                'Mode can be "standard" [generate standard web form], "ajax" [generate ajax based form], or "api" [generate api endpoint]',
                'standard'
            )
            ->addOption(
                'fields',
                'f',
                InputOption::VALUE_REQUIRED,
                'Define fields for the table using the following format "field_name:field_type[:options][|anothe_field:field_type[:options]].
                Example: field_one:increments|field_two:integer|field_three:enum:val1,val2|field_four:float:8,2',
                'id:increments|name:string'
            );
    }

    /**
     * Execute the command, start the execution stack to create the scaffolding.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input        = $input;
        $this->output       = $output;
        $this->app          = $this->getSilexStarter();
        $this->filesystem   = $this->app['filesystem'];
        $this->moduleManager= $this->app['module'];
        $this->module       = $input->getOption('module');
        $this->entity       = $input->getArgument('entity-name');
        $this->mode         = $input->getOption('mode');
        $this->fields       = $this->getFields();

        if ($this->module) {
            $this->basePath = $this->moduleManager->getModulePath($this->module);
            $this->resources= $this->moduleManager->getModule($this->module)->getResources();
        } else {
            $this->basePath = $this->app['path.root'];
        }

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/stubs', 'stubs');

        $this->generateStubList();
        $this->generateMigration();
        $this->generateModel();
        $this->generateEntity();
        $this->generateRepositoryInterface();
        $this->generateRepository();
        $this->generateRepositoryServiceProvider();
        $this->generateController();
        $this->generateTemplate();
        $this->registerServiceProvider();
        $this->appendRoute();

        if ($input->getOption('migrate')) {
            $this->runMigration();
        }

    }

    protected function getFields()
    {
        $fields = explode('|', $this->input->getOption('fields'));

        foreach ($fields as $key => $field) {
            $fieldStruct = explode(':', $field);
            $fieldName   = trim($fieldStruct[0]);

            if ($fieldName) {
                $fields[$key] = [
                    'name'          => $fieldName,
                    'type'          => isset($fieldStruct[1]) ? $fieldStruct[1] : 'text',
                    'option'        => isset($fieldStruct[2]) ? $fieldStruct[2] : '',
                    'name_camel'    => Str::camel($fieldName),
                    'name_studly'   => Str::studly($fieldName),
                ];
            }
        }

        return $fields;
    }

    protected function generateStubList()
    {
        $mode           = $this->mode;
        $basePath       = $this->basePath;
        $baseClassName  = Str::studly($this->entity);
        $baseNamespace  = $this->module ? $this->moduleManager->getModuleNamespace($this->module) : 'App';

        if ($this->module) {
            $this->generated = [
                'entity' => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'Entity/' . $baseClassName . '.php',
                    'namespace' => $baseNamespace . '\\Entity' ,
                    'fqcn'      => $baseNamespace . '\\Entity\\' . $baseClassName ,
                    'template'  => '@stubs/entity.stub',
                ],
                'model'  => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'Model/' . $baseClassName . '.php',
                    'namespace' => $baseNamespace . '\\Model' ,
                    'fqcn'      => $baseNamespace . '\\Model\\' . $baseClassName ,
                    'template'  => '@stubs/model.stub',
                ],
                'controller' => [
                    'class'     => $baseClassName . 'Controller',
                    'file_path' => $basePath . $this->resources->controllers . '/' . $baseClassName . 'Controller.php',
                    'namespace' => $baseNamespace . '\\' . $this->resources->controllers,
                    'fqcn'      => $baseNamespace . '\\' . $this->resources->controllers .'\\' . $baseClassName . 'Controller',
                    'template'  => '@stubs/' . $mode . '/controller.stub',
                ],
                'repository_interface' => [
                    'class'     => $baseClassName . 'RepositoryInterface',
                    'file_path' => $basePath . 'Contracts/' . $baseClassName . 'RepositoryInterface.php',
                    'namespace' => $baseNamespace . '\\Contracts',
                    'fqcn'      => $baseNamespace . '\\Contracts\\' . $baseClassName . 'RepositoryInterface',
                    'template'  => '@stubs/' . $mode . '/repositoryInterface.stub',
                ],
                'repository'  => [
                    'class'     => $baseClassName . 'Repository',
                    'file_path' => $basePath . 'Repository' . '/' . $baseClassName . 'Repository.php',
                    'namespace' => $baseNamespace . '\\Repository',
                    'fqcn'      => $baseNamespace . '\\Repository\\' . $baseClassName . 'Repository',
                    'template'  => '@stubs/' . $mode . '/repository.stub',
                ],
                'repository_service_provider' => [
                    'class'     => $baseClassName . 'RepositoryServiceProvider',
                    'file_path' => $basePath . 'Provider' . '/' . $baseClassName . 'RepositoryServiceProvider.php',
                    'namespace' => $baseNamespace . '\\Provider',
                    'fqcn'      => $baseNamespace . '\\Provider\\' . $baseClassName . 'RepositoryServiceProvider',
                    'template'  => '@stubs/repositoryServiceProvider.stub',
                ],
                'template' => [
                    'dir_path'      => $this->moduleManager->getTemplatePath($this->module) . '/' . $this->entity,
                    'relative_path' => '@' . $this->module . '/' . $this->entity,
                    'stub_dir'      => __DIR__ . '/stubs/' . $mode . '/template/'
                ],
                'asset' => [
                    'public_path'   => $this->moduleManager->getPublicAssetPath($this->module),
                    'module_path'   => $this->moduleManager->getModulePath($this->module) . $this->resources->assets,
                ]
            ];
        } else {
            $this->generated = [
                'entity' => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'src/App/Entity/' . $baseClassName . '.php',
                    'namespace' => $baseNamespace . '\\Entity',
                    'fqcn'      => $baseNamespace . '\\Entity\\' . $baseClassName,
                    'template'  => '@stubs/entity.stub',
                ],
                'model'  => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'src/App/Model/' . $baseClassName . '.php',
                    'namespace' => $baseNamespace . '\\Model',
                    'fqcn'      => $baseNamespace . '\\Model\\' . $baseClassName,
                    'template'  => '@stubs/model.stub',
                ],
                'controller' => [
                    'class'     => $baseClassName . 'Controller',
                    'file_path' => $basePath . 'src/App/Controller/' . $baseClassName . 'Controller.php',
                    'namespace' => $baseNamespace . '\\Controller',
                    'fqcn'      => $baseNamespace . '\\Controller\\' . $baseClassName . 'Controller',
                    'template'  =>  '@stubs/' . $mode . '/controller.stub',
                ],
                'repository_interface' => [
                    'class'     => $baseClassName . 'RepositoryInterface',
                    'file_path' => $basePath . 'src/App/Contracts/' . $baseClassName . 'RepositoryInterface.php',
                    'namespace' => $baseNamespace . '\\Contracts',
                    'fqcn'      => $baseNamespace . '\\Contracts\\' . $baseClassName . 'RepositoryInterface',
                    'template'  => '@stubs/' . $mode . '/repositoryInterface.stub',
                ],
                'repository'  => [
                    'class'     => $baseClassName . 'Repository',
                    'file_path' => $basePath . 'src/App/Repository/' . $baseClassName . 'Repository.php',
                    'namespace' => $baseNamespace . '\\Repository',
                    'fqcn'      => $baseNamespace . '\\Repository\\' . $baseClassName . 'Repository',
                    'template'  => '@stubs/' . $mode . '/repository.stub',
                ],
                'repository_service_provider' => [
                    'class'     => $baseClassName . 'RepositoryServiceProvider',
                    'file_path' => $basePath . 'src/App/Provider/' . $baseClassName . 'RepositoryServiceProvider.php',
                    'namespace' => $baseNamespace . '\\Provider',
                    'fqcn'      => $baseNamespace . '\\Provider\\' . $baseClassName . 'RepositoryServiceProvider',
                    'template'  => '@stubs/repositoryServiceProvider.stub',
                ],
                'template' => [
                    'dir_path'      => $this->app['config']['twig.template_dir'] . '/' . $this->entity,
                    'relative_path' => $this->entity,
                    'stub_dir'      => __DIR__ . '/stubs/' . $mode . '/template/'
                ],
                'asset' => [
                    'public_path'   => $this->app['path.public'] . 'assets',
                    'module_path'   => '',
                ]
            ];
        }
    }

    /**
     * Generate migration file for the specified entity
     */
    protected function generateMigration()
    {
        /** return if module is specified but migration isn't */
        if ($this->resources && !$this->resources->migrations) {
            return;
        }

        $this->output->writeln("<comment>Generating migration</comment>");
        $command    = $this->getApplication()->find('migration:create');
        $input      = [
            'command'   => 'migration:create',
            'class-name'=> 'Create' . Str::studly(Str::plural($this->entity)) . 'Table',
            '--table'   => Str::plural($this->entity),
            '--fields'  => $this->input->getOption('fields'),
        ];

        if ($this->module) {
            $input['--module'] =  $this->module;
        }

        $command->run(new ArrayInput($input), $this->output);
    }

    /**
     * Run generated migration
     */
    protected function runMigration()
    {
        /** return if module is specified but migration isn't */
        if ($this->resources && !$this->resources->migrations) {
            return;
        }

        $this->output->writeln("<comment>Running migration</comment>");
        $command    = $this->getApplication()->find('migration:migrate');
        $input      = [
            'command'   => 'migration:migrate'
        ];

        if ($this->module) {
            $input['--module'] =  $this->module;
        }

        $command->run(new ArrayInput($input), $this->output);
    }

    /**
     * Generate controller for the specified table.
     */
    protected function generateController()
    {
        /** return if module is specified but has no controller dir */
        if ($this->resources && !$this->resources->controllers) {
            return;
        }

        $this->output->writeln("<comment>Generating controller '" . $this->generated['controller']['class'] . "'</comment>");

        $namespace      = $this->generated['controller']['namespace'];
        $controllerFile = $this->generated['controller']['file_path'];
        $replacement    = [
            'repositoryInterface'       => $this->generated['repository_interface']['class'],
            'controller'                => $this->generated['controller']['class'],
            'templatePath'              => $this->generated['template']['relative_path'],
            'urlName'                   => $this->module ? $this->module . '.' . $this->entity : $this->entity,
            'repositoryInterfaceFqcn'   => $namespace ? 'use ' . $this->generated['repository_interface']['fqcn'] . ';' : '',
            'namespace'                 => $namespace ? "\nnamespace $namespace;\n\nuse Exception;" : '',
            'entity'                    => $this->entity,
            'fields'                    => $this->fields,
        ];

        $compiledCode  = $this->app['twig']->render($this->generated['controller']['template'], $replacement);

        $this->filesystem->dumpFile($controllerFile, $compiledCode);
        $this->output->writeln('<info> - Controller created at '. $controllerFile .'</info>');
    }

    /**
     * Generate entity class
     */
    protected function generateEntity()
    {
        $entityFile     = $this->generated['entity']['file_path'];
        $replacement    = [
            'namespace' => "\nnamespace " . $this->generated['entity']['namespace'] . ";\n",
            'entity'    => $this->generated['entity']['class'],
            'fields'    => $this->getFields()
        ];

        $compiledCode   = $this->app['twig']->render($this->generated['entity']['template'], $replacement);

        $this->filesystem->dumpFile($entityFile, $compiledCode);
        $this->output->writeln('<info> - Controller created at '. $entityFile .'</info>');
    }

    /**
     * Generate model entity for specified table.
     */
    protected function generateModel()
    {
        $this->output->writeln("<comment>Generating model '" . $this->generated['model']['class'] . "'</comment>");

        $namespace      = $this->generated['model']['namespace'];
        $modelFile      = $this->generated['model']['file_path'];
        $replacement    = [
            'namespace' => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            'model'     => $this->generated['model']['class']
        ];

        $compiledCode   = $this->app['twig']->render($this->generated['model']['template'], $replacement);

        $this->filesystem->dumpFile($modelFile, $compiledCode);
        $this->output->writeln('<info> - Model created at '. $modelFile .'</info>');
    }

    /**
     * Generate common repository interface for specified table.
     */
    protected function generateRepositoryInterface()
    {
        $this->output->writeln("<comment>Generating repository interface '" . $this->generated['repository_interface']['class'] . "'</comment>");

        $namespace      = $this->generated['repository_interface']['namespace'];
        $interfaceFile  = $this->generated['repository_interface']['file_path'];
        $replacement    = [
            'namespace' => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            'modelFqcn' => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            'model'     => $this->generated['model']['class'],
        ];

        $compiledCode   = $this->app['twig']->render($this->generated['repository_interface']['template'], $replacement);

        $this->filesystem->dumpFile($interfaceFile, $compiledCode);
        $this->output->writeln('<info> - Interface created at '. $interfaceFile . '<info>');

    }

    /**
     * Generate repository  for specified table, implement the previously generated interface.
     */
    protected function generateRepository()
    {
        $this->output->writeln("<comment>Generating repository '" . $this->generated['repository']['class'] . "'</comment>");

        $namespace      = $this->generated['repository']['namespace'];
        $repoFile       = $this->generated['repository']['file_path'];
        $replacement    = [
            'namespace'     => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            'modelFqcn'     => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            'ifaceFqcn'     => $namespace ? 'use ' . $this->generated['repository_interface']['fqcn'] . ';' : '',
            'model'         => $this->generated['model']['class'],
            'entity'        => $this->entity,
        ];

        $compiledCode   = $this->app['twig']->render($this->generated['repository']['template'], $replacement);

        $this->filesystem->dumpFile($repoFile, $compiledCode);
        $this->output->writeln('<info> - Repository created at '. $repoFile .'</info>');
    }

    /**
     * Generate repository service provider
     */
    protected function generateRepositoryServiceProvider()
    {
        $this->output->writeln("<comment>Generating service provider '" . $this->generated['repository_service_provider']['class'] . "'</comment>");

        $namespace      = $this->generated['repository_service_provider']['namespace'];
        $serviceFile    = $this->generated['repository_service_provider']['file_path'];

        $replacement    = [
            'namespace'     => $namespace ? "\nnamespace $namespace;\n" : '',
            'useModelFqcn'  => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            'useRepoFqcn'   => $namespace ? 'use ' . $this->generated['repository']['fqcn'] . ';' : '',
            'repoFqcn'      => $this->generated['repository']['fqcn'],
            'repoClass'     => $this->generated['repository']['class'],
            'model'         => $this->generated['model']['class'],
            'ifaceFqcn'     => $this->generated['repository_interface']['fqcn'],
        ];

        $compiledCode   = $this->app['twig']->render($this->generated['repository_service_provider']['template'], $replacement);

        $this->filesystem->dumpFile($serviceFile, $compiledCode);
        $this->output->writeln('<info> - Service provider created at '. $serviceFile .'</info>');
    }

    /**
     * Registering the generated service provider into service list
     */
    protected function registerServiceProvider()
    {
        $this->output->writeln('<comment>Registering ' . $this->generated['repository_service_provider']['class'] . '</comment>');

        try {
            if ($this->module) {
                $this->output->writeln('<comment> - Trying to register to module services list</comment>');

                $servicesList = $this->app['config']->get('@' . $this->module . '.services');

                if (false === array_search($this->generated['repository_service_provider']['fqcn'], $servicesList)) {
                    $servicesList[] = $this->generated['repository_service_provider']['fqcn'];
                }

                $this->app['config']->set('@' . $this->module . '.services', $servicesList);
                $this->app['config']->save('@' . $this->module . '.services');

                $this->output->writeln('<info> - Service registered at '. $this->moduleManager->getModulePath($this->module) . $this->resources->config . '/services.php</info>');
            } else {
                throw new Exception("No module found, write to main services config");
            }

        } catch (Exception $e) {
            if ($this->module) {
                $this->output->writeln('<error> - Unable register service to module service list</error>');
                $this->output->writeln('<comment> - Trying to register to application services list</comment>');
            }

            $servicesList = $this->app['config']->get('services.common');

            if (false === array_search($this->generated['repository_service_provider']['fqcn'], $servicesList)) {
                $servicesList[] = $this->generated['repository_service_provider']['fqcn'];
            }

            $this->app['config']->set('services.common', $servicesList);
            $this->app['config']->save('services');
            $this->output->writeln('<info> - Service registered at '. $this->app['path.app'] . 'config/services.php</info>');
        }
    }

    /**
     * Generate template for displaying and editing record on the table.
     */
    protected function generateTemplate()
    {
        if ($this->mode == 'api') {
            return;
        }

        $this->output->writeln("<comment>Creating template for the resource</comment>");

        if ($this->mode == 'ajax') {
            $stubs = ['index', 'form-modal', 'show-modal'];

            /** write the js into public and module path */
            $template   = file_get_contents($this->generated['template']['stub_dir'] . 'datatable.js.stub');
            $compiled   = str_replace(['{{module}}', '{{entity}}'], [$this->module, $this->entity], $template);
            $jsFile     = '/js/' . $this->entity . '_datatable.js';

            $publicJsPath = $this->generated['asset']['public_path'] . $jsFile;
            $this->filesystem->dumpFile($publicJsPath, $compiled);
            $this->output->writeln('<info> - Javascript template generated at '. $publicJsPath. '</info>');

            if ($this->module && $this->resources->assets) {
                $moduleJsPath = $this->generated['asset']['module_path'] . $jsFile;
                $this->filesystem->dumpFile($moduleJsPath, $compiled);
                $this->output->writeln('<info> - Javascript template generated at ' . $moduleJsPath . '</info>');
            }
        } else {
            $stubs = ['index', 'edit', 'create', 'show'];
        }

        $replacement = [
            '{{module}}'        => $this->module,
            '{{entity}}'        => $this->entity,
            '{{colspan}}'       => count($this->fields)+1,
            '{{datatableUrl}}'  => ($this->module ? $this->module . '.' . $this->entity : $this->entity) . '.datatable',
            '{{actionUrl}}'     => ($this->module ? $this->module . '.' . $this->entity : $this->entity) . '.store',
        ];

        foreach ($stubs as $stub) {
            $template   = $this->app['twig']->render('@stubs/' . $this->mode . '/template/' . $stub . '.twig.stub', ['fields' => $this->fields]);

            //$template   = file_get_contents($this->generated['template']['stub_dir'] . $stub . '.twig.stub');
            $compiled   = str_replace(array_keys($replacement), array_values($replacement), $template);
            $targetFile = $this->generated['template']['dir_path'] . '/' . $stub . '.twig';

            $this->filesystem->dumpFile($targetFile, $compiled);
            $this->output->writeln('<info> - Template generated at '. $targetFile .'</info>');
        }
    }

    /**
     * Configuring route to the previously generated controller.
     */
    protected function appendRoute()
    {
        $this->output->writeln("<comment>Creating route to the resource</comment>");
        $routePath = $this->resources && $this->resources->routes
                    ? $this->basePath . $this->resources->routes
                    : $this->basePath . 'routes.php';

        $replacement = [
            'controller'=> $this->generated['controller']['class'],
            'namespace' => $this->generated['controller']['namespace'],
            'entity'    => $this->entity,
            'routeName' => $this->module ? $this->module . '.' . $this->entity : $this->entity,
        ];

        $routeCode  = $this->app['twig']->render('@stubs/' . $this->mode . '/route.stub', $replacement);
        $routeFile  = fopen($routePath, 'a');
        fwrite($routeFile, $routeCode);
        fclose($routeFile);

        $this->output->writeln('<info> - Route configuration written on '. $routePath .'</info>');

    }
}
