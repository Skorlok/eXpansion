<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Events;

class Event extends \ManiaLive\Event\Event
{
    const ON_SCORE_UPDATED = 0x1;

    const HIDE_PANEL = 0x2;

    protected $params;

    public function __construct()
    {
        $params = func_get_args();
        $onWhat = array_shift($params);
        parent::__construct($onWhat);
        $this->params = $params;
    }

    public function fireDo($listener)
    {
        $p = $this->params;

        switch ($this->onWhat) {
            case self::ON_SCORE_UPDATED:
                $listener->onEnduranceScoresUpdated();
                break;
            case self::HIDE_PANEL:
                $listener->onEndurancePanelHide();
                break;
        }
    }
}
