<?php

namespace SilexStarter\Contracts;

interface ModuleProviderInterface extends ModuleInterface
{

    /**
     * register the module, module's service provider, or twig extension here.
     */
    public function register();

    /**
     * setup the required action here.
     */
    public function boot();
}
