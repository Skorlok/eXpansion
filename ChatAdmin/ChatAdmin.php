<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin;

use Exception;
use ManiaLib\Utils\Formatting;
use ManiaLib\Utils\Path;
use ManiaLive\Application\Application;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\PluginHandler\Dependency;
use ManiaLive\Utilities\Time;
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
use ManiaLivePlugins\eXpansion\ChatAdmin\Structures\ActionDuration;
use ManiaLivePlugins\eXpansion\Core\Config;
use ManiaLivePlugins\eXpansion\Core\Events\ExpansionEvent;
use ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\Helpers\TimeConversion;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\Player;
use Maniaplanet\DedicatedServer\Structures\PlayerBan;
use Phine\Exception\Exception as Exception2;

/**
 * Description of Admin
 *
 * @author oliverde8
 */
class ChatAdmin extends ExpPlugin
{
    /** @var integer $dynamicTime */
    private $dynamicTime = 0;

    /** @var integer $teamGap */
    private $teamGap = 0;

    /** @var ActionDuration[] $durations */
    private $durations = array();

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
        $this->setPublicMethod("forcePointsRounds");
        $this->setPublicMethod("forcePointsTeam");
        $this->setPublicMethod("shuffleMaps");
    }

    /**
     *
     */
    public function eXpOnLoad()
    {
        $adminGroup = AdminGroups::getInstance();

        $cmd = AdminGroups::addAdminCommand('game ta', $this, 'fastTa', Permission::GAME_SETTINGS);
        $cmd->setHelp('/ta limit; Sets timelimit for TimeAttack');
        $adminGroup->addShortAlias($cmd, 'ta');

        $cmd = AdminGroups::addAdminCommand('game laps', $this, 'fastLaps', Permission::GAME_SETTINGS);
        $cmd->setHelp('/laps laps X; Sets Laps Limit');
        $adminGroup->addShortAlias($cmd, 'laps');

        $cmd = AdminGroups::addAdminCommand('game rounds', $this, 'fastRounds', Permission::GAME_SETTINGS);
        $cmd->setHelp('/rounds limit X; Sets PointLimit in Rounds');
        $adminGroup->addShortAlias($cmd, 'rounds');

        $cmd = AdminGroups::addAdminCommand('game cup', $this, 'fastCup', Permission::GAME_SETTINGS);
        $cmd->setHelp('/cup limit X; Sets CupRoundsLimit for Winner');
        $adminGroup->addShortAlias($cmd, 'cup');

        $cmd = AdminGroups::addAdminCommand('game team', $this, 'fastTeam', Permission::GAME_SETTINGS);
        $cmd->setHelp('/team limit X; Sets Team PointLimit');
        $adminGroup->addShortAlias($cmd, 'team');

        /*
         * *******************
         * Concerning Players
         * *******************
         *
         *
         */

        $cmd = AdminGroups::addAdminCommand('player kick', $this, 'kick', Permission::PLAYER_KICK); //
        $cmd->setHelp('kick the player from the server');
        $cmd->setHelpMore(
            '$w/admin player kick #login$z will kick the player from the server.
A kicked player may return to the server whanever he desires.'
        );
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "kick"); // xaseco & fast


        $cmd = AdminGroups::addAdminCommand('player guest', $this, 'guest', Permission::PLAYER_KICK); //
        $cmd->setHelp('guest the player from the server');
        $cmd->setHelpMore(
            '$w/admin player guest #login$z will guest the player from the server.
A guest player doesen\'t need to enter passwords to enter the server.'
        );
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "guest"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player remove guest', $this, 'guestRemove', Permission::PLAYER_KICK); //
        $cmd->setHelp('remove the guest status of the player');
        $cmd->setHelpMore(
            '$w/admin remove guest #login$z will remove the guest status of the player.
A guest player doesen\'t need to enter passwords to enter the server.'
        );
        $cmd->setMinParam(1);

        $cmd = AdminGroups::addAdminCommand('player ban', $this, 'ban', Permission::PLAYER_BAN);
        $cmd->setHelp('Ban the player from the server');
        $cmd->setHelpMore(
            '$w/admin player ban #login$z will ban  the player from the server.
He may not return until the server is restarted'
        );
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "ban"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player black', $this, 'blacklist', Permission::PLAYER_BLACK);
        $cmd->setHelp('Add the player to the black list');
        $cmd->setHelpMore(
            '$w/admin player black #login$z will add the player to the blacklist of this server.
He may not return until the server blacklist file is deleted.
Other server might use the same blacklist file!!'
        );
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "black"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player remove ban', $this, 'unban', Permission::PLAYER_UNBAN);
        $cmd->setHelp('Removes the ban of the player')->addLineHelpMore(
            '$w/admin player remove ban #login$z will remove the ban of the player from this server'
        )->addLineHelpMore('He may rejoin the server after this.')->setMinParam(1);
        AdminGroups::addAlias($cmd, "unban"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('clear banlist', $this, 'cleanBanlist', Permission::PLAYER_UNBAN);
        $cmd->setHelp('clears the banlist of players')->addLineHelpMore(
            'Will completeley clear the banlist.'
        )->addLineHelpMore('All banned players will be able to rejoin the server.')->setMinParam(0);
        AdminGroups::addAlias($cmd, "cleanbanlist"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('get banlist', $this, 'showBanList', Permission::SERVER_GENERIC_OPTIONS);
        $cmd->setHelp('shows the current banlist of players')->setMinParam(0);
        AdminGroups::addAlias($cmd, "getbanlist");

        $cmd = AdminGroups::addAdminCommand('clear blacklist', $this, 'cleanBlacklist', Permission::PLAYER_UNBLACK);
        $cmd->setHelp('clears the blacklist of players')->addLineHelpMore(
            'Will completeley clear the blackList.'
        )->addLineHelpMore('All blacklist players will be able to rejoin the server.')->setMinParam(0);
        AdminGroups::addAlias($cmd, "cleanblacklist");

        $cmd = AdminGroups::addAdminCommand(
            'get blacklist',
            $this,
            'showBlackList',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('shows the current banlist of players')->setMinParam(0);
        AdminGroups::addAlias($cmd, "getblacklist");

        $cmd = AdminGroups::addAdminCommand(
            'get guestlist',
            $this,
            'showGuestList',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('shows the current guest of players')->setMinParam(0);
        AdminGroups::addAlias($cmd, "getguestlist");

        $cmd = AdminGroups::addAdminCommand('get ignorelist', $this, 'showIgnoreList', Permission::PLAYER_IGNORE);
        $cmd->setHelp('shows the current ignorelist of players')->setMinParam(0);
        AdminGroups::addAlias($cmd, "getignorelist");

        $cmd = AdminGroups::addAdminCommand('remove black', $this, 'unBlacklist', Permission::PLAYER_UNBLACK);
        $cmd->setHelp('Removes the player from the black list')
            ->addLineHelpMore('$w/admin player remove black #login$z will remove the player from the servers blacklist')
            ->addLineHelpMore('He may rejoin the server after this.')->setMinParam(1);
        AdminGroups::addAlias($cmd, "unblack"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player spec', $this, 'forceSpec', Permission::PLAYER_FORCESPEC);
        $cmd->setHelp('Forces the player to become spectator')
            ->addLineHelpMore('$w/admin player spec #login$z The playing player will be forced to become a spectator')
            ->addLineHelpMore('If the max spectators is reached it the player won\'t become a spectator')
            ->setMinParam(1);
        AdminGroups::addAlias($cmd, "spec"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player play', $this, 'forcePlay', Permission::PLAYER_FORCESPEC);
        $cmd->setHelp('Forces the spectator to become player')
            ->addLineHelpMore('$w/admin player play #login$z The spectator will be forced to become a player')
            ->setMinParam(1);
        AdminGroups::addAlias($cmd, "play"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player ignore', $this, 'ignore', Permission::PLAYER_IGNORE);
        $cmd->setHelp('Adds player to ignore list and mutes him from the chat')
            ->addLineHelpMore('$w/admin player ignore #login$z will ignore the players chat')
            ->addLineHelpMore('This player won\'t be able to communicate with other players.')->setMinParam(1);
        AdminGroups::addAlias($cmd, "ignore"); // xaseco & fast

        $cmd = AdminGroups::addAdminCommand('player unignore', $this, 'unignore', Permission::PLAYER_IGNORE);
        $cmd->setHelp('Removes player to ignore list and allows him to chat')
            ->addLineHelpMore('$w/admin player unignore #login$z will allow this player to use the chat again')
            ->addLineHelpMore('This player will be able to communicate with other players')->setMinParam(1);
        AdminGroups::addAlias($cmd, "unignore"); // xaseco & fast
        //ENDSUPER

        /*
         * ***************************
         * Concerning Server Settings
         * ***************************
         */

        $cmd = AdminGroups::addAdminCommand(
            'settings',
            $this,
            'invokeExpSettings',
            Permission::EXPANSION_PLUGIN_SETTINGS
        );
        $cmd->setMinParam(0);

        $cmd = AdminGroups::addAdminCommand('netstats', $this, 'invokeNetStats', Permission::CHAT_ADMINCHAT);
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "netstat"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'get server planets',
            $this,
            'getServerPlanets',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('Gets the serveraccount planets amount')
            ->addLineHelpMore('$w/admin planets $zreturn the planets amount on server account.')->setMinParam(0);
        AdminGroups::addAlias($cmd, "planets"); // fast

        $cmd = AdminGroups::addAdminCommand('set server pay', $this, 'pay', Permission::SERVER_PLANETS);
        $cmd->setHelp('Pays out planets')
            ->addLineHelpMore('$w/admin pay #login #amount$z pays amount of planets to login')->setMinParam(2);
        $cmd->addchecker(2, Integer::getInstance());
        AdminGroups::addAlias($cmd, "pay"); // xaseco

        $cmd = AdminGroups::addAdminCommand('set server name', $this, 'setServerName', Permission::SERVER_NAME);
        $cmd->setHelp('Changes the name of the server')
            ->addLineHelpMore('$w/admin set server name #name$z will change the server name.')
            ->addLineHelpMore('This servers name will be changed.')->setMinParam(1);
        AdminGroups::addAlias($cmd, "setservername"); // xaseco
        AdminGroups::addAlias($cmd, "name"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server comment',
            $this,
            'setServerComment',
            Permission::SERVER_COMMENT
        );
        $cmd->setHelp('Changes the server comment')
            ->addLineHelpMore('$w/admin set server comment #comment$z will change the server comment.')
            ->addLineHelpMore('This servers comment will be changed.')->setMinParam(1);
        AdminGroups::addAlias($cmd, "setcomment"); // xaseco
        AdminGroups::addAlias($cmd, "comment"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server player password',
            $this,
            'setServerPassword',
            Permission::SERVER_PASSWORD
        );
        $cmd->setHelp('Changes the player password')
            ->setHelpMore(
                '$w/admin set server spec password #pwd$z will change the password'
                . ' needed for players to connect to this server'
            )
            ->setMinParam(0);
        AdminGroups::addAlias($cmd, "setpwd"); // xaseco
        AdminGroups::addAlias($cmd, "pass"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server spec password',
            $this,
            'setSpecPassword',
            Permission::SERVER_SPECPWD
        );
        $cmd->setHelp('Changes the spectator password')
            ->setHelpMore(
                '$w/admin set server spec password #pwd$z will change the password'
                . ' needed for spectators to connect to this server'
            )
            ->setMinParam(1);
        AdminGroups::addAlias($cmd, "setspecpwd"); // xaseco
        AdminGroups::addAlias($cmd, "spectpass"); // fast


        $cmd = AdminGroups::addAdminCommand(
            'set server ref password',
            $this,
            'setRefereePassword',
            Permission::SERVER_REFPWD
        );
        $cmd->setHelp('Changes the Referee password')->setMinParam(1);
        AdminGroups::addAlias($cmd, "setrefpwd"); // xaseco


        $cmd = AdminGroups::addAdminCommand(
            'set server maxplayers',
            $this,
            'setServerMaxPlayers',
            Permission::SERVER_MAXPLAYER
        );
        $cmd->setHelp('Sets a new maximum of players')
            ->setHelpMore('Sets the maximum number of players who can play on this server.')->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setmaxplayers"); //xaseco
        AdminGroups::addAlias($cmd, "maxplayers"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server maxspectators',
            $this,
            'setServerMaxSpectators',
            Permission::SERVER_MAXSPEC
        );
        $cmd->setHelp('Sets a new maximum of spectator')
            ->setHelp('Sets the maximum number of players who can spectate the players on this server.');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setmaxspecs"); // xaseco
        AdminGroups::addAlias($cmd, "maxspec"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server chattime',
            $this,
            'setserverchattime',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('Sets the Chat time duration.')
            ->addLineHelpMore('This is the time players get between the challenge end and the the new map')
            ->setMinParam(1);
        $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, "setchattime"); // xaseco
        AdminGroups::addAlias($cmd, "chattime"); // fast

        $cmd = AdminGroups::addAdminCommand(
            'set server hide',
            $this,
            'setHideServer',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('Allows you to hide or show the server to players')
            ->addLineHelpMore(
                '$w\admin set server hide true$z Will hide the server from other players.'
                . ' Players would need to have the servers in their favorites or need to know the server login '
            )
            ->addLineHelpMore('$w\admin set server hide false$z Will make the server visible to any player')
            ->addchecker(1, Boolean::getInstance());
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "sethideserver");

        $cmd = AdminGroups::addAdminCommand(
            'set server mapdownload',
            $this,
            'setServerMapDownload',
            Permission::SERVER_GENERIC_OPTIONS
        );
        $cmd->setHelp('Will allow players to download maps they are playing from the server.')
            ->addLineHelpMore('$w\admin set server mapdownload true$z will allow the maps to be downloaded.')
            ->addLineHelpMore(
                '$w\admin set server mapdownload false$z will not allow players to download the maps of this server.'
            )
            ->addchecker(1, Boolean::getInstance());
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "setmapdownload");

        $cmd = AdminGroups::addAdminCommand(
            'stop dedicated',
            $this,
            'stopDedicated',
            Permission::SERVER_STOP_DEDICATED
        );
        $cmd->setHelp("Stops this server. Manialive will stop after this.");
        AdminGroups::addAlias($cmd, 'stop dedi');

        $cmd = AdminGroups::addAdminCommand(
            'manialive stop',
            $this,
            'stopManiaLive',
            Permission::SERVER_STOP_MANIALIVE
        );
        $cmd->setHelp("Stops the Manialive instance running on for the server.");
        AdminGroups::addAlias($cmd, 'exp stop');
        AdminGroups::addAlias($cmd, 'expansion stop');
        AdminGroups::addAlias($cmd, 'manialive stop');

        $cmd = AdminGroups::addAdminCommand(
            'manialive restart',
            $this,
            'restartManiaLive',
            Permission::SERVER_STOP_MANIALIVE
        );
        $cmd->setHelp("Restart the Manialive instance running on for the server.");
        AdminGroups::addAlias($cmd, 'manialive res');
        AdminGroups::addAlias($cmd, 'exp restart');
        AdminGroups::addAlias($cmd, 'exp res');
        AdminGroups::addAlias($cmd, 'expansion res');
        AdminGroups::addAlias($cmd, 'expansion restart');

        /*
         * *************************
         * Concerning Game Settings
         * *************************
         */
        $cmd = AdminGroups::addAdminCommand('skip', $this, 'skipMap', Permission::MAP_SKIP);
        $cmd->setHelp("Skips the current track");
        AdminGroups::addAlias($cmd, 'skip'); // shortcut
        AdminGroups::addAlias($cmd, 'skipmap'); // xaseco
        AdminGroups::addAlias($cmd, 'next'); // fast
        AdminGroups::addAlias($cmd, 'nextmap');

        $cmd = AdminGroups::addAdminCommand('restart', $this, 'restartMap', Permission::MAP_RES);
        $cmd->setHelp("Restarts this map to allow you to replay the map");
        AdminGroups::addAlias($cmd, 'res'); // xaseco
        AdminGroups::addAlias($cmd, 'restart'); // fast
        AdminGroups::addAlias($cmd, 'restartmap'); //xaseco

        $cmd = AdminGroups::addAdminCommand('rskip', $this, 'skipScoreReset', Permission::MAP_SKIP);
        $cmd->setHelp("Skips the current track and reset scores");

        $cmd = AdminGroups::addAdminCommand('rres', $this, 'restartScoreReset', Permission::MAP_RES);
        $cmd->setHelp("Restarts this map and resets the scores");

        $cmd = AdminGroups::addAdminCommand('set game mode', $this, 'setGameMode', Permission::GAME_GAMEMODE);
        $cmd->setHelp('Sets next mode {ta,rounds,team,laps,cup,reload}')
            ->addLineHelpMore('$w\admin set game mode reload$z will reload the current gamemode.')
            ->addLineHelpMore('$w\admin set game mode ta$z will change gamemode to TimeAttack.')
            ->addLineHelpMore('$w\admin set game mode rounds$z will change gamemode to Rounds mode.')
            ->addLineHelpMore('$w\admin set game mode team$z will change gamemode to Team mode.')
            ->addLineHelpMore('$w\admin set game mode laps$z will change gamemode to Laps mode.')
            ->addLineHelpMore('$w\admin set game mode cup$z will change gamemode to Cup mode.');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, 'setgamemode'); //xaseco
        AdminGroups::addAlias($cmd, 'mode'); //fast

        $cmd = AdminGroups::addAdminCommand(
            'set game AllWarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Set the warmup duration at the begining of the maps for all gamemodes')
            ->addchecker(1, Integer::getInstance());
            $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, 'setAllWarmUpDuration');

        $cmd = AdminGroups::addAdminCommand(
            'set game disableRespawn',
            $this,
            'setDisableRespawn',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Will disable the respawn capabilities of the players')
            ->addLineHelpMore(
                '$w/admin set game disableRespawn true$z will force the players to restart the map when they respaw'
            )
            ->addLineHelpMore(
                '$w/admin set game disableRespawn false$z player that respaw will return back to the last checkpoint'
            )
            ->addLineHelpMore("\n" . 'A player respaws when he clicks on backspace on his keyboard')->setMinParam(1);
        AdminGroups::addAlias($cmd, 'setDisableRespawn');

        //TimeAttack
        $cmd = AdminGroups::addAdminCommand('set game ta timelimit', $this, 'setTAlimit', Permission::GAME_SETTINGS);
        $cmd->setHelp('Changes the time limit of Time Attack mode.')
            ->addLineHelpMore('$w/admin set game ta timelimit #num$z will change the play time of a map')
            ->setMinParam(1);
        $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, 'setTAlimit');

        $cmd = AdminGroups::addAdminCommand('set game ta dynamic', $this, 'setTAdynamic', Permission::GAME_SETTINGS);
        $cmd->setHelp('Enables the dynamic timelimit for Time Attack Mode.')
            ->addLineHelpMore(
                '$w/admin set game ta timelimit #num$z will change the multiplier used for map authortime.'
            )
            ->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, 'setTAdynamic');

        $cmd = AdminGroups::addAdminCommand(
            'set game ta WarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the warmup duration of Time Attack mode only')->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());

        //rounds
        $cmd = AdminGroups::addAdminCommand('endround', $this, 'forceEndRound', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends a round. Only work in round mode');
        AdminGroups::addAlias($cmd, 'end'); // fast
        AdminGroups::addAlias($cmd, 'endround'); // xaseco
        AdminGroups::addAlias($cmd, 'er'); // xaseco
		
		//rounds warmup
        $cmd = AdminGroups::addAdminCommand('endwu', $this, 'forceEndWu', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends the WarmUp. Only work in round mode');
        AdminGroups::addAlias($cmd, 'endwu');
        AdminGroups::addAlias($cmd, 'ewu');
		
		//rounds warmup on round
        $cmd = AdminGroups::addAdminCommand('endwuround', $this, 'forceEndWuR', Permission::MAP_END_ROUND);
        $cmd->setHelp('Ends the round of the WarmUp. Only work in round mode');
        AdminGroups::addAlias($cmd, 'endwuround');
        AdminGroups::addAlias($cmd, 'ewur');

        //forcescores in rounds
        $cmd = AdminGroups::addAdminCommand('forceroundpoints', $this, 'forcePointsRounds', Permission::GAME_SETTINGS);
        $cmd->setHelp('Force the current scores of one player');
        AdminGroups::addAlias($cmd, 'forceroundpoints');
        AdminGroups::addAlias($cmd, 'frpts');

        //forcescores in team
        $cmd = AdminGroups::addAdminCommand('forceteampoints', $this, 'forcePointsTeam', Permission::GAME_SETTINGS);
        $cmd->setHelp('Force the current scores of one team');
        AdminGroups::addAlias($cmd, 'forceteampoints');
        AdminGroups::addAlias($cmd, 'ftpts');

        $cmd = AdminGroups::addAdminCommand(
            'set game rounds PointsLimit',
            $this,
            'setRoundPointsLimit',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the points limit of rounds mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, 'rpoints');

        $cmd = AdminGroups::addAdminCommand(
            'set game rounds ForcedLaps',
            $this,
            'setRoundForcedLaps',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Forces laps in Rounds mode')
            ->addLineHelpMore(
                '$w\admin set game rounds ForcedLaps #num$z will force multi laps maps lap number to the given value'
            )
            ->addLineHelpMore('using 0 as number of laps will change the nb of laps to the default value')
            ->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, 'setRoundForcedLaps');

        $cmd = AdminGroups::addAdminCommand(
            'set game rounds NewRules',
            $this,
            'setUseNewRulesRound',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Allows you tu use new rules in rounds mode')
            ->addLineHelpMore(
                '$w/admin set game rounds NewRules true$z will force the usage of new rules in rounds mode'
            )
            ->addLineHelpMore(
                '$w/admin set game rounds NewRules false$z will force the usage of old rules in rounds mode'
            )
            ->setMinParam(1);
        $cmd->addchecker(1, Boolean::getInstance());
        AdminGroups::addAlias($cmd, 'setUseNewRulesRound');

        $cmd = AdminGroups::addAdminCommand(
            'set game rounds WarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the warmup duration of Rounds mode only')->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, 'setAllWarmUpDuration');

        //laps
        $cmd = AdminGroups::addAdminCommand(
            'set game laps TimeLimit',
            $this,
            'setLapsTimeLimit',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the limit of time players has to finish the track')
            ->setMinParam(1)->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, "setLapsTimeLimit");

        $cmd = AdminGroups::addAdminCommand('set game laps nbLaps', $this, 'setNbLaps', Permission::GAME_SETTINGS);
        $cmd->setHelp('Changes the numbers of laps players need to do to finish the map');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setNbLaps");

        $cmd = AdminGroups::addAdminCommand(
            'set game laps FinishTimeOut',
            $this,
            'setFinishTimeout',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the time that has a player to finish a map once 1 player has already finished the map')
            ->setMinParam(1)->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, "setFinishTimeout");


        $cmd = AdminGroups::addAdminCommand(
            'set game laps WarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the warmup duration of laps mode only')
            ->setMinParam(1)->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setAllWarmUpDuration");

        //team
        $cmd = AdminGroups::addAdminCommand(
            'set game team PointsLimit',
            $this,
            'setTeamPointsLimit',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the points limit of team mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setTeamPointsLimit");

        $cmd = AdminGroups::addAdminCommand(
            'set game team PointsLimit',
            $this,
            'setTeamBalance',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('tries to autobalance teams');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "setTeamBalance");

        $cmd = AdminGroups::addAdminCommand(
            'set game team maxPoints',
            $this,
            'setMaxPointsTeam',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Max PointsLimit of team mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setMaxPointsTeam");

        $cmd = AdminGroups::addAdminCommand(
            'set game team NewRules',
            $this,
            'setUseNewRulesTeam',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the NewRules of team mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Boolean::getInstance());
        AdminGroups::addAlias($cmd, "setUseNewRulesTeam");

        $cmd = AdminGroups::addAdminCommand(
            'set game team forcePlayer',
            $this,
            'forcePlayerTeam',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Team for a Player by Forcing him');
        $cmd->setMinParam(2);
        $cmd->addchecker(
            2,
            Arraylist::getInstance()->items("0,1,red,blue")
        );
        AdminGroups::addAlias($cmd, "forcePlayerTeam");


        $cmd = AdminGroups::addAdminCommand(
            'set game team WarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the WarmUpDuration of team mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setAllWarmUpDuration");

        //cup
        $cmd = AdminGroups::addAdminCommand(
            'set game cup PointsLimit',
            $this,
            'setCupPointsLimit',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Cup PointLimit of Cup mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setCupPointsLimit");

        $cmd = AdminGroups::addAdminCommand(
            'set game cup RoundsPerMap',
            $this,
            'setCupRoundsPerMap',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Cup RoundsPerMap of Cup mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setCupRoundsPerMap");

        $cmd = AdminGroups::addAdminCommand(
            'set game cup WarmUpDuration',
            $this,
            'setAllWarmUpDuration',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Cup WarmUpDuration of Cup mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, "setCupWarmUpDuration");

        $cmd = AdminGroups::addAdminCommand(
            'set game cup NbWinners',
            $this,
            'setCupNbWinners',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Cup NbWinners of Cup mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Integer::getInstance());
        AdminGroups::addAlias($cmd, "setCupNbWinners");

        $cmd = AdminGroups::addAdminCommand(
            'set game cup finishtimeout',
            $this,
            'setFinishTimeout',
            Permission::GAME_SETTINGS
        );
        $cmd->setHelp('Changes the Cup Finishtimeout of Cup mode');
        $cmd->setMinParam(1);
        $cmd->addchecker(1, Time_ms::getInstance());
        AdminGroups::addAlias($cmd, "setFinishTimeout");

        $cmd = AdminGroups::addAdminCommand('maps shuffle', $this, 'shuffleMaps', Permission::GAME_SETTINGS);
        $cmd->setHelp('Shuffles the mapslist');
        $cmd->setMinParam(0);
        AdminGroups::addAlias($cmd, "shuffle");

        $this->enableDatabase();
        $this->enableTickerEvent();
        self::$showActions['ignore'] = ActionHandler::getInstance()
            ->createAction(array($this, 'showIgnoreList'));
        self::$showActions['ban'] = ActionHandler::getInstance()
            ->createAction(array($this, 'showBanList'));
        self::$showActions['black'] = ActionHandler::getInstance()
            ->createAction(array($this, 'showBlackList'));
        self::$showActions['guest'] = ActionHandler::getInstance()
            ->createAction(array($this, 'showGuestList'));

        self::$showActions['guestPlayer'] = ActionHandler::getInstance()
            ->createAction(array($this, 'addGuestList'));
        self::$showActions['ignorePlayer'] = ActionHandler::getInstance()
            ->createAction(array($this, 'addIgnore'));
        self::$showActions['banPlayer'] = ActionHandler::getInstance()
            ->createAction(array($this, 'addBan'));
        self::$showActions['blackPlayer'] = ActionHandler::getInstance()
            ->createAction(array($this, 'addBlack'));

    }

    /**
     *
     */
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
    }

    /**
     *
     */
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

            switch (strtolower($command)) {
                case "time":
                case "limit":
                case "timelimit":
                    $this->setTAlimit($fromLogin, $params);
                    break;
                case "dyn":
                case "dynamic":
                    $this->setTAdynamic($fromLogin, $params);
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
                    $msg = eXpGetMessage("possible parameters: limit, dynamic, wu");
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


            switch (strtolower($command)) {
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
                    $msg = eXpGetMessage("possible parameters: laps, limit, wu, fto, ftimeout");
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

            switch (strtolower($command)) {
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
                case "skip":
                    $this->skipScoreReset($fromLogin, $params);
                    break;
                case "res":
                    $this->restartScoreReset($fromLogin, $params);
                    break;
                default:
                    $msg = eXpGetMessage("possible parameters: pointslimit, newrules, wu, fto, ftimeout");
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

            switch (strtolower($command)) {
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
                    $msg = eXpGetMessage("possible parameters: limit, rounds, nbwin, wu, fto, ftimeout");
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

            switch (strtolower($command)) {
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
                    $this->enableTeamGap($fromLogin, $params);
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
                default:
                    $msg = eXpGetMessage(
                        "possible parameters: balance, limit, maxpoint, newrules, wu, fto, ftimeout, blue, red, gap"
                    );
                    $this->eXpChatSendServerMessage($msg, $fromLogin);
                    break;
            }
        } catch (Exception $e) {

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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable#%s $z$s#admin_action# AutoBalances the Teams!',
                null,
                array($adminNick)
            );
            $this->connection->autoTeamBalance();
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage(
                "#admin_error#error while AutoTeamBalance: " . $e->getMessage(),
                $fromLogin
            );
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setScriptName($fromLogin, $params)
    {
        if (sizeof($params) == 0) {
            $name = $this->connection->getScriptName();
            $this->eXpChatSendServerMessage("current script name: " . $name['CurrentValue'], $fromLogin);

            return;
        }

        if (!is_string($params[0])) {
            $this->eXpChatSendServerMessage("#admin_error#needs script name to be text!", $fromLogin);

            return;
        }


        try {
            $this->connection->setScriptName($params[0]);
            $this->eXpChatSendServerMessage(
                "new script in run: " . $params[0] . ", please restart or skip the map for changes to be active.",
                $fromLogin
            );
        } catch (Exception2 $ex) {
            $this->eXpChatSendServerMessage(
                "#admin_error#Error:" . $ex->getMessage() . " on line:" . $ex->getLine(),
                $fromLogin
            );
        }
    }

    /**
     * @param $login
     * @param $params
     */
    public function enableTeamGap($login, $params)
    {
        if ($this->storage->gameInfos->gameMode != GameInfos::GAMEMODE_TEAM) {
            $this->eXpChatSendServerMessage("#admin_error#Not in teams mode!", $login);
        }

        if (sizeof($params) > 0 && is_numeric($params[0])) {
            $this->teamGap = intval($params[0]);

            $this->eXpChatSendServerMessage(
                '#admin_action#Team gap set to #variable# %1$s!',
                $login,
                array($params[0])
            );
            $this->connection->restartMap();
        }
    }

    /**
     *
     */
    public function onBeginMatch()
    {
    }

    /**
     * @param \ManiaLive\DedicatedApi\Callback\SPlayerRanking[] $rankings
     * @param int|\ManiaLive\DedicatedApi\Callback\SMapInfo $winnerTeamOrMap
     */
    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->teamGap > 1 && $this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_TEAM) {
            $points = $this->teamGap * 10;
            if ($this->teamGap <= 5) {
                $points = 50;
            }
            $this->connection->setTeamPointsLimit($points);
        }
    }

    /**
     *
     */
    public function onEndRound()
    {
        $this->checkTeamGap();
    }

    /**
     *
     */
    public function onBeginRound()
    {
        $this->checkTeamGap();
    }

    /**
     *
     */
    public function checkTeamGap()
    {
        if ($this->teamGap >= 1) {

            $ranking = $this->expStorage->getCurrentRanking();
            $scoregap = abs($ranking[0]->score - $ranking[1]->score);
            $scoremax = $ranking[0]->score > $ranking[1]->score ? $ranking[0]->score : $ranking[1]->score;
            print_r($ranking);
            print_r($this->storage->players);

            echo "gap:" . $scoregap . " max:" . $scoremax . "\n";
            if ($scoremax >= $this->teamGap && $scoregap >= 2) {
                echo "next map\n";
            }
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function pay($fromLogin, $params)
    {
        try {
            $this->connection->pay(
                $params[0],
                intval($params[1]),
                $params[0] . " Planets payed out from server " . $this->storage->server->name
            );
            $this->eXpChatSendServerMessage(
                '#admin_action#Server just sent#variable# %s #admin_action#Planets to#variable# %s #admin_action#!',
                $fromLogin,
                array($params[1], $params[0])
            );
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

            $this->eXpChatSendServerMessage(
                '#admin_action#Server has #variable# %s #admin_action#Planets.',
                $fromLogin,
                array($this->connection->getServerPlanets())
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->forcePlayerTeam($params[0], 0);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sends '
                . 'player#variable# %s #admin_action#to team $00fBlue.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->forcePlayerTeam($params[0], 1);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sends '
                . 'player#variable# %s #admin_action#to team $f00Red.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_NbOfWinners" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets cup winners to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_RoundsPerMap" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets use new rounds to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets use '
                . 'new cup points limit to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function forcePlayerTeam($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        $player = $this->storage->getPlayerObject($params[0]);
        if ($player == null) {
            $this->eXpChatSendServerMessage(
                '#admin_action#Player #variable# %s #admin_action#doesn\' exist.',
                null,
                array($params[0])
            );

            return;
        }

        if ($params[1] == "red") {
            $params[1] = 1;
        }
        if ($params[1] == "blue") {
            $params[1] = 0;
        }

        try {
            $this->connection->forcePlayerTeam($player, intval($params[0]));
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces '
                . 'player #variable# %s #admin_action# to team#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $player->nickName, $params[0])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setUseNewRulesTeam($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_UseAlternateRules" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets use '
                . 'new team rules to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_MaxPointsPerRound" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets Team max points to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets Team points limit to#variable# %s #admin_action#.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_FinishTimeout" => intval($newLimit)]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets new'
                . ' finish timeout to#variable# %s #admin_action#minutes.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_ForceLapsNb" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets '
                . 'new number of laps to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($newLimit)]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets '
                . 'new laps timelimit to#variable# %s #admin_action#minutes.',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_PointsLimit" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets rounds points limits to#variable# %s.',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->connection->triggerModeScriptEventArray('Trackmania.ForceEndRound', array((string)time()));

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces the round to end.',
                null,
                array($admin->nickName)
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }
	
	public function forceEndWu($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.WarmUp.ForceStop', array((string)time()));

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces the WarmUp to end.',
                null,
                array($admin->nickName)
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }
	
	public function forceEndWuR($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.WarmUp.ForceStopRound', array((string)time()));

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces the WarmUp Round to end.',
                null,
                array($admin->nickName)
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    public function forcePointsRounds($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        $player = $this->storage->getPlayerObject($params[0]);
        if ($params[0] == null){
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the login of the player'), $fromLogin);
            return;
        }
        if ($params[1] == null){
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the points of the player'), $fromLogin);
            return;
        }
        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$params[0]", "", "", "$params[1]"));

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces the Roundpoints of $fff%s #admin_action#to $fff%s#admin_action#.',
                null,
                array($admin->nickName, $player->nickName, $params[1])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    public function forcePointsTeam($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        if ($params[0] == null){
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the id of the team (red or blue) !'), $fromLogin);
            return;
        }
        if ($params[1] == null){
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide the points of the team'), $fromLogin);
            return;
        }

        if ($params[0] == blue){
            $teampts = 0;
        }
        if ($params[0] == red){
            $teampts = 1;
        }

        try {
            $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$teampts", "", "$params[1]", "$params[1]"));

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#forces the points of the team $fff%s #admin_action#to $fff%s#admin_action#.',
                null,
                array($admin->nickName, $params[0], $params[1])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, $e->getMessage());
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setNbWarmUp($fromLogin, $params)
    {
        try {
            $this->connection->setModeScriptSettings(["S_WarmUpNb" => intval($params[0])]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action#sets all game modes warmup number to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
        try {
            $this->connection->setModeScriptSettings(["S_DisplayTimeDiff" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action#sets the display time diff to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_UseAlternateRules" => filter_var($params[0], FILTER_VALIDATE_BOOLEAN)]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets new round rules to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setRoundForcedLaps($fromLogin, $params)
    {
        $admin = $this->storage->getPlayerObject($fromLogin);
        try {
            $this->connection->setModeScriptSettings(["S_ForceLapsNb" => intval($params[0])]);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets new round forced laps to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action#blacklists '
                . 'the player #variable# %s reason: #admin_error#%s',
                null,
                array($admin->nickName, $nickname, $reason)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#cleans the blacklist.',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#cleans the banlist.',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#cleans the ignorelist.',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets game mode to#variable# %s',
                null,
                array($admin->nickName, $scriptName)
            );
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

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#unblacklists the player %s',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action# bans '
                . 'the player#variable# %s reason: #admin_error# %s',
                null,
                array($admin->nickName, $nickname, $reason)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action# ignores the player#variable# %s',
                null,
                array($admin->nickName, $nickname)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#unbans the player %s',
                null,
                array($admin->nickName, $nickname)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#unignores the player %s',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_error#Player #variable# %s doesn\' exist.',
                $fromLogin,
                array($target)
            );

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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %1$s #admin_action#kicks the '
                . 'player#variable# %2$s (%3$s) #variable#Reason: #admin_error#%4$s',
                null,
                array($admin->nickName, $player->nickName, $target, $reason)
            );
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

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#add as guest the player#variable# %s',
                null,
                array($admin->nickName, $nick)
            );
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

            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#removed guest status of the player#variable# %s',
                null,
                array($admin->nickName, $nickname)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Player #variable# %s doesn\' exist.',
                $fromLogin,
                array($params[0])
            );
            return;
        }
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->connection->forceSpectator($player, 1);
            $this->connection->forceSpectator($player, 0);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#Forces '
                . 'the player#variable# %s #admin_action#to spectate.',
                null,
                array($admin->nickName, $player->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Player #variable# %s doesn\' exist.',
                $fromLogin,
                array($params[0])
            );
            return;
        }
        try {
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->connection->forceSpectator($player, 2);
            $this->connection->forceSpectator($player, 0);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#Forces '
                . 'the spectator#variable# %s #admin_action#to play.',
                null,
                array($admin->nickName, $player->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action# sets new server name:#variable# %s',
                null,
                array($admin->nickName, $name)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets new server comment:#variable# %s',
                null,
                array($admin->nickName, $comment)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets server maximum players to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets server maximum spectators to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->connection->setServerPassword($params[0]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action# sets/unsets new server password.',
                null,
                array($admin->nickName)
            );
            $this->eXpChatSendServerMessage(
                '#admin_action#New server password:#variable# %s',
                null,
                array($params[0]),
                $fromLogin
            );
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
            $this->connection->setServerPasswordForSpectator($params[0]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets/unsets new spectator password.',
                null,
                array($admin->nickName)
            );
            $this->eXpChatSendServerMessage(
                '#admin_action#New spectator password:#variable# %s',
                null,
                array($params[0]),
                $fromLogin
            );
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
            $this->connection->setRefereePassword($params[0]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets/unsets new referee password.',
                null,
                array($admin->nickName)
            );
            $this->eXpChatSendServerMessage(
                '#admin_action#New referee password:#variable# %s',
                null,
                array($params[0]),
                $fromLogin
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable#%s $z#admin_action#sets '
                . 'new chat time limit of #variable# %s #admin_action#minutes.',
                null,
                array($admin->nickName, $params[0])
            );
        } catch (Exception $e) {
            $this->sendErrorChat($fromLogin, 'Incompatible game mode');
            return;
        }
    }

    /**
     * @param $fromLogin
     * @param $params
     */
    public function setTAdynamic($fromLogin, $params)
    {
        try {
            $this->dynamicTime = $params[0];
            $admin = $this->storage->getPlayerObject($fromLogin);
            if ($params[0] == 0) {
                $this->eXpChatSendServerMessage(
                    '#admin_action#Admin#variable# %s #admin_action# disables the dynamic time limit!',
                    null,
                    array($admin->nickName)
                );
                $this->eXpChatSendServerMessage(
                    '#admin_action#Static timelimit is set to #variable#5:00 #admin_action#minutes.'
                );
                $this->connection->setModeScriptSettings(["S_TimeLimit" => 300]);

                return;
            }
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets dynamic '
                . 'time limit multiplier to #variable# %s #admin_action#!',
                null,
                array($admin->nickName, $params[0])
            );
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

        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        try {
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($newLimit)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#sets '
                . 'new time limit of #variable# %s #admin_action#minutes.',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->sendErrorChat(
                $fromLogin,
                'Invalid parameter. Correct parameter for the command is either true or false.'
            );

            return;
        }

        try {
            $this->connection->allowMapDownload($bool);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#set allow download maps to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
            $this->sendErrorChat(
                $fromLogin,
                'Invalid parameter. Correct parameters for command are: 0,1,2,visible,hidden,nations.'
            );
            return;
        }
        try {
            $this->connection->setHideServer($output);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#set Hide Server to#variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
        $args = getopt(null, array('help::', //Display Help
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#skips the challenge!',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#restarts the challenge!',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#skips the challenge!',
                null,
                array($admin->nickName)
            );
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
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#restarts the challenge!',
                null,
                array($admin->nickName)
            );
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
        if (strtolower($gamemode) == "rounds") {
            $gamemode = "Rounds";
        }
        if (strtolower($gamemode) == "team") {
            $gamemode = "Team";
        }
        if (strtolower($gamemode) == "cup") {
            $gamemode = "Cup";
        }
        if (strtolower($gamemode) == "laps") {
            $gamemode = "Laps";
        }
        if (strtolower($gamemode) == "grav") {
            $gamemode = "Gravity";
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
        $newLimit = TimeConversion::MStoTM($params[0]) / 1000;

        try {
            $this->connection->setModeScriptSettings(["S_WarmUpDuration" => intval($newLimit)]);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin #variable# %s #admin_action#sets warmup duration to#variable# %s #admin_action#minutes',
                null,
                array($admin->nickName, $params[0])
            );
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
        $admin = $this->storage->getPlayerObject($fromLogin);
        $vote = $this->connection->getCurrentCallVote();
        if (!empty($vote->cmdName)) {
            try {
                $this->connection->cancelVote();
                $this->eXpChatSendServerMessage(
                    '#admin_action#Admin#variable# %s #admin_action#cancels the vote.',
                    null,
                    array($admin->nickName)
                );

                return;
            } catch (Exception $e) {
                $this->eXpChatSendServerMessage(
                    '#admin_error#Error: Server said %1$s',
                    $admin->login,
                    array($e->getMessage())
                );
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
            $this->sendErrorChat(
                $fromLogin,
                'Invalid parameter. Correct parameter for the command is either true or false.'
            );

            return;
        }

        try {
            $this->connection->setDisableRespawn($bool);
            $admin = $this->storage->getPlayerObject($fromLogin);
            $this->eXpChatSendServerMessage(
                '#admin_action#Admin#variable# %s #admin_action#set allow respawn to #variable# %s',
                null,
                array($admin->nickName, $params[0])
            );
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
     * @param $statusCode
     * @param $statusName
     */
    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        try {
            if ($this->expStorage->simpleEnviTitle == Storage::TITLE_SIMPLE_TM && $this->dynamicTime > 0) {
                if ($this->eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TIMEATTACK) {
                    $map = $this->connection->getCurrentMapInfo();
                    $laps = $map->nbLaps;
                    if ($map->nbLaps <= 1) {
                        $laps = 1;
                    }

                    $newLimit = floor((intval($map->authorTime) / intval($laps)) * floatval($this->dynamicTime));

                    $max = TimeConversion::MStoTM(Config::getInstance()->time_dynamic_max);
                    $min = TimeConversion::MStoTM(Config::getInstance()->time_dynamic_min);

                    if ($newLimit > $max) {
                        $newLimit = $max;
                    }
                    if ($newLimit < $min) {
                        $newLimit = $min;
                    }
                    $scriptLimit = $newLimit / 1000;
                    $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($scriptLimit)]);

                    $this->eXpChatSendServerMessage(
                        '#admin_action#Dynamic time limit set to: #variable#' . Time::fromTM($newLimit),
                        null
                    );
                }
            }
        } catch (Exception $e) {
            $this->console($e->getMessage());
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
