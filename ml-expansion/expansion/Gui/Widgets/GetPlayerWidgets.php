<?php

namespace ManiaLivePlugins\eXpansion\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

class GetPlayerWidgets extends Widget
{
    protected $script;

    public static $parentPlugin;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->setName("GetPlayerWidgets");

        $action = $this->createAction(array(self::$parentPlugin, "showHudConfig"));

        $this->script = new Script("Gui\Scripts\GetPlayerWidgets");
        $this->script->setParam("action", $action);
        $this->registerScript($this->script);

        $entry = new \ManiaLive\Gui\Elements\Xml();
        $entry->setContent('<entry posn="0 900 0" id="widgetStatus" scriptevents="1" class="isTabIndex isEditable" name="widgetStatus" hidden="1"/>');
        $this->addComponent($entry);
    }
}
