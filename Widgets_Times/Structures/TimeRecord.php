<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times\Structures;

class TimeRecord extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{
    /** @var string */
    public $login;

    /** @var int */
    public $time;

    /** @var int */
    public $rank = -1;

    /** @var string */
    public $checkpoints = "[ -1 ]";

    /**
     * @param string $login
     * @param int $time
     * @param int $rank
     * @param array|string $checkpoints
     */
    public function __construct($login, $time, $rank = -1, $checkpoints = array())
    {
        $this->login = $login;
        $this->rank = intval($rank);
        $this->time = intval($time);

        if (is_string($checkpoints)) {
            $this->checkpoints = '[' . $checkpoints . ']';
        } else {
            // Normally all CP even last one should be in the object,
            // but not in databases imported from XAseco where last CP is missing.
            if ($checkpoints[count($checkpoints) - 1] != $this->time) {
                $checkpoints[] = $this->time;
            }
            $this->checkpoints = "[" . implode(",", $checkpoints) . "]";
        }
    }
}
