<?php

namespace SilexStarter\Menu;

class MenuContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $menuContainer;

    public function setUp()
    {
        $this->menuContainer = new MenuContainer('test-menu');
    }

    public function tearDown()
    {
        $this->menuContainer = null;
    }

    public function testCreateItem()
    {

    }

    public function testHasActiveItem()
    {

    }

    public function testHasActiveChildren()
    {

    }
}
