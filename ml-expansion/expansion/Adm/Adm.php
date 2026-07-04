<?php

namespace ManiaLivePlugins\eXpansion\Adm;

use Exception;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ForceScores;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\GameOptions;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\MatchSettings;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\RoundPoints;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ScriptSettings;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ServerOptions;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\I18n\Message;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\ServerOptions as DedicatedServerOptions;

class Adm extends ExpPlugin
{
    /** @var Message Messages needed */
    private $msgScriptSettings;
    /** @var Message Messages needed */
    private $msgDatabasePlugin;
    /** @var Message Messages needed */
    private $msgForceScoreError;

    protected $actions = array("ServerControlMain" => array(), "ServerManagement" => array(), "GameOptions" => array(), "ServerOptions" => array());

    protected $serverControlMainWindow;
    protected $serverManagementWindow;
    protected $gameOptionsWindow;
    protected $serverOptionsWindow;

    /**
     * @inheritdoc
     */
    public function eXpOnLoad()
    {
        $this->msgForceScoreError = eXpGetMessage("ForceScores can be used only with rounds or team mode");
        $this->msgScriptSettings = eXpGetMessage("ScriptSettings available only in script mode");
        $this->msgDatabasePlugin = eXpGetMessage("Database plugin not loaded!");

        $this->setPublicMethod('serverControlMain');

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

        /** @var ActionHandler $ah */
        $ah = ActionHandler::getInstance();
        $this->actions["ServerControlMain"]["serverOptions"]    = $ah->createAction(array($this, 'serverOptions'));
        $this->actions["ServerControlMain"]["gameOptions"]      = $ah->createAction(array($this, 'gameOptions'));
        $this->actions["ServerControlMain"]["matchSettings"]    = $ah->createAction(array($this, 'matchSettings'));
        $this->actions["ServerControlMain"]["serverManagement"] = $ah->createAction(array($this, 'serverManagement'));
        $this->actions["ServerControlMain"]["adminGroups"]      = $ah->createAction(array($this, 'adminGroups'));
        $this->actions["ServerControlMain"]["scriptSettings"]   = $ah->createAction(array($this, 'scriptSettings'));
        $this->actions["ServerControlMain"]["forceScores"]      = $ah->createAction(array($this, 'forceScores'));
        $this->actions["ServerControlMain"]["roundPoints"]      = $ah->createAction(array($this, 'roundPoints'));
        $this->actions["ServerControlMain"]["dbTools"]          = $ah->createAction(array($this, 'dbTools'));
        $this->actions["ServerControlMain"]["expSettings"]      = $ah->createAction(array($this, 'showExpSettings'));
        $this->actions["ServerControlMain"]["votesConfig"]      = $ah->createAction(array($this, 'showVotesConfig'));
        $this->actions["ServerControlMain"]["pluginManagement"] = $ah->createAction(array($this, 'showPluginManagement'));

        $this->actions["ServerManagement"]["stopServerf"]    = $ah->createAction(array($this, 'stopServer'));
        $this->actions["ServerManagement"]["stopServer"]     = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($this->actions["ServerManagement"]["stopServerf"]);
        $this->actions["ServerManagement"]["stopManialivef"] = $ah->createAction(array($this, 'stopManialive'));
        $this->actions["ServerManagement"]["stopManialive"]  = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($this->actions["ServerManagement"]["stopManialivef"]);

        $this->actions["GameOptions"]["ok"] = $ah->createAction(array($this, 'gameOptionsOk'));

        $this->actions["ServerOptions"]["ok"] = $ah->createAction(array($this, 'serverOptionsOk'));



        $this->gameOptionsWindow = new Window("Adm\Gui\Windows\GameOptions.xml");
        $this->gameOptionsWindow->setName("GameOptions");
        $this->gameOptionsWindow->setSize(160, 85);
        $this->gameOptionsWindow->setTitle('Game Options');
        $this->gameOptionsWindow->setParam("actions", $this->actions["GameOptions"]);
        $this->gameOptionsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted::getScriptML());
        $this->gameOptionsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Ratiobutton::getScriptML());

        $this->serverControlMainWindow = new Window("Adm\Gui\Windows\ServerControlMain.xml");
        $this->serverControlMainWindow->setName("ServerControlMain");
        $this->serverControlMainWindow->setSize(140, 25);
        $this->serverControlMainWindow->setTitle('Control Panel');
        $this->serverControlMainWindow->setParam("actions", $this->actions["ServerControlMain"]);
        $this->serverControlMainWindow->setParam("isRelay", Storage::getInstance()->isRelay);

        $this->serverManagementWindow = new Window("Adm\Gui\Windows\ServerManagement.xml");
        $this->serverManagementWindow->setName("ServerManagement");
        $this->serverManagementWindow->setSize(90, 30);
        $this->serverManagementWindow->setTitle("Server Control");
        $this->serverManagementWindow->setParam("actions", $this->actions["ServerManagement"]);

        $this->serverOptionsWindow = new Window("Adm\Gui\Windows\ServerOptions.xml");
        $this->serverOptionsWindow->setName("ServerOptions");
        $this->serverOptionsWindow->setSize(160, 100);
        $this->serverOptionsWindow->setTitle('Server Options');
        $this->serverOptionsWindow->setParam("actions", $this->actions["ServerOptions"]);
        $this->serverOptionsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
        $this->serverOptionsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted::getScriptML());

        // bypass the problem where the scripts are registered twice because the template is reloaded on every show
        $scriptServerPass = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/InputboxMasked");
        $scriptServerPass->setParam("btName", "serverPass");
        $this->serverOptionsWindow->registerScript($scriptServerPass);

        $scriptServerSpecPass = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/InputboxMasked");
        $scriptServerSpecPass->setParam("btName", "serverSpecPass");
        $this->serverOptionsWindow->registerScript($scriptServerSpecPass);

        $scriptRefereePass = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/InputboxMasked");
        $scriptRefereePass->setParam("btName", "refereePass");
        $this->serverOptionsWindow->registerScript($scriptRefereePass);
        // ----------------------------------------------------------------------------------------------------------

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
            $server = $this->connection->getServerOptions();
            $this->serverOptionsWindow->setParam("login",             $login);
            $this->serverOptionsWindow->setParam("server",            $server);
            $this->serverOptionsWindow->setParam("serverName",        $this->connection->getServerName());
            $this->serverOptionsWindow->setParam("serverComment",     $this->connection->getServerComment());
            $this->serverOptionsWindow->setParam("serverPassword",    $this->connection->getServerPassword());
            $this->serverOptionsWindow->setParam("serverSpecPassword", $this->connection->getServerPasswordForSpectator());
            $this->serverOptionsWindow->setParam("refereePassword",   $this->connection->getRefereePassword());
            $this->serverOptionsWindow->show($login);
        }
    }

    public function serverOptionsOk($login, $args)
    {
        $server         = $this->storage->server;
        $canGenericOpts = AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS);

        $serverOptions = array(
            "Name"                      => !AdminGroups::hasPermission($login, Permission::SERVER_NAME)
                ? $server->name : $args['serverName'],
            "Comment"                   => !AdminGroups::hasPermission($login, Permission::SERVER_COMMENT)
                ? $server->comment : $args['serverComment'],
            "Password"                  => !AdminGroups::hasPermission($login, Permission::SERVER_PASSWORD)
                ? $server->password : $args['serverPass'],
            "PasswordForSpectator"      => !AdminGroups::hasPermission($login, Permission::SERVER_SPECPWD)
                ? $server->passwordForSpectator : $args['serverSpecPass'],
            "NextCallVoteTimeOut"       => !AdminGroups::hasPermission($login, Permission::SERVER_VOTES)
                ? $server->nextCallVoteTimeOut : intval($server->nextCallVoteTimeOut),
            "CallVoteRatio"             => !AdminGroups::hasPermission($login, Permission::SERVER_VOTES)
                ? $server->callVoteRatio : floatval($server->callVoteRatio),
            /*"RefereePassword"           => !AdminGroups::hasPermission($login, Permission::SERVER_REFPWD)
                ? $server->refereePassword : $args['refereePass'],*/
            "RefereePassword"           => $server->refereePassword,
            "IsP2PUpload"               => !$canGenericOpts
                ? $server->isP2PUpload : isset($args['p2pUpload'])        && $args['p2pUpload']        == '1',
            "IsP2PDownload"             => !$canGenericOpts
                ? $server->isP2PDownload : isset($args['p2pDownload'])    && $args['p2pDownload']      == '1',
            "AllowMapDownload"          => !$canGenericOpts
                ? $server->allowMapDownload : isset($args['allowMapDownload']) && $args['allowMapDownload'] == '1',
            "NextMaxPlayers"            => !AdminGroups::hasPermission($login, Permission::SERVER_MAXPLAYER)
                ? $server->nextMaxPlayers : intval($args['maxPlayers']),
            "NextMaxSpectators"         => !AdminGroups::hasPermission($login, Permission::SERVER_MAXSPEC)
                ? $server->nextMaxSpectators : intval($args['maxSpec']),
            "RefereeMode"               => !AdminGroups::hasPermission($login, 'server_refmode')
                ? $server->refereeMode : isset($args['refereeMode'])      && $args['refereeMode']      == '1',
            "AutoSaveReplays"           => isset($args['AutosaveReplays'])    && $args['AutosaveReplays']    == '1',
            "AutoSaveValidationReplays" => isset($args['AutosaveValidation']) && $args['AutosaveValidation'] == '1',
            "DisableHorns"              => isset($args['DisableHorns'])       && $args['DisableHorns']       == '1',
            "DisableServiceAnnounces"   => isset($args['DisableAnnounces'])   && $args['DisableAnnounces']   == '1',
            "KeepPlayerSlots"           => isset($args['KeepPlayerSlots'])    && $args['KeepPlayerSlots']    == '1',
        );

        try {
            $this->connection->setServerOptions(DedicatedServerOptions::fromArray($serverOptions));
            $this->connection->keepPlayerSlots(isset($args['KeepPlayerSlots']) && $args['KeepPlayerSlots'] == '1');

            if (AdminGroups::hasPermission($login, Permission::SERVER_MAXPLAYER)) {
                $this->connection->setMaxPlayers(intval($args['maxPlayers']));
            }

            if (AdminGroups::hasPermission($login, Permission::SERVER_MAXSPEC)) {
                $this->connection->setMaxSpectators(intval($args['maxSpec']));
            }
        } catch (Exception $e) {
            $this->connection->chatSendServerMessage("Error: " . $e->getMessage());
            $this->connection->chatSendServerMessage(__("Settings not changed.", $login));
        }

        $this->serverOptionsWindow->erase($login);
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
            $this->gameOptionsWindow->setParam("nextGameInfo", $this->connection->getNextGameInfo());
            $this->gameOptionsWindow->show($login);
        }
    }

    /**
     * Apply game options submitted from GameOptions window
     *
     * @param string $login   The login of the player
     * @param array  $options Submitted form values
     */
    public function gameOptionsOk($login, $options)
    {
        if (!AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            return;
        }

        $gameInfos = $this->connection->getNextGameInfo();

        if      (isset($options['TimeAttack']) && $options['TimeAttack'] == '1') $gameInfos->gameMode = GameInfos::GAMEMODE_TIMEATTACK;
        elseif  (isset($options['Rounds'])     && $options['Rounds']     == '1') $gameInfos->gameMode = GameInfos::GAMEMODE_ROUNDS;
        elseif  (isset($options['Cup'])        && $options['Cup']        == '1') $gameInfos->gameMode = GameInfos::GAMEMODE_CUP;
        elseif  (isset($options['Laps'])       && $options['Laps']       == '1') $gameInfos->gameMode = GameInfos::GAMEMODE_LAPS;
        elseif  (isset($options['Team'])       && $options['Team']       == '1') $gameInfos->gameMode = GameInfos::GAMEMODE_TEAM;

        $gameInfos->allWarmUpDuration  = intval($options['AllWarmupDuration']);
        $gameInfos->cupWarmUpDuration  = intval($options['AllWarmupDuration']);
        $gameInfos->finishTimeout      = intval(\ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['finishTimeOut']));
        $gameInfos->chatTime           = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['ChatTime']);

        $gameInfos->disableRespawn        = isset($options['DisableRespawn'])        && $options['DisableRespawn']        == '1';
        $gameInfos->forceShowAllOpponents = isset($options['ForceShowAllOpponents']) && $options['ForceShowAllOpponents'] == '1';
        $gameInfos->roundsUseNewRules     = isset($options['roundsUseNewRules'])     && $options['roundsUseNewRules']     == '1';
        $gameInfos->teamUseNewRules       = isset($options['teamUseNewRules'])       && $options['teamUseNewRules']       == '1';

        $gameInfos->timeAttackLimit              = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['timeAttackLimit']);
        $gameInfos->timeAttackSynchStartPeriod   = intval($options['timeAttackSynchStartPeriod']);

        $gameInfos->roundsForcedLaps             = intval($options['roundsForcedLaps']);
        $gameInfos->roundsPointsLimit            = intval($options['roundsPointsLimit']);
        $gameInfos->roundsPointsLimitNewRules    = intval($options['roundsPointsLimitNewRules']);

        $gameInfos->teamPointsLimit              = intval($options['teamPointsLimit']);
        $gameInfos->teamPointsLimitNewRules      = intval($options['teamPointsLimitNewRules']);
        $gameInfos->teamMaxPoints                = intval($options['teamMaxPoints']);

        $gameInfos->cupNbWinners                 = intval($options['cupNbWinners']);
        $gameInfos->cupPointsLimit               = intval($options['cupPointsLimit']);
        $gameInfos->cupRoundsPerMap              = intval($options['cupRoundsPerMap']);

        $gameInfos->lapsNbLaps                   = intval($options['lapsNbLaps']);
        $gameInfos->lapsTimeLimit                = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['lapsTimeLimit']);

        try {
            $this->connection->setGameInfos($gameInfos);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage('$f00Dedicated error: ' . $e->getMessage(), $login);
            \ManiaLib\Utils\Logger::error("Error while setGameInfos: " . $e->getMessage());
        }

        $this->gameOptionsWindow->erase($login);
    }

    /**
     * Show the window for server management
     *
     * @param string $login The login of the player
     */
    public function serverManagement($login)
    {
        $canDedicated  = AdminGroups::hasPermission($login, Permission::SERVER_STOP_DEDICATED);
        $canManialive  = AdminGroups::hasPermission($login, Permission::SERVER_STOP_MANIALIVE);

        if ($canDedicated || $canManialive) {
            $this->serverManagementWindow->setParam("canStopDedicated", $canDedicated);
            $this->serverManagementWindow->setParam("canStopManialive", $canManialive);
            $this->serverManagementWindow->show($login);
        }
    }

    public function stopServer($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_STOP_DEDICATED)) {
            $this->connection->chatSendServerMessage("[Notice] Stopping server...");
            $this->connection->stopServer();
        }
    }

    public function stopManialive($login)
    {
        if (AdminGroups::hasPermission($login, Permission::SERVER_STOP_MANIALIVE)) {
            $this->connection->chatSendServerMessage("[Notice] Stopping eXpansion...");
            $this->connection->sendHideManialinkPage();
            \ManiaLive\Application\Application::getInstance()->kill();
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
            $this->serverControlMainWindow->show($login);
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
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s#admin_action# changes script parameter #variable#%s#admin_action# to #variable#%s #admin_action#(#admin_action#Was #variable#%s#admin_action#)', null, array($admin->cleanNickName, $key, $value[1], $value[0]));
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
            $nick = $this->storage->getPlayerObject($login)->cleanNickName;
            /** @var \ManiaLivePlugins\eXpansion\Core\Config $config */
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
                    $this->connection->setModeScriptSettings(array("S_UseCustomPointsRepartition" => true));
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
        MatchSettings::EraseAll();
        RoundPoints::EraseAll();
        ScriptSettings::EraseAll();

        if ($this->serverControlMainWindow instanceof Window) {
            $this->serverControlMainWindow->erase();
        }
        $this->serverControlMainWindow = null;

        if ($this->serverManagementWindow instanceof Window) {
            $this->serverManagementWindow->erase();
        }
        $this->serverManagementWindow = null;

        if ($this->gameOptionsWindow instanceof Window) {
            $this->gameOptionsWindow->erase();
        }
        $this->gameOptionsWindow = null;

        if ($this->serverOptionsWindow instanceof Window) {
            $this->serverOptionsWindow->erase();
        }
        $this->serverOptionsWindow = null;

        /** @var ActionHandler $aH */
        $aH = ActionHandler::getInstance();
        foreach ($this->actions as $actions) {
            foreach ($actions as $action) {
                $aH->deleteAction($action);
            }
        }
        $this->actions = array();
    }
}
