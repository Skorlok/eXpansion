<?php

namespace ManiaLivePlugins\eXpansion\Maps\Structures;

/**
 * Structure mapWish
 *
 * @author Reaby
 */
class MapWish
{

    /** @var \Maniaplanet\DedicatedServer\Structures\Map */
    public $map;

    /** @var \ManiaLivePlugins\eXpansion\Helpers\PlayerStorage */
    public $player;

    /** @bool - For temporary additions */
    public $isTemp = false;

    /**
     * MapWish($player, $map, $isTemp);
     *
     * @param \ManiaLivePlugins\eXpansion\Helpers\PlayerStorage $player
     * @param \Maniaplanet\DedicatedServer\Structures\Map $map
     * @param bool $isTemp
     */
    public function __construct(\ManiaLivePlugins\eXpansion\Helpers\PlayerStorage $player, \Maniaplanet\DedicatedServer\Structures\Map $map, $isTemp = false)
    {
        $this->map = $map;
        $this->player = $player;
        $this->isTemp = $isTemp;
    }
}
