<?php

namespace SilexStarter\Provider;

use Exception;
use Twig_Extension_Debug;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\Twig\RuntimeLoader;
use SilexStarter\TwigExtension\TwigCookieExtension;
use SilexStarter\TwigExtension\TwigAssetExtension;
use SilexStarter\TwigExtension\TwigEventExtension;
use SilexStarter\TwigExtension\TwigUrlExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;

class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * The Silex Container instance.
     *
     * @var \Pimple\Container
     */
    protected $app;

    protected function registerFilesystemLoader()
    {
        $this->app['twig.loader.filesystem'] = $this->app->share(
            function (Container $app) {
                return new \Twig_Loader_Filesystem($app['config']['twig.template_dir']);
            }
        );
    }

    protected function registerTwigLoader()
    {
        $this->app['twig.loader'] = $this->app->share(
            function (Container $app) {
                return $app['twig.loader.filesystem'];
            }
        );
    }

    protected function registerRuntimeLoader()
    {
        $this->app['twig.runtime.httpkernel'] = function ($app) {
            return new HttpKernelRuntime($this->app['fragment.handler']);
        };

        $this->app['twig.runtimes'] = function ($app) {
            return [
                HttpKernelRuntime::class => 'twig.runtime.httpkernel',
            ];
        };

        $this->app['twig.runtime_loader'] = function ($app) {
            return new RuntimeLoader($app, $app['twig.runtimes']);
        };
    }

    protected function registerTwig()
    {
        $this->app['twig'] = $this->app->share(
            function (Container $app) {
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

                        $twigEnv->addRuntimeLoader($app['twig.runtime_loader']);
                        //$twigEnv->addExtension(new WebLinkExtension($app['request_stack']));
                    }
                }

                return $twigEnv;

            }
        );
    }

    public function register(Container $app)
    {
        $this->app = $app;
        $this->registerFilesystemLoader();
        $this->registerTwigLoader();
        $this->registerRuntimeLoader();
        $this->registerTwig();
    }
}
