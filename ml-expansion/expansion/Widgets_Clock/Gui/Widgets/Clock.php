<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Clock\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

class Clock extends Widget
{
    public function eXpOnBeginConstruct()
    {
        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($edgeWidget);
        }
        
        $this->setName("Local time");

        $bg = new WidgetBackGround(16.5, 6);
        $this->addComponent($bg);
        
        $label = new \ManiaLive\Gui\Elements\Xml();
        $label->setContent('<label id="Time" posn="0 -1 1.0E-5" sizen="20 6" halign="left" valign="top" style="TextValueSmallSm" textsize="3"/>');
        $this->addComponent($label);

        $script = new Script("Widgets_Clock/Gui/Script");
        $this->registerScript($script);
    }
}
