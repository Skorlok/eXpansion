<?php

namespace ManiaLivePlugins\eXpansion\Endurance\Gui\Widgets;

class EnduroPanel2 extends PlainPanel
{

    protected function eXpOnBeginConstruct()
    {

        parent::eXpOnBeginConstruct();
        // $this->setName("LocalRecords Panel (Tab-layer)");
        $this->timeScript->setParam('varName', 'LocalTime2');
    }
}
