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
use ManiaLivePlugins\eXpansion\Gui\Elements\Button as MyButton;
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
                $this->ignoreButton = new MyButton(7, 5);
                $this->ignoreButton->setDescription(__('Ignore/UnIgnore player', $login), 50);
                $this->ignoreButton->setTextColor("fff");
                $this->ignoreButton->colorize("a22");
                $this->ignoreButton->setAction($action);
                $this->ignoreButton->setIcon('Icons128x128_1', 'Easy');
                //$this->ignoreButton->setIcon('Icons128x128_1', 'Beginner'); IN CASE OF ALREADY MUTED
                $this->ignoreButton->setId('column_' . $indexNumber . '_2');
                $this->ignoreButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->ignoreButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_KICK)) {
                $this->kickButton = new MyButton(7, 5);
                $this->kickButton->setDescription(__('Kick player', $login), 50);
                $this->kickButton->setTextColor("fff");
                $this->kickButton->colorize("a22");
                $this->kickButton->setAction($action);
                $this->kickButton->setIcon('Icons128x128_1', 'Medium');
                $this->kickButton->setId('column_' . $indexNumber . '_3');
                $this->kickButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->kickButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_BAN)) {
                $this->banButton = new MyButton(7, 5);
                $this->banButton->setDescription(__('Ban player', $login), 50);
                $this->banButton->setTextColor("fff");
                $this->banButton->colorize("a22");
                $this->banButton->setAction($action);
                $this->banButton->setIcon('Icons128x128_1', 'Hard');
                $this->banButton->setId('column_' . $indexNumber . '_4');
                $this->banButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->banButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_BLACK)) {
                $this->blacklistButton = new MyButton(7, 5);
                $this->blacklistButton->setDescription(__('Blacklist player', $login), 50);
                $this->blacklistButton->setTextColor("fff");
                $this->blacklistButton->colorize("a22");
                $this->blacklistButton->setAction($action);
                $this->blacklistButton->setIcon('Icons128x128_1', 'Extreme');
                $this->blacklistButton->setId('column_' . $indexNumber . '_5');
                $this->blacklistButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->blacklistButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->switchSpecButton = new MyButton(6, 5);
                $this->switchSpecButton->setAction($action);
                $this->switchSpecButton->colorize("2f2");
                $this->switchSpecButton->setIcon('BgRaceScore2', 'Spectator');
                $this->switchSpecButton->setDescription(__('Switch to spectate', $login), 50);
                $this->switchSpecButton->setId('column_' . $indexNumber . '_6');
                $this->switchSpecButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->switchSpecButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->switchPlayButton = new MyButton(6, 5);
                $this->switchPlayButton->setAction($action);
                $this->switchPlayButton->colorize("2f2");
                $this->switchPlayButton->setIcon('Icons64x64_1', 'Opponents');
                $this->switchPlayButton->setDescription(__('Switch to play', $login), 50);
                $this->switchPlayButton->setId('column_' . $indexNumber . '_7');
                $this->switchPlayButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->switchPlayButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->forceSpecButton = new MyButton(6, 5);
                $this->forceSpecButton->setAction($action);
                $this->forceSpecButton->colorize("2f2");
                $this->forceSpecButton->setIcon('BgRaceScore2', 'Spectator');
                $this->forceSpecButton->setDescription(__('Force to spectate', $login), 50);
                $this->forceSpecButton->setId('column_' . $indexNumber . '_8');
                $this->forceSpecButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->forceSpecButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->forcePlayButton = new MyButton(6, 5);
                $this->forcePlayButton->setAction($action);
                $this->forcePlayButton->colorize("2f2");
                $this->forcePlayButton->setIcon('Icons64x64_1', 'Opponents');
                $this->forcePlayButton->setDescription(__('Force to play', $login), 50);
                $this->forcePlayButton->setId('column_' . $indexNumber . '_9');
                $this->forcePlayButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->forcePlayButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_GUEST)) {
                $this->guestButton = new MyButton(6, 5);
                $this->guestButton->setAction($action);
                $this->guestButton->colorize("2f2");
                $this->guestButton->setIcon('Icons128x128_1', 'Buddies');
                $this->guestButton->setDescription(__('Add to guest list', $login), 50);
                $this->guestButton->setId('column_' . $indexNumber . '_10');

                $this->guestButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->guestButton);
            }

            if (AdminGroups::hasPermission($login, Permission::PLAYER_CHANGE_TEAM)) {
                $this->teamButton = new MyButton(6, 5);
                $this->teamButton->setAction($action);
                $this->teamButton->colorize("2f2");
                $this->teamButton->setIcon('Icons128x32_1', 'RT_Team');
                $this->teamButton->setDescription(__('Switch player team', $login), 50);
                $this->teamButton->setId('column_' . $indexNumber . '_11');

                $this->teamButton->setClass("eXpOptimizedPagerAction");
                $this->columnCount++;
                $this->frame->addComponent($this->teamButton);
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
        if (is_object($this->banButton)) {
            $this->banButton->destroy();
        }
        if (is_object($this->switchSpecButton)) {
            $this->switchSpecButton->destroy();
        }
        if (is_object($this->switchPlayButton)) {
            $this->switchPlayButton->destroy();
        }
        if (is_object($this->forceSpecButton)) {
            $this->forceSpecButton->destroy();
        }
        if (is_object($this->forcePlayButton)) {
            $this->forcePlayButton->destroy();
        }
        if (is_object($this->kickButton)) {
            $this->kickButton->destroy();
        }
        if (is_object($this->blacklistButton)) {
            $this->blacklistButton->destroy();
        }
        if (is_object($this->ignoreButton)) {
            $this->ignoreButton->destroy();
        }
        if (is_object($this->guestButton)) {
            $this->guestButton->destroy();
        }
        if (is_object($this->teamButton)) {
            $this->teamButton->destroy();
        }

        $this->destroyComponents();

        parent::destroy();
    }

    public function getNbTextColumns()
    {
        return 2;
    }
}
