<?php

namespace ManiaLivePlugins\eXpansion\Gears\Gui\Widgets;

class GearsWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    protected $lbl_stuntName;
    protected $lbl_description;
    protected $frame;
    protected $script;

    protected function eXpOnBeginConstruct()
    {
        $this->setAlign("center", "bottom");
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->addComponent($this->frame);

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gears/Gui/Script");
        $this->registerScript($script);
    }

    public function setLabels($name, $description)
    {
        $this->GearsWidget->setText($name);
    }
}
