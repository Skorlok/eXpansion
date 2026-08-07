<?php

namespace ManiaLivePlugins\eXpansion\Gui;

use Exception;
use ManiaLive\Utilities\Logger;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window as MLWindow;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\GetPlayerWidgets;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Preloader;
use ManiaLivePlugins\eXpansion\Gui\Windows\HudMove;
use ManiaLivePlugins\eXpansion\Gui\Windows\ResetHud;

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

    /** @var MLWindow */
    private static $errorWindow = null;

    /** @var MLWindow */
    private static $noticeWindow = null;

    /** @var MLWindow */
    private static $confirmWindow = null;
    /** @var Script */
    private static $confirmScript = null;

    public $playersWidgets = array();

    /** @var MLWindow */
    private $configWindow;
    private $configStatuses = array();
    private $configGameMode = array();

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

        $this->registerManialinkCallback('showConfirmDialogMl', false, true);
        $this->registerManialinkCallback('configurationOk', true);

        $this->registerChatCommand("hud", "hudCommands", 0, true);
        $this->registerChatCommand("hud", "hudCommands", 1, true);
        
        $this->setPublicMethod("hudCommands");

        GetPlayerWidgets::$parentPlugin = $this;

        $this->msg_params = eXpGetMessage("possible parameters: move, lock, reset, config");
        $this->msg_disabled = eXpGetMessage("#error#Server Admin has disabled personal huds. Sorry!");

        $this->preloader = Preloader::Create(null);
        $this->preloader->show();

        self::$errorWindow = new MLWindow("Gui\\Windows\\Error.xml");
        self::$errorWindow->setName("Gui Error");
        self::$errorWindow->setSize(120, 90);
        self::$errorWindow->setTitle("Error");

        self::$noticeWindow = new MLWindow("Gui\\Windows\\Notice.xml");
        self::$noticeWindow->setName("Gui Notice");
        self::$noticeWindow->setSize(90, 60);
        self::$noticeWindow->setTitle("Notice");

        self::$confirmScript = new Script("Gui/Scripts/ConfirmDialog");
        self::$confirmWindow = new MLWindow("Gui\\Windows\\ConfirmDialog.xml");
        self::$confirmWindow->setName("Gui Confirm");
        self::$confirmWindow->setSize(57, 16);
        self::$confirmWindow->setTitle("Really do this ?");
        self::$confirmWindow->registerScript(self::$confirmScript);

        $this->configWindow = new MLWindow("Gui\\Windows\\Configuration.xml");
        $this->configWindow->setName("HUD Configuration");
        $this->configWindow->setSize(120, 90);
        $this->configWindow->setTitle("Configure HUD");
        $this->configWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(6, 82));
        $this->configWindow->setParam("sizeX", 120);
        $this->configWindow->setParam("sizeY", 90);

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
<manialink id="GuiChecker" version="2" layer="scorestable" name="GuiChecker">
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
        
                if ((Speed < 10.0 && Speed > -10.0) || (InputPlayer.RaceState == CTmMlPlayer::ERaceState::Finished || PageIsVisible)) {
                    if (lastValue == True) {
                        edge_isMinimized = False;
                        lastValue = False;
                        is_edge_animated = True;
                    } else {
                        edge_isMinimized = False;
                        lastValue = False;
                    }
                }
        
                if ((Speed > 10.0 || Speed < -10.0) && (InputPlayer.RaceState != CTmMlPlayer::ERaceState::Finished && !PageIsVisible)) {
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
        , 0, false, false);

        
        $widgetsToSend = "";
        foreach (self::$persistentWidgets as $widget) {
            $widgetsToSend .= $widget;
        }

        if ($widgetsToSend != "") {
            try {
                $this->connection->sendDisplayManialinkPage($login, $widgetsToSend, 0, false, false);
            } catch (Exception $e) {
                if ($e->getMessage() != "Login unknown.") {
                    $this->console("Impossible to send persistent widgets to player, server said: " . $e->getMessage());
                    $this->console("Trying to send persistent widgets to player again.");
                    foreach (self::$persistentWidgets as $widget) {
                        try {
                            $this->connection->sendDisplayManialinkPage($login, $widget, 0, false, false);
                        } catch (Exception $e) {
                            $this->console("Cannot send persistent widgets to player, server said: " . $e->getMessage());
                        }
                    }
                } else {
                    $this->console("Cannot send persistent widgets to player, server said: " . $e->getMessage());
                }
            }
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        unset($this->configStatuses[$login]);
        unset($this->configGameMode[$login]);
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
            return;
        }
        if (!array_key_exists("widgetStatus", $entries)) {
            return;
        }

        $rawEntries = explode("|", $entries["widgetStatus"]);
        $statuses   = array();
        $gameMode   = "";
        foreach ($rawEntries as $entry) {
            if (empty($entry)) {
                continue;
            }
            $val      = explode(":", $entry, 3);
            $gameMode = $val[1];
            $statuses[] = array('id' => $val[0], 'value' => ($val[2] != "0"));
        }

        $this->configStatuses[$login] = $statuses;
        $this->configGameMode[$login] = $gameMode;

        $this->configWindow->setParam("items", $statuses);
        $this->configWindow->show($login);
    }

    public function configurationOk($login, $params = array())
    {
        if (!isset($this->configStatuses[$login])) {
            return;
        }
        $outValues = array();
        foreach ($this->configStatuses[$login] as $x => $status) {
            $isChecked = isset($params['cb_' . $x]) && $params['cb_' . $x] == '1';
            $outValues[] = new Structures\ConfigItem($status['id'], $this->configGameMode[$login], $isChecked ? '1' : '0');
        }
        $apply = Windows\HudSetVisibility::Create($login);
        $apply->setData($outValues);
        $apply->setTimeout(5);
        $apply->show();
        $this->configWindow->erase($login);
        unset($this->configStatuses[$login]);
        unset($this->configGameMode[$login]);
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

    /**
     * Cleans the string for manialink or maniascript purposes.
     *
     * @param string $string The string to clean
     * @param bool $multiline if the string is multiline
     * @return string cleaned up string
     */
    public static function fixString($string, $multiline = false)
    {
        if ($multiline) {
            return str_replace(array("\r", '"',  '\\',  '-'),
                               array('',   "'", '\\\\', '–'), $string);
        }
        return str_replace(array("\r", "\n", '"',  '\\',  '-'),
                           array('',   '',   "'", '\\\\', '–'), $string);
    }

    /**
     * Cleans the string for maniascript purposes.
     *
     * @param string $string The string to clean
     * @param bool $multiline if the string is multiline
     * @return string cleaned up string
     */
    public static function fixString2($string, $multiline = false)
    {
        $out = str_replace('\\', '\\\\', $string);
        $out = str_replace('"', '\\"', $out);
        if (!$multiline) {
            $out = str_replace("\n", '', $out);
        }
        return $out;
    }

    /**
     * @param $login
     * @param $actionId
     * @param string $text
     */
    public static function showConfirmDialog($login, $actionId, $text = "")
    {
        self::$confirmScript->setParam("action", $actionId);
        self::$confirmWindow->setParam("text", self::$confirmWindow->handleSpecialChars($text));
        self::$confirmWindow->show($login);
    }

    public function showConfirmDialogMl($login, $actionId, $text = "")
    {
        self::$confirmScript->setParam("action", $actionId);
        self::$confirmWindow->setParam("text", self::$confirmWindow->handleSpecialChars($text));
        self::$confirmWindow->show($login);
    }

    /**
     * @param $message
     * @param $login
     * @param array $args
     */
    public static function showNotice($message, $login, $args = array())
    {
        $textId = self::$noticeWindow->addLang((string)$message, empty($args) ? null : $args);
        self::$noticeWindow->setParam("textId", $textId);
        self::$noticeWindow->show($login);
    }

    /**
     * @param $message
     * @param $login
     */
    public static function showError($message, $login)
    {
        $out = $message;
        if (is_array($message)) {
            $out = '';
            foreach ($message as $line) {
                $out .= trim($line) . "\n";
            }
        }
        self::$errorWindow->setParam("message", $out);
        self::$errorWindow->show($login);
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
        return 'exp:eXpansion.Gui:showConfirmDialogMl:' . $finalAction;
    }
}
