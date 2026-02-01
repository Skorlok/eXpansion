<?php
/**
 * eXpansion - Chat plugin
 *
 * @name Chat
 * @date      29-01-2013
 * @version   r1
 * @package   eXpansion
 *
 * @author    Petri Järvisalo
 * @copyright 2013
 *
 */

namespace ManiaLivePlugins\eXpansion\Chat;

use Exception;
use ManiaLib\Utils\Formatting;
use ManiaLive\Data\Player;
use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Utilities\Logger;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Redirects the chat in order to display it nicer.
 * Can be used to disable the chat as well.
 *
 * @package ManiaLivePlugins\eXpansion\Chat
 *
 * @author  Reaby
 */
class Chat extends ExpPlugin
{
    /** Is the redirection enabled or not ?
     *
     * @type bool
     */
    private $enabled = true;

    public $channels = array();
    public $playerChannels = array("Public");

    /** @var Config */
    private $config;
    private $exclude = array();
    private $badWords = array();

    private $widget;
    private $action;

    /**
     *
     */
    public function eXpOnLoad()
    {
        /** @var Config $config */
        $config = Config::getInstance();
        $this->loadProfanityList();
        $this->channels = array_merge(array("Public"), $config->channels);

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $this->action = $aH->createAction(array($this, 'selectChannel'));

        $this->widget = new Widget("Chat\Gui\Widgets\ChatSelect.xml");
        $this->widget->setName("Chat Channel Selector");
        $this->widget->setLayer("normal");
        $this->widget->setSize(55, 6);
        $this->widget->setParam("action", $this->action);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
    }

    /**
     *
     */
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents(Event::ON_PLAYER_CONNECT);
        $this->enableDedicatedEvents(Event::ON_PLAYER_DISCONNECT);

        Dispatcher::register(Event::getClass(), $this, Event::ON_PLAYER_CHAT, 10);

        $this->config = Config::getInstance();

        try {
            $this->connection->chatEnableManualRouting(true);
            $cmd = AdminGroups::addAdminCommand('chat', $this, 'admChat', Permission::GAME_SETTINGS);
            $cmd->setHelp('//chat enable or disable');
            $this->registerChatCommand("chat", "cmdChat", 1, true);
            $this->registerChatCommand("chat", "cmdChat", 0, true);
        } catch (\Exception $e) {
            $this->console("Couldn't initialize chat. Error from server: " . $e->getMessage());
            $this->enabled = false;
        }

