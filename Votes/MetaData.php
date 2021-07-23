<?php

namespace ManiaLivePlugins\eXpansion\Votes;

use ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;

/**
 * Description of MetaData
 *
 * @author Petri
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Tools: Votes");
        $this->setDescription("Provides Votes handler");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new Boolean("use_votes", "Enable managed voting for this server ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);


        $var = new TypeInt("restartLimit", "Map limit for restart votes", $config, false, false);
        $var->setDescription("0 disable, othervice after x maps, votes are disabled");
        $var->setDefaultValue(0);
        $this->registerVariable($var);


        $var = new TypeInt("limit_votes", "Limit voting for a player on map", $config, false, false);
        $var->setDescription("-1 to disable, othervice number of vote start");
        $var->setDefaultValue(1);
        $this->registerVariable($var);

        $var = new Boolean(
            "restartVote_useQueue",
            "Use track queue instead of intant restart for replay votes ?",
            $config,
            false,
            false
        );
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new HashList("managedVote_enable", "Use eXp managed votes ?", $config, false, false);
        $type = new Boolean("", "", null);
        $var->setType($type);
        $var->setVisible(true);
        $var->setDefaultValue(array("NextMap" => true,
            "RestartMap" => true,
            "Kick" => true,
            "Ban" => true,
            "SetModeScriptSettingsAndCommands" => true,
            "JumpToMapIdent" => true,
            "SetNextMapIdent" => true,
            "ExtendTime" => true,
            "EndRound" => true,
            "Question" => true,
            "AutoTeamBalance" => true));
        $this->registerVariable($var);

        $var = new BasicList("managedVote_commands", "Managed vote commands", $config, false, false);
        $type = new TypeString("", "", null);
        $var->setType($type);
        $var->setVisible(true);
        $var->setDefaultValue(
            array(
                "NextMap", "RestartMap",
                "Kick", "Ban", "SetModeScriptSettingsAndCommands",
                "JumpToMapIdent", "SetNextMapIdent", "ExtendTime", "EndRound", "Question",
                "AutoTeamBalance")
        );
        $this->registerVariable($var);

        $var = new HashList("managedVote_ratios", "Managed vote ratios", $config, false, false);
        $var->setDescription("set ratio -1 for disable, and ratio between 0 to 1");
        $type = new BoundedTypeFloat("", "", null);
        $type->setMin(-1.0);
        $type->setMax(1.0);
        $var->setVisible(true);
        $var->setType($type);
        $var->setDefaultValue(array("NextMap" => 0.5,
            "RestartMap" => 0.5,
            "Kick" => 0.6,
            "Ban" => -1.,
            "SetModeScriptSettingsAndCommands" => -1.,
            "JumpToMapIdent" => -1.,
            "SetNextMapIdent" => -1.,
            "ExtendTime" => 0.6,
            "EndRound" => 0.6,
            "Question" => 0.6,
            "AutoTeamBalance" => 0.5));
        $this->registerVariable($var);

        $var = new HashList("managedVote_timeouts", "Managed vote timeouts", $config, false, false);
        $var->setDescription("time in seconds");
        $type = new TypeInt("", "", null);
        $var->setType($type);
        $var->setVisible(true);
        $var->setDefaultValue(array("NextMap" => 30,
            "RestartMap" => 30,
            "Kick" => 30,
            "Ban" => 30,
            "SetModeScriptSettingsAndCommands" => 60,
            "JumpToMapIdent" => 60,
            "SetNextMapIdent" => 30,
            "ExtendTime" => 30,
            "EndRound" => 30,
            "Question" => 30,
            "AutoTeamBalance" => 30));
        $this->registerVariable($var);

        $var = new HashList("managedVote_voters", "Managed vote voters", $config, false, false);
        $type = new BoundedTypeInt("", "", null);
        $type->setMin(0);
        $type->setMax(2);
        $var->setVisible(true);
        $var->setType($type);
        $var->setDefaultValue(array("NextMap" => 1,
            "RestartMap" => 1,
            "Kick" => 1,
            "Ban" => 1,
            "SetModeScriptSettingsAndCommands" => 1,
            "JumpToMapIdent" => 1,
            "SetNextMapIdent" => 1,
            "ExtendTime" => 1,
            "EndRound" => 1,
            "Question" => 1,
            "AutoTeamBalance" => 1));
        $this->registerVariable($var);
    }
}
