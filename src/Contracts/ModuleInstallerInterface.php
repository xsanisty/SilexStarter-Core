<?php

namespace SilexStarter\Contracts;

interface ModuleInstallerInterface extends ModuleInterface
{
    /**
     * Installing the module, covering:
     *     - publishing assets file
     *     - creating the database table
     *     - etc.
     */
    public function install();

    /**
     * Uninstalling the module, covering:
     *     - dropping table
     *     - removing asset file
     *     - etc.
     */
    public function uninstall();

    /**
     * Upgrade the module to the newer version.
     */
    public function upgrade();

    /**
     * Downgrade the module to previous version.
     */
    public function downgrade();
}
