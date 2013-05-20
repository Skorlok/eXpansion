<?php

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Windows;

use \ManiaLivePlugins\eXpansion\Maps\Gui\Controls\Mapitem;
use ManiaLive\Gui\ActionHandler;

class Maplist extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window {

    public static $records = array();
    public static $mapsPlugin = null;
    private $items = array();
    private $btnRemoveAll;
    private $actionRemoveAll;

    /** @var \ManiaLive\Gui\Controls\Pager */
    private $pager;

    /** @var  \DedicatedApi\Connection */
    private $connection;

    /** @var  \ManiaLive\Data\Storage */
    private $storage;

    protected function onConstruct() {
        parent::onConstruct();
        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \DedicatedApi\Connection::factory($config->host, $config->port);
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->pager = new \ManiaLive\Gui\Controls\Pager();
        $this->mainFrame->addComponent($this->pager);
        $login = $this->getRecipient();
        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, 'server_maps')) {
            $this->actionRemoveAll = $this->createAction(array($this, "removeAllMaps"));
            $this->btnRemoveAll = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
            $this->btnRemoveAll->setAction($this->actionRemoveAll);
            $this->btnRemoveAll->setText("Clear Maplist");
            $this->btnRemoveAll->colorize("d00");
            $this->mainFrame->addComponent($this->btnRemoveAll);
        }
    }

    static function Initialize($mapsPlugin) {
        self::$mapsPlugin = $mapsPlugin;
    }

    function gotoMap($login, \DedicatedApi\Structures\Map $map) {
        self::$mapsPlugin->gotoMap($login, $map);
        $this->Erase($this->getRecipient());
    }

    function removeMap($login, \DedicatedApi\Structures\Map $map) {
        self::$mapsPlugin->removeMap($login, $map);
        $this->RedrawAll();
    }

    function queueMap($login, \DedicatedApi\Structures\Map $map, $isTemp = false) {
        self::$mapsPlugin->queueMap($login, $map, $isTemp);
    }

    function showRec($login, \DedicatedApi\Structures\Map $map) {
        self::$mapsPlugin->showRec($login, $map);
    }

    function onResize($oldX, $oldY) {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX - 2, $this->sizeY - 14);
        $this->pager->setStretchContentX($this->sizeX);
        $this->pager->setPosition(4, 0);
        if (is_object($this->btnRemoveAll))
            $this->btnRemoveAll->setPosition(4, -$this->sizeY + 6);
    }

    function removeAllMaps($login) {
        $mapsAtServer = array();
        $maps = $this->connection->getMapList(-1, 0);

        foreach ($maps as $map) {
            $mapsAtServer[] = $map->fileName;
        }

        array_shift($mapsAtServer);

        try {
            $this->connection->RemoveMapList($mapsAtServer);
            $this->connection->chatSendServerMessage("Maplist cleared with:" . count($mapsAtServer) . " maps!", $login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("Oops, couldn't clear the map list. server said:" . $e->getMessage());
        }
    }

    protected function onDraw() {
        $login = $this->getRecipient();
        foreach ($this->items as $item) {
            $item->destroy();
        }

        $this->pager->clearItems();
        $this->items = array();


        $isAdmin = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, 'server_maps');
        $localrec = self::$mapsPlugin->isLocalRecordsLoaded();
        $x = 0;
        foreach (\ManiaLive\Data\Storage::getInstance()->maps as $map) {
            $this->items[$x] = new Mapitem($x, $login, $map, $this, $isAdmin, $localrec, $this->sizeX);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }

        parent::onDraw();
    }

    function destroy() {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = null;
        if (is_object($this->btnRemoveAll))
            $this->btnRemoveAll->destroy();
        $this->pager->destroy();
        $this->clearComponents();
        parent::destroy();
    }

}

?>
