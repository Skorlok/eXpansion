<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin;

use Exception;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLib\Utils\Formatting;
use ManiaLib\Utils\Path;
use ManiaLive\Application\Application;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\PluginHandler\Dependency;
use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Adm\Gui\Windows\ScriptSettings;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AdminGroups\types\Arraylist;
use ManiaLivePlugins\eXpansion\AdminGroups\types\Boolean;
use ManiaLivePlugins\eXpansion\AdminGroups\types\Integer;
use ManiaLivePlugins\eXpansion\AdminGroups\types\Time_ms;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Controls\BannedPlayeritem;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Controls\BlacklistPlayeritem;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Controls\GuestPlayeritem;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Controls\IgnoredPlayeritem;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows\GenericPlayerList;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows\ParameterDialog;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows\TeamSetup;
use ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows\ClubLinksSetup;
use ManiaLivePlugins\eXpansion\ChatAdmin\Structures\ActionDuration;
use ManiaLivePlugins\eXpansion\Core\Config;
use ManiaLivePlugins\eXpansion\Core\Events\ExpansionEvent;
use ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\Helpers\TimeConversion;
use ManiaLivePlugins\eXpansion\Helpers\ColorConversion;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\Player;
use Maniaplanet\DedicatedServer\Structures\PlayerBan;
use oliverde8\AsynchronousJobs\Job\Curl;
use Phine\Exception\Exception as Exception2;

/**
 * Description of Admin
 *
 * @author oliverde8
 */
class ChatAdmin extends ExpPlugin
{
    /** @var ActionDuration[] $durations */
    private $durations = array();
    /** @var \ManiaLivePlugins\eXpansion\Core\DataAccess */
    protected $dataAccess;
    protected $clubLinks = array();
    protected $clubLinksGet = 0;
    protected $clubLinksExpected = 0;

    public static $showActions = array();

    /**
     *
     */
    public function eXpOnInit()
    {
        ParameterDialog::$mainPlugin = $this;
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups'));

        $this->setPublicMethod("restartMap");
        $this->setPublicMethod("skipMap");
        $this->setPublicMethod("cancelVote");
        $this->setPublicMethod("showGuestList");
        $this->setPublicMethod("showBanList");
        $this->setPublicMethod("showBlackList");
        $this->setPublicMethod("showIgnoreList");
        $this->setPublicMethod("forceEndRound");
        $this->setPublicMethod("forceEndWu");
        $this->setPublicMethod("forceEndWuR");
        $this->setPublicMethod("shuffleMaps");
        $this->setPublicMethod("setPause");
        $this->setPublicMethod("setResume");
    }

    /**
     *
     */
    public function eXpOnLoad()
    {
        $adminGroup = AdminGroups::getInstance();

        $cmd = AdminGroups::addAdminCommand('ta', $this, 'fastTa', Permission::GAME_SETTINGS);
        $cmd->setHelp('/ta limit; Sets timelimit for TimeAttack');

        $cmd = AdminGroups::addAdminCommand('laps', $this, 'fastLaps', Permission::GAME_SETTINGS);
        $cmd->setHelp('/laps laps X; Sets Laps Limit');

        $cmd = AdminGroups::addAdminCommand('rounds', $this, 'fastRounds', Permission::GAME_SETTINGS);
        $cmd->setHelp('/rounds limit X; Sets PointLimit in Rounds');

        $cmd = AdminGroups::addAdminCommand('cup', $this, 'fastCup', Permission::GAME_SETTINGS);
        $cmd->setHelp('/cup limit X; Sets CupRoundsLimit for Winner');

        $cmd = AdminGroups::addAdminCommand('team', $this, 'fastTeam', Permission::GAME_SETTINGS);
        $cmd->setHelp('/team limit X; Sets Team PointLimit');

        /*
         * *******************
         * Concerning Players
         * *******************
         *
         *
         */

        $cmd = AdminGroups::addAdminCommand('kick', $this, 'kick', Permission::PLAYER_KICK); //
        $cmd->setHelp('kick the player from the server');
        $cmd->setHelpMore('$w//kick #login$z will kick the player from the server. A kicked player may return to the server whanever he desires.');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('guest', $this, 'guest', Permission::PLAYER_KICK); //
        $cmd->setHelp('guest the player from the server');
        $cmd->setHelpMore('$w//guest #login$z will guest the player from the server. A guest player doesen\'t need to enter passwords to enter the server.');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('unguest', $this, 'guestRemove', Permission::PLAYER_KICK); //
        $cmd->setHelp('remove the guest status of the player');
        $cmd->setHelpMore('$w//unguest #login$z will remove the guest status of the player. A guest player doesen\'t need to enter passwords to enter the server.');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('ban', $this, 'ban', Permission::PLAYER_BAN);
        $cmd->setHelp('Ban the player from the server');
        $cmd->setHelpMore('$w//ban #login$z will ban  the player from the server. He may not return until the server is restarted');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('black', $this, 'blacklist', Permission::PLAYER_BLACK);
        $cmd->setHelp('Add the player to the black list');
        $cmd->setHelpMore('$w//black #login$z will add the player to the blacklist of this server. He may not return until the server blacklist file is deleted. Other server might use the same blacklist file!!');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('unban', $this, 'unban', Permission::PLAYER_UNBAN);
        $cmd->setHelp('Removes the ban of the player');
        $cmd->addLineHelpMore('$w//unban #login$z will remove the ban of the player from this server');
        $cmd->addLineHelpMore('He may rejoin the server after this.');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('cleanbanlist', $this, 'cleanBanlist', Permission::PLAYER_UNBAN);
        $cmd->setHelp('clears the banlist of players');
        $cmd->addLineHelpMore('Will completeley clear the banlist.');
        $cmd->addLineHelpMore('All banned players will be able to rejoin the server.');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('getbanlist', $this, 'showBanList', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('shows the current banlist of players');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('cleanblacklist', $this, 'cleanBlacklist', Permission::PLAYER_UNBLACK);
        $cmd->setHelp('clears the blacklist of players');
        $cmd->addLineHelpMore('Will completeley clear the blackList.');
        $cmd->addLineHelpMore('All blacklist players will be able to rejoin the server.');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('getblacklist', $this, 'showBlackList', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('shows the current banlist of players');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('getguestlist', $this, 'showGuestList', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('shows the current guest of players');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('getignorelist', $this, 'showIgnoreList', Permission::PLAYER_IGNORE);
        $cmd->setHelp('shows the current ignorelist of players');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('unblack', $this, 'unBlacklist', Permission::PLAYER_UNBLACK);
        $cmd->setHelp('Removes the player from the black list');
        $cmd->addLineHelpMore('$w//unblack #login$z will remove the player from the servers blacklist');
        $cmd->addLineHelpMore('He may rejoin the server after this.');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('spec', $this, 'forceSpec', Permission::PLAYER_FORCESPEC);
        $cmd->setHelp('Forces the player to become spectator');
        $cmd->addLineHelpMore('$w//spec #login$z The playing player will be forced to become a spectator');
        $cmd->addLineHelpMore('If the max spectators is reached it the player won\'t become a spectator');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('play', $this, 'forcePlay', Permission::PLAYER_FORCESPEC);
        $cmd->setHelp('Forces the spectator to become player');
        $cmd->addLineHelpMore('$w//play #login$z The spectator will be forced to become a player');
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('ignore', $this, 'ignore', Permission::PLAYER_IGNORE);
        $cmd->setHelp('Adds player to ignore list and mutes him from the chat');
        $cmd->addLineHelpMore('$w//ignore #login$z will ignore the players chat');
        $cmd->addLineHelpMore('This player won\'t be able to communicate with other players.');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "mute");

        $cmd = AdminGroups::addAdminCommand('unignore', $this, 'unignore', Permission::PLAYER_IGNORE);
        $cmd->setHelp('Removes player to ignore list and allows him to chat');
        $cmd->addLineHelpMore('$w//unignore #login$z will allow this player to use the chat again');
        $cmd->addLineHelpMore('This player will be able to communicate with other players');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "unmute");
        //ENDSUPER

        /*
         * ***************************
         * Concerning Server Settings
         * ***************************
         */

        $cmd = AdminGroups::addAdminCommand('settings', $this, 'invokeExpSettings', Permission::EXPANSION_PLUGIN_SETTINGS);
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('netstats', $this, 'invokeNetStats', Permission::CHAT_ADMINCHAT);
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "netstat"); // fast

        $cmd = AdminGroups::addAdminCommand('planets', $this, 'getServerPlanets', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('Gets the serveraccount planets amount');
        $cmd->addLineHelpMore('$w//planets $zreturn the planets amount on server account.');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('pay', $this, 'pay', Permission::SERVER_PLANETS);
        $cmd->setHelp('Pays out planets');
        $cmd->addLineHelpMore('$w//pay #login #amount$z pays amount of planets to login');
        $cmd->setMinParam(2);
        $cmd->addchecker(2, Integer::getInstance());

        $cmd = AdminGroups::addAdminCommand('name', $this, 'setServerName', Permission::SERVER_NAME);
        $cmd->setHelp('Changes the name of the server');
        $cmd->addLineHelpMore('$w//name #name$z will change the server name.');
        $cmd->addLineHelpMore('This servers name will be changed.');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "setservername"); // xaseco

        $cmd = AdminGroups::addAdminCommand('comment', $this, 'setServerComment', Permission::SERVER_COMMENT);
        $cmd->setHelp('Changes the server comment');
        $cmd->addLineHelpMore('$w//comment #comment$z will change the server comment.');
        $cmd->addLineHelpMore('This servers comment will be changed.');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "setcomment"); // xaseco

