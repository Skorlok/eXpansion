<?php

namespace ManiaLivePlugins\eXpansion\Players\Gui\Controls;

use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Data\Player;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Structures\OptimizedPagerElement;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;

class Playeritem extends Control implements OptimizedPagerElement
{
    protected $bg;
    protected $forceSpecButton;
    protected $forcePlayButton;
    protected $switchSpecButton;
    protected $switchPlayButton;
    protected $guestButton;
    protected $ignoreButton;
    protected $kickButton;
    protected $banButton;
    protected $blacklistButton;
    protected $teamButton;
    protected $login;
    protected $nickname;
    protected $ignoreAction;
    protected $kickAction;
    protected $banAction;
    protected $blacklistAction;
    protected $forceAction;
    protected $guestAction;
    protected $frame;
    protected $recipient;
    protected $widths;
    protected $team;
    protected $icon;
    protected $toggleTeam = null;

    /** @var Player */
    protected $player;
    protected $columnCount = 1;

    /** @var Connection */
    protected $connection;

    public function __construct($indexNumber, $login, $action)
    {
        $this->storage = Storage::getInstance();
        $this->connection = Singletons::getInstance()->getDediConnection();

        $this->recipient = $login;
        $sizeY = 6;
        $sizeX = 135;
        $this->bg = new ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);


        $this->frame = new Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new Line());

        $this->nickname = new Label(50, 4);
        $this->nickname->setAlign('left', 'center');
        $this->nickname->setId('column_' . $indexNumber . '_0');
        $this->nickname->setScriptEvents();
        $this->frame->addComponent($this->nickname);

        $this->login = new Label(30, 4);
        $this->login->setAlign('left', 'center');
        $this->login->setId('column_' . $indexNumber . '_1');
        $this->frame->addComponent($this->login);

        $spacer = new Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(Icons64x64_1::EmptyIcon);

        $this->frame->addComponent($spacer);

        // admin additions
        if (AdminGroups::isInList($login)) {
            if (AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE)) {
                $this->ignoreButton = new \ManiaLive\Gui\Elements\Xml();
                $this->ignoreButton->setContent('<frame posn="84 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Ignore/UnIgnore player', $login), 50), null, null, "fff", null, $action, null, null, array('Icons128x128_1', 'Easy'), 'column_' . $indexNumber . '_2', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->ignoreButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_KICK)) {
                $this->kickButton = new \ManiaLive\Gui\Elements\Xml();
                $this->kickButton->setContent('<frame posn="90.75 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Kick player', $login), 50), null, null, "fff", null, $action, null, null, array('Icons128x128_1', 'Medium'), 'column_' . $indexNumber . '_3', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->kickButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_BAN)) {
                $this->banButton = new \ManiaLive\Gui\Elements\Xml();
                $this->banButton->setContent('<frame posn="97.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Ban player', $login), 50), null, null, "fff", null, $action, null, null, array('Icons128x128_1', 'Hard'), 'column_' . $indexNumber . '_4', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->banButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_BLACK)) {
                $this->blacklistButton = new \ManiaLive\Gui\Elements\Xml();
                $this->blacklistButton->setContent('<frame posn="104.25 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Blacklist player', $login), 50), null, null, "fff", null, $action, null, null, array('Icons128x128_1', 'Extreme'), 'column_' . $indexNumber . '_5', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->blacklistButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->switchSpecButton = new \ManiaLive\Gui\Elements\Xml();
                $this->switchSpecButton->setContent('<frame posn="111 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Switch to spectate', $login), 50), null, null, null, null, $action, null, null, array('BgRaceScore2', 'Spectator'), 'column_' . $indexNumber . '_6', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->switchSpecButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->switchPlayButton = new \ManiaLive\Gui\Elements\Xml();
                $this->switchPlayButton->setContent('<frame posn="117 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Switch to play', $login), 50), null, null, null, null, $action, null, null, array('Icons64x64_1', 'Opponents'), 'column_' . $indexNumber . '_7', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->switchPlayButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->forceSpecButton = new \ManiaLive\Gui\Elements\Xml();
                $this->forceSpecButton->setContent('<frame posn="123 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Force to spectate', $login), 50), null, null, null, null, $action, null, null, array('BgRaceScore2', 'Spectator'), 'column_' . $indexNumber . '_8', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->forceSpecButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->forcePlayButton = new \ManiaLive\Gui\Elements\Xml();
                $this->forcePlayButton->setContent('<frame posn="129 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Force to play', $login), 50), null, null, null, null, $action, null, null, array('Icons64x64_1', 'Opponents'), 'column_' . $indexNumber . '_9', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->forcePlayButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_GUEST)) {
                $this->guestButton = new \ManiaLive\Gui\Elements\Xml();
                $this->guestButton->setContent('<frame posn="135 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Add to guest list', $login), 50), null, null, null, null, $action, null, null, array('Icons128x128_1', 'Buddies'), 'column_' . $indexNumber . '_10', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->guestButton);

                $this->columnCount++;
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_CHANGE_TEAM)) {
                $this->teamButton = new \ManiaLive\Gui\Elements\Xml();
                $this->teamButton->setContent('<frame posn="141 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(5, 5, null, array(__('Switch player team', $login), 50), null, null, null, null, $action, null, null, array('Icons128x32_1', 'RT_Team'), 'column_' . $indexNumber . '_11', "eXpOptimizedPagerAction", null) . '</frame>');
                $this->frame->addComponent($this->teamButton);

                $this->columnCount++;
            }
        }

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSize($this->getSizeX(), $this->getSizeY());
        $this->bg->setSize($this->getSizeX() + 15, $this->getSizeY());
    }

    public function destroy()
    {
        $this->destroyComponents();

        parent::destroy();
    }

    public function getNbTextColumns()
    {
        return 2;
    }
}
