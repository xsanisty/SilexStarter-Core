<?php

namespace SilexStarter\Provider;

use Exception;
use Twig_Extension_Debug;
use Silex\Application;
use Silex\ServiceProviderInterface;
use SilexStarter\TwigExtension\TwigCookieExtension;
use SilexStarter\TwigExtension\TwigAssetExtension;
use SilexStarter\TwigExtension\TwigEventExtension;
use SilexStarter\TwigExtension\TwigUrlExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;

class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * The Silex Application instance.
     *
     * @var \Silex\Application
     */
    protected $app;

    protected function registerFilesystemLoader()
    {
        $this->app['twig.loader.filesystem'] = $this->app->share(
            function (Application $app) {
                return new \Twig_Loader_Filesystem($app['config']['twig.template_dir']);
            }
        );
    }

    protected function registerTwigLoader()
    {
        $this->app['twig.loader'] = $this->app->share(
            function (Application $app) {
                return $app['twig.loader.filesystem'];
            }
        );
    }

    protected function registerTwig()
    {
        $this->app['twig'] = $this->app->share(
            function (Application $app) {
                $twigOptions = array_replace(
                    [
                        'charset'          => $app['charset'],
                        'debug'            => $app['debug'],
                        'strict_variables' => $app['debug'],
                    ],
                    $app['config']['twig.options']
                );

                /* set twig template cache to app/storage/console for console environment */
                if (isset($app['console'])) {
                    $twigOptions['cache'] = $app['path.app'] . '/storage/console';
                }

                $twigEnv = new \Twig_Environment(
                    $app['twig.loader'],
                    $twigOptions
                );

                if (!isset($app['console'])) {
                    $twigEnv->addExtension(new TwigAssetExtension($app['asset_manager']));
                    $twigEnv->addExtension(new TwigEventExtension($app['dispatcher']));
                    $twigEnv->addExtension(new TwigUrlExtension($app['request_stack'], $app['url_generator']));
                    $twigEnv->addExtension(new TwigCookieExtension($app['request_stack']));

                    $twigEnv->addGlobal('config', $app['config']);

                    try {
                        $twigEnv->addGlobal('current_user', $app['sentry']->getUser());
                    } catch (Exception $e) {
                        $twigEnv->addGlobal('current_user', null);
                    }

                    if ($app['config']['twig.options.debug']) {
                        $twigEnv->addExtension(new Twig_Extension_Debug());
                    }

                    if ($app['enable_profiler']) {
                        $twigEnv->addGlobal('app', $app);
                        $app->registerServices(['Silex\Provider\HttpFragmentServiceProvider']);
                    }

                    if (class_exists('Symfony\Bridge\Twig\Extension\RoutingExtension')) {
                        if (isset($app['url_generator'])) {
                            $twigEnv->addExtension(new RoutingExtension($app['url_generator']));
                        }

                        if (isset($app['translator'])) {
                            $twigEnv->addExtension(new TranslationExtension($app['translator']));
                        }

                        if (isset($app['security'])) {
                            $twigEnv->addExtension(new SecurityExtension($app['security']));
                        }

                        if (isset($app['fragment.handler'])) {
                            $app['fragment.renderer.hinclude']->setTemplating($twigEnv);

                            $twigEnv->addExtension(new HttpKernelExtension($app['fragment.handler']));
                        }
                    }
                }

                return $twigEnv;

            }
        );
    }

    public function register(Application $app)
    {
        $this->app = $app;
        $this->registerFilesystemLoader();
        $this->registerTwigLoader();
        $this->registerTwig();
    }

    public function boot(Application $app)
    {
    }
}
