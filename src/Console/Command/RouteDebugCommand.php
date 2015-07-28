<?php

namespace SilexStarter\Console\Command;

use SilexStarter\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class RouteDebugCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('route:debug')
            ->setDescription('Debug routing configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app   = $this->getSilexStarter();
        $table = new Table($output);
        $rows  = [];

        if ($app['enable_module']) {
            foreach ($app['module']->getRouteFiles() as $route) {
                require $route;
            }
        }

        require $app['path.app'] . 'routes.php';

        $controllers    = $app['controllers'];
        $collection     = $controllers->flush();

        foreach ($collection as $name => $route) {
            $requirements = [];
            $defaults = [];

            foreach ($route->getRequirements() as $key => $requirement) {
                $requirements[] = $key . "\n" . $requirement . "\n";
            }

            foreach ($route->getDefaults() as $key => $default) {
                $defaults[] = $key . "\n" . $default . "\n";
            }

            $rows[] = [$name, $route->getPath(), implode("\n", $requirements), implode("\n", $defaults)];
        }

        $table->setHeaders(['Name', 'Path', 'Requirements', 'Defaults']);
        $table->setRows($rows);

        $table->render();
    }
}
