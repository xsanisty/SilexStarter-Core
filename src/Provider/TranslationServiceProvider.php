<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['translator'] = $app->share(
            function (Container $app) {
                $translator = new Translator($app['locale'], new MessageSelector(), $app['config']['translator.cache_dir'], $app['debug']);

                $translator->addLoader('array', new ArrayLoader());
            }
        );
    }

    public function boot(Container $app)
    {
    }
}
