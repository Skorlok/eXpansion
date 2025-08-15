<?php

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Windows;

use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Maps\Gui\Controls\Wishitem;

class Jukelist extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $items = array();
    public static $mainPlugin;

    /** @var \ManiaLive\Gui\Controls\Pager */
    protected $pager;
    protected $btnRemoveAll;
    protected $actionRemoveAll;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);

        $this->actionRemoveAll = $this->createAction(array(self::$mainPlugin, "emptyWishesGui"));
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        $this->pager->setSize($this->sizeX - 2, $this->sizeY - 14);
        $this->pager->setStretchContentX($this->sizeX);
        $this->pager->setPosition(4, 0);
    }

    protected function onDraw()
    {
        $login = $this->getRecipient();
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->pager->clearItems();
        $this->items = array();

        $isAdmin = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN);
        if ($isAdmin) {
            $this->btnRemoveAll = new \ManiaLive\Gui\Elements\Xml();
            $this->btnRemoveAll->setContent('<frame posn="4 -94 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Clear Jukebox", null, null, 'd00', null, null, $this->actionRemoveAll, null, null, null, null, null, null) . '</frame>');
            $this->mainFrame->addComponent($this->btnRemoveAll);
        }

        $x = 0;
        foreach ($this->maps as $map) {
            $this->items[$x] = new Wishitem($x, $login, $map, self::$mainPlugin, $isAdmin, $this->sizeX);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
        parent::onDraw();
    }

    public function setList($maps)
    {
        $this->maps = $maps;
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }
        $this->items = null;
        $this->pager->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
