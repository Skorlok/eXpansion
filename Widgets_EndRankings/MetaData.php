<?php

namespace ManiaLivePlugins\eXpansion\Widgets_EndRankings;

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
        $this->setName("Widget: Podium Infos and Statistics");
        $this->setDescription("Server ranks, top playtime and top donators during podium");
        $this->setGroups(array('Records', 'Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat("donatorPanel_PosX", "Position of TopDonators Panel X", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("donatorPanel_PosY", "Position of TopDonators Panel Y", $config, false, false);
        $var->setDefaultValue(0);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("donatorPanel_nbFields", "Number of TopDonators to show", $config, false, false);
        $var->setDefaultValue(15);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("rankPanel_PosX", "Position of ServerRanking Panel X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("rankPanel_PosY", "Position of ServerRanking Panel Y", $config, false, false);
        $var->setDefaultValue(60);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("rankPanel_nbFields", "Number of ServerRanking to show", $config, false, false);
        $var->setDefaultValue(30);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("playtimePanel_PosX", "Position of TopPlaytime Panel X", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("playtimePanel_PosY", "Position of TopPlaytime Panel Y", $config, false, false);
        $var->setDefaultValue(60);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("playtimePanel_nbFields", "Number of TopPlaytime to show", $config, false, false);
        $var->setDefaultValue(18);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
