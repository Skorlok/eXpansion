<?php

namespace ManiaLivePlugins\eXpansion\MusicBox;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
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
        $this->setName("Tools: Music Box");
        $this->setDescription("Provides custom musics loader for your server");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new Boolean("override", "Override all music on server, even if map has defined custom one ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeString("url", "Enter tracklist index.csv url for musicbox ", $config, false, false);
        $var->setDefaultValue("http://reaby.kapsi.fi/ml/musictest");
        $this->registerVariable($var);

        $var = new Boolean("disableJukebox", "Disable jukeboxing of music?", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new Boolean("shuffle", "Shuffle song list ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeFloat("musicWidget_PosX", "Position of Music Widget X", $config, false, false);
        $var->setDefaultValue(0);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("musicWidget_PosY", "Position of Music Widget Y", $config, false, false);
        $var->setDefaultValue(80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