        $cmd = AdminGroups::addAdminCommand('password', $this, 'setServerPassword', Permission::SERVER_PASSWORD);
        $cmd->setHelp('Changes the player password');
        $cmd->setHelpMore('$w//password #pwd$z will change the password needed for players to connect to this server');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "setpwd"); // xaseco
        AdminGroups::addAlias($cmd, "pass"); // fast

        $cmd = AdminGroups::addAdminCommand('specpassword', $this, 'setSpecPassword', Permission::SERVER_SPECPWD);
        $cmd->setHelp('Changes the spectator password');
        $cmd->setHelpMore('$w//specpassword #pwd$z will change the password needed for spectators to connect to this server');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "setspecpwd"); // xaseco
        AdminGroups::addAlias($cmd, "spectpass"); // fast
        AdminGroups::addAlias($cmd, "specpass"); // fast

        $cmd = AdminGroups::addAdminCommand('refpassword', $this, 'setRefereePassword', Permission::SERVER_REFPWD);
        $cmd->setHelp('Changes the Referee password');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "setrefpwd"); // xaseco

        $cmd = AdminGroups::addAdminCommand('maxplayers', $this, 'setServerMaxPlayers', Permission::SERVER_MAXPLAYER);
        $cmd->setHelp('Sets a new maximum of players');
        $cmd->setHelpMore('Sets the maximum number of players who can play on this server.');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setmaxplayers"); //xaseco

        $cmd = AdminGroups::addAdminCommand('maxspectators', $this, 'setServerMaxSpectators', Permission::SERVER_MAXSPEC);
        $cmd->setHelp('Sets a new maximum of spectator');
        $cmd->setHelpMore('Sets the maximum number of players who can spectate the players on this server.');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setmaxspecs"); // xaseco
        AdminGroups::addAlias($cmd, "maxspec"); // fast

        $cmd = AdminGroups::addAdminCommand('chattime', $this, 'setserverchattime', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('Sets the Chat time duration.');
        $cmd->addLineHelpMore('This is the time players get between the challenge end and the the new map');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Time_ms::getInstance());

        $cmd = AdminGroups::addAdminCommand('sethideserver', $this, 'setHideServer', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('Allows you to hide or show the server to players');
        $cmd->addLineHelpMore('$w//sethideserver true$z Will hide the server from other players. Players would need to have the servers in their favorites or need to know the server login');
        $cmd->addLineHelpMore('$w//sethideserver false$z Will make the server visible to any player');
        $cmd->addchecker(1, Boolean::getInstance());
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('setmapdownload', $this, 'setServerMapDownload', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('Will allow players to download maps they are playing from the server.');
        $cmd->addLineHelpMore('$w//setmapdownload true$z will allow the maps to be downloaded.');
        $cmd->addLineHelpMore('$w//setmapdownload false$z will not allow players to download the maps of this server.');
        $cmd->addchecker(1, Boolean::getInstance());
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('stopdedi', $this, 'stopDedicated', Permission::SERVER_STOP_DEDICATED);
        $cmd->setHelp("Stops this server. Manialive will stop after this.");

        $cmd = AdminGroups::addAdminCommand('expstop', $this, 'stopManiaLive', Permission::SERVER_STOP_MANIALIVE);
        $cmd->setHelp("Stops the Manialive instance running on for the server.");

        $cmd = AdminGroups::addAdminCommand('expres', $this, 'restartManiaLive', Permission::SERVER_STOP_MANIALIVE);
        $cmd->setHelp("Restart the Manialive instance running on for the server.");

        /*
         * *************************
         * Concerning Game Settings
         * *************************
         */
        $cmd = AdminGroups::addAdminCommand('skip', $this, 'skipMap', Permission::MAP_SKIP);
        $cmd->setHelp("Skips the current track");
        AdminGroups::addAlias($cmd, 'skipmap'); // xaseco
        AdminGroups::addAlias($cmd, 'next'); // fast
        AdminGroups::addAlias($cmd, 'nextmap');

        $cmd = AdminGroups::addAdminCommand('restart', $this, 'restartMap', Permission::MAP_RES);
        $cmd->setHelp("Restarts this map to allow you to replay the map");
        AdminGroups::addAlias($cmd, 'res'); // xaseco
        AdminGroups::addAlias($cmd, 'restartmap'); //xaseco

        $cmd = AdminGroups::addAdminCommand('rskip', $this, 'skipScoreReset', Permission::MAP_SKIP);
        $cmd->setHelp("Skips the current track and reset scores");

        $cmd = AdminGroups::addAdminCommand('rres', $this, 'restartScoreReset', Permission::MAP_RES);
        $cmd->setHelp("Restarts this map and resets the scores");

        $cmd = AdminGroups::addAdminCommand('mode', $this, 'setGameMode', Permission::GAME_GAMEMODE);
        $cmd->setHelp('Sets next mode {ta,rounds,team,laps,cup,reload}');
        $cmd->addLineHelpMore('$w//mode reload$z will reload the current gamemode.');
        $cmd->addLineHelpMore('$w//mode ta$z will change gamemode to TimeAttack.');
        $cmd->addLineHelpMore('$w//mode rounds$z will change gamemode to Rounds mode.');
        $cmd->addLineHelpMore('$w//mode team$z will change gamemode to Team mode.');
        $cmd->addLineHelpMore('$w//mode laps$z will change gamemode to Laps mode.');
        $cmd->addLineHelpMore('$w//mode cup$z will change gamemode to Cup mode.');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, 'setgamemode'); //xaseco

        $cmd = AdminGroups::addAdminCommand('script', $this, 'setModeScriptSettings', Permission::GAME_SETTINGS);
        $cmd->setHelp('Changes the script settings for the current gamemode');
        AdminGroups::addAlias($cmd, 's');

        $cmd = AdminGroups::addAdminCommand('WarmUpDuration', $this, 'setAllWarmUpDuration', Permission::GAME_SETTINGS);
        $cmd->setHelp('Set the warmup duration at the begining of the maps for all gamemodes')->addchecker(1, Integer::getInstance());
        $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, 'wu');
        AdminGroups::addAlias($cmd, 'warmup');
        AdminGroups::addAlias($cmd, 'setWarmUpDuration');

        $cmd = AdminGroups::addAdminCommand('disableRespawn', $this, 'setDisableRespawn', Permission::GAME_SETTINGS);
        $cmd->setHelp('Will disable the respawn capabilities of the players');
        $cmd->addLineHelpMore('$w//disableRespawn true$z will force the players to restart the map when they respaw');
        $cmd->addLineHelpMore('$w//disableRespawn false$z player that respaw will return back to the last checkpoint');
        $cmd->addLineHelpMore("\n" . 'A player respaws when he clicks on backspace on his keyboard')->setMinParam(1);
        AdminGroups::addAlias($cmd, 'setDisableRespawn');

        $cmd = AdminGroups::addAdminCommand('endround', $this, 'forceEndRound', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends a round. Only work in round mode');
        AdminGroups::addAlias($cmd, 'end'); // fast
        AdminGroups::addAlias($cmd, 'er'); // xaseco
		
		$cmd = AdminGroups::addAdminCommand('endwu', $this, 'forceEndWu', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends the WarmUp. Only work in round mode');
        AdminGroups::addAlias($cmd, 'ewu');
		
		$cmd = AdminGroups::addAdminCommand('endwuround', $this, 'forceEndWuR', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends the round of the WarmUp. Only work in round mode');
        AdminGroups::addAlias($cmd, 'ewur');

        $cmd = AdminGroups::addAdminCommand('forcepoints', $this, 'forcePointsRounds', Permission::GAME_SETTINGS);
        $cmd->setHelp('Force the current scores of one player');
        AdminGroups::addAlias($cmd, 'fpts');

        $cmd = AdminGroups::addAdminCommand('pause', $this, 'setPause', Permission::GAME_SETTINGS);
        $cmd->setHelp('Set the game to pause');
        AdminGroups::addAlias($cmd, 'p');

        $cmd = AdminGroups::addAdminCommand('resume', $this, 'setResume', Permission::GAME_SETTINGS);
        $cmd->setHelp('Continue the game after pause');
        AdminGroups::addAlias($cmd, 're');

        $cmd = AdminGroups::addAdminCommand('extend', $this, 'extendTimeOrPoints', Permission::GAME_SETTINGS);
        $cmd->setHelp('Extend current timelimit or pointslimit');
        AdminGroups::addAlias($cmd, 'ext');

        $cmd = AdminGroups::addAdminCommand('shuffle', $this, 'shuffleMaps', Permission::GAME_SETTINGS);
        $cmd->setHelp('Shuffles the mapslist');
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('forceclublinks', $this, 'forceClubLinks', Permission::GAME_SETTINGS);
        $cmd->setHelp('Force the clublinks to be displayed');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, 'clublinks');
        AdminGroups::addAlias($cmd, 'clublink');

        $this->enableDatabase();
        $this->enableTickerEvent();
        self::$showActions['ignore'] = ActionHandler::getInstance() ->createAction(array($this, 'showIgnoreList'));
        self::$showActions['ban'] = ActionHandler::getInstance() ->createAction(array($this, 'showBanList'));
        self::$showActions['black'] = ActionHandler::getInstance() ->createAction(array($this, 'showBlackList'));
        self::$showActions['guest'] = ActionHandler::getInstance() ->createAction(array($this, 'showGuestList'));

        self::$showActions['guestPlayer'] = ActionHandler::getInstance() ->createAction(array($this, 'addGuestList'));
        self::$showActions['ignorePlayer'] = ActionHandler::getInstance() ->createAction(array($this, 'addIgnore'));
        self::$showActions['banPlayer'] = ActionHandler::getInstance() ->createAction(array($this, 'addBan'));
        self::$showActions['blackPlayer'] = ActionHandler::getInstance() ->createAction(array($this, 'addBlack'));
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        TeamSetup::$mainPlugin = $this;
        ClubLinksSetup::$mainPlugin = $this;

        $this->dataAccess = \ManiaLivePlugins\eXpansion\Core\DataAccess::getInstance();

        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

        if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
            if (isset($var["team1Link"]) || isset($var["team2Link"])) {
                if (isset($var["team1Link"]) && isset($var["team2Link"])) {
                    $this->getClubLinks(null, array("team1Clublink" => $var["team1Link"], "team2Clublink" => $var["team2Link"]));
                } elseif (isset($var["team1Link"])) {
                    $this->getClubLinks(null, array("team1Clublink" => $var["team1Link"]));
                } elseif (isset($var["team2Link"])) {
                    $this->getClubLinks(null, array("team2Clublink" => $var["team2Link"]));
                }
            } else {
                $this->connection->setTeamInfo($var["team1Name"], floatval($var["team1ColorHSL"]), '', $var["team2Name"], floatval($var["team2ColorHSL"]), '');
            }
        }
    }

    public function onTick()
    {
        if (time() % 30 == 0) {
            foreach ($this->durations as $duration) {
                if ($duration->stamp < time()) {
                    switch ($duration->action) {
                        case "ban":
                            unset($this->durations[$duration->login]);
                            if ($this->checkBanList($duration->login)) {
                                $this->connection->unBan($duration->login);
                            }
                            break;
                        case "black":
                            unset($this->durations[$duration->login]);
                            if ($this->checkBlackList($duration->login)) {
                                $this->connection->unBlackList($duration->login);
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param $login
     * @return bool
     */
    public function checkBanList($login)
    {
        foreach ($this->connection->getBanList(-1, 0) as $player) {
            if ($player->login == $login) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $login
     * @return bool
     */
    public function checkBlackList($login)
    {
        foreach ($this->connection->getBlackList(-1, 0) as $player) {
            if ($player->login == $login) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set ban or backlist duration
     *
     * @param string $login
     * @param string $action
     * @param string $duration
     */
    public function addActionDuration($login, $action, $duration)
    {
        if ($duration != "permanent") {
            $this->durations[$login] = new ActionDuration($login, $action, $duration);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function fastTa($fromLogin, $params)
    {

        try {
            $command = array_shift($params);

            switch ($command ? strtolower($command): "") {
                case "time":
                case "limit":
                case "timelimit":
                    $this->setTAlimit($fromLogin, $params);
                    break;
                case "ext":
                case "extend":
                    $this->extendTimeOrPoints($fromLogin, $params);
                    break;
                case "wud":
                case "wu":
                case "warmupduration":
                    $this->setAllWarmUpDuration($fromLogin, $params);
                    break;
                case "wunb":
                case "warmupnumber":
                    $this->setNbWarmUp($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: limit, extend, wu, wunb");
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function fastLaps($fromLogin, $params)
    {
        try {
            $command = array_shift($params);

            switch ($command ? strtolower($command): "") {
                case "laps":
                case "nb":
                case "nblaps":
                    $this->setNbLaps($fromLogin, $params);
                    break;
                case "time":
                case "limit":
                case "timelimit":
                    $this->setLapsTimeLimit($fromLogin, $params);
                    break;
                case "wud":
                case "wu":
                case "warmupduration":
                    $this->setAllWarmUpDuration($fromLogin, $params);
                    break;
                case "wunb":
                case "warmupnumber":
                    $this->setNbWarmUp($fromLogin, $params);
                    break;
                case "fto":
                case "ftimeout":
                case "finishtimeout":
                    $this->setFinishTimeout($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: laps, limit, wu, wunb, fto");
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function fastRounds($fromLogin, $params)
    {
        try {
            $command = array_shift($params);

            switch ($command ? strtolower($command): "") {
                case "limit":
                case "pointslimit":
                    $this->setRoundPointsLimit($fromLogin, $params);
                    break;
                case "newrules":
                    $this->setUseNewRulesRound($fromLogin, $params);
                    break;
                case "wud":
                case "wu":
                case "warmupduration":
                    $this->setAllWarmUpDuration($fromLogin, $params);
                    break;
                case "wunb":
                case "warmupnumber":
                    $this->setNbWarmUp($fromLogin, $params);
                    break;
                case "dtd":
                case "displaytimediff":
                    $this->setDisplayTimeDiff($fromLogin, $params);
                    break;
                case "fto":
                case "ftimeout":
                case "finishtimeout":
                    $this->setFinishTimeout($fromLogin, $params);
                    break;
                case "laps":
                case "forcedlaps":
                    $this->setNbLaps($fromLogin, $params);
                    break;
                case "skip":
                    $this->skipScoreReset($fromLogin, $params);
                    break;
                case "res":
                    $this->restartScoreReset($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: pointslimit, newrules, wu, wunb, dtd, fto, laps, skip, res");
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function fastCup($fromLogin, $params)
    {
        try {
            $command = array_shift($params);

            switch ($command ? strtolower($command): "") {
                case "limit":
                case "pointslimit":
                    $this->setCupPointsLimit($fromLogin, $params);
                    break;
                case "rpm":
                case "rpc":
                case "rounds":
                case "roundspermap":
                    $this->setCupRoundsPerMap($fromLogin, $params);
                    break;
                case "nbwinners":
                case "nbwin":
                case "nbw":
                case "nb":
                    $this->setCupNbWinners($fromLogin, $params);
                    break;
                case "wud":
                case "wu":
                case "warmupduration":
                    $this->setAllWarmUpDuration($fromLogin, $params);
                    break;
                case "wunb":
                case "warmupnumber":
                    $this->setNbWarmUp($fromLogin, $params);
                    break;
                case "dtd":
                case "displaytimediff":
                    $this->setDisplayTimeDiff($fromLogin, $params);
                    break;
                case "fto":
                case "ftimeout":
                case "finishtimeout":
                    $this->setFinishTimeout($fromLogin, $params);
                    break;
                case "skip":
                    $this->skipScoreReset($fromLogin, $params);
                    break;
                case "res":
                    $this->restartScoreReset($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: limit, rounds, nbwin, wu, wunb, dtd, fto, skip, res");
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function fastTeam($fromLogin, $params)
    {
        try {
            $command = array_shift($params);

            switch ($command ? strtolower($command): "") {
                case "limit":
                case "pointslimit":
                    $this->setTeamPointsLimit($fromLogin, $params);
                    break;
                case "max":
                case "maxpoint":
                    $this->setMaxPointsTeam($fromLogin, $params);
                    break;
                case "newrules":
                    $this->setUseNewRulesTeam($fromLogin, $params);
                    break;
                case "wud":
                case "wu":
                case "warmupduration":
                    $this->setAllWarmUpDuration($fromLogin, $params);
                    break;
                case "wunb":
                case "warmupnumber":
                    $this->setNbWarmUp($fromLogin, $params);
                    break;
                case "dtd":
                case "displaytimediff":
                    $this->setDisplayTimeDiff($fromLogin, $params);
                    break;
                case "fto":
                case "ftimeout":
                case "finishtimeout":
                    $this->setFinishTimeout($fromLogin, $params);
                    break;
                case "blue":
                    $this->setTeamBlue($fromLogin, $params);
                    break;
                case "red":
                    $this->setTeamRed($fromLogin, $params);
                    break;
                case "gap":
                    $this->setTeamGap($fromLogin, $params);
                    break;
                case "balance":
                    $this->setTeamBalance($fromLogin, $params);
                    break;
                case "skip":
                    $this->skipScoreReset($fromLogin, $params);
                    break;
                case "res":
                    $this->restartScoreReset($fromLogin, $params);
                    break;
                case "display":
                    $this->setTeamDisplay($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: limit, maxpoint, newrules, wu, wunb, dtd, fto, blue, red, gap, balance, skip, res, display");
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

        }
    }

    public function onBeginMatch()
    {
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

        if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
            if (isset($var["team1Link"]) || isset($var["team2Link"])) {
                if (isset($var["team1Link"]) && isset($var["team2Link"])) {
                    $this->getClubLinks(null, array("team1Clublink" => $var["team1Link"], "team2Clublink" => $var["team2Link"]));
                } elseif (isset($var["team1Link"])) {
                    $this->getClubLinks(null, array("team1Clublink" => $var["team1Link"]));
                } elseif (isset($var["team2Link"])) {
                    $this->getClubLinks(null, array("team2Clublink" => $var["team2Link"]));
                }
            } else {
                $this->connection->setTeamInfo($var["team1Name"], floatval($var["team1ColorHSL"]), '', $var["team2Name"], floatval($var["team2ColorHSL"]), '');
            }
        }
    }

    /**
     * @param $fromLogin
     * @param null $params
     */
    public function invokeExpSettings($fromLogin, $params = null)
    {
        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', "showExpSettings", $fromLogin);
    }

    /**
     * @param $fromLogin
     * @param null $params
     */
    public function invokeNetStats($fromLogin, $params = null)
    {
        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', "showNetStats", $fromLogin);
    }

    public function setTeamDisplay($fromLogin, $params = null)
    {
        $window = TeamSetup::Create($fromLogin);
        $window->setSize(38, 60);
        $window->setTitle("Team names and colors");
        $window->show();
    }

    public function setTeamDisplayAfterWindow($fromLogin, $params)
    {
        $r1 = hexdec($params["team1Color"][0].$params["team1Color"][0]);
		$g1 = hexdec($params["team1Color"][1].$params["team1Color"][1]);
		$b1 = hexdec($params["team1Color"][2].$params["team1Color"][2]);

        $r2 = hexdec($params["team2Color"][0].$params["team2Color"][0]);
		$g2 = hexdec($params["team2Color"][1].$params["team2Color"][1]);
		$b2 = hexdec($params["team2Color"][2].$params["team2Color"][2]);

        $params["team1ColorHSL"] = (ColorConversion::RGBToHUE($r1, $g1, $b1)/360);
        $params["team2ColorHSL"] = (ColorConversion::RGBToHUE($r2, $g2, $b2)/360);

        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams');
        $var->setRawValue($params);
        \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

        $this->connection->setTeamInfo($params["team1Name"], $params["team1ColorHSL"], '', $params["team2Name"], $params["team2ColorHSL"], '');
        $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s$z$s#admin_action# Sets new teams name and color.', null, array($this->storage->getPlayerObject($fromLogin)->nickName));
    }

    public function forceClubLinks($fromLogin, $params)
    {
        $window = ClubLinksSetup::Create($fromLogin);
        $window->setSize(38, 60);
        $window->setTitle("Team clublinks");
        $window->show();
    }

    public function getClubLinks($login, $data)
    {
        $this->clubLinksGet = 0;
        $this->clubLinksExpected = 0;
        if (isset($data["team1Clublink"]) && $data["team1Clublink"] != "") {
            $this->clubLinksExpected++;
            $options = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 10);
            $this->dataAccess->httpCurl($data["team1Clublink"], array($this, "clubLinkCallback"), array("Login" => $login, "clubLink" => "team1", "link" => $data["team1Clublink"]), $options);
        }

        if (isset($data["team2Clublink"]) && $data["team2Clublink"] != "") {
            $this->clubLinksExpected++;
            $options = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 10);
            $this->dataAccess->httpCurl($data["team2Clublink"], array($this, "clubLinkCallback"), array("Login" => $login, "clubLink" => "team2", "link" => $data["team2Clublink"]), $options);
        }

        if ($this->clubLinksExpected == 0) {
            $this->forceClubLinksAfterWindow($login, array());
        }
    }

    public function clubLinkCallback($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();
        $additionalData = $job->__additionalData;

        $login = $additionalData['Login'];
        $teamId = $additionalData['clubLink'];
        $link = $additionalData['link'];
        $this->clubLinksGet++;

        if (!$data || $code !== 200) {
            if ($login != null) {
                $this->eXpChatSendServerMessage("#admin_error#Error: Clublink not found or not accessible for team #variable#%s", $login, array($teamId));
            }
            $this->clubLinksGet--;
            $this->clubLinksExpected--;
            return;
        }

        try {
            $clubLink = simplexml_load_string($data);
        } catch (\Exception $e) {
            if ($login != null) {
                $this->eXpChatSendServerMessage("#admin_error#Error: Clublink is not a valid XML for team #variable#%s", $login, array($teamId));
            }
            $this->clubLinksGet--;
            $this->clubLinksExpected--;
            return;
        }
        
        if (!isset($clubLink->name) || !isset($clubLink->color) || !isset($clubLink->color[0]->attributes()->primary)) {
            if ($login != null) {
                $this->eXpChatSendServerMessage("#admin_error#Error: Clublink doesn't contain required infos for team #variable#%s", $login, array($teamId));
            }
            $this->clubLinksGet--;
            $this->clubLinksExpected--;
            return;
        }
        $name = (String)$clubLink->name[0];
        $color = (String)$clubLink->color[0]->attributes()->primary[0];

        if (strlen($color) != 3) {
            if ($login != null) {
                $this->eXpChatSendServerMessage("#admin_error#Error: Clublink color for team #variable#%s #admin_error#is not valid", $login, array($teamId));
            }
            $this->clubLinksGet--;
            $this->clubLinksExpected--;
            return;
        }

        $r = hexdec($color[0].$color[0]);
		$g = hexdec($color[1].$color[1]);
		$b = hexdec($color[2].$color[2]);
        $colorHSL = (ColorConversion::RGBToHUE($r, $g, $b)/360);
        $this->clubLinks[$teamId] = array("Name" => $name, "Color" => $color, "ColorHSL" => $colorHSL, "Link" => $link);
        
        if ($this->clubLinksGet == $this->clubLinksExpected) {
            $this->forceClubLinksAfterWindow($login, $this->clubLinks);
        }
    }

    public function forceClubLinksAfterWindow($fromLogin, $params)
    {
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams');

        if (isset($params["team1"]["Link"]) && isset($params["team2"]["Link"])) {
            $this->connection->setForcedClubLinks($params["team1"]["Link"], $params["team2"]["Link"]);

            $stored = array("team1Name" => $params["team1"]["Name"], "team2Name" => $params["team2"]["Name"], "team1Color" => $params["team1"]["Color"], "team2Color" => $params["team2"]["Color"], "team1ColorHSL" => $params["team1"]["ColorHSL"], "team2ColorHSL" => $params["team2"]["ColorHSL"], "team1Link" => $params["team1"]["Link"], "team2Link" => $params["team2"]["Link"]);
            $var->setRawValue($stored);
            \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

            if ($fromLogin != null) {
                $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s$z$s#admin_action# Sets new forced clublinks.', null, array($this->storage->getPlayerObject($fromLogin)->nickName));
            }
        } else if (isset($params["team1"]["Link"])) {
            $this->connection->setForcedClubLinks($params["team1"]["Link"], "");
            if ($fromLogin != null) {

                $stored = array("team1Name" => $params["team1"]["Name"], "team1Color" => $params["team1"]["Color"], "team1ColorHSL" => $params["team1"]["ColorHSL"], "team1Link" => $params["team1"]["Link"]);
                $var->setRawValue($stored);
                \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

                $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s$z$s#admin_action# Sets new forced clublinks.', null, array($this->storage->getPlayerObject($fromLogin)->nickName));
            }
        } else if (isset($params["team2"]["Link"])) {
            $this->connection->setForcedClubLinks("", $params["team2"]["Link"]);
            if ($fromLogin != null) {
                
                $stored = array("team2Name" => $params["team2"]["Name"], "team2Color" => $params["team2"]["Color"], "team2ColorHSL" => $params["team2"]["ColorHSL"], "team2Link" => $params["team2"]["Link"]);
                $var->setRawValue($stored);
                \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

                $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s$z$s#admin_action# Sets new forced clublinks.', null, array($this->storage->getPlayerObject($fromLogin)->nickName));
            }
        } else {
            if ($fromLogin != null) {
                $this->connection->setForcedClubLinks("", "");

                $stored = array();
                $var->setRawValue($stored);
                \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

                $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s$z$s#admin_action# Removes forced clublinks.', null, array($this->storage->getPlayerObject($fromLogin)->nickName));
            }
        }

        $this->clubLinksGet = 0;
        $this->clubLinksExpected = 0;
    }

    public function setModeScriptSettings($fromLogin, $params)
    {
        $window = ScriptSettings::Create($fromLogin);
        $window->setTitle(__('Script Settings', $fromLogin));
        $window->centerOnScreen();
        $window->setSize(160, 100);
        $window->show();
    }

    /**
     * @param $login
     * @param null $params
     */
    public function shuffleMaps($login, $params = null)
    {
        $mapsArray = array();
        foreach ($this->storage->maps as $map) {
            $mapsArray[] = $map->fileName;
        }
        try {
            $this->connection->removeMapList($mapsArray);
            shuffle($mapsArray);
            $this->connection->addMapList($mapsArray);
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s $z$s#admin_action#shuffles the maps list!');
            $nick = $this->storage->getPlayerObject($login)->nickName;

            $this->eXpChatSendServerMessage($msg, null, array($nick));
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage("#admin_error#there was error while shuffling the maps", $login);
            $this->console("Error while shuffling maps: " . $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTeamBalance($fromLogin, $params)
    {
        try {
            $adminNick = $this->storage->getPlayerObject($fromLogin)->nickName;
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s $z$s#admin_action# AutoBalances the Teams!', null, array($adminNick));
            $this->connection->autoTeamBalance();
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage("#admin_error#error while AutoTeamBalance: " . $e->getMessage(), $fromLogin);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function pay($fromLogin, $params)
    {
        try {
            $this->connection->pay($params[0], intval($params[1]), $params[0] . " Planets payed out from server " . $this->storage->server->name);
            $this->eXpChatSendServerMessage('#admin_action#Server just sent#variable# %s #admin_action#Planets to#variable# %s #admin_action#!', $fromLogin, array($params[1], $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param null $params
     */
    public function getServerPlanets($fromLogin, $params = null)
    {
        try {
            $this->eXpChatSendServerMessage('#admin_action#Server has #variable# %s #admin_action#Planets.', $fromLogin, array($this->connection->getServerPlanets()));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTeamBlue($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a player login.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        $player = $this->storage->getPlayerObject($params[0]);
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();
        try {
            $this->connection->forcePlayerTeam($params[0], 0);
            $team = ((isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) ? '$'.$var["team1Color"] . $var["team1Name"] : '$00fBlue');
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team '. $team . '.', null, array($admin->nickName, $player->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTeamRed($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a player login.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        $player = $this->storage->getPlayerObject($params[0]);
        $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();
        try {
            $this->connection->forcePlayerTeam($params[0], 1);
            $team = ((isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) ? '$'.$var["team2Color"] . $var["team2Name"] : '$f00Red');
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sends player#variable# %s #admin_action#to team '. $team . '.', null, array($admin->nickName, $player->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setCupNbWinners($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of winners.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_NbOfWinners" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets cup winners to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setCupRoundsPerMap($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of rounds.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_RoundsPerMap" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets use new rounds to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setCupPointsLimit($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of points.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets use new cup points limit to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setUseNewRulesTeam($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a boolean.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_UseAlternateRules" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets use new team rules to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setMaxPointsTeam($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of points.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_MaxPointsPerRound" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets Team max points to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTeamPointsLimit($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of points.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets Team points limit to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTeamGap($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of points.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsGap" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets Team points gap to#variable# %s #admin_action#.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setFinishTimeout($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a duration in format: MM:SS');
            return;
        }
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_FinishTimeout" => intval($newLimit)]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new finish timeout to#variable# %s #admin_action#minutes.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setNbLaps($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of laps.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_ForceLapsNb" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new number of laps to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setLapsTimeLimit($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a duration in format: MM:SS');
            return;
        }
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($newLimit)]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new laps timelimit to#variable# %s #admin_action#minutes.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setRoundPointsLimit($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of points.');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets rounds points limits to#variable# %s.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function forceEndRound($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.ForceEndRound', array());
            $this->connection->triggerModeScriptEventArray('Shootmania.ForceEndRound', array());
            $this->connection->triggerModeScriptEventArray('Trackmania.Chase.ForceStopRound', array());
            $this->connection->triggerModeScriptEvent('Rounds_ForceEndRound');

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the round to end.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }
	
	public function forceEndWu($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.WarmUp.ForceStop', array());
            $this->connection->triggerModeScriptEventArray('Shootmania.WarmUp.ForceStop', array());

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the WarmUp to end.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }
	
	public function forceEndWuR($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.WarmUp.ForceStopRound', array());
            $this->connection->triggerModeScriptEventArray('Shootmania.WarmUp.ForceStopRound', array());

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the WarmUp Round to end.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    public function forcePointsRounds($fromLogin, $params)
    {
        if (Core::$useTeams) {

            $admin = $this->storage->getPlayerObject($fromLogin);
            if (!isset($params[0]) || ($params[0] != "blue" && $params[0] != "red")) {
                $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the id of the team (red or blue) !'), $fromLogin);
                return;
            }
            if (!isset($params[1])) {
                $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the points of the team'), $fromLogin);
                return;
            }

            if ($params[0] == "blue"){
                $teampts = 0;
            }
            if ($params[0] == "red"){
                $teampts = 1;
            }

            try {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$teampts", "", "$params[1]", "$params[1]"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetTeamPoints', array("$teampts", "", "$params[1]", "$params[1]"));

                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the points of the team $fff%s #admin_action#to $fff%s#admin_action#.', null, array($admin->nickName, $params[0], $params[1]));
            } catch (Exception $e) {
                $this->sendErrorChat($fromLogin, $e->getMessage());
            }

        } else {

            $admin = $this->storage->getPlayerObject($fromLogin);
            $player = $this->storage->getPlayerObject($params[0]);
            if (!isset($params[0])) {
                $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the login of the player'), $fromLogin);
                return;
            }
            if (!isset($params[1])) {
                $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the points of the player'), $fromLogin);
                return;
            }
            try {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$params[0]", "", "", "$params[1]"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetPlayerPoints', array("$params[0]", "", "", "$params[1]"));

                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the Roundpoints of $fff%s #admin_action#to $fff%s#admin_action#.', null, array($admin->nickName, $player->nickName, $params[1]));
            } catch (Exception $e) {
                $this->sendErrorChat($fromLogin, $e->getMessage());
            }

        }
    }

    public function setPause($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Maniaplanet.Pause.SetActive', array("true"));
            $this->connection->triggerModeScriptEventArray('Maniaplanet.Pause.GetStatus', array());

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the game to pause.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    public function setResume($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Maniaplanet.Pause.SetActive', array("false"));
            $this->connection->triggerModeScriptEventArray('Maniaplanet.Pause.GetStatus', array());

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#forces the game to play.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    public function extendTimeOrPoints($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);

        if (Core::$isTimeExtendable) {
            if (!isset($params[0]) || !$params[0]) {

                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', null);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#extended the time limit.', null, array($admin->nickName));
                return;

            } else {

                if (!is_numeric($params[0])) {
                    $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $fromLogin);
                    return;
                }

                $timeToAdd = intval($params[0]*60);

                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', $timeToAdd);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#extended the time limit with #variable#%s #admin_action#minutes.', null, array($admin->nickName, $params[0]));
                return;
            }
        }

        if (Core::$isPointExtendable) {
            if (!isset($params[0]) || !$params[0]) {

                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', null);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#extended the points limit.', null, array($admin->nickName));
                return;

            } else {

                if (!is_numeric($params[0])) {
                    $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $fromLogin);
                    return;
                }

                $pointsToAdd = intval($params[0]);

                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', $pointsToAdd);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#extended the points limit with #variable#%s #admin_action#points.', null, array($admin->nickName, $pointsToAdd));
                return;
            }
        }

        if (!Core::$isTimeExtendable || !Core::$isPointExtendable) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#Incompatible mode'), $fromLogin);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setNbWarmUp($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a number of warmup');
            return;
        }
        try {
            $this->connection->setModeScriptSettings(["S_WarmUpNb" => intval($params[0])]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action#sets all game modes warmup number to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setDisplayTimeDiff($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a boolean');
            return;
        }
        try {
            $this->connection->setModeScriptSettings(["S_DisplayTimeDiff" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action#sets the display time diff to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setUseNewRulesRound($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a boolean');
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_UseAlternateRules" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new round rules to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     * @param bool $showBlacklistDialog
     */
    public function blacklist($fromLogin, $params, $showBlacklistDialog = false)
    {
        $target = array_shift($params);
        $reason = implode(" ", $params);
        $player = $this->storage->getPlayerObject($target);
        if (is_object($player)) {
            $nickname = $player->nickName;
        } else {
            $nickname = $target;
        }

        if (empty($reason)) {
            /** @var ParameterDialog $dialog */
            $dialog = ParameterDialog::Create($fromLogin);
            $dialog->setTitle(__("blacklist", $fromLogin), Formatting::stripStyles($nickname));
            $dialog->setData("black", $target);
            $dialog->show($fromLogin);

            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->expStorage->loadBlackList();
            try {
                $this->connection->banAndBlackList($target, $reason, true);
            } catch (\Exception $ex) {
                $this->connection->blackList($target);
            }

            $this->expStorage->saveBlackList();

            $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action#blacklists the player #variable# %s reason: #admin_error#%s', null, array($admin->nickName, $nickname, $reason));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }

        if ($showBlacklistDialog) {
            $this->showBlackList($fromLogin);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function cleanBlacklist($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->cleanBlackList();
            $this->expStorage->saveBlackList();
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#cleans the blacklist.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function cleanBanlist($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->cleanBanList();
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#cleans the banlist.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function cleanIgnorelist($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->cleanIgnoreList();
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#cleans the ignorelist.', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function loadScript($fromLogin, $params)
    {
        $dataDir = Helper::getPaths()->getGameDataPath();
        $mode = "TrackMania";
        if ($this->expStorage->simpleEnviTitle == "SM") {
            $mode = "ShootMania";
        }

        $scriptName = $params[0];

        // Append .Script.txt if left out
        if (strtolower(substr($scriptName, -11)) !== '.script.txt') {
            $scriptName .= '.Script.txt';
        }

        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->connection->setScriptName($scriptName);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets game mode to#variable# %s', null, array($admin->nickName, $scriptName));
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage("#admin_error#" . $e->getMessage(), $fromLogin);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function unBlacklist($fromLogin, $params)
    {

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->expStorage->loadBlackList();
            $this->connection->unBlackList($params[0]);
            $this->expStorage->saveBlackList();

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unblacklists the player %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function unBlackListClick($fromLogin, $params)
    {
        $this->unBlacklist($fromLogin, $params);
        $this->showBlackList($fromLogin);
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function ban($fromLogin, $params)
    {
        $target = array_shift($params);
        $reason = implode(" ", $params);
        $player = $this->storage->getPlayerObject($target);
        if (is_object($player)) {
            $nickname = $player->nickName;
        } else {
            $nickname = $target;
        }
        if (empty($reason)) {
            /** @var ParameterDialog $dialog */
            $dialog = ParameterDialog::Create($fromLogin);
            $dialog->setTitle(__("ban", $fromLogin), Formatting::stripStyles($nickname));
            $dialog->setData("ban", $target);
            $dialog->show($fromLogin);
            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->ban($target, $reason);
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action# bans the player#variable# %s reason: #admin_error# %s', null, array($admin->nickName, $nickname, $reason));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function ignore($fromLogin, $params)
    {
        $player = $this->storage->getPlayerObject($params[0]);
        if (is_object($player)) {
            $nickname = $player->nickName;
        } else {
            $nickname = $params[0];
        }

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->ignore($params[0]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action# ignores the player#variable# %s', null, array($admin->nickName, $nickname));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function unignoreClick($fromLogin, $params)
    {
        $this->unignore($fromLogin, $params);
        $this->showIgnoreList($fromLogin);
    }

    /**
     * @param $fromlogin
     * @param $params
     */
    public function unbanClick($fromlogin, $params)
    {
        $this->unban($fromlogin, $params);
        $this->showBanList($fromlogin);
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function unban($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        $player = $this->storage->getPlayerObject($params[0]);

        try {
            if (is_object($player)) {
                $nickname = $player->nickName;
            } else {
                $nickname = $params[0];
            }

            $this->connection->unBan($params[0]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unbans the player %s', null, array($admin->nickName, $nickname));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function unignore($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);

        try {
            $this->connection->unIgnore($params[0]);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unignores the player %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function kick($fromLogin, $params)
    {
        $target = array_shift($params);
        $reason = implode(" ", $params);
        $reason = trim($reason);
        $player = $this->storage->getPlayerObject($target);
        if ($player == null) {
            $this->eXpChatSendServerMessage("#admin_error#Player #variable# %s doesn' exist.", $fromLogin, array($target));
            return;
        }
        if (empty($reason)) {
            /** @var ParameterDialog $dialog */
            $dialog = ParameterDialog::Create($fromLogin);
            $dialog->setTitle(__("kick", $fromLogin), Formatting::stripStyles($player->nickName));
            $dialog->setData("kick", $target);
            $dialog->show($fromLogin);

            return;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->kick($player, $reason);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %1$s #admin_action#kicks the player#variable# %2$s (%3$s) #variable#Reason: #admin_error#%4$s', null, array($admin->nickName, $player->nickName, $target, $reason));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function guest($fromLogin, $params)
    {
        $target = array_shift($params);

        $player = $this->storage->getPlayerObject($target);
        $nick = $target;
        if ($player != null) {
            $nick = $player->nickName;
        }
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->expStorage->loadGuestList();
            $this->connection->addGuest($target);
            $this->expStorage->saveGuestList();

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#add as guest the player#variable# %s', null, array($admin->nickName, $nick));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function guestRemove($fromLogin, $params)
    {
        $target = array_shift($params);
        $player = $this->storage->getPlayerObject($target);
        if (is_object($player)) {
            $nickname = $player->nickName;
        } else {
            $nickname = $target;
        }

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->expStorage->loadGuestList();
            $this->connection->removeGuest($target);
            $this->expStorage->saveGuestList();

            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#removed guest status of the player#variable# %s', null, array($admin->nickName, $nickname));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function removeGuestClick($fromLogin, $params)
    {
        $this->guestRemove($fromLogin, $params);
        $this->showGuestList($fromLogin);
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function forceSpec($fromLogin, $params)
    {
        $player = $this->storage->getPlayerObject($params[0]);
        if ($player == null) {
            $this->eXpChatSendServerMessage("#admin_action#Player #variable# %s doesn' exist.", $fromLogin, array($params[0]));
            return;
        }
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->connection->forceSpectator($player, 1);
            $this->connection->forceSpectator($player, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the player#variable# %s #admin_action#to spectate.', null, array($admin->nickName, $player->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function forcePlay($fromLogin, $params)
    {
        $player = $this->storage->getPlayerObject($params[0]);
        if ($player == null) {
            $this->eXpChatSendServerMessage("#admin_action#Player #variable# %s doesn't exist.", $fromLogin, array($params[0]));
            return;
        }
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->connection->forceSpectator($player, 2);
            $this->connection->forceSpectator($player, 0);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#Forces the spectator#variable# %s #admin_action#to play.', null, array($admin->nickName, $player->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }


    /**
     * @param $login
     * @param $message
     */
    public function sendErrorChat($login, $message)
    {
        $this->eXpChatSendServerMessage('#admin_error#' . $message, $login);
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerName($fromLogin, $params)
    {
        $name = implode(" ", $params);
        try {
            $this->connection->setServerName($name);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action# sets new server name:#variable# %s', null, array($admin->nickName, $name));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerComment($fromLogin, $params)
    {
        $comment = implode(" ", $params);
        try {
            $this->connection->setServerComment($comment);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new server comment:#variable# %s', null, array($admin->nickName, $comment));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerMaxPlayers($fromLogin, $params)
    {
        $params[0] = (int)$params[0];
        try {
            $this->connection->setMaxPlayers($params[0]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets server maximum players to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerMaxSpectators($fromLogin, $params)
    {
        $params[0] = (int)$params[0];
        try {
            $this->connection->setMaxSpectators($params[0]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets server maximum spectators to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerPassword($fromLogin, $params)
    {
        try {
            if (isset($params[0]) && $params[0]) {
                $this->connection->setServerPassword($params[0]);
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action# sets new server password.', null, array($admin->nickName));
                $this->eXpChatSendServerMessage('#admin_action#New server password:#variable# %s', null,array($params[0]), $fromLogin);
            } else {
                $this->connection->setServerPassword("");
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action# unsets server password.', null, array($admin->nickName));
            }
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setSpecPassword($fromLogin, $params)
    {
        try {
            if (isset($params[0]) && $params[0]) {
                $this->connection->setServerPasswordForSpectator($params[0]);
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new spectator password.', null, array($admin->nickName));
                $this->eXpChatSendServerMessage('#admin_action#New spectator password:#variable# %s', null, array($params[0]), $fromLogin);
            } else {
                $this->connection->setServerPasswordForSpectator("");
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unsets spectator password.', null, array($admin->nickName));
            }
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setRefereePassword($fromLogin, $params)
    {
        try {
            if (isset($params[0]) && $params[0]) {
                $this->connection->setRefereePassword($params[0]);
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new referee password.', null, array($admin->nickName));
                $this->eXpChatSendServerMessage('#admin_action#New referee password:#variable# %s', null, array($params[0]), $fromLogin);
            } else {
                $this->connection->setRefereePassword("");
                $admin = $this->storage->getPlayerObject($fromLogin);
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#unsets referee password.', null, array($admin->nickName));
            }
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setserverchattime($fromLogin, $params)
    {
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        if ($newLimit < 0) {
            $newLimit = 0;
        }

        try {
            $this->connection->setModeScriptSettings(["S_ChatTime" => intval($newLimit)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable#%s $z#admin_action#sets new chat time limit of #variable# %s #admin_action#minutes.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTAlimit($fromLogin, $params)
    {
        if (!isset($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a duration in format: MM:SS');
            return;
        }
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        try {
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($newLimit)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#sets new time limit of #variable# %s #admin_action#minutes.', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setServerMapDownload($fromLogin, $params)
    {

        $bool = false;
        if ($params[0] == 'true' || $params[0] == 'false') {
            if ($params[0] == 'true') {
                $bool = true;
            }
            if ($params[0] == 'false') {
                $bool = false;
            }
        } else {
            $this->sendErrorChat($fromLogin, 'Invalid parameter. Correct parameter for the command is either true or false.');
            return;
        }

        try {
            $this->connection->allowMapDownload($bool);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#set allow download maps to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setHideServer($fromLogin, $params)
    {
        $validValues = array("1", "0", "2", "all", "visible", "both", "nations", "off", "hidden");
        $output = 0;
        if (in_array(strtolower($params[0]), $validValues, true)) {
            if ($params[0] == 'off' || $params[0] == 'visible') {
                $output = 0;
            }
            if ($params[0] == 'all' || $params[0] == 'both' || $params[0] == 'hidden') {
                $output = 1;
            }
            if ($params[0] == 'nations') {
                $output = 2;
            }
            if (is_numeric($params[0])) {
                $output = intval($params[0]);
            }
        } else {
            $this->sendErrorChat($fromLogin, 'Invalid parameter. Correct parameters for command are: 0,1,2,visible,hidden,nations.');
            return;
        }
        try {
            $this->connection->setHideServer($output);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#set Hide Server to#variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function stopDedicated($fromLogin, $params)
    {
        try {
            $this->connection->sendHideManialinkPage();
            $this->connection->stopServer();
            Application::getInstance()->kill();
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function stopManiaLive($fromLogin, $params)
    {
        $this->connection->chatSendServerMessage("[Notice] stopping eXpansion...");
        $this->connection->sendHideManialinkPage();
        $this->connection->chatEnableManualRouting(false);
        Application::getInstance()->kill();
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function restartManiaLive($fromLogin, $params)
    {
        Dispatcher::dispatch(new ExpansionEvent(ExpansionEvent::ON_RESTART_START));

        $this->eXpChatSendServerMessage("[Notice] restarting eXpansion...");
        $this->connection->sendHideManialinkPage();
        $this->connection->chatEnableManualRouting(false);

        Application::getInstance()->kill();

        $path = Path::getInstance();
        $dir = $path->getRoot(true) . "bootstrapper.php";
        $cmd = PHP_BINARY . " " . realpath($dir);

        //Getting the server arguments.
        $args = getopt("", array('help::', //Display Help
            'manialive_cfg::', 'rpcport::', //Set the XML RPC Port to use
            'address::', //Set the adresse of the server
            'password::', //Set the User Password
            'dedicated_cfg::',//Set the configuration file to use to define XMLRPC Port, SuperAdmin/Admin/User passwords
            'user::', //Set the user to use during the communication with the server
            'logsPrefix::', //Set the log prefix option
            'debug::' // Set up the debug option//Set a configuration file to load instead of config.ini
        ));
        $arg_string = " ";
        foreach ($args as $key => $value) {
            $arg_string .= "--$key";
            if (!empty($value)) {
                $arg_string .= "=$value";
            }
            $arg_string .= " ";
        }

        $cmd .= $arg_string;

        $this->console('Restarting manialive with command : ' . $cmd);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (class_exists("COM")) {
                $WshShell = new \COM("WScript.Shell");
                $WshShell->Run($cmd, 3, false);
            } else {
                exec($cmd);
            }
        } else {
            exec("cd " . $path->getRoot(true) . "; " . $cmd . " >> /tmp/manialive.log 2>&1 &");
        }
        $this->console("eXpansion will restart!!This instance is stopping now!!");
        Dispatcher::dispatch(new ExpansionEvent(ExpansionEvent::ON_RESTART_END));
        exit();
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function skipMap($fromLogin, $params)
    {
        try {
            \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_SKIP));
            $this->connection->nextMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#skips the challenge!', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function restartMap($fromLogin, $params)
    {
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#restarts the challenge!', null, array($admin->nickName));

            if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Maps\Maps')) {
                \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_RESTART));
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "replayMapInstant");
                return;
            }

            \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_RESTART));
            $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function skipScoreReset($fromLogin, $params)
    {
        try {
            \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_SKIP));
            $this->connection->nextMap(false);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#skips the challenge!', null, array($admin->nickName));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function restartScoreReset($fromLogin, $params)
    {
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#restarts the challenge!', null, array($admin->nickName));

            if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Maps\Maps')) {
                \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_RESTART));
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "replayScoreReset");
                return;
            }

            \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_RESTART));
            $this->connection->restartMap(false);
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setGameMode($fromLogin, $params)
    {
        $gamemode = null;

        $gamemode = $params[0];

        if (strtolower($gamemode) == "reload") {
            $scriptNameArr = $this->connection->getScriptName();
            $scriptName = $scriptNameArr['CurrentValue'];

            // Workaround for a 'bug' in setModeScriptText.
            if ($scriptName === '<in-development>') {
                $scriptName = $scriptNameArr['NextValue'];
            }

            $this->loadScript($fromLogin, array($scriptName));
            return;
        }

        if (strtolower($gamemode) == "ta") {
            $gamemode = "TimeAttack";
        }
        if (strtolower($gamemode) == "timeattack") {
            $gamemode = "TimeAttack";
        }
        if (strtolower($gamemode) == "round") {
            $gamemode = "Rounds";
        }

        $this->loadScript($fromLogin, [ucfirst($gamemode)]);
        return;
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setAllWarmUpDuration($fromLogin, $params)
    {
        if (!isset($params[0]) || !is_numeric($params[0])) {
            $this->sendErrorChat($fromLogin, 'Please provide a duration in format: MM:SS');
            return;
        }
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        try {
            $this->connection->setModeScriptSettings(["S_WarmUpDuration" => intval($newLimit)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin #variable# %s #admin_action#sets warmup duration to#variable# %s #admin_action#minutes', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     */
    public function cancelVote($fromLogin)
    {
        if ($this->isPluginLoaded("\\ManiaLivePlugins\\eXpansion\\Votes\\Votes")) {
            $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\Votes\\Votes", "cancelVote", $fromLogin);
            return;
        }

        $admin = $this->storage->getPlayerObject($fromLogin);
        $vote = $this->connection->getCurrentCallVote();
        if (!empty($vote->cmdName)) {
            try {
                $this->connection->cancelVote();
                $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#cancels the vote.', null, array($admin->nickName));
                return;
            } catch (Exception $e) {
                $this->eXpChatSendServerMessage('#admin_error#Error: Server said %1$s', $admin->login, array($e->getMessage()));
            }
        } else {
            $this->eXpChatSendServerMessage('#admin_error#Can\'t cancel a vote, no vote in progress!', $admin->login);
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setDisableRespawn($fromLogin, $params)
    {
        $bool = false;
        if ($params[0] == 'true' || $params[0] == 'false') {
            if ($params[0] == 'true') {
                $bool = false;
            } // reverse the order as the command is for disable;
            if ($params[0] == 'false') {
                $bool = true;
            } // ^^
        } else {
            $this->sendErrorChat($fromLogin,'Invalid parameter. Correct parameter for the command is either true or false.');
            return;
        }

        try {
            $this->connection->setDisableRespawn($bool);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage('#admin_action#Admin#variable# %s #admin_action#set allow respawn to #variable# %s', null, array($admin->nickName, $params[0]));
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /* Graphical Methods */

    /**
     * @param $login
     */
    public function showBanList($login)
    {
        GenericPlayerList::Erase($login);

        try {
            /** @var GenericPlayerList $window */
            $window = GenericPlayerList::Create($login);
            $window->setTitle('Banned Players on the server');
            $indexNumber = 0;
            $items = array();

            /**
             * @var PlayerBan
             */
            foreach ($this->connection->getBanList(-1, 0) as $player) {
                $items[] = new BannedPlayeritem($indexNumber, $player, $this, $login);
            }
            $window->setAction(self::$showActions['banPlayer']);
            $window->populateList($items);
            $window->setSize(90, 120);
            $window->centerOnScreen();
            $window->show();
        } catch (Exception $e) {
            $this->sendErrorChat($login, $e->getMessage());
        }
    }

    /**
     * @param $login
     * @param $entries
     */
    public function addBan($login, $entries)
    {
        $this->ban($login, array($entries['login']));
        $this->showBanList($login);
    }

    /**
     * @param $login
     * @param $entries
     */
    public function addBlack($login, $entries)
    {
        $this->blacklist($login, array($entries['login']), true);
    }

    /**
     * @param $login
     * @param $entries
     */
    public function addIgnore($login, $entries)
    {
        $this->ignore($login, array($entries['login']));
        $this->showIgnoreList($login);
    }

    /**
     * @param $login
     * @param $entries
     */
    public function addGuestList($login, $entries)
    {
        $this->guest($login, array($entries['login']));
        $this->showGuestList($login);
    }


    /**
     * @param $login
     */
    public function showBlackList($login)
    {
        GenericPlayerList::Erase($login);

        //	try {
        /** @var GenericPlayerList $window */
        $window = GenericPlayerList::Create($login);
        $window->setTitle(__('Blacklisted Players on the server', $login));
        $indexNumber = 0;
        $items = array();

        /**
         * @var Player
         */
        foreach ($this->connection->getBlackList(-1, 0) as $player) {
            $items[] = new BlacklistPlayeritem($indexNumber, $player, $this, $login);
        }
        $window->setAction(self::$showActions['blackPlayer']);
        $window->populateList($items);
        $window->setSize(90, 120);
        $window->centerOnScreen();
        $window->show();
    }

    /**
     * @param $login
     */
    public function showGuestList($login)
    {
        GenericPlayerList::Erase($login);

        try {
            /** @var GenericPlayerList $window */
            $window = GenericPlayerList::Create($login);
            $window->setTitle(__('Guest Players on the server'));
            $indexNumber = 0;
            $items = array();

            /**
             * @var Player
             */
            foreach ($this->connection->getGuestList(-1, 0) as $player) {
                $items[] = new GuestPlayeritem($indexNumber, $player, $this, $login);
            }

            $window->populateList($items);
            $window->setAction(self::$showActions['guestPlayer']);
            $window->setSize(90, 120);
            $window->centerOnScreen();
            $window->show();
        } catch (Exception $e) {
            $this->sendErrorChat($login, $e->getMessage());
        }
    }

    /**
     * @param $login
     */
    public function showIgnoreList($login)
    {
        GenericPlayerList::Erase($login);

        try {
            /** @var GenericPlayerList $window */
            $window = GenericPlayerList::Create($login);
            $window->setTitle(__('Ignored Players on the server'));
            $indexNumber = 0;
            $items = array();

            /**
             * @var Player
             */
            foreach ($this->connection->getIgnoreList(-1, 0) as $player) {
                $items[] = new IgnoredPlayeritem($indexNumber, $player, $this, $login);
            }
            $window->setAction(self::$showActions['ignorePlayer']);
            $window->populateList($items);
            $window->setSize(90, 120);
            $window->centerOnScreen();
            $window->show();
        } catch (Exception $e) {
            $this->sendErrorChat($login, $e->getMessage());
        }
    }

    /**
     *
     */
    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
        ParameterDialog::EraseAll();
        GenericPlayerList::EraseAll();
        self::$showActions = null;
    }
}
