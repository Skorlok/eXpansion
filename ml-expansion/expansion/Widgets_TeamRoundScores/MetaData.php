<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamRoundScores;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;

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
        $this->setName("Widget: Round Score widget for teams mode");
        $this->setDescription("");
        $this->setGroups(array('Widgets'));

        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);

        $config = Config::getInstance();

        $var = new TypeFloat("teamRoundScorePanel_PosX", "Position of TeamRoundScores Panel X", $config, false, false);
        $var->setDefaultValue(-124);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("teamRoundScorePanel_PosY", "Position of TeamRoundScores Panel Y", $config, false, false);
        $var->setDefaultValue(58);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
