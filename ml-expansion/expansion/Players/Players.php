<?php

namespace ManiaLivePlugins\eXpansion\Players;

use ManiaLivePlugins\eXpansion\Players\Gui\Windows\Playerlist;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;

class Players extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public $msg_broadcast;

    public function eXpOnInit()
    {
        parent::eXpOnInit();

        $this->addDependency(new \ManiaLive\PluginHandler\Dependency("\\ManiaLivePlugins\\eXpansion\\ChatAdmin\\ChatAdmin"));

        Gui\Windows\Playerlist::$mainPlugin = $this;
    }

    public function eXpOnLoad()
    {
        $this->msg_broadcast = eXpGetMessage('%s$1 $z$s$fff is $f00broadcasting$fff at $lwww.twitch.tv$l, say hello to all the viewers :)');
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->registerChatCommand("players", "showPlayerList", 0, true); // xaseco
        $this->registerChatCommand("plist", "showPlayerList", 0, true); // fast

        $this->setPublicMethod("showPlayerList");

        if ($this->isPluginLoaded('eXpansion\Menu')) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Menu', 'addSeparator', __('Players'), false);
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Menu', 'addItem', __('Show Players'), null, array($this, 'showPlayerList'), false);
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        \ManiaLivePlugins\eXpansion\Players\Gui\Windows\Playerlist::Erase($login);
        // needs to be removed, autoupdate windows doesn't work good with high number of players
        //$this->updateOpenedWindows();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        // needs to be removed, autoupdate windows doesn't work good with high number of players
        //$this->updateOpenedWindows();
        $player = $this->storage->getPlayerObject($login);
        if ($player->isBroadcasting) {
            $this->announceBroadcasting($player->login);
        }
    }

    public function updateOpenedWindows()
    {
        $windows = \ManiaLivePlugins\eXpansion\Players\Gui\Windows\Playerlist::GetAll();
        foreach ($windows as $window) {
            $window->redraw($window->getRecipient());
        }
    }

    public function announceBroadcasting($login)
    {
        $player = $this->storage->getPlayerObject($login);
        $this->eXpChatSendServerMessage($this->msg_broadcast, null, array($player->nickName));
    }

    public function showPlayerList($login)
    {
        \ManiaLivePlugins\eXpansion\Players\Gui\Windows\Playerlist::Erase($login);
        $window = \ManiaLivePlugins\eXpansion\Players\Gui\Windows\Playerlist::Create($login);
        $window->setTitle('Players');

        $window->setSize(155, 100);
        $window->centerOnScreen();
        $window->show();
    }
    
    public function ignorePlayer($login, $target)
    {
        try {
            if (!AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE)) {
                $this->connection->chatSendServerMessage(__('$ff3$iYou are not allowed to do that!', $login), $login);
            }
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);
            $list = $this->connection->getIgnoreList(-1, 0);
            $ignore = true;
            foreach ($list as $test) {
                if ($target == $test->login) {
                    $ignore = false;
                    break;
                }
            }
            if ($ignore) {
                $this->connection->ignore($target);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action# ignores the player#variable# %s', null, array($admin->nickName, $player->nickName));
            } else {
                $this->connection->unignore($target);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unignores the player %s', null, array($admin->nickName, $player->nickName));
            }

            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function kickPlayer($login, $target)
    {
        try {
            AdminGroups::getInstance()->adminCmd($login, "kick " . $target);
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function banPlayer($login, $target)
    {
        try {
            AdminGroups::getInstance()->adminCmd($login, "ban " . $target);
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function blacklistPlayer($login, $target)
    {
        try {
            AdminGroups::getInstance()->adminCmd($login, "black " . $target);
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function guestlistPlayer($login, $target)
    {
        try {
            AdminGroups::getInstance()->adminCmd($login, "guest " . $target);
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function toggleSpec($login, $target)
    {
        try {
            if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->connection->chatSendServerMessage(__('$ff3$iYou are not allowed to do that!', $login), $login);
            }
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 1);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the player#variable# %s #admin_action#to spectate.', null, array($admin->nickName, $player->nickName));
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function togglePlay($login, $target)
    {
        try {
            if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->connection->chatSendServerMessage(__('$ff3$iYou are not allowed to do that!', $login), $login);
            }
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 2);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the spectator#variable# %s #admin_action#to play.', null, array($admin->nickName, $player->nickName));
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function switchSpec($login, $target)
    {
        try {
            if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->connection->chatSendServerMessage(__('$ff3$iYou are not allowed to do that!', $login), $login);
            }
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 1);
            $this->connection->forceSpectator($target, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the player#variable# %s #admin_action#to spectate.', null, array($admin->nickName, $player->nickName));
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function switchPlay($login, $target)
    {
        try {
            if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
                $this->connection->chatSendServerMessage(__('$ff3$iYou are not allowed to do that!', $login), $login);
            }
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 2);
            $this->connection->forceSpectator($target, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the spectator#variable# %s #admin_action#to play.', null, array($admin->nickName, $player->nickName));
            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function toggleTeam($login, $target)
    {
        if (AdminGroups::hasPermission($login, Permission::PLAYER_CHANGE_TEAM)) {
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);
            if ($player->teamId === 0) {
                $this->connection->forcePlayerTeam($target, 1);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team $f00Red.', null, array($admin->nickName, $player->nickName));
            } else if ($player->teamId === 1) {
                $this->connection->forcePlayerTeam($target, 0);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team $00fBlue.', null, array($admin->nickName, $player->nickName));
            } else {
                $this->connection->chatSendServerMessage(__('%s$z$s$fff is a spectator and can not be forced into a team', $login, $player->nickName));
            }
            $this->showPlayerList($login);
        }
    }

    public function eXpOnUnload()
    {
        PlayerList::EraseAll();
    }
}