        $this->initChat();
    }


    /**
     *
     */
    public function initChat()
    {
        /** @var Config $config */
        $config = Config::getInstance();
        $all = $this->storage->players + $this->storage->spectators;
        foreach ($all as $login => $player) {
            $this->playerChannels[$login] = "Public";
            if ($config->useChannels) {
                $this->displayWidget($login);
            }
        }
    }

    /**
     *
     */
    private function loadProfanityList()
    {
        $ignore = array(".", "..", "LICENSE", "README.md", "USERS.md", ".git");
        $path = realpath(APP_ROOT)
            . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "bad_words"
            . DIRECTORY_SEPARATOR . "List-of-Dirty-Naughty-Obscene-and-Otherwise-Bad-Words-master";

        if (is_dir($path)) {
            $this->console("[Chat] loading profanity filter words...");
            $dir = new \DirectoryIterator($path);
            foreach ($dir as $file) {
                if (!in_array($file->getBaseName(), $ignore)) {
                    foreach (file($file->getPathname()) as $line) {
                        $this->badWords[] = strtolower(trim($line, "\r\n"));
                    }
                }
            }
        }
    }

    /**
     * @param Variable $var
     */
    public function onSettingsChanged(Variable $var)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        if ($var->getConfigInstance() instanceof Config) {
            if ($var->getName() == "useChannels") {
                if ($var->getRawValue() == true) {
                    $this->initChat();
                } else {
                    if ($this->widget instanceof Widget) {
                        $this->widget->erase();
                    }
                }
            }
            if ($var->getName() == "channels") {
                $this->channels = array_merge(array("Public"), $var->getRawValue());
                if ($config->useChannels) {
                    if ($this->widget instanceof Widget) {
                        $this->widget->erase();
                    }
                    $this->initChat();
                }
            }
        }
    }

    /**
     * @param $text
     * @return string
     */
    public function applyFilter($text)
    {
        $out = array();
        $words = explode(" ", $text);
        foreach ($words as $word) {
            if (in_array(strtolower($word), $this->badWords)) {
                $out[] = str_repeat("#", strlen($word));
            } else {
                $out[] = $word;
            }
        }

        return implode(" ", $out);
    }

    /**
     * @param $login
     * @param string $params
     */
    public function cmdChat($login, $params = "help")
    {
        switch (strtolower($params)) {
            case "on":
                if (array_key_exists($login, $this->exclude)) {
                    unset($this->exclude[$login]);
                }
                $this->eXpChatSendServerMessage(eXpGetMessage("Chat messages enabled."), $login);
                break;
            case "off":
                $this->exclude[$login] = $login;
                $this->eXpChatSendServerMessage(eXpGetMessage("Chat messages disabled."), $login);
                break;
            default:
                $this->eXpChatSendServerMessage(eXpGetMessage("Usage: /chat on or /chat off."), $login);
                break;
        }
    }

    /**
     * @param $login
     * @param $params
     */
    public function admChat($login, $params = array())
    {
        $command = array_shift($params);

        $var = MetaData::getInstance()->getVariable('publicChatActive');

        switch ($command ? strtolower($command) : "") {
            case "enable":
            case "on":
                $var->setRawValue(true);
                $this->eXpChatSendServerMessage("#admin_action#Public chat is now #variable#Enabled");
                break;
            case "disable":
            case "off":
                $var->setRawValue(false);
                $this->eXpChatSendServerMessage("#admin_action#Public chat is now #variable#Disabled");
                break;
            default:
                $this->eXpChatSendServerMessage(eXpGetMessage("Usage: //chat on or //chat off."), $login);
                break;
        }
    }

    /**
     * On Player connect just show console
     *
     * @param $login
     * @param $isSpectator
     */
    public function onPlayerConnect($login, $isSpectator)
    {
        /** @var Config $config */
        $config = Config::getInstance();
        $this->playerChannels[$login] = "Public";
        if ($config->useChannels) {
            $this->displayWidget($login);
        }
        $player = $this->storage->getPlayerObject($login);
        $nickLog = Formatting::stripStyles($player->nickName);
        Logger::getLog('chat')->write(
            " (" . $player->iPAddress . ") [" . $login . "] Connect with nickname " . $nickLog
        );
    }


    /**
     * On player just disconnect
     *
     * @param      $login
     * @param null $reason
     */
    public function onPlayerDisconnect($login, $reason = null)
    {
        if (isset($this->playerChannels[$login])) {
            unset($this->playerChannels[$login]);
        }
        $player = $this->storage->getPlayerObject($login);
        if (empty($player)) {
            return;
        }
        Logger::getLog('chat')->write(
            " (" . $player->iPAddress . ") [" . $login . "] Disconnected"
        );
    }

    /**
     * @param $login
     */
    public function displayWidget($login)
    {
        $this->widget->setParam("channels", $this->channels);
        $this->widget->setPosition($this->config->chatSelector_PosX, $this->config->chatSelector_PosY, 0);
        $this->widget->show($login);
    }

    public function selectChannel($login, $entries)
    {
        $channel = $this->channels[$entries['channel']];
        if ($this->playerChannels[$login] == $channel) return;

        $this->playerChannels[$login] = $channel;
        $this->connection->chatSendServerMessage('Your chat channel is set to: $0d0' . $channel, $login);
    }

    /**
     * @return array
     */
    public function getRecepients()
    {

        $array = array_values(
            array_merge($this->storage->spectators , AdminGroups::getAdminsByPermission(Permission::CHAT_ON_DISABLED))
        );
        foreach (Core::$playerInfo as $login => $playerinfo) {
            if ($playerinfo->hasRetired) {
                $array[] = $playerinfo->login;
            }
        }

        $recepients = array();
        foreach ($array as $player) {
            if ($player instanceof Player) {
                $recepients[$player->login] = $player->login;
            } else {
                $recepients[$player] = $player;
            }
        }

        foreach ($this->exclude as $login => $player) {
            if (array_key_exists($login, $recepients)) {
                unset($recepients[$login]);
            }
        }

        return array_keys(array_intersect_key(($this->storage->players + $this->storage->spectators), $recepients));
    }

    /**
     * onPlayerChat()
     * Processes the chat incoming from server, changes the look and color.
     *
     * @param int $playerUid
     * @param string $login
     * @param string $text
     * @param bool $isRegistredCmd
     *
     * * @return void
     */
    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {

        if ($playerUid != 0 && substr($text, 0, 2) == " /") {
            Dispatcher::dispatch(new Event("PlayerChat", array($playerUid, $login, ltrim($text), true)));
            return;
        }

        if ($playerUid != 0 && substr($text, 0, 1) != "/" && $this->enabled) {
            /** @var Config $config */
            $config = Config::getInstance();
            $force = "";
            $source_player = $this->storage->getPlayerObject($login);
            $nick = $source_player->nickName;
            if ($config->allowMPcolors) {
                if (strstr($source_player->nickName, '$>')) {
                    $nick = $source_player->nickName;
                    $pos = strpos($source_player->nickName, '$>');
                    $color = substr($source_player->nickName, $pos);
                    if (substr($nick, -1) == '$') {
                        $nick = substr($nick, 0, -1);
                    }
                    if ($color != '$>$') {
                        $force = str_replace('$>', "", $color);
                    }
                }
            }
            if ($config->useProfanityFilter) {
                $text = $this->applyFilter($text);
            }

            if ($source_player == null) {
                return;
            }

            $nick = str_ireplace('$w', '', $nick);
            $nick = str_ireplace('$z', '$z$s', $nick);
            // fix for chat...
            $nick = str_replace('$<', '', $nick);
            $text = str_replace('$<', '', $text);

            if ($this->config->publicChatActive || AdminGroups::hasPermission($login, Permission::CHAT_ON_DISABLED)) {
                $playersCombined = $this->storage->players + $this->storage->spectators;
                $channels = array();
                $currentChannel = $this->playerChannels[$login];

                foreach ($this->playerChannels as $key => $value) {
                    $channels[$value][] = $key;
                }

                // by default set global channel
                $receivers = null;
                $channel = "";

                if ($config->useChannels) {
                    // if group
                    if ($this->playerChannels[$login] != "Public") {
                        $channel = "[" . ucfirst($currentChannel) . "] ";
                        $receivers = implode(",", array_intersect(array_keys($playersCombined),
                                (AdminGroups::getAdminsByPermission(Permission::CHAT_ADMINCHAT)
                                    + $channels[$currentChannel]))
                        );
                    }
                }

                try {
                    // change text color, if admin is defined at admingroups
                    if (AdminGroups::isInList($login)) {
                        $color = $config->adminChatColor;

                        if ($this->expStorage->isRelay) {
                            $color = $config->otherServerChatColor;
                        }
                        $this->connection->chatSendServerMessage(
                            $channel .
                            $config->adminSign . '$fff$<' . $nick . '$z$s$> '
                            . $config->chatSeparator . $color . $force . $text,
                            $receivers
                        );
                    } else {
                        $color = $config->publicChatColor;
                        if ($this->expStorage->isRelay) {
                            $color = $config->otherServerChatColor;
                        }

                        $this->connection->chatSendServerMessage(
                            $channel . '$fff$<' . $nick . '$z$s$> ' . $config->chatSeparator . $color . $force . $text,
                            $receivers
                        );
                    }
                    $nickLog = Formatting::stripStyles($nick);

                    Logger::getLog('chat')->write("[" . $login . "] " . $nickLog . " - " . $text);
                } catch (\Exception $e) {
                    $this->console(
                        __(
                            'error sending chat from %s: %s with folloing error %s',
                            $login,
                            $login,
                            $text,
                            $e->getMessage()
                        )
                    );
                }
            } else {
                // chat is disabled
                $recepient = $this->getRecepients();

                if ($config->enableSpectatorChat) {

                    if (in_array($login, $recepient)) {


                        $color = $config->otherServerChatColor;
                        $this->connection->chatSendServerMessage(
                            '$fff$<' . $nick . '$z$s$> ' . $config->chatSeparator . $color . $force . $text,
                            $recepient
                        );
                        $nickLog = Formatting::stripStyles($nick);
                        Logger::getLog('chat')->write(
                            "[" . $login . "] " . $nickLog . " - " . $text
                        );
                    } else {
                        $this->eXpChatSendServerMessage(
                            "#error#Chat is disabled at at the moment!!! "
                            . "You can chat when you retire or go spectator. You may still use PM messages",
                            $login,
                            array()
                        );
                    }
                } else {
                    $this->eXpChatSendServerMessage(
                        "#error#Chat is disabled at at the moment!!! "
                        . "Only admins may chat. You may still use PM messages",
                        $login,
                        array()
                    );
                }
            }
        }
    }

    /**
     * onUnload()
     * Function called on unloading this plugin.
     *
     * @return void
     */
    public function eXpOnUnload()
    {
        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $aH->deleteAction($this->action);

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
        $this->action = null;
        $this->config = null;
        $this->widget = null;

        try {
			Dispatcher::unregister(Event::getClass(), $this, Event::ON_PLAYER_CHAT);
            $this->connection->chatEnableManualRouting(false);
		} catch (Exception $e) {
			return;
		}
    }
}
