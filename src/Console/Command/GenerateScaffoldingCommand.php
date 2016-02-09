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
    protected $output;
    protected $app;
    protected $mode;
    protected $module;
    protected $filesystem;
    protected $entity;
    protected $basePath;
    protected $resources;
    protected $generated = [];

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('generate:scaffolding')
            ->setDescription('Generate basic crud scaffolding')
            ->addArgument(
                'entity-name',
                InputArgument::REQUIRED,
                'The entity name as the base of the scaffolding'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_REQUIRED,
                'If set, the command will create scaffolding for specific module'
            )
            ->addOption(
                'mode',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Mode can be "standard" [generate standard web form], "ajax" [generate ajax based form], or "api" [generate api endpoint]',
                'standard'
            );
    }

    /**
     * Execute the command, start the execution stack to create the scaffolding.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output       = $output;
        $this->app          = $this->getSilexStarter();
        $this->filesystem   = $this->app['filesystem'];
        $this->moduleManager= $this->moduleManager = $this->app['module'];
        $this->module       = $input->getOption('module');
        $this->entity       = $input->getArgument('entity-name');
        $this->mode         = $input->getOption('mode');

        if ($this->module) {
            $this->basePath = $this->moduleManager->getModulePath($this->module);
            $this->resources= $this->moduleManager->getModule($this->module)->getResources();
        } else {
            $this->basePath = $this->app['path.app'];
        }

        $this->generateStubList();
        $this->generateMigration();
        $this->generateModel();
        $this->generateRepositoryInterface();
        $this->generateRepository();
        $this->generateRepositoryServiceProvider();
        $this->registerServiceProvider();
        $this->generateController();
        $this->generateTemplate();
        $this->appendRoute();
    }

    protected function generateStubList()
    {
        $basePath       = $this->basePath;
        $baseClassName  = Str::studly($this->entity);
        $baseNamespace  = $this->module ? $this->moduleManager->getModuleNamespace($this->module) : '';

        if ($this->module) {
            $this->generated = [
                'model'  => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'Model/' . $baseClassName . '.php',
                    'namespace' => $baseNamespace . '\\Model' ,
                    'fqcn'      => $baseNamespace . '\\Model\\' . $baseClassName ,
                    'template'  => __DIR__ . '/stubs/model.stub',
                ],
                'controller' => [
                    'class'     => $baseClassName . 'Controller',
                    'file_path' => $basePath . $this->resources->controllers . '/' . $baseClassName . 'Controller.php',
                    'namespace' => $baseNamespace . '\\' . $this->resources->controllers,
                    'fqcn'      => $baseNamespace . '\\' . $this->resources->controllers .'\\' . $baseClassName . 'Controller',
                    'template'  =>  __DIR__ . '/stubs/controller.stub',
                ],
                'repository_interface' => [
                    'class'     => $baseClassName . 'RepositoryInterface',
                    'file_path' => $basePath . 'Contract/' . $baseClassName . 'RepositoryInterface.php',
                    'namespace' => $baseNamespace . '\\Contract',
                    'fqcn'      => $baseNamespace . '\\Contract\\' . $baseClassName . 'RepositoryInterface',
                    'template'  => __DIR__ . '/stubs/repositoryInterface.stub',
                ],
                'repository'  => [
                    'class'     => $baseClassName . 'Repository',
                    'file_path' => $basePath . 'Repository' . '/' . $baseClassName . 'Repository.php',
                    'namespace' => $baseNamespace . '\\Repository',
                    'fqcn'      => $baseNamespace . '\\Repository\\' . $baseClassName . 'Repository',
                    'template'  => __DIR__ . '/stubs/repository.stub',
                ],
                'repository_service_provider' => [
                    'class'     => $baseClassName . 'RepositoryServiceProvider',
                    'file_path' => $basePath . 'Provider' . '/' . $baseClassName . 'RepositoryServiceProvider.php',
                    'namespace' => $baseNamespace . '\\Provider',
                    'fqcn'      => $baseNamespace . '\\Provider\\' . $baseClassName . 'RepositoryServiceProvider',
                    'template'  => __DIR__ . '/stubs/repositoryServiceProvider.stub',
                ],
                'template' => [
                    'dir_path'      => $this->moduleManager->getTemplatePath($this->module) . '/' . $this->entity,
                    'relative_path' => '@' . $this->module . '/' . $this->entity
                ]
            ];
        } else {
            $this->generated = [
                'model'  => [
                    'class'     => $baseClassName,
                    'file_path' => $basePath . 'models' . '/' . $baseClassName . '.php',
                    'namespace' => '',
                    'fqcn'      => $baseClassName,
                    'template'  => __DIR__ . '/stubs/model.stub',
                ],
                'controller' => [
                    'class'     => $baseClassName . 'Controller',
                    'file_path' => $basePath . 'controllers' . '/' . $baseClassName . 'Controller.php',
                    'namespace' => '',
                    'fqcn'      => $baseClassName . 'Controller',
                    'template'  =>  __DIR__ . '/stubs/controller.stub',
                ],
                'repository_interface' => [
                    'class'     => $baseClassName . 'RepositoryInterface',
                    'file_path' => $basePath . 'repositories' . '/' . $baseClassName . 'RepositoryInterface.php',
                    'namespace' => '',
                    'fqcn'      => $baseClassName . 'RepositoryInterface',
                    'template'  => __DIR__ . '/stubs/repositoryInterface.stub',
                ],
                'repository'  => [
                    'class'     => $baseClassName . 'Repository',
                    'file_path' => $basePath . 'repositories' . '/' . $baseClassName . 'Repository.php',
                    'namespace' => '',
                    'fqcn'      => $baseClassName . 'Repository',
                    'template'  => __DIR__ . '/stubs/repository.stub',
                ],
                'repository_service_provider' => [
                    'class'     => $baseClassName . 'RepositoryServiceProvider',
                    'file_path' => $basePath . 'services' . '/' . $baseClassName . 'RepositoryServiceProvider.php',
                    'namespace' => '',
                    'fqcn'      => $baseClassName . 'RepositoryServiceProvider',
                    'template'  => __DIR__ . '/stubs/repositoryServiceProvider.stub',
                ],
                'template' => [
                    'dir_path'      => $this->app['config']['twig.template_dir'] . '/' . $this->entity,
                    'relative_path' => $this->entity
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

        $template       = file_get_contents($this->generated['controller']['template']);
        $namespace      = $this->generated['controller']['namespace'];
        $controllerFile = $this->generated['controller']['file_path'];
        $replacement    = [
            '{{repositoryInterface}}'       => $this->generated['repository_interface']['class'],
            '{{controller}}'                => $this->generated['controller']['class'],
            '{{templatePath}}'              => $this->generated['template']['relative_path'],
            '{{urlName}}'                   => $this->module ? $this->module . '.' . $this->entity : $this->entity,
            '{{repositoryInterfaceFqcn}}'   => $namespace ? 'use ' . $this->generated['repository_interface']['fqcn'] . ';' : '',
            '{{namespace}}'                 => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            '{{entity}}'                    => $this->entity,
        ];

        $compiledCode  = str_replace(array_keys($replacement), array_values($replacement), $template);

        $this->filesystem->dumpFile($controllerFile, $compiledCode);
        $this->output->writeln('<info> - Controller created at '. $controllerFile .'</info>');
    }

    /**
     * Generate model entity for specified table.
     */
    protected function generateModel()
    {
        $this->output->writeln("<comment>Generating model '" . $this->generated['model']['class'] . "'</comment>");
        $template = file_get_contents($this->generated['model']['template']);

        $namespace      = $this->generated['model']['namespace'];
        $modelFile      = $this->generated['model']['file_path'];
        $replacement    = [
            '{{namespace}}' => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            '{{model}}'     => $this->generated['model']['class']
        ];

        $compiledCode   = str_replace(array_keys($replacement), array_values($replacement), $template);

        $this->filesystem->dumpFile($modelFile, $compiledCode);
        $this->output->writeln('<info> - Model created at '. $modelFile .'</info>');
    }

    /**
     * Generate common repository interface for specified table.
     */
    protected function generateRepositoryInterface()
    {
        $this->output->writeln("<comment>Generating repository interface '" . $this->generated['repository_interface']['class'] . "'</comment>");

        $template       = file_get_contents($this->generated['repository_interface']['template']);
        $namespace      = $this->generated['repository_interface']['namespace'];
        $interfaceFile  = $this->generated['repository_interface']['file_path'];
        $replacement    = [
            '{{namespace}}' => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            '{{modelFqcn}}' => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            '{{model}}'     => $this->generated['model']['class'],
        ];

        $compiledCode   = str_replace(array_keys($replacement), array_values($replacement), $template);

        $this->filesystem->dumpFile($interfaceFile, $compiledCode);
        $this->output->writeln('<info> - Interface created at '. $interfaceFile . '<info>');

    }

    /**
     * Generate repository  for specified table, implement the previously generated interface.
     */
    protected function generateRepository()
    {
        $this->output->writeln("<comment>Generating repository '" . $this->generated['repository']['class'] . "'</comment>");

        $template       = file_get_contents($this->generated['repository']['template']);
        $namespace      = $this->generated['repository']['namespace'];
        $repoFile       = $this->generated['repository']['file_path'];
        $replacement    = [
            '{{namespace}}'     => $namespace ? "\nnamespace $namespace;\n\nuse Exception;\n" : '',
            '{{modelFqcn}}'     => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            '{{ifaceFqcn}}'     => $namespace ? 'use ' . $this->generated['repository_interface']['fqcn'] . ';' : '',
            '{{model}}'         => $this->generated['model']['class'],
            '{{entity}}'        => $this->entity,
        ];

        $compiledCode   = str_replace(array_keys($replacement), array_values($replacement), $template);

        $this->filesystem->dumpFile($repoFile, $compiledCode);
        $this->output->writeln('<info> - Repository created at '. $repoFile .'</info>');
    }

    /**
     * Generate repository service provider
     */
    protected function generateRepositoryServiceProvider()
    {
        $this->output->writeln("<comment>Generating service provider '" . $this->generated['repository_service_provider']['class'] . "'</comment>");

        $template       = file_get_contents($this->generated['repository_service_provider']['template']);
        $namespace      = $this->generated['repository_service_provider']['namespace'];
        $serviceFile    = $this->generated['repository_service_provider']['file_path'];

        $replacement    = [
            '{{namespace}}'     => $namespace ? "\nnamespace $namespace;\n" : '',
            '{{useModelFqcn}}'  => $namespace ? 'use ' . $this->generated['model']['fqcn'] . ';' : '',
            '{{useRepoFqcn}}'   => $namespace ? 'use ' . $this->generated['repository']['fqcn'] . ';' : '',
            '{{repoFqcn}}'      => $this->generated['repository']['fqcn'],
            '{{repoClass}}'     => $this->generated['repository']['class'],
            '{{model}}'         => $this->generated['model']['class'],
            '{{ifaceFqcn}}'     => $this->generated['repository_interface']['fqcn'],
        ];

        $compiledCode   = str_replace(array_keys($replacement), array_values($replacement), $template);

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
        $this->output->writeln("<comment>Creating template for the resource</comment>");

        $stubs = ['index', 'edit', 'create', 'show'];

        foreach ($stubs as $stub) {
            $template = file_get_contents(__DIR__ . '/stubs/template/' . $stub . '.twig.stub');
            $compiled = str_replace(['{{module}}', '{{entity}}'], [$this->module, $this->entity], $template);

            $this->filesystem->dumpFile($this->generated['template']['dir_path'] . '/' . $stub . '.twig', $compiled);
            $this->output->writeln('<info> - Templated generated at '. $this->generated['template']['dir_path'] . '/' . $stub . '.twig</info>');
        }
    }

    /**
     * Configuring route to the previously generated controller.
     */
    protected function appendRoute()
    {
        $this->output->writeln("<comment>Creating route to the resource</comment>");

        $codeTemplate   = file_get_contents(__DIR__ . '/stubs/route.stub');
        $controller     = $this->generated['controller']['class'];
        $namespace      = $this->generated['controller']['namespace'];
        $routeName      = $this->module ? $this->module . '.' . $this->entity : $this->entity;

        $routeCode      = str_replace(
            ['{{controller}}', '{{namespace}}', '{{entity}}', '{{routeName}}'],
            [$controller, $namespace, $this->entity, $routeName],
            $codeTemplate
        );

        if ($this->resources && $this->resources->routes) {
            /** write to module routes */
            $routeFile = fopen($this->basePath . $this->resources->routes, 'a');
            fwrite($routeFile, $routeCode);
            fclose($routeFile);

            $this->output->writeln('<info> - Route configuration written on '. $this->basePath . $this->resources->routes .'</info>');
        } else {
            /** write to main route */
            $routeFile = fopen($this->basePath . 'routes.php', 'a');
            fwrite($routeFile, $routeCode);
            fclose($routeFile);

            $this->output->writeln('<info> - Route configuration written on '. $this->basePath . 'routes.php</info>');
        }
    }
}
