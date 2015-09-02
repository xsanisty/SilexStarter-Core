<?php

namespace SilexStarter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['translator'] = $app->share(
            function (Application $app) {
                $translator = new Translator($app['locale'], new MessageSelector(), $app['config']['translator.cache_dir'], $app['debug']);

                $translator->addLoader('array', new ArrayLoader());
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
