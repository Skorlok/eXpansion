<?php

namespace ManiaLivePlugins\eXpansion\Adm;

use Exception;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ForceScores;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\GameOptions;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\MatchSettings;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\RoundPoints;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ScriptSettings;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ServerControlMain;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ServerManagement;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ServerOptions;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Events\Event;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\I18n\Message;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Adm extends ExpPlugin
{
    /** @var Message Messages needed */
    private $msgScriptSettings;
    /** @var Message Messages needed */
    private $msgDatabasePlugin;
    /** @var Message Messages needed */
    private $msgForceScoreError;

    /**
     * @inheritdoc
     */
    public function eXpOnLoad()
    {
        $this->msgForceScoreError = eXpGetMessage("ForceScores can be used only with rounds or team mode");
        $this->msgScriptSettings = eXpGetMessage("ScriptSettings available only in script mode");
        $this->msgDatabasePlugin = eXpGetMessage("Database plugin not loaded!");

        $this->setPublicMethod('serverControlMain');

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups')) {
            Dispatcher::register(Event::getClass(), $this);
        }

        $cmd = AdminGroups::addAdminCommand('setting expansion', $this, 'showExpSettings', 'expansion_settings');
        $cmd->setHelp('Set up your expansion');
        AdminGroups::addAlias($cmd, "setexp"); // xaseco & fast
    }

    /**
     * @inheritdoc
     */
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        ServerControlMain::$mainPlugin = $this;
        RoundPoints::$plugin = $this;
        ForceScores::$mainPlugin = $this;
        ScriptSettings::$mainPlugin = $this;


        $cmd = AdminGroups::addAdminCommand('server control', $this, 'serverControlMain', Permission::SERVER_CONTROL_PANEL);
        $cmd->setHelp('Displays the main control panel for the server');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "server");
        AdminGroups::addAlias($cmd, "options");
        AdminGroups::addAlias($cmd, "control");

        $this->onBeginMap(null, null, null);
    }

    /**
     * Display eXpansion settings.
     *
     * @param string $login The login of the player
     */
    public function showExpSettings($login)
    {
        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'showExpSettings', $login);
    }

    /**
     * Display server options window
     *
     * @param string $login The login of the player
     */
    public function serverOptions($login)
    {
        if (AdminGroups::getAdmin($login) != null) {
            $window = ServerOptions::Create($login);
            $window->setTitle(__('Server Options', $login));
            $window->centerOnScreen();
            $window->setSize(160, 100);
            $window->show();
        }
    }

    /**
     * Show windows to the set up forced scores
     *
     * @param string $login The login of the player
     */
    public function forceScores($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            $gamemode = $this->storage->gameInfos->gameMode;
            if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || GameInfos::GAMEMODE_CUP) {
                $window = ForceScores::Create($login);
                $window->setTitle(__('Force Scores', $login));
                $window->centerOnScreen();
                $window->setSize(160, 80);
                $window->show();
            } else {
                $this->eXpChatSendServerMessage($this->msgForceScoreError, $login);
            }
        }
    }

    /**
     * Function to validated score change
     */
    public function forceScoresOk()
    {
        // @TODO Replace this by a proper event.
        $this->eXpChatSendServerMessage('Notice: Admin has altered the scores of current match!');
        if ($this->isPluginLoaded("\\ManiaLivePlugins\\eXpansion\ESLcup\\ESLcup")) {
            $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\ESLcup\\ESLcup", "syncScores");
        }
    }

    /**
     * Show window for game options
     *
     * @param string $login The login of the player
     */
    public function gameOptions($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            $window = GameOptions::Create($login);
            $window->setTitle(__('Game Options', $login));
            $window->setSize(160, 85);
            $window->centerOnScreen();
            $window->show();
        }
    }

    /**
     * Show the window for server management
     *
     * @param string $login The login of the player
     */
    public function serverManagement($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_STOP_DEDICATED) || AdminGroups::hasPermission($login, Permission::SERVER_STOP_MANIALIVE)) {
            $window = ServerManagement::Create($login);
            $window->setTitle(__('Server Control', $login));
            $window->setSize(90, 30);
            $window->centerOnScreen();
            $window->show();
        }
    }

    /**
     * Show window to customized points
     *
     * @param string $login The login of the player
     */
    public function roundPoints($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            $window = RoundPoints::Create($login);
            $window->setTitle(__('Custom Round Points', $login));
            $window->setSize(160, 90);
            $window->centerOnScreen();
            $window->show();
        }
    }

    /**
     * Show window to access all server configurations.
     *
     * @param string $login The login of the player
     */
    public function serverControlMain($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_CONTROL_PANEL)) {
            $window = ServerControlMain::Create($login);
            $window->setSize(140, 25);
            $window->show();
        }
    }

    /**
     * Show window that allows votes configuration
     *
     * @param string $login The login of the player
     */
    public function showVotesConfig($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_VOTES)) {
            if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Votes\Votes')) {
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Votes\Votes', 'showVotesConfig', $login);
            }
        }
    }

    /**
     * Show window that allows to start/stop plugins & see list of plugins
     *
     * @param string $login The login of the player
     */
    public function showPluginManagement($login)
    {
        if (AdminGroups::hasPermission($login, Permission::EXPANSION_PLUGIN_START_STOP)) {
            if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad')) {
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad', 'showPluginsWindow', $login);
            }
        }
    }

    /**
     * Show window to set up the match settings used
     *
     *  string $login The login of the player
     */
    public function matchSettings($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_MATCH_SAVE) || AdminGroups::hasPermission($login, 'game_matchDelete') || AdminGroups::hasPermission($login, 'game_match')) {
            $window = MatchSettings::Create($login);
            $window->setTitle(__('Match Settings', $login));
            $window->centerOnScreen();
            $window->setSize(160, 100);
            $window->show();
        }
    }

    /**
     * Show window for script game settings.
     *
     * @param string $login The login of the player
     */
    public function scriptSettings($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            $window = ScriptSettings::Create($login);
            $window->setTitle(__('Script Settings', $login));
            $window->centerOnScreen();
            $window->setSize(160, 100);
            $window->show();
        }
    }

    public function afterScriptSettings($login, $diffPameters = array())
    {
        $admin = $this->storage->getPlayerObject($login);
        foreach ($diffPameters as $key => $value) {
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s#admin_action# changes script parameter #variable#%s#admin_action# to #variable#%s #admin_action#(#admin_action#Was #variable#%s#admin_action#)', null, array($admin->nickName, $key, $value[1], $value[0]));
        }
    }

    /**
     * Show the window for db tools
     *
     * @param string $login The login of the player
     */
    public function dbTools($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_DATABASE)) {
            if ($this->isPluginLoaded("\\ManiaLivePlugins\\eXpansion\\Database\\Database")) {
                $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\Database\\Database", "showDbMaintenance", $login);
            } else {
                $this->eXpChatSendServerMessage($this->msgDatabasePlugin, $login);
            }
        }
    }

    /**
     * Display admin groups windows to manage admins.
     *
     * @param string $login The login of the player
     */
    public function adminGroups($login)
    {
        AdminGroups::getInstance()->windowGroups($login);
    }

    /**
     * Set the current points for rounds action
     *
     * @param string $login The login of the player
     * @param        $points
     */
    public function setPoints($login, $points)
    {
        try {
            $nick = $this->storage->getPlayerObject($login)->nickName;
            $config = \ManiaLivePlugins\eXpansion\Core\Config::getInstance();
            foreach ($points as $p) {
                $intPoints[] = intval($p);
            }

            $config->roundsPoints = $intPoints;

            $var = \ManiaLivePlugins\eXpansion\Core\MetaData::getInstance()->getVariable('roundsPoints');
            $var->setRawValue($intPoints);

            \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

            $points = $intPoints;
            foreach ($points as &$num) {
                settype($num, 'string');
            }
            unset($num);
            $this->connection->triggerModeScriptEventArray('Trackmania.SetPointsRepartition', $points);
            $this->connection->triggerModeScriptEventArray('Rounds_SetPointsRepartition', $points);
            $this->connection->setRoundCustomPoints($intPoints);

            $config->scriptRoundsPoints = $points;

            $var = \ManiaLivePlugins\eXpansion\Core\MetaData::getInstance()->getVariable('scriptRoundsPoints');
            $var->setRawValue($points);

            \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

            //enable custom points in team mode
            if ($this->eXpGetCurrentCompatibilityGameMode()== \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM) {
                try {
                    $this->connection->setModeScriptSettings(["S_UseCustomPointsRepartition" => true]);
                } catch (Exception $e) {
                    $this->console('[CustomPoints] Impossible to set S_UseCustomPointsRepartition to true, Incompatible mode ?');
                }
            }

            $msg = eXpGetMessage('#admin_action#Admin %s $z$s#admin_action#sets custom ' . "round points to #variable#%s");
            $this->eXpChatSendServerMessage($msg, null, array($nick, implode(",", $intPoints)));
        } catch (Exception $e) {
            $this->connection->chatSendServerMessage(__('#admin_error#Error: %s', $login, $e->getMessage()), $login);
        }
    }

    /**
     * @inheritdoc
     */
    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
        ForceScores::EraseAll();
        GameOptions::EraseAll();
        MatchSettings::EraseAll();
        RoundPoints::EraseAll();
        ScriptSettings::EraseAll();
        ServerControlMain::EraseAll();
        ServerManagement::EraseAll();
        ServerOptions::EraseAll();
    }
}
