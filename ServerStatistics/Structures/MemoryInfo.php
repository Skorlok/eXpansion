<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Structures;

/**
 * Description of MemoryInfo
 *
 * @author Reaby
 */
class MemoryInfo extends \DedicatedApi\Structures\AbstractStructure {

    public $total;
    public $free;

    public function __construct($total, $free) {
        $this->total = $total;
        $this->free = $free;
    }

}
