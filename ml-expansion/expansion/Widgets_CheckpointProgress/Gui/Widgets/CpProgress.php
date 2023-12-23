<?php

namespace ManiaLivePlugins\eXpansion\Widgets_CheckpointProgress\Gui\Widgets;

use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

class CpProgress extends Widget
{
    protected $storage;

    protected function eXpOnBeginConstruct()
    {
        $gauge1 = new \ManiaLive\Gui\Elements\Xml();
        $gauge1->setContent('<gauge id="totalProgress" sizen="160 7" halign="left" valign="top" style="EnergyBar" color="2f2" grading="1" ratio="0" drawbg="0" drawblockbg="1"/>');
        $this->addComponent($gauge1);

        $gauge2 = new \ManiaLive\Gui\Elements\Xml();
        $gauge2->setContent('<gauge id="myProgress" posn="0 -3 1.0E-5" sizen="160 7" halign="left" valign="top" style="EnergyBar" color="2af" grading="1" ratio="0" drawbg="0" drawblockbg="1"/>');
        $this->addComponent($gauge2);

        $this->storage = Storage::getInstance();
        $script = new Script("Widgets_CheckpointProgress\Gui\Scripts_Infos");
        $script->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
        $this->registerScript($script);
        $this->setName("Checkpoint progress Widget");
    }

    public function destroy()
    {
        $this->destroyComponents();
        $this->storage = null;
        parent::destroy();
    }
}
