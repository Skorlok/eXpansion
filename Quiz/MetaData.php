<?php

namespace ManiaLivePlugins\eXpansion\Quiz;

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
        $this->setName("Games: Quiz");
        $this->setDescription(
            "Run a Questionnaire powered by questions made up by players, requires gd2 for image support."
        );
        $this->setGroups(array('Games'));

        $config = Config::getInstance();

        $var = new TypeFloat("quizImage_PosX", "Position of Quiz Widget X", $config, false, false);
        $var->setDefaultValue(-152);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("quizImage_PosY", "Position of Quiz Widget Y", $config, false, false);
        $var->setDefaultValue(80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
