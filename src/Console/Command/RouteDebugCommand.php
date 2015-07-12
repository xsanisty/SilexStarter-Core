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

        $table->setHeaders(['Name', 'Path', 'Requirements']);

        $controllers    = $app['controllers'];
        $collection     = $controllers->flush();

        foreach ($collection as $name => $route) {
            $requirements = [];
            foreach ($route->getRequirements() as $key => $requirement) {
                $requirements[] = $key . ' => ' . $requirement;
            }

            $rows[] = [$name, $route->getPath(), join(', ', $requirements)];
        }

        $table->setRows($rows);

        $table->render();
    }
}
