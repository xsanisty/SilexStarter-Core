<?php

namespace SilexStarter\Module;

class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $moduleManager;

    public function setUp()
    {
        $silex = $this->getMockBuilder('SilexStarter\SilexStarter')
                      ->disableOriginalConstructor()
                      ->getMock();

        $this->moduleManager = new ModuleManager($silex);
    }

    public function tearDown()
    {
        $this->moduleManager = null;
    }

    public function test_non_registered_module()
    {
        assertFalse($this->moduleManager->isRegistered('non-existence-module'));
    }
}
