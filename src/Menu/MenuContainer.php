<?php

namespace SilexStarter\Menu;

use Exception;
use InvalidArgumentException;
use SilexStarter\Contracts\MenuRendererInterface;

class MenuContainer
{
    /**
     * List of menu item.
     *
     * @var array array of SilexStarter\Menu\MenuItem
     */
    protected $items = [];

    /**
     * The menu renderer for rendering the collection.
     *
     * @var SilexStarter\Menu\MenuRendererInterface
     */
    protected $renderer;

    /**
     * The collection name.
     *
     * @var string
     */
    protected $name;

    /**
     * Nested menu level.
     *
     * @var int
     */
    protected $level = 0;

    /**
     * Currently active menu item.
     * @var SilexStarter\Menu\MenuItem|null
     */
    protected $activeItem = null;

    /**
     * Build the MenuCOntainer instance.
     *
     * @param string $name The menu collection name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the current level of nested menu container, default is 0.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set the current level of menu container object.
     *
     * @param int $level The current level of menu container object
     */
    public function setLevel($level)
    {
        $this->level = $level;
        foreach ($this->items as $item) {
            $item->setLevel($level);
        }
    }

    /**
     * Create new MenuItem object inside the MenuContainer.
     *
     * @param string $name       MenuItem name
     * @param array  $attributes MenuItem attributes
     *
     * @return SilexStarter\Menu\MenuItem
     */
    public function createItem($name, array $attributes)
    {
        $attributes['name'] = $name;

        $menuItem = new MenuItem($attributes);
        $options  = isset($attributes['options']) ? $attributes['options'] : [];

        $this->addItem($menuItem, $options);

        return $menuItem;
    }

    /**
     * Add new MenuItem object into container item lists.
     *
     * @param SilexStarter\Menu\MenuItem    $menu       MenuItem object
     * @param array                         $options    Menu options
     * [
     *     'position'   => 'first' | 'last' | 'before:item' | 'after:item'
     * ]
     */
    public function addItem(MenuItem $menu, array $options = [])
    {
        $position   = isset($options['position']) ? explode(':', $options['position']) : ['last'];

        switch ($position[0]) {
            case 'first':
                $this->items = array_merge([$menu->name => $menu], $this->items);
                break;
            case 'before':
            case 'after':
                if (!isset($position[1])) {
                    throw new InvalidArgumentException("'before' and 'after' position require specified menu id, e.g: 'after:somemenu'");
                }

                $keys   = array_keys($this->items);
                $index  = array_search($position[1], $keys);

                $index  = ('after' === $position[0]) ? $index+1 : $index;

                $firstChunk     = array_slice($this->items, 0, $index, true);
                $secondChunk    = array_slice($this->items, $index, null, true);
                $this->items    = array_merge($firstChunk, [$menu->name => $menu], $secondChunk);

                break;
            case 'last':
            default:
                $this->items[$menu->name] = $menu;
                break;
        }
    }

    /**
     * Get MenuItem object from the item list or from the child container.
     *
     * @param string $name MenuItem name
     *
     * @return SilexStarter\Menu\MenuItem menu item object
     */
    public function getItem($name)
    {
        $names      = explode('.', $name);
        $firstItem  = array_shift($names);

        if (!isset($this->items[$firstItem])) {
            throw new Exception("Can not find menu with name: $name");
        }

        $item = $this->items[$firstItem];

        foreach ($names as $itemName) {
            $container  = $item->getChildren();
            $item       = $container->getItem($itemName);
        }

        return $item;

    }

    /**
     * Check if current menu container has item in it.
     *
     * @return bool
     */
    public function hasItem()
    {
        return count($this->items) !== 0;
    }

    /**
     * Get all registered item inside the container.
     *
     * @return array array of MenuItem object
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Remove MenuItem object from the container item lists.
     *
     * @param string $name MenuItem name
     */
    public function removeItem($name)
    {
        if (isset($this->items[$name])) {
            unset($this->items[$name]);
        }
    }

    /**
     * Set current active menu in container item list, this will deactivate the currently active menu.
     *
     * @param string $name MenuItem name or dot sparated for multi level menu item
     */
    public function setActive($name)
    {
        if ($this->activeItem) {
            $this->activeItem->setActive(false);
        }

        $item = $this->getItem($name);

        $this->activeItem = $item;
        $item->setActive();
    }

    /**
     * Get current active item in the list.
     *
     * @return MenuItem
     */
    public function getActiveItem()
    {
        return $this->activeItem;
    }

    /**
     * Check if the menu container has active item.
     *
     * @return boolean
     */
    public function hasActiveItem()
    {
        return !is_null($this->activeItem);
    }

    /**
     * Render menu item list based on registered MenuRenderer.
     *
     * @return string rendered item list
     */
    public function render()
    {
        if ($this->renderer) {
            return $this->renderer->render($this);
        }
    }

    /**
     * Set menu renderer to render menu into specific format.
     *
     * @param MenuRendererInterface $renderer The menu renderer class
     */
    public function setRenderer(MenuRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }
}
