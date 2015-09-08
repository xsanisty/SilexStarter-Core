<?php

namespace SilexStarter\Menu;

class MenuContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $menuContainer;

    public function setUp()
    {
        $this->menuContainer = new MenuContainer;
    }

    public function tearDown()
    {
        $this->menuContainer = null;
    }
}
