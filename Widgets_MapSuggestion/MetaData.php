<?php

namespace ManiaLivePlugins\eXpansion\Widgets_MapSuggestion;

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

        $this->setName("Widget: Suggest a map button");
        $this->setDescription("Map suggestion button");
        $this->setGroups(array('Widgets', 'Maps'));

        $config = Config::getInstance();

        $var = new TypeFloat("mapSuggestionButton_PosX", "Position of Map Suggestion Button X", $config, false, false);
        $var->setDefaultValue(-161);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("mapSuggestionButton_PosY", "Position of Map Suggestion Button Y", $config, false, false);
        $var->setDefaultValue(-45);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
