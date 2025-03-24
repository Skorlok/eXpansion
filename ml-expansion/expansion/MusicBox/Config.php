<?php

namespace ManiaLivePlugins\eXpansion\MusicBox;

class Config extends \ManiaLib\Utils\Singleton
{
    public $url = "http://reaby.kapsi.fi/ml/musictest";
    public $override = true;
    public $disableJukebox = false;
    public $shuffle = true;

    public $musicWidget_PosX = 0;
    public $musicWidget_PosY = 80;
}
