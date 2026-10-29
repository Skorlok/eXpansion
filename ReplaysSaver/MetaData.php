<?php

namespace ManiaLivePlugins\eXpansion\ReplaysSaver;

use ManiaLive\PluginHandler\PluginHandler;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Replays Saver");
        $this->setDescription("Provides a way to save replays of the best records.");
        $this->setGroups(array('Records', 'Tools'));
    }

    public function checkOtherCompatibility()
    {
        $errors = parent::checkOtherCompatibility();

        /** @var PluginHandler $phandler */
        $phandler = PluginHandler::getInstance();

        if ($phandler->isLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            return $errors;
        }

        $errors[] = 'You need to enable the LocalRecords plugin to use the Save Replays plugin !';

        return $errors;
    }
}
