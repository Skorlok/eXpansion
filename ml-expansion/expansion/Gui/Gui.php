<?php

namespace ManiaLivePlugins\eXpansion\Gui;

use Exception;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Gui\GuiHandler;
use ManiaLive\Utilities\Logger;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Preloader;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;
use ManiaLivePlugins\eXpansion\Gui\Widgets\GetPlayerWidgets;
use ManiaLivePlugins\eXpansion\Gui\Windows\Configuration;
use ManiaLivePlugins\eXpansion\Gui\Windows\ConfirmDialog;
use ManiaLivePlugins\eXpansion\Gui\Windows\HudMove;
use ManiaLivePlugins\eXpansion\Gui\Windows\Notice;
use ManiaLivePlugins\eXpansion\Gui\Windows\ResetHud;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Gui extends ExpPlugin
{
    private $titleId;
    private $msg_params;
    private $msg_disabled;
    private $counter = 0;
    private $preloader;
    // next 2 is used by contextMenu
    public static $items = array();
    public static $callbacks = array();

    // used to sent widgets to players when they join, it's more efficient than sending statics widgets with callbacks in plugins
    public static $persistentWidgets = array();

    public $playersWidgets = array();

    public function eXpOnInit()
    {
        $this->setVersion("0.1");
    }

    public function eXpOnLoad()
    {
        $config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->registerChatCommand("hud", "hudCommands", 0, true);
        $this->registerChatCommand("hud", "hudCommands", 1, true);
        $this->setPublicMethod("hudCommands");

        GetPlayerWidgets::$parentPlugin = $this;

        $this->msg_params = eXpGetMessage("possible parameters: move, lock, reset, config");
        $this->msg_disabled = eXpGetMessage("#error#Server Admin has disabled personal huds. Sorry!");

        $this->preloader = Preloader::Create(null);
        $this->preloader->show();

        foreach ($this->storage->players as $player) {
            $this->onPlayerConnect($player->login, false);
        }
        foreach ($this->storage->spectators as $player) {
            $this->onPlayerConnect($player->login, true);
        }
    }

    public static function getScaledSize($sizes, $totalSize)
    {
        $nsize = array();

        $total = 0;
        foreach ($sizes as $val) {
            $total += $val;
        }

        $coff = $totalSize / $total;

        foreach ($sizes as $val) {
            $nsize[] = $val * $coff;
        }

        return $nsize;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        try {
            if ($this->expStorage->simpleEnviTitle == "SM") {
                $this->counter = time();
                $this->connection->TriggerModeScriptEvent("LibXmlRpc_DisableAltMenu", $login);
                $this->connection->sendDisplayManialinkPage($login, '<manialinks><manialink id="0"><quad></quad></manialink><custom_ui><altmenu_scores visible="false" /></custom_ui></manialinks>', 0, false);
            }
        } catch (Exception $e) {
            $this->console("Error while disabling alt menu : " . $e->getMessage());
        }

        $this->connection->sendDisplayManialinkPage(null,
<<<EOT
<manialink id="GuiChecker" version="2" layer="normal" name="GuiChecker">
<script><!--
main () {
    declare persistent Boolean exp_isWidgetsHidden = False;
    declare Boolean exp_needToCheckPersistentVars for UI = False;

    declare persistent Boolean edge_isLockedVisible = True;
    declare Boolean edge_isMinimized for UI = False;
    declare Boolean lastValue = edge_isMinimized;
    declare Boolean is_edge_animated for UI = edge_isMinimized;
    declare Integer eXp_lastClockUpdate = Now;

    while(True) {
        yield;
        foreach (Event in PendingEvents) {
            if (Event.Type == CMlEvent::Type::KeyPress && Event.KeyName == "F8") {
                exp_isWidgetsHidden = !exp_isWidgetsHidden;
                if (exp_isWidgetsHidden == True) {
                    exp_needToCheckPersistentVars = True;
                } else {
                    exp_needToCheckPersistentVars = True;
                }
            }
            if (Event.Type == CMlEvent::Type::KeyPress && Event.KeyName == "F9") {
                edge_isLockedVisible = !edge_isLockedVisible;
            }
        }

        if (edge_isLockedVisible == False && (Now - eXp_lastClockUpdate) >= 50) {
            if (InputPlayer != Null) {
                declare Real Speed = InputPlayer.Speed*3.6;
        
                if ((Speed < 10.0 && Speed > -10.0) || InputPlayer.RaceState == CTmMlPlayer::ERaceState::Finished) {
                    if (lastValue == True) {
                        edge_isMinimized = False;
                        lastValue = False;
                        is_edge_animated = True;
                    } else {
                        edge_isMinimized = False;
                        lastValue = False;
                    }
                }
        
                if ((Speed > 10.0 || Speed < -10.0) && InputPlayer.RaceState != CTmMlPlayer::ERaceState::Finished) {
                    if (lastValue == False) {
                        edge_isMinimized = True;
                        lastValue = True;
                        is_edge_animated = True;
                    } else {
                        edge_isMinimized = True;
                        lastValue = True;
                    }
                }
            }
        
            eXp_lastClockUpdate = Now;
        }

        if (edge_isLockedVisible == True && (Now - eXp_lastClockUpdate) >= 500) {
            if (lastValue == True) {
                edge_isMinimized = False;
                lastValue = False;
                is_edge_animated = True;
            }
        }
    }
}
--></script>
</manialink>
EOT
        , 0, false);

        
        $widgetsToSend = "";
        foreach (self::$persistentWidgets as $widget) {
            $widgetsToSend .= $widget;
        }

        if ($widgetsToSend != "") {
            $this->connection->sendDisplayManialinkPage($login, $widgetsToSend, 0, false, true);
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {

    }

    public function hudCommands($login, $param = "null")
    {
        switch ($param) {
            case "reset":
                $this->resetHud($login);
                break;
            case "move":
                $this->enableHudMove($login);
                break;
            case "lock":
                $this->disableHudMove($login);
                break;
            case "config":
                $this->getPlayersWidgets($login);
                break;
            default:
                $this->eXpChatSendServerMessage($this->msg_params, $login);
                break;
        }
    }

    public function enableHudMove($login)
    {
        if (Config::getInstance()->disablePersonalHud) {
            $this->eXpChatSendServerMessage($this->msg_disabled, $login);
        } else {
            $window = HudMove::Create($login, false);
            $window->enable();
            $window->show();
        }
    }

    public function disableHudMove($login)
    {
        if (Config::getInstance()->disablePersonalHud) {
            $this->eXpChatSendServerMessage($this->msg_disabled, $login);
        } else {
            $window = HudMove::Create($login, false);
            $window->disable();
            $window->show();
        }
    }

    public function getPlayersWidgets($login)
    {
        GetPlayerWidgets::Erase($login);
        $widget = GetPlayerWidgets::Create($login, false);
        $widget->show();
    }

    public function showHudConfig($login, $entries = array())
    {
        if (!isset($this->playersWidgets[$login])) {
            $this->playersWidgets[$login] = "";
        }
        if (isset($entries['widgetStatus'])) {
            if ($entries['widgetStatus'] != 'finished') {
                $this->playersWidgets[$login] .= $entries['widgetStatus'];
            } else {
                $this->showConfigWindow($login, array('widgetStatus' => $this->playersWidgets[$login]));
                unset($this->playersWidgets[$login]);
                GetPlayerWidgets::Erase($login);
            }
        }
    }

    public function showConfigWindow($login, $entries)
    {
        if (Config::getInstance()->disablePersonalHud) {
            $this->eXpChatSendServerMessage($this->msg_disabled, $login);
        } else {
            $window = Configuration::Create($login, true);
            $window->setSize(120, 90);
            $window->setData($entries);
            $window->show();
        }
    }

    public function resetHud($login)
    {
        if (Config::getInstance()->disablePersonalHud) {
            $this->eXpChatSendServerMessage($this->msg_disabled, $login);
        } else {
            $window = ResetHud::Create($login);
            $window->setTimeout(1);
            $window->show();
            $this->eXpChatSendServerMessage(eXpGetMessage("Hud reset done!"), $login);
            //ResetHud::Erase($login);
        }
    }

    public function logMemory()
    {
        $mem = "Memory Usage: " . round(memory_get_usage() / 1024) . "Kb";
        Logger::getLog("memory")->write($mem);
        print "\n" . $mem . "\n";
    }

    public function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
    {
        if (strpos($answer, "onMenuItemClick") !== false) {

            $parseStr = str_replace("onMenuItemClick?", "", $answer);
            $parsed = array();

            parse_str($parseStr, $parsed);

            if (!array_key_exists($parsed['hash'], self::$callbacks)) {
                return;
            }
            $item = $parsed['item'];
            $hash = $parsed['hash'];
            $value = $parsed['dataId'];

            $test = \call_user_func(self::$callbacks[$hash], array($login, $item, self::$items[$hash][$value]->data));
        }
    }

    /**
     * Cleans the string for manialink or maniascript purposes.
     *
     * @param string $string The string to clean
     * @param bool $multiline if the string is multiline
     * @return string cleaned up string
     */
    public static function fixString($string, $multiline = false)
    {
        $out = str_replace("\r", '', $string);
        if (!$multiline) {
            $out = str_replace("\n", '', $out);
        }
        $out = str_replace('"', "'", $out);
        $out = str_replace('\\', '\\\\', $out);
        $out = str_replace('-', '–', $out);

        return $out;
    }

    /**
     * @param $login
     * @param $actionId
     * @param string $text
     */
    public static function showConfirmDialog($login, $actionId, $text = "")
    {
        $window = ConfirmDialog::Create($login);
        $window->setText($text);
        $window->setInvokeAction($actionId);
        $window->show();
    }

    /**
     * @param $message
     * @param $login
     * @param array $args
     */
    public static function showNotice($message, $login, $args = array())
    {
        $window = null;
        if (is_array($login)) {
            $grp = \ManiaLive\Gui\Group::Create("notice", $login);
            $window = Notice::Create($grp);
        } else {
            $window = Notice::Create($login);
        }

        if (is_string($message)) {
            $message = eXpGetMessage($message);
        }
        $window->setMessage($message, $args);
        $window->show($login);
    }

    /**
     * @param $message
     * @param $login
     */
    public static function showError($message, $login)
    {
        $window = null;
        if (is_array($login)) {
            $grp = \ManiaLive\Gui\Group::Create("error", $login);
            $window = Windows\Error::Create($grp);
        } else {
            $window = Windows\Error::Create($login);
        }
        $window->setMessage($message);
        $window->show($login);
    }

    /**
     * Preload image
     *
     * @param string $url
     */
    public static function preloadImage($url)
    {
        Preloader::add($url);
    }

    /**
     * Preload image
     *
     * @param type $url
     */
    public static function preloadRemove($url)
    {
        Preloader::remove($url);
    }

    public static function preloadUpdate()
    {
        $preloader = Preloader::Create(null);
        $preloader->show();
    }

    /**
     * Displays a Confirm Dialog for action.
     *
     */
    public static function createConfirm($finalAction)
    {
        $outAction = ActionHandler::getInstance()->createAction(
            array('\\ManiaLivePlugins\\eXpansion\\Gui\\Gui', 'showConfirmDialog'),
            $finalAction
        );

        return $outAction;
    }
}
