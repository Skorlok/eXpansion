<?php

namespace ManiaLivePlugins\eXpansion\Adm;

use Exception;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\I18n\Message;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\ServerOptions as DedicatedServerOptions;

class Adm extends ExpPlugin
{
    /** @var Message Messages needed */
    private $msgDatabasePlugin;
    /** @var Message Messages needed */
    private $msgForceScoreError;

    protected $actions = array("ServerControlMain" => array(), "ServerManagement" => array(), "GameOptions" => array(), "ServerOptions" => array(), "ForceScores" => array(), "ScriptSettings" => array(), "MatchSettings" => array(), "RoundPoints" => array());

    protected $matchSettingsFileActions = array();

    /** @var Window */
    protected $serverControlMainWindow;
    /** @var Window */
    protected $serverManagementWindow;
    /** @var Window */
    protected $gameOptionsWindow;
    /** @var Window */
    protected $serverOptionsWindow;
    /** @var Window */
    protected $forceScoresWindow;
    /** @var Window */
    protected $scriptSettingsWindow;
    /** @var Window */
    protected $matchSettingsWindow;
    /** @var Window */
    protected $roundPointsWindow;

    /**
     * @inheritdoc
     */
    public function eXpOnLoad()
    {
        $this->msgForceScoreError = eXpGetMessage("ForceScores can be used only with rounds or team mode");
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
        
        $this->registerManialinkCallback('forceScoresClear');
        $this->registerManialinkCallback('forceScoresSkip');
        $this->registerManialinkCallback('forceScoresRestart');
        $this->registerManialinkCallback('forceScoresApply', true);
        $this->registerManialinkCallback('gameOptionsOk', true);
        $this->registerManialinkCallback('matchSettingsSaveAs', true);
        $this->registerManialinkCallback('matchSettingsLoadAs', true);
        $this->registerManialinkCallback('roundPointsSetCustom', true);
        $this->registerManialinkCallback('scriptSettingsApply', true);
        $this->registerManialinkCallback('serverManagement');
        $this->registerManialinkCallback('serverOptions');
        $this->registerManialinkCallback('gameOptions');
        $this->registerManialinkCallback('adminGroups');
        $this->registerManialinkCallback('matchSettings');
        $this->registerManialinkCallback('scriptSettings');
        $this->registerManialinkCallback('forceScores');
        $this->registerManialinkCallback('roundPoints');
        $this->registerManialinkCallback('dbTools');
        $this->registerManialinkCallback('showExpSettings');
        $this->registerManialinkCallback('showPluginManagement');
        $this->registerManialinkCallback('showVotesConfig');
        $this->registerManialinkCallback('serverOptionsOk', true);
        $this->registerManialinkCallback('stopManialive');
        $this->registerManialinkCallback('stopServer');

        /** @var ActionHandler $ah */
        $ah = ActionHandler::getInstance();

        $rpoints = $this->roundPointsGetPresets();
        $presetsForTemplate = array();
        foreach ($rpoints as $i => $preset) {
            $actionKey = "preset_" . $i;
            $this->actions["RoundPoints"][$actionKey] = $ah->createAction(array($this, 'roundPointsSetPreset'), $preset['points']);
            $presetsForTemplate[] = array(
                'name'   => $preset['name'],
                'points' => implode(",", $preset['points']),
                'action' => $this->actions["RoundPoints"][$actionKey],
            );
        }



        $this->gameOptionsWindow = new Window("Adm\Gui\Windows\GameOptions.xml");
        $this->gameOptionsWindow->setName("GameOptions");
        $this->gameOptionsWindow->setSize(160, 85);
        $this->gameOptionsWindow->setTitle('Game Options');

        $this->serverControlMainWindow = new Window("Adm\Gui\Windows\ServerControlMain.xml");
        $this->serverControlMainWindow->setName("ServerControlMain");
        $this->serverControlMainWindow->setSize(140, 25);
        $this->serverControlMainWindow->setTitle('Control Panel');
        $this->serverControlMainWindow->setParam("isRelay", Storage::getInstance()->isRelay);

        $this->serverManagementWindow = new Window("Adm\Gui\Windows\ServerManagement.xml");
        $this->serverManagementWindow->setName("ServerManagement");
        $this->serverManagementWindow->setSize(90, 30);
        $this->serverManagementWindow->setTitle("Server Control");
        $this->serverManagementWindow->setParam("stopManialiveAction", \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm("exp:eXpansion.Adm:stopManialive"));
        $this->serverManagementWindow->setParam("stopServerAction", \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm("exp:eXpansion.Adm:stopServer"));

        $this->serverOptionsWindow = new Window("Adm\Gui\Windows\ServerOptions.xml");
        $this->serverOptionsWindow->setName("ServerOptions");
        $this->serverOptionsWindow->setSize(160, 100);
        $this->serverOptionsWindow->setTitle('Server Options');

        $this->forceScoresWindow = new Window("Adm\Gui\Windows\ForceScores.xml");
        $this->forceScoresWindow->setName("ForceScores");
        $this->forceScoresWindow->setSize(160, 80);
        $this->forceScoresWindow->setTitle("Force Scores");
        $this->forceScoresWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(6, 72));

