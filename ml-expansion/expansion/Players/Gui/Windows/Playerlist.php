<?php

namespace ManiaLivePlugins\eXpansion\Players\Gui\Windows;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Playerlist extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $pager;

    /** @var \ManiaLivePlugins\eXpansion\Players\Players */
    public static $mainPlugin;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var \ManiaLive\Data\Storage */
    protected $storage;
    protected $items = array();
    protected $frame;
    protected $title_status;
    protected $title_login;
    protected $title_nickname;

    public static $widths = array(1, 8, 6, 6);

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();
        $this->setScriptEvents();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\OptimizedPager();
        $this->mainFrame->addComponent($this->pager);
        $this->setName("Players on server");

        $line = new \ManiaLive\Gui\Controls\Frame(24, 2);
        $line->setLayout(new \ManiaLib\Gui\Layouts\Line());


        if (AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE)) {
            $btn = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
            $btn->setText(__("Ignore List", $login));
            $btn->setAction(\ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin::$showActions['ignore']);
            $line->addComponent($btn);
        }

        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            $btn = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
            $btn->setText(__("Guest List", $login));
            $btn->setAction(\ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin::$showActions['guest']);
            $line->addComponent($btn);
        }

        if (AdminGroups::hasPermission($login, Permission::PLAYER_UNBAN)) {
            $btn = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
            $btn->setText(__("Ban List", $login));
            $btn->setAction(\ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin::$showActions['ban']);
            $line->addComponent($btn);
        }
        if (AdminGroups::hasPermission($login, Permission::PLAYER_BLACK)) {
            $btn = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
            $btn->setText(__("Black List", $login));
            $btn->setAction(\ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin::$showActions['black']);
            $line->addComponent($btn);
        }

        $this->mainFrame->addComponent($line);

        Gui::getScaledSize(self::$widths, $this->sizeX);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setPosition(0, -6);
        $this->pager->setSize($this->sizeX, $this->sizeY - 10);
    }

    public function onDraw()
    {
        $this->populateList();
        parent::onDraw();
    }

    private function populateList()
    {

        $this->pager->clearItems();
        $this->items = array();
        $this->storage = \ManiaLive\Data\Storage::getInstance();
        $login = $this->getRecipient();

        foreach ($this->storage->players as $player) {
            $ignoreAction = $this->createAction(array(self::$mainPlugin, 'ignorePlayer'), $player->login);
            $kickAction = $this->createAction(array(self::$mainPlugin, 'kickPlayer'), $player->login);
            $banAction = $this->createAction(array(self::$mainPlugin, 'banPlayer'), $player->login);
            $blacklistAction = $this->createAction(array(self::$mainPlugin, 'blacklistPlayer'), $player->login);
            $switchSpecAction = $this->createAction(array(self::$mainPlugin, 'switchSpec'), $player->login);
            $switchPlayAction = $this->createAction(array(self::$mainPlugin, 'switchPlay'), $player->login);
            $forceSpecAction = $this->createAction(array(self::$mainPlugin, 'toggleSpec'), $player->login);
            $forcePlayAction = $this->createAction(array(self::$mainPlugin, 'togglePlay'), $player->login);
            $guestAction = $this->createAction(array(self::$mainPlugin, 'guestlistPlayer'), $player->login);
            $teamAction = $this->createAction(array(self::$mainPlugin, 'toggleTeam'), $player->login);

            $this->pager->addSimpleItems(array(Gui::fixString($player->nickName) . " " => -1,
                Gui::fixString($player->login) => -1,
                "ignore" => $ignoreAction,
                "kick" => $kickAction,
                "ban" => $banAction,
                "blacklist" => $blacklistAction,
                "switchSpec" => $switchSpecAction,
                "switchPlay" => $switchPlayAction,
                "forceSpec" => $forceSpecAction,
                "forcePlay" => $forcePlayAction,
                "guest" => $guestAction,
                "team" => $teamAction
            ));
        }
        foreach ($this->storage->spectators as $player) {

            $ignoreAction = $this->createAction(array(self::$mainPlugin, 'ignorePlayer'), $player->login);
            $kickAction = $this->createAction(array(self::$mainPlugin, 'kickPlayer'), $player->login);
            $banAction = $this->createAction(array(self::$mainPlugin, 'banPlayer'), $player->login);
            $blacklistAction = $this->createAction(array(self::$mainPlugin, 'blacklistPlayer'), $player->login);
            $switchSpecAction = $this->createAction(array(self::$mainPlugin, 'switchSpec'), $player->login);
            $switchPlayAction = $this->createAction(array(self::$mainPlugin, 'switchPlay'), $player->login);
            $forceSpecAction = $this->createAction(array(self::$mainPlugin, 'toggleSpec'), $player->login);
            $forcePlayAction = $this->createAction(array(self::$mainPlugin, 'togglePlay'), $player->login);
            $guestAction = $this->createAction(array(self::$mainPlugin, 'guestlistPlayer'), $player->login);
            $teamAction = $this->createAction(array(self::$mainPlugin, 'toggleTeam'), $player->login);

            $this->pager->addSimpleItems(array(Gui::fixString($player->nickName) . " " => -1,
                Gui::fixString($player->login) => -1,
                "ignore" => $ignoreAction,
                "kick" => $kickAction,
                "ban" => $banAction,
                "blacklist" => $blacklistAction,
                "switchSpec" => $switchSpecAction,
                "switchPlay" => $switchPlayAction,
                "forceSpec" => $forceSpecAction,
                "forcePlay" => $forcePlayAction,
                "guest" => $guestAction,
                "team" => $teamAction
            ));
        }

        $this->pager->setContentLayout('\\ManiaLivePlugins\\eXpansion\\Players\\Gui\\Controls\\Playeritem');
        $this->pager->update($this->getRecipient());
    }

    public function destroy()
    {
        $this->connection = null;
        $this->storage = null;
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = null;

        parent::destroy();
    }
}
