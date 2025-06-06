<?php

namespace ManiaLivePlugins\eXpansion\MapRatings;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
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
        $this->setName("Maps: Maps Ratings");
        $this->setDescription("Provides ratings for maps");
        $this->setGroups(array('Maps'));

        $config = Config::getInstance();

        $var = new Boolean("sendBeginMapNotices", "Send Map ratings messages at begin of map ?", $config, true, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("showPodiumWindow", "Show map ratings widget at podium ?", $config, true, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new BoundedTypeInt("minVotes", "Map Autoremoval required minimum votes (min: 5)", $config, true, false);
        $var->setGroup("Voting");
        $var->setMin(5);
        $var->setDefaultValue(10);
        $this->registerVariable($var);

        $var = new BoundedTypeInt("removeTresholdPercentage", "Map ratings autoremove percentage", $config, true, false);
        $var->setDescription("%-value for autoremove treshold (min: 10, max:60)");
        $var->setGroup("Voting");
        $var->setMin(10);
        $var->setMax(60);
        $var->setDefaultValue(30);
        $this->registerVariable($var);

        $var = new TypeInt("karmaRequireFinishes", "number of finishes before being able to vote", $config, true, false);
        $var->setDescription('number of times a player should have finished a Map before being allowed to karma vote for it. $f00REQUIRE PLUGIN LOCALRECORDS !!!');
        $var->setDefaultValue(0);
        $this->registerVariable($var);


        $var = new Boolean("mxKarmaEnabled", "Use mxKarma ?", $config, false, false);
        $var->setGroup("MXKarma");
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new TypeString("mxKarmaServerLogin", "MxKarma serverlogin", $config, false, false);
        $var->setGroup("MXKarma");
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $var = new TypeString("mxKarmaApiKey", 'MxKarma apikey, $l[http://karma.mania-exchange.com]click this text to register$l', $config, false, false);
        $var->setDescription('For apikey: click the header or visit http://karma.mania-exchange.com');
        $var->setGroup("MXKarma");
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $var = new TypeFloat("mapRating_PosX", "Position of MapRating Widget X", $config, false, false);
        $var->setDefaultValue(128);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("mapRating_PosY", "Position of MapRating Widget Y", $config, false, false);
        $var->setDefaultValue(75);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("mapRating_PosX_Shootmania", "Position of MapRating Widget X (Shootmania)", $config, false, false);
        $var->setDefaultValue(38);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("mapRating_PosY_Shootmania", "Position of MapRating Widget Y (Shootmania)", $config, false, false);
        $var->setDefaultValue(90);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("endMapRating_PosX", "Position of EndMapRating Widget X", $config, false, false);
        $var->setDefaultValue(-45);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("endMapRating_PosY", "Position of EndMapRating Widget Y", $config, false, false);
        $var->setDefaultValue(-42);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $this->setRelaySupport(false);
    }
}
