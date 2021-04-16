<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\DonatePanel;

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
        $this->setName("Widget: Donates panel");
        $this->setDescription("Donates for players to send for server or eachother");
        $this->setGroups(array('Widgets'));
        $config = Config::getInstance();
        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt(
            "donateAmountForGlobalMsg",
            "Treshold to show public message on donation",
            $config,
            false,
            true
        );
        $var->setGroup("Planets");
        $var->setDefaultValue(500);
        $this->registerVariable($var);
    }
}
