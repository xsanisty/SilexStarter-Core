<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use SilexStarter\SilexStarter;
use Symfony\Component\Form\Forms;

class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['form.factory'] = $app->factory(
            function () {
                $formFactory = Forms::createFormFactory();
            }
        );

        if ($app instanceof SilexStarter) {
            $app->bind('Symfony\Component\Form\Forms', 'form.factory');
        }
    }
}
