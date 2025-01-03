<?php

namespace ManiaLivePlugins\eXpansion\Maps;

class Config extends \ManiaLib\Utils\Singleton
{
    public $skipLeft = true;
    public $skipRight = false;
    public $bufferSize = 5;
    public $historySize = 7;
    public $showCurrentMapWidget = true;
    public $showNextMapWidget = true;
    public $showEndMatchNotices = true;
    public $showEndMatchNoticesJukebox = true;
    public $publicQueueAmount = array(0);
}
