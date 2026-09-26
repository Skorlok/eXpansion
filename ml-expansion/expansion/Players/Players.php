<?php

namespace ManiaLivePlugins\eXpansion\Players;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class Players extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public $msg_broadcast;

    /** @var Window */
    private $playerListWindow;

    /**
     * Admins waiting for a forced spec/play change to actually happen before their
     * player list is refreshed (the storage is not updated synchronously by forceSpectator()).
     *
     * @var array  targetLogin => array(adminLogin => true)
     */
    private $pendingSideRefresh = array();

    /** @var Window */
    private $configSpecWindow;

    public function eXpOnInit()
    {
        parent::eXpOnInit();

        $this->addDependency(new \ManiaLive\PluginHandler\Dependency("\\ManiaLivePlugins\\eXpansion\\ChatAdmin\\ChatAdmin"));
    }

    public function eXpOnLoad()
    {
        $this->msg_broadcast = eXpGetMessage('%s$1 $z$s$fff is $f00broadcasting$fff at $lwww.twitch.tv$l, say hello to all the viewers :)');
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableStorageEvents();
        $this->registerChatCommand("players", "showPlayerList", 0, true); // xaseco
        $this->registerChatCommand("plist", "showPlayerList", 0, true); // fast

        $this->setPublicMethod("showPlayerList");

        $this->registerManialinkCallback('warnPlayer', false, true);
        $this->registerManialinkCallback('configSpec', false, true);
        $this->registerManialinkCallback('configSpecSelectTarget', true, true);
        $this->registerManialinkCallback('configSpecSelectAutomaticTarget', true, true);
        $this->registerManialinkCallback('applyConfigSpec', true, true);
        $this->registerManialinkCallback('ignorePlayer', false, true);
        $this->registerManialinkCallback('kickPlayer', false, true);
        $this->registerManialinkCallback('banPlayer', false, true);
        $this->registerManialinkCallback('blacklistPlayer', false, true);
        $this->registerManialinkCallback('guestlistPlayer', false, true);
        $this->registerManialinkCallback('switchSpec', false, true);
        $this->registerManialinkCallback('forceSpectator', false, true);
        $this->registerManialinkCallback('forcePlayer', false, true);
        $this->registerManialinkCallback('setSpectator', false, true);
        $this->registerManialinkCallback('setPlayer', false, true);
        $this->registerManialinkCallback('toggleTeam', false, true);

        $this->playerListWindow = new Window("Players\Gui\Windows\Playerlist.xml");
        $this->playerListWindow->setName("Players on server");
        $this->playerListWindow->setSize(186, 100);

        $this->configSpecWindow = new Window("Players\Gui\Windows\ConfigSpec.xml");
        $this->configSpecWindow->setName("PlayerConfigSpec");
        $this->configSpecWindow->setSize(60, 85);
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $player = $this->storage->getPlayerObject($login);
        if ($player->isBroadcasting) {
            $this->announceBroadcasting($player->login);
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        unset($this->pendingSideRefresh[$login]);
        foreach ($this->pendingSideRefresh as $target => $admins) {
            unset($this->pendingSideRefresh[$target][$login]);
            if (empty($this->pendingSideRefresh[$target])) {
                unset($this->pendingSideRefresh[$target]);
            }
        }
    }

    public function announceBroadcasting($login)
    {
        $player = $this->storage->getPlayerObject($login);
        $this->eXpChatSendServerMessage($this->msg_broadcast, null, array($player->cleanNickName));
    }

    public function onPlayerChangeSide($player, $oldSide)
    {
        $login = $player->login;
        if (!isset($this->pendingSideRefresh[$login])) {
            return;
        }

        $admins = array_keys($this->pendingSideRefresh[$login]);
        unset($this->pendingSideRefresh[$login]);

        foreach ($admins as $admin) {
            if (isset($this->storage->players[$admin]) || isset($this->storage->spectators[$admin])) {
                $this->showPlayerList($admin);
            }
        }
    }

    public function showPlayerList($login)
    {
        $hideIgnoreListBtn = !AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE);
        $hideGuestListBtn  = !AdminGroups::hasPermission($login, Permission::PLAYER_GUEST);
        $hideBanListBtn    = !AdminGroups::hasPermission($login, Permission::PLAYER_UNBAN);
        $hideBlackListBtn  = !AdminGroups::hasPermission($login, Permission::PLAYER_UNBLACK);

        $hideWarn              = !AdminGroups::hasPermission($login, Permission::PLAYER_WARN);
        $hideIgnore            = !AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE);
        $hideKick              = !AdminGroups::hasPermission($login, Permission::PLAYER_KICK);
        $hideBan               = !AdminGroups::hasPermission($login, Permission::PLAYER_BAN);
        $hideBlacklist         = !AdminGroups::hasPermission($login, Permission::PLAYER_BLACK);
        $hideForceSpecActions  = !AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC);
        $hideGuest             = !AdminGroups::hasPermission($login, Permission::PLAYER_GUEST);
        $hideTeam              = !AdminGroups::hasPermission($login, Permission::PLAYER_CHANGE_TEAM);

        $items = array();
        $data  = array();
        $i     = 0;

        $ignoredLogins = array();
        foreach ($this->connection->getIgnoreList(-1, 0) as $ig) {
            $ignoredLogins[$ig->login] = true;
        }
        $specLogins = array();
        foreach ($this->storage->spectators as $s) {
            $specLogins[$s->login] = true;
        }

        $txtWarn       = __('Warn', $login);
        $txtKick       = __('Kick', $login);
        $txtBan        = __('Ban', $login);
        $txtBlacklist  = __('Blacklist', $login);
        $txtConfigSpec = __('Config Spec', $login);

        foreach (array_merge($this->storage->players, $this->storage->spectators) as $player) {
            $target    = $player->login;
            $isSpec    = isset($specLogins[$target]);
            $isIgnored = isset($ignoredLogins[$target]);

            $lang = $player->language !== '' ? substr($player->language, 0, 2) : '--';

            $items[$i] = array(
                Gui::fixString($player->nickName) . " ",                         // 0  nickname
                Gui::fixString($target),                                         // 1  login
                $lang,                                                           // 2  language
                $txtWarn,                                                        // 3  Warn
                $isIgnored ? __('UnIgnore', $login) : __('Ignore', $login),      // 4  (Un)Ignore
                $txtKick,                                                        // 5  Kick
                $txtBan,                                                         // 6  Ban
                $txtBlacklist,                                                   // 7  Blacklist
                $isSpec ? __('Switch Play', $login) : __('Switch Spec', $login), // 8  Switch
                $txtConfigSpec,                                                  // 9  Force status (target/camera/lock)
            );
            $data[$i]  = array(
                -1,                                                 // 0
                -1,                                                 // 1
                -1,                                                 // 2  language, not clickable
                'exp:eXpansion.Players:warnPlayer:' . $target,      // 3
                'exp:eXpansion.Players:ignorePlayer:' . $target,    // 4  (toggles internally)
                'exp:eXpansion.Players:kickPlayer:' . $target,      // 5
                'exp:eXpansion.Players:banPlayer:' . $target,       // 6
                'exp:eXpansion.Players:blacklistPlayer:' . $target, // 7
                'exp:eXpansion.Players:switchSpec:' . $target,      // 8  (toggles internally)
                'exp:eXpansion.Players:configSpec:' . $target,      // 9
                'exp:eXpansion.Players:guestlistPlayer:' . $target, // 10 guest icon
                'exp:eXpansion.Players:toggleTeam:' . $target,      // 11 team icon
            );
            $i++;
        }

        $this->playerListWindow->setTitle('Players');
        $this->playerListWindow->setParam("hideIgnoreListBtn",    $hideIgnoreListBtn);
        $this->playerListWindow->setParam("hideGuestListBtn",     $hideGuestListBtn);
        $this->playerListWindow->setParam("hideBanListBtn",       $hideBanListBtn);
        $this->playerListWindow->setParam("hideBlackListBtn",     $hideBlackListBtn);
        $this->playerListWindow->setParam("hideWarn",             $hideWarn);
        $this->playerListWindow->setParam("ignoreListAction",     ChatAdmin::$showActions['ignore']);
        $this->playerListWindow->setParam("guestListAction",      ChatAdmin::$showActions['guest']);
        $this->playerListWindow->setParam("banListAction",        ChatAdmin::$showActions['ban']);
        $this->playerListWindow->setParam("blackListAction",      ChatAdmin::$showActions['black']);
        $this->playerListWindow->setParam("hideIgnore",           $hideIgnore);
        $this->playerListWindow->setParam("hideKick",             $hideKick);
        $this->playerListWindow->setParam("hideBan",              $hideBan);
        $this->playerListWindow->setParam("hideBlacklist",        $hideBlacklist);
        $this->playerListWindow->setParam("hideForceSpecActions", $hideForceSpecActions);
        $this->playerListWindow->setParam("hideGuest",            $hideGuest);
        $this->playerListWindow->setParam("hideTeam",             $hideTeam);
        $this->playerListWindow->setParam("playerItems",          $items);
        $this->playerListWindow->setParam("playerData",           $data);
        $this->playerListWindow->show($login);
    }

    public function configSpec($login, $target = null)
    {
        $target = str_replace('–', '-', $target);
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage("#admin_error#You are not allowed to do that!", $login);
            return;
        }

        $this->renderConfigSpec($login, $target, '', array());
    }

    public function configSpecSelectTarget($login, $spectator, $entries = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage("#admin_error#You are not allowed to do that!", $login);
            return;
        }
        Gui::showPlayerSelection($login, array($this, 'configSpecTargetSelected'), 'Select target to watch', 'Select', array($entries, $spectator), $spectator);
    }

    public function configSpecSelectAutomaticTarget($login, $spectator, $entries = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage("#admin_error#You are not allowed to do that!", $login);
            return;
        }
        $this->renderConfigSpec($login, $spectator, '', $entries);
    }

    public function configSpecTargetSelected($login, $targetLogin, $extra)
    {
        list($entries, $spectator) = $extra;
        $this->renderConfigSpec($login, $spectator, $targetLogin, $entries);
    }

    private function renderConfigSpec($login, $spectator, $targetLogin, $entries)
    {
        $spectatorObj = $this->storage->getPlayerObject($spectator);
        if ($spectatorObj === null) {
            return;
        }

        $targetObj = $targetLogin !== '' ? $this->storage->getPlayerObject($targetLogin) : null;
        if ($targetObj === null) {
            $targetLogin = '';
            $targetName  = '$i-- ' . __('automatic', $login) . ' --';
        } else {
            $targetName  = $targetObj->nickName;
        }

        $camFree     = isset($entries['cam_free']) && $entries['cam_free'] == '1';
        $camReplay   = isset($entries['cam_replay']) && $entries['cam_replay'] == '1';
        $camFollow   = isset($entries['cam_follow']) && $entries['cam_follow'] == '1';
        $forceSpec   = isset($entries['status_forcespec']) && $entries['status_forcespec'] == '1';

        $this->configSpecWindow->setTitle('Force status: %s', array($spectatorObj->cleanNickName));
        $this->configSpecWindow->setParam("playerLogin", $spectator);
        $this->configSpecWindow->setParam("specTargetLogin", $targetLogin);
        $this->configSpecWindow->setParam("specTargetName", $this->configSpecWindow->handleSpecialChars($targetName));
        $this->configSpecWindow->setParam("camUnchanged", !$camFree && !$camReplay && !$camFollow);
        $this->configSpecWindow->setParam("camFree", $camFree);
        $this->configSpecWindow->setParam("camReplay", $camReplay);
        $this->configSpecWindow->setParam("camFollow", $camFollow);
        $this->configSpecWindow->setParam("statusForceSpec", $forceSpec);
        $this->configSpecWindow->show($login);
    }

    public function applyConfigSpec($login, $param, $entries = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage("#admin_error#You are not allowed to do that!", $login);
            return;
        }

        $parts       = explode(':', $param, 2);
        $spectator   = $parts[0];
        $targetLogin = isset($parts[1]) ? $parts[1] : '';

        $camera = -1;
        if (isset($entries['cam_free'])   && $entries['cam_free']   == '1') $camera = 2;
        if (isset($entries['cam_replay']) && $entries['cam_replay'] == '1') $camera = 0;
        if (isset($entries['cam_follow']) && $entries['cam_follow'] == '1') $camera = 1;

        $forceSpec = isset($entries['status_forcespec'])&& $entries['status_forcespec'] == '1';
        $mode      = $forceSpec ? 1 : 3;

        try {
            $this->connection->forceSpectator($spectator, $mode);
            $this->connection->forceSpectatorTarget($spectator, $targetLogin, $camera);

            $admin         = $this->storage->getPlayerObject($login);
            $spectatorObj  = $this->storage->getPlayerObject($spectator);
            $spectatorNick = $spectatorObj ? $spectatorObj->cleanNickName : $spectator;
            $adminNick     = $admin        ? $admin->cleanNickName       : $login;

            if ($camera === 2) {
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces spectator free mode on#variable# %s #admin_action#.', null, array($adminNick, $spectatorNick));
            } else {
                $targetObj  = $targetLogin !== '' ? $this->storage->getPlayerObject($targetLogin) : null;
                $targetNick = $targetObj ? $targetObj->cleanNickName : __('automatic', $login);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the player#variable# %s #admin_action#to spectate#variable# %s', null, array($adminNick, $spectatorNick, $targetNick));
            }
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }

        $this->pendingSideRefresh[$spectator][$login] = true;

        $this->configSpecWindow->erase($login);
    }

    public function warnPlayer($login, $target)
    {
        $target = str_replace('–', '-', $target);
        try {
            AdminGroups::getInstance()->adminCmd($login, "warn " . $target);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function ignorePlayer($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_IGNORE)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
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
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action# ignores the player#variable# %s', null, array($admin->cleanNickName, $player->cleanNickName));
            } else {
                $this->connection->unignore($target);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unignores the player %s', null, array($admin->cleanNickName, $player->cleanNickName));
            }

            $this->showPlayerList($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function kickPlayer($login, $target)
    {
        $target = str_replace('–', '-', $target);
        try {
            AdminGroups::getInstance()->adminCmd($login, "kick " . $target);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function banPlayer($login, $target)
    {
        $target = str_replace('–', '-', $target);
        try {
            AdminGroups::getInstance()->adminCmd($login, "ban " . $target);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function blacklistPlayer($login, $target)
    {
        $target = str_replace('–', '-', $target);
        try {
            AdminGroups::getInstance()->adminCmd($login, "black " . $target);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function switchSpec($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            $player = $this->storage->getPlayerObject($target);
            $admin  = $this->storage->getPlayerObject($login);

            if (isset($this->storage->spectators[$target])) {
                $this->connection->forceSpectator($target, 2);
                $this->connection->forceSpectator($target, 0);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the spectator#variable# %s #admin_action#to play.', null, array($admin->cleanNickName, $player->cleanNickName));
            } else {
                $this->connection->forceSpectator($target, 1);
                $this->connection->forceSpectator($target, 0);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the player#variable# %s #admin_action#to spectate.', null, array($admin->cleanNickName, $player->cleanNickName));
            }
            $this->pendingSideRefresh[$target][$login] = true;
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function forceSpectator($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 1);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the player#variable# %s #admin_action#to spectate.', null, array($admin->cleanNickName, $player->cleanNickName));

            $this->pendingSideRefresh[$target][$login] = true;
            $this->configSpecWindow->erase($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function forcePlayer($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 2);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the spectator#variable# %s #admin_action#to play.', null, array($admin->cleanNickName, $player->cleanNickName));

            $this->pendingSideRefresh[$target][$login] = true;
            $this->configSpecWindow->erase($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function setSpectator($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 1);
            $this->connection->forceSpectator($target, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the player#variable# %s #admin_action#to spectate.', null, array($admin->cleanNickName, $player->cleanNickName));

            $this->pendingSideRefresh[$target][$login] = true;
            $this->configSpecWindow->erase($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function setPlayer($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_FORCESPEC)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            $player = $this->storage->getPlayerObject($target);
            $admin = $this->storage->getPlayerObject($login);

            $this->connection->forceSpectator($target, 2);
            $this->connection->forceSpectator($target, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Switchs the spectator#variable# %s #admin_action#to play.', null, array($admin->cleanNickName, $player->cleanNickName));

            $this->pendingSideRefresh[$target][$login] = true;
            $this->configSpecWindow->erase($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function guestlistPlayer($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_GUEST)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }
        $target = str_replace('–', '-', $target);
        try {
            AdminGroups::getInstance()->adminCmd($login, "guest " . $target);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('#admin_error#' . $e->getMessage(), $login);
        }
    }

    public function toggleTeam($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::PLAYER_CHANGE_TEAM)) {
            $this->eXpChatSendServerMessage('$ff3$iYou are not allowed to do that!', $login);
            return;
        }

        $target = str_replace('–', '-', $target);
        $player = $this->storage->getPlayerObject($target);
        $admin = $this->storage->getPlayerObject($login);
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();
        if ($player->teamId === 0) {
            $this->connection->forcePlayerTeam($target, 1);
            $team = ((isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) ? '$'.$var["team2Color"] . $var["team2Name"] : '$f00Red');
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team '. $team . '.', null, array($admin->cleanNickName, $player->cleanNickName));
        } else if ($player->teamId === 1) {
            $this->connection->forcePlayerTeam($target, 0);
            $team = ((isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) ? '$'.$var["team1Color"] . $var["team1Name"] : '$00fBlue');
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team '. $team . '.', null, array($admin->cleanNickName, $player->cleanNickName));
        } else {
            $this->eXpChatSendServerMessage('%s$z$s$fff is a spectator and can not be forced into a team', $login, array($player->cleanNickName));
        }
    }

    public function eXpOnUnload()
    {
        if ($this->playerListWindow instanceof Window) {
            $this->playerListWindow->erase();
        }
        $this->playerListWindow = null;

        if ($this->configSpecWindow instanceof Window) {
            $this->configSpecWindow->erase();
        }
        $this->configSpecWindow = null;
    }
}
