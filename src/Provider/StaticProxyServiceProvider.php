<?php

namespace SilexStarter\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Acclimate\Container\ContainerAcclimator;
use XStatic\ProxyManager;

class StaticProxyServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if ($app['enable_static_proxy']) {
            $app['static_proxy_manager'] = $app->share(
                function () use ($app) {
                    $acclimator     = new ContainerAcclimator();
                    $proxyManager   = new ProxyManager($acclimator->acclimate($app));

                    $proxyManager->enable(ProxyManager::ROOT_NAMESPACE_ANY);

                    return $proxyManager;
                }
            );
        }
    }
}
