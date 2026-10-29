<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamPlayerScores;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;

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
        $this->setName("Widget: Player Score widget for teams mode");
        $this->setDescription("");
        $this->setGroups(array('Widgets'));

        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);

        $config = Config::getInstance();

        $var = new TypeFloat("teamPlayerScorePanel_PosX", "Position of TeamPlayerScores Panel X", $config, false, false);
        $var->setDefaultValue(-124);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("teamPlayerScorePanel_PosY", "Position of TeamPlayerScores Panel Y", $config, false, false);
        $var->setDefaultValue(6);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("teamPlayerScorePanel_nbFields", "Number of field in TeamPlayerScores Panel", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