        $this->scriptSettingsWindow = new Window("Adm\Gui\Windows\ScriptSettings.xml");
        $this->scriptSettingsWindow->setName("ScriptSettings");
        $this->scriptSettingsWindow->setSize(160, 100);
        $this->scriptSettingsWindow->setTitle("Script Settings");
        $this->scriptSettingsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(6, 92));

        $this->matchSettingsWindow = new Window("Adm\Gui\Windows\MatchSettings.xml");
        $this->matchSettingsWindow->setName("MatchSettings");
        $this->matchSettingsWindow->setSize(160, 100);
        $this->matchSettingsWindow->setTitle("Match Settings");
        $this->matchSettingsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(6, 84));

        $this->roundPointsWindow = new Window("Adm\Gui\Windows\RoundPoints.xml");
        $this->roundPointsWindow->setName("RoundPoints");
        $this->roundPointsWindow->setSize(160, 90);
        $this->roundPointsWindow->setTitle("Custom Round Points");
        $this->roundPointsWindow->setParam("presets", $presetsForTemplate);
        $this->roundPointsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(6, 82));


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
                $this->forceScoresWindow->setParam("players", Core::$rankings);
                $this->forceScoresWindow->show($login);
            } else {
                $this->eXpChatSendServerMessage($this->msgForceScoreError, $login);
            }
        }
    }

    public function forceScoresApply($fromLogin, $scores = array())
    {
        foreach ($scores as $login => $val) {
            if ($val != null) {
                if (!Core::$useTeams) {
                    $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$login", "", "", "$val"));
                    $this->connection->triggerModeScriptEventArray('Shootmania.SetPlayerPoints', array("$login", "", "", "$val"));
                } else {
                    $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$login", "", "$val", "$val"));
                    $this->connection->triggerModeScriptEventArray('Shootmania.SetTeamPoints', array("$login", "", "$val", "$val"));
                }
            }
        }
        $this->connection->triggerModeScriptEventArray('Trackmania.GetScores', array());
        $this->connection->triggerModeScriptEventArray('Shootmania.GetScores', array());
        $this->forceScoresOk();
        $this->forceScoresWindow->erase($fromLogin);
    }

    public function forceScoresClear($fromLogin)
    {
        foreach (Core::$rankings as $rank) {
            if (!Core::$useTeams) {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$rank->login", "0", "0", "0"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetPlayerPoints', array("$rank->login", "0", "0", "0"));
            } else {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$rank->login", "0", "0", "0"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetTeamPoints', array("$rank->login", "0", "0", "0"));
            }
        }
        $this->connection->triggerModeScriptEventArray('Trackmania.GetScores', array());
        $this->connection->triggerModeScriptEventArray('Shootmania.GetScores', array());
        $this->forceScoresOk();

        $this->forceScoresWindow->setParam("players", Core::$rankings);
        $this->forceScoresWindow->show($fromLogin);
    }

    public function forceScoresSkip($login)
    {
        $ag = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
        $ag->adminCmd($login, "rskip");
        $this->forceScoresWindow->erase($login);
    }

    public function forceScoresRestart($login)
    {
        $ag = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
        $ag->adminCmd($login, "rres");
        $this->forceScoresWindow->erase($login);
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
            $this->serverManagementWindow->setParam("hideStopDedicated", !$canDedicated);
            $this->serverManagementWindow->setParam("hideStopManialive", !$canManialive);
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
            $this->roundPointsWindow->setParam("customPoints", implode(",", $this->connection->getRoundCustomPoints()));
            $this->roundPointsWindow->show($login);
        }
    }

    public function roundPointsSetCustom($fromLogin, $entries = array())
    {
        if (!empty($entries['customPoints'])) {
            $parts = explode(",", $entries['customPoints']);
            rsort($parts, SORT_NUMERIC);
            $intPoints = array();
            foreach ($parts as $p) {
                $intPoints[] = intval($p);
            }
            $this->setPoints($fromLogin, $intPoints);
        }
        $this->roundPointsWindow->erase($fromLogin);
    }

    public function roundPointsSetPreset($fromLogin, $points)
    {
        $this->setPoints($fromLogin, $points);
        $this->roundPointsWindow->erase($fromLogin);
    }

    private function roundPointsGetPresets()
    {
        return array(
            array('name' => 'Formula 1 GP New',       'points' => array(25, 18, 15, 12, 10, 8, 6, 4, 2, 1)),
            array('name' => 'Formula 1 GP Old',       'points' => array(10, 8, 6, 5, 4, 3, 2, 1)),
            array('name' => 'MotoGP',                 'points' => array(25, 20, 16, 13, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1)),
            array('name' => 'MotoGP + 5',             'points' => array(30, 25, 21, 18, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1)),
            array('name' => 'Formula ET Season 1',    'points' => array(12, 10, 9, 8, 7, 6, 5, 4, 4, 3, 3, 3, 2, 2, 2, 1)),
            array('name' => 'Formula ET Season 2',    'points' => array(15, 12, 11, 10, 9, 8, 7, 6, 6, 5, 5, 4, 4, 3, 3, 3, 2, 2, 2, 1)),
            array('name' => 'Formula ET Season 3',    'points' => array(15, 12, 11, 10, 9, 8, 7, 6, 6, 5, 5, 4, 4, 3, 3, 3, 2, 2, 2, 2, 1)),
            array('name' => 'Champ Car World Series', 'points' => array(31, 27, 25, 23, 21, 19, 17, 15, 13, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1)),
            array('name' => 'Superstars',             'points' => array(20, 15, 12, 10, 8, 6, 4, 3, 2, 1)),
            array('name' => 'Simple 5',               'points' => array(5, 4, 3, 2, 1)),
            array('name' => 'Simple 10',              'points' => array(10, 9, 8, 7, 6, 5, 4, 3, 2, 1)),
        );
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
     * @param string $login The login of the player
     */
    public function matchSettings($login)
    {
        if (AdminGroups::hasPermission($login, Permission::GAME_MATCH_SAVE) || AdminGroups::hasPermission($login, 'game_matchDelete') || AdminGroups::hasPermission($login, 'game_match')) {
            $this->matchSettingsShowFor($login);
        }
    }

    private function matchSettingsShowFor($login)
    {
        /** @var ActionHandler $ah */
        $ah = ActionHandler::getInstance();
        foreach ($this->matchSettingsFileActions as $acts) {
            $ah->deleteAction($acts['load']);
            $ah->deleteAction($acts['save']);
            $ah->deleteAction($acts['deletef']);
            $ah->deleteAction($acts['delete']);
        }
        $this->matchSettingsFileActions = array();

        $isRemote = Storage::getInstance()->isRemoteControlled;
        $files    = array();

        if (!$isRemote) {
            $path     = Helper::getPaths()->getMatchSettingPath() . "*.txt";
            $settings = glob($path);
            if ($settings) {
                foreach ($settings as $filename) {
                    $loadAct    = $ah->createAction(array($this, 'matchSettingsLoad'), $filename);
                    $saveAct    = $ah->createAction(array($this, 'matchSettingsSave'), $filename);
                    $deleteActf = $ah->createAction(array($this, 'matchSettingsDelete'), $filename);
                    $deleteAct  = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($deleteActf);

                    $this->matchSettingsFileActions[$filename] = array(
                        'load'    => $loadAct,
                        'save'    => $saveAct,
                        'deletef' => $deleteActf,
                        'delete'  => $deleteAct,
                    );

                    $parts    = explode(DIRECTORY_SEPARATOR, $filename);
                    $basename = end($parts);
                    $files[]  = array(
                        'filename' => $filename,
                        'basename' => $basename,
                        'load'     => $loadAct,
                        'save'     => $saveAct,
                        'delete'   => $deleteAct,
                    );
                }
            }
        }

        $perms = array(
            'canSaveAs' => AdminGroups::hasPermission($login, Permission::GAME_MATCH_SAVE),
            'canLoad'   => AdminGroups::hasPermission($login, Permission::GAME_MATCH_SETTINGS),
            'canSave'   => AdminGroups::hasPermission($login, Permission::GAME_MATCH_SAVE),
            'canDelete' => AdminGroups::hasPermission($login, Permission::GAME_MATCH_DELETE),
        );

        $this->matchSettingsWindow->setParam("files",     $files);
        $this->matchSettingsWindow->setParam("isRemote",  $isRemote);
        $this->matchSettingsWindow->setParam("perms",     $perms);
        $this->matchSettingsWindow->show($login);
    }

    public function matchSettingsSaveAs($login, $entries = array())
    {
        try {
            if (empty($entries['SaveAs'])) {
                $this->connection->chatSendServerMessage(__("Error in filename", $login), $login);
                return;
            }
            $appendTxt = ".txt";
            if (substr($entries['SaveAs'], -4, 4) == ".txt") {
                $appendTxt = "";
            }
            $filename = Helper::getPaths()->getMatchSettingPath() . $entries['SaveAs'] . $appendTxt;
            $this->connection->saveMatchSettings($filename);
            $file = explode("/", $filename);
            $this->connection->chatSendServerMessage(__("Saved MatchSettings to file: %s", $login, end($file)), $login);
            $this->matchSettingsShowFor($login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage(__('$f00$oError $z$s$fff%s', $login, $e->getMessage()), $login);
        }
    }

    public function matchSettingsLoadAs($login, $entries = array())
    {
        try {
            if (empty($entries['LoadAs'])) {
                $this->connection->chatSendServerMessage(__("Error in filename", $login), $login);
                return;
            }
            $appendTxt = ".txt";
            if (substr($entries['LoadAs'], -4, 4) == ".txt") {
                $appendTxt = "";
            }
            $filename = Helper::getPaths()->getMatchSettingPath() . $entries['LoadAs'] . $appendTxt;
            $this->connection->loadMatchSettings($filename);
            $file = explode("/", $filename);
            $this->connection->chatSendServerMessage(__("Loaded MatchSettings from file: %s", $login, end($file)), $login);
            $this->matchSettingsShowFor($login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage(__('$f00$oError $z$s$fff%s', $login, $e->getMessage()), $login);
        }
    }

    public function matchSettingsSave($login, $filename)
    {
        try {
            $this->connection->saveMatchSettings($filename);
            $file = explode("/", $filename);
            $this->connection->chatSendServerMessage(__("Saved MatchSettings to file: %s", $login, end($file)), $login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage(__('$f00$oError $z$s$fff%s', $login, $e->getMessage()), $login);
        }
    }

    public function matchSettingsLoad($login, $filename)
    {
        try {
            $this->connection->loadMatchSettings($filename);
            $file = explode("/", $filename);
            $this->connection->chatSendServerMessage(__("Loaded MatchSettings from file: %s", $login, end($file)), $login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage(__('$f00$oError $z$s$fff%s', $login, $e->getMessage()), $login);
        }
    }

    public function matchSettingsDelete($login, $filename)
    {
        try {
            unlink($filename);
            $file = explode("/", $filename);
            $this->connection->chatSendServerMessage(__("File '%s' deleted from filesystem!", $login, end($file)), $login);
            $this->matchSettingsShowFor($login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage(__('$f00$oError $z$s$fff%s', $login, $e->getMessage()), $login);
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
            $this->scriptSettingsWindow->setParam("settings", $this->connection->getModeScriptSettings());
            $this->scriptSettingsWindow->show($login);
        }
    }

    public function scriptSettingsApply($fromLogin, $submitted = array())
    {
        $currentSettings = $this->connection->getModeScriptSettings();
        $newSettings     = array();
        $diffParams      = array();

        foreach ($currentSettings as $name => $oldValue) {
            $type = gettype($oldValue);
            if ($type === 'boolean') {
                $newValue = isset($submitted[$name]) && $submitted[$name] == '1';
                if ((bool)$oldValue !== $newValue) {
                    $diffParams[$name] = array(
                        ($oldValue ? "True" : "False"),
                        ($newValue ? "True" : "False")
                    );
                }
                $newSettings[$name] = $newValue;
            } else {
                $newValue = isset($submitted[$name]) ? $submitted[$name] : $oldValue;
                settype($newValue, $type);
                if ($oldValue != $newValue) {
                    $diffParams[$name] = array(
                        ($oldValue ? $oldValue : '$iEmpty$i'),
                        ($newValue ? $newValue : '$iEmpty$i')
                    );
                }
                $newSettings[$name] = $newValue;
            }
        }

        if (!empty($newSettings)) {
            $this->connection->setModeScriptSettings($newSettings);
            $this->afterScriptSettings($fromLogin, $diffParams);
        }

        $this->scriptSettingsWindow->erase($fromLogin);
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

        if ($this->forceScoresWindow instanceof Window) {
            $this->forceScoresWindow->erase();
        }
        $this->forceScoresWindow = null;

        if ($this->scriptSettingsWindow instanceof Window) {
            $this->scriptSettingsWindow->erase();
        }
        $this->scriptSettingsWindow = null;

        if ($this->matchSettingsWindow instanceof Window) {
            $this->matchSettingsWindow->erase();
        }
        $this->matchSettingsWindow = null;

        if ($this->roundPointsWindow instanceof Window) {
            $this->roundPointsWindow->erase();
        }
        $this->roundPointsWindow = null;

        /** @var ActionHandler $aH */
        $aH = ActionHandler::getInstance();
        foreach ($this->matchSettingsFileActions as $acts) {
            $aH->deleteAction($acts['load']);
            $aH->deleteAction($acts['save']);
            $aH->deleteAction($acts['deletef']);
            $aH->deleteAction($acts['delete']);
        }
        $this->matchSettingsFileActions = array();

        foreach ($this->actions as $actions) {
            foreach ($actions as $action) {
                $aH->deleteAction($action);
            }
        }
        $this->actions = array();
    }
}
