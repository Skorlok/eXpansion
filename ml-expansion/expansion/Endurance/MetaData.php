<?php

namespace ManiaLivePlugins\eXpansion\Endurance;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Tool: Endurance integration");

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setDescription("Provides integration for EnduroCup gamemode");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new TypeInt("rounds", "Rounds per matchs", $config, false, false);
        $var->setDefaultValue(3);
        $this->registerVariable($var);

        $var = new TypeInt("maps", "maps per matchs", $config, false, false);
        $var->setDefaultValue(1);
        $this->registerVariable($var);

        $var = new TypeFloat("decreaser", "Point decreaser", $config, false, false);
        $var->setDefaultValue(0.95);
        $this->registerVariable($var);

        $var = new Boolean("auto_reset", "Auto reset total points ?", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new TypeString("points", "Rounds points array", $config, false, false);
        $var->setDefaultValue('750,720,690,660,645,630,615,600,585,570,558,546,534,522,510,498,486,474,462,450,440,430,420,410,400,390,380,370,360,351,342,333,324,315,306,298,290,282,274,266,258,251,244,237,230,224,218,212,206,200,195,190,185,180,175,170,166,162,158,154,150,146,142,138,134,130,127,124,121,118,115,112,109,106,103,100,98,96,94,92,90,88,86,84,82,80,78,76,74,72,70,68,66,64,62,60,58,56,54,52,50,49,48,47,46,45,44,43,42,41,40,39,38,37,36,35,34,33,32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1');
        $this->registerVariable($var);

        $var = new TypeInt("points_last", "Points given to finished players outside the points array", $config, false, false);
        $var->setDefaultValue(1);
        $this->registerVariable($var);

        $var = new TypeInt("wu", "Warmup time between rounds", $config, false, false);
        $var->setDefaultValue(15);
        $this->registerVariable($var);

        $var = new TypeInt("wustart", "Warmup time on new map", $config, false, false);
        $var->setDefaultValue(23);
        $this->registerVariable($var);

        $var = new TypeString("save_csv", "CSV file to store the final points when using //savepoints", $config, false, false);
        $var->setDefaultValue('enduro_results.csv');
        $this->registerVariable($var);

        $var = new Boolean("save_total_points", "If true, //savepoints will save the total points as final points. If false, //savepoints will save the final points according to the rounds points based on the total points", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new TypeFloat("enduroPointPanel_PosX", "Position of EnduroPoints Panel X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("enduroPointPanel_PosY", "Position of EnduroPoints Panel Y", $config, false, false);
        $var->setDefaultValue(67);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("enduroPointPanel_nbFields", "Number of fields in EnduroPoints Panel", $config, false, false);
        $var->setDefaultValue(13);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("enduroPointPanel_nbFirstFields", "Number of first fields in EnduroPoints Panel", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
