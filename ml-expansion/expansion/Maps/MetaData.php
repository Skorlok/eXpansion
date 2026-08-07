<?php

namespace ManiaLivePlugins\eXpansion\Maps;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
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
        $this->setName("Maps: Generic Management and Jukebox");
        $this->setDescription("Provides common functions for maps, add,remove and list and jukebox");
        $this->setGroups(array('Maps'));

        $config = Config::getInstance();

        $var = new Boolean("skipLeft", "Skip jukeboxed map if requester left ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("skipRight", "Jukebox skipleft affect admins ?", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new SortedList("publicQueueAmount", "Jukeboxing costs", $config, false, false);
        $var->setGroup('Planets');
        $var->setType(new TypeInt("", "", null));
        $var->setDescription(
            array(
                "Set the amount a player has to pay for jukeboxing a map,",
                "the key is the planet id, and the value is the amount.",
                "If the number of maps in the queue is higher than the amount of values,",
                "jukeboxing won't be possible.",
                "If the value is set to 0, jukeboxing will be free for that planet.",
                "If the parameter is not set, jukeboxing will be free"
            )
        );
        $this->registerVariable($var);

        $var = new TypeInt("historySize", "Map History size", $config, false, false);
        $var->setGroup("Buffers");
        $var->setDefaultValue(7);
        $this->registerVariable($var);

        $var = new TypeInt("maxPlayerQueueSize", "Max amount of maps a player can queue", $config, false, false);
        $var->setGroup("Buffers");
        $var->setDefaultValue(1);
        $var->setDescription("Max amount of maps a player can queue, if set to -1, no limit");
        $this->registerVariable($var);

        $var = new Boolean("showCurrentMapWidget", "Show Now Playing map widget", $config, false, false);
        $var->setGroup("Widgets");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("showNextMapWidget", "Show next map widget", $config, false, false);
        $var->setGroup("Widgets");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("showEndMatchNotices", "Show end map notices", $config, false, false);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("showEndMatchNoticesJukebox", "Show end map notices if someone jukeboxed a map", $config, false, false);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeFloat("currentMapWidget_PosX", "Position of CurrentMap Widget X", $config, false, false);
        $var->setDefaultValue(-80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("currentMapWidget_PosY", "Position of CurrentMap Widget Y", $config, false, false);
        $var->setDefaultValue(61);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("nextMapWidget_PosX", "Position of NextMap Widget X", $config, false, false);
        $var->setDefaultValue(20);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("nextMapWidget_PosY", "Position of NextMap Widget Y", $config, false, false);
        $var->setDefaultValue(61);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
