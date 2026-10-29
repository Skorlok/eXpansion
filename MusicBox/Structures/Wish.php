<?php

namespace ManiaLivePlugins\eXpansion\MusicBox\Structures;

class Wish
{

    public $song;
    public $player;

    public function __construct(Song $song, \ManiaLivePlugins\eXpansion\Helpers\PlayerStorage $player)
    {
        $this->song = $song;
        $this->player = $player;
    }
}
