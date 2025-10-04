<?php

namespace ManiaLivePlugins\eXpansion\Votes;

class Config extends \ManiaLib\Utils\Singleton
{
    public $restartLimit = 2;
    public $use_callvotes = false;
    public $global_timeout = 30;
    public $limit_votes = 0;
    public $extendTimeLimit = 15;
    public $extendPointLimit = 200;
    public $autoVoteStarter = true;

    /**
     * @var bool Use track queue instead of instant restart, if 'eXpansion\Maps' plugin is loaded
     */
    public $restartVote_useQueue = true;


    public $managedVote_enable = array("NextMap" => true,
        "RestartMap" => true,
        "Kick" => true,
        "Ban" => true,
        "SetModeScriptSettingsAndCommands" => true,
        "JumpToMapIdent" => true,
        "SetNextMapIdent" => true,
        "ExtendTime" => true,
        "EndRound" => true,
        "AutoTeamBalance" => true);

    public $managedVote_commands = array(
        "NextMap",
        "RestartMap",
        "Kick",
        "Ban",
        "SetModeScriptSettingsAndCommands",
        "JumpToMapIdent",
        "SetNextMapIdent",
        "ExtendTime",
        "EndRound",
        "AutoTeamBalance"
    );

    public $managedVote_ratios = array("NextMap" => 0.5,
        "RestartMap" => 0.5,
        "Kick" => 0.8,
        "Ban" => -1.,
        "SetModeScriptSettingsAndCommands" => -1.,
        "JumpToMapIdent" => -1.,
        "SetNextMapIdent" => -1.,
        "ExtendTime" => 0.6,
        "EndRound" => 0.6,
        "AutoTeamBalance" => 0.5);

    public $managedVote_timeouts = array("NextMap" => 30,
        "RestartMap" => 30,
        "Kick" => 30,
        "Ban" => 30,
        "SetModeScriptSettingsAndCommands" => 30,
        "JumpToMapIdent" => 30,
        "SetNextMapIdent" => 30,
        "ExtendTime" => 30,
        "EndRound" => 30,
        "AutoTeamBalance" => 30);

    public $managedVote_voters = array("NextMap" => 1,
        "RestartMap" => 1,
        "Kick" => 1,
        "Ban" => 1,
        "SetModeScriptSettingsAndCommands" => 1,
        "JumpToMapIdent" => 1,
        "SetNextMapIdent" => 1,
        "ExtendTime" => 1,
        "EndRound" => 1,
        "AutoTeamBalance" => 1);

    public $voteWidget_PosX = 4;
    public $voteWidget_PosY = 64;
    public $voteWidget_PosX_Shootmania = 17;
    public $voteWidget_PosY_Shootmania = 79;
}
