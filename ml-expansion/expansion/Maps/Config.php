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

    public $currentMapWidget_PosX = -80;
    public $currentMapWidget_PosY = 61;

    public $nextMapWidget_PosX = 20;
    public $nextMapWidget_PosY = 61;
}
