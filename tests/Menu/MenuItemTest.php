<?php

namespace SilexStarter\Menu;

class MenuItemTest extends \PHPUnit_Framework_TestCase
{
    protected $menuContainer;

    public function setUp()
    {
        $this->menuConfig =  [
            'url'       => 'http://domain.com/',
            'label'     => 'Test Menu',
            'icon'      => 'user',
            'class'     => 'some-class',
            'id'        => 'some-id',
            'name'      => 'menu-name',
            'title'     => 'menu title',
            'permission'=> 'menu.access',
            'meta'      => [
                'menu-type'     => 'user-menu',
                'menu-render'   => 'notification'
            ]
        ];

        $this->menuItem = new MenuItem($this->menuConfig);
    }

    public function tearDown()
    {
        $this->menuContainer = null;
    }

    public function testAttributeGetter()
    {
        $config = $this->menuConfig;

        unset($config['meta']);

        foreach ($config as $attribute => $value) {
            assertEquals($value, $this->menuItem->$attribute);
            assertEquals($value, $this->menuItem->getAttribute($attribute));
        }
    }

    public function testAttributeSetter()
    {
        $newUrl = 'http://anotherdomain.com';
        $this->menuItem->url = $newUrl;

        assertEquals($newUrl, $this->menuItem->url);
    }

    public function testNonExistAttribute()
    {
        assertNull($this->menuItem->nonExistenceAttribute);
        assertNull($this->menuItem->getAttribute('nonExistenceAttribute'));
    }

    public function testMetaAttributeSetter()
    {
        $this->menuItem->setMetaAttribute('test_meta', 'test_value');

        assertSame('test_value', $this->menuItem->getMetaAttribute('test_meta'));
    }

    public function testMetaAttributeGetter()
    {
        foreach ($this->menuConfig['meta'] as $attribute => $value) {
            assertEquals($value, $this->menuItem->getMetaAttribute($attribute));
        }

        assertEquals($this->menuConfig['meta'], $this->menuItem->meta);
    }

    public function testActiveStatus()
    {
        $this->menuItem->setActive(true);
        assertTrue($this->menuItem->isActive());

        $this->menuItem->setActive(false);
        assertFalse($this->menuItem->isActive());
    }

    public function testGetChildContainer()
    {
        assertInstanceOf('\\SilexStarter\\Menu\\ChildMenuContainer', $this->menuItem->getChildContainer(), 'message');
    }
}
