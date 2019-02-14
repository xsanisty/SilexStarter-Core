<?php

namespace SilexStarter;

use Exception;
use ReflectionClass;
use Pimple\Container;
use Silex\Application;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveCallbackFilterIterator;

class SilexStarter extends Application
{

    public function __construct()
    {
        parent::__construct();
        $this['app'] = $this;

        $this['request'] = function (Container $app) {
            return $app['request_stack']->getCurrentRequest();
        };

        $this->bind('Silex\Application', 'app');
        $this->bind('SilexStarter\SilexStarter', 'app');
        $this->bind('Symfony\Component\HttpFoundation\Request', 'request');
        //$this->bind($this['dispatcher_class'], $this['dispatcher']);
    }

    /**
     * Register all services provider to the application container.
     *
     * @param array $providerList List of service providers
     */
    public function registerServices(array $providerList)
    {
        foreach ($providerList as $provider => $providerOptions) {
            if (is_numeric($provider)) {
                $this->register(new $providerOptions());
            } else {
                $this->register(new $provider(), $providerOptions);
            }
        }
    }

    /**
     * Search for controllers in the controllers dir and register it as a service.
     *
     * @param string $controllerDir The directory where controllers is located
     * @param string $namespace     The root namespace of the controller directory
     */
    public function registerControllerDirectory($controllerDir, $namespace = '')
    {
        $files  = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($controllerDir, FilesystemIterator::SKIP_DOTS),
            function ($file, $key, $iterator) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($file->isFile() && $file->getExtension() == 'php') {
                    return true;
                }

                return false;
            }
        );

        $fileIter   = new RecursiveIteratorIterator($files);
        $namespace  = ($namespace) ? rtrim($namespace, '\\').'\\' : '';

        foreach ($fileIter as $file) {
            $controller = str_replace([$controllerDir, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file);
            $controller = ltrim($controller, '\\');

            $this[$namespace.$controller] = $this->share(
                $this->controllerServiceClosureFactory($namespace.$controller)
            );
        }
    }

    /**
     * Provide controller service factory.
     *
     * @param string $controller Fully qualified controller class name
     *
     * @return \Closure
     */
    protected function controllerServiceClosureFactory($controller)
    {
        /**
         * @param Application $app
         * @return object
         * @throws Exception
         */
        return function (Application $app) use ($controller) {
            $controllerReflection   = new ReflectionClass($controller);
            $controllerConstructor  = $controllerReflection->getConstructor();

            /*
             * If constructor exists, build the dependency list from the dependency container
             */
            if ($controllerConstructor) {
                $constructorParameters  = $controllerConstructor->getParameters();
                $invocationParameters   = [];

                foreach ($constructorParameters as $parameterReflection) {
                    $parameterClassName = $parameterReflection->getClass()->getName();

                    if ($app->offsetExists($parameterClassName)) {
                        $invocationParameters[] = $app[$parameterClassName];
                    } elseif (class_exists($parameterClassName)) {
                        $invocationParameters[] = new $parameterClassName();
                    } else {
                        throw new Exception("Can not resolve either $parameterClassName or it's instance from the container", 1);
                    }
                }

                $callback = $controllerReflection->newInstanceArgs($invocationParameters);

                /*
                 * Else, Instantiate the class directly
                 */
            } else {
                $callback = $controllerReflection->newInstance();
            }

            if ($controllerReflection->implementsInterface('\SilexStarter\Contracts\ContainerAwareInterface')) {
                $callback->setContainer($app);
            }

            if ($controllerReflection->implementsInterface('\SilexStarter\Contracts\DispatcherAwareInterface')) {
                $callback->setDispatcher($app['dispatcher']);
            }

            return $callback;
        };
    }

    /**
     * Register filter middleware to the ap container.
     *
     * @param string        $name     The name of the filter callback
     * @param callable|null $callback The callable callback to be registered
     *
     * @return callable|null
     */
    public function filter($name, callable $callback = null)
    {
        if (is_null($callback)) {
            return $this['filter.'.$name];
        }

        $this['filter.'.$name] = $this->protect($callback);

        return null;
    }

    /**
     * Alias for filter method.
     *
     * @param string        $name       The middleware name
     * @param callable|null $callback   The callable callback to be registered
     *
     * @return callable|null
     */
    public function middleware($name, callable $callback = null)
    {
        return $this->filter($name, $callback);
    }

    /**
     * Get current application environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this['environment'];
    }

    /**
     * Bind an interface into specific service.
     *
     * @param string $interface the fully qualified interface/class name
     * @param string $service   the service key registered in container
     *
     * @return mixed the service object
     */
    public function bind($interface, $service)
    {
        $this[$interface] = $this->share(
            function () use ($service) {
                return $this[$service];
            }
        );
    }

    /**
     * Group route into specific pattern and apply same middleware.
     *
     * @param string  $pattern  Matched route pattern
     * @param callable $callback The route callback
     * @param array   $options  The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function group($pattern, callable $callback, array $options = [])
    {
        return $this['route_builder']->group($pattern, $callback, $options);
    }

    /**
     * Group route into predefined resource pattern.
     *
     * @param string $pattern    Matched route pattern
     * @param string $controller The fully qualified controller class name
     * @param array  $options    The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function resource($pattern, $controller, array $options = [])
    {
        return $this['route_builder']->resource($pattern, $controller, $options);
    }

    /**
     * Build route based on available public method on the controller.
     *
     * @param string $pattern    Matched route pattern
     * @param string $controller The fully qualified controller class name
     * @param array  $options    The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function controller($pattern, $controller, array $options = [])
    {
        return $this['route_builder']->controller($pattern, $controller, $options);
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function match($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->match($pattern, $to, $options);
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function get($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->get($pattern, $to, $options);
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function post($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->post($pattern, $to, $options);
    }

    /**
     * Maps a PUT request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function put($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->put($pattern, $to, $options);
    }

    /**
     * Maps a DELETE request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function delete($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->delete($pattern, $to, $options);
    }

    /**
     * Maps a PATCH request to a callable.
     *
     * @param string $pattern Matched route pattern
     * @param mixed  $to      Callback that returns the response when matched
     * @param array  $options The route options, including before and after middleware
     *
     * @return \Silex\Controller
     */
    public function patch($pattern, $to = null, array $options = [])
    {
        return $this['route_builder']->patch($pattern, $to, $options);
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers and module when not handling a request.
     */
    public function boot()
    {
        parent::boot();

        if ($this['enable_module']) {
            $this['module']->boot();
        }
    }

    function share(callable $provider)
    {
        return $provider;
    }
}
