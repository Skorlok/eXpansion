<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Events;

interface Listener extends \ManiaLive\Event\Listener
{
    public function onEnduranceScoresUpdated($update);
    
    public function onEndurancePanelHide();
}
