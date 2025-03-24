<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecords;

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
        $this->setName("Widget: Local Records");
        $this->setDescription("Local Records widget");
        $this->setGroups(array('Widgets', 'Records'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("localRecordsPanel_PosX_Default", "Position of LocalRecords Panel X (Default)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY_Default", "Position of LocalRecords Panel Y (Default)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields_Default", "Number of fields in LocalRecords Panel (Default)", $config, false, false);
        $var->setDefaultValue(15);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFirstFields_Default", "Number of first fields in LocalRecords Panel (Default)", $config, false, false);
        $var->setDefaultValue(5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("localRecordsPanel_PosX_Rounds", "Position of LocalRecords Panel X (Rounds)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY_Rounds", "Position of LocalRecords Panel Y (Rounds)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields_Rounds", "Number of fields in LocalRecords Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFirstFields_Rounds", "Number of first fields in LocalRecords Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("localRecordsPanel_PosX_Cup", "Position of LocalRecords Panel X (Cup)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY_Cup", "Position of LocalRecords Panel Y (Cup)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields_Cup", "Number of fields in LocalRecords Panel (Cup)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFirstFields_Cup", "Number of first fields in LocalRecords Panel (Cup)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("localRecordsPanel_PosX_Laps", "Position of LocalRecords Panel X (Laps)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY_Laps", "Position of LocalRecords Panel Y (Laps)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields_Laps", "Number of fields in LocalRecords Panel (Laps)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFirstFields_Laps", "Number of first fields in LocalRecords Panel (Laps)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("localRecordsPanel_PosX_Team", "Position of LocalRecords Panel X (Team)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY_Team", "Position of LocalRecords Panel Y (Team)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields_Team", "Number of fields in LocalRecords Panel (Team)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFirstFields_Team", "Number of first fields in LocalRecords Panel (Team)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
