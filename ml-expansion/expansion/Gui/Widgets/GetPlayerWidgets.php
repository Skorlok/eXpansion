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
		
		$this->script = new Script("Gui\Scripts\GetPlayerWidgets");

        $action = $this->createAction(array(self::$parentPlugin, "showHudConfig"));
        $this->script->setParam("action", $action);

        $this->registerScript($this->script);
		
		$inputbox = new \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox("widgetStatus");
        $inputbox->setPosition(900, 900);
        $inputbox->setScriptEvents();
        $this->addComponent($inputbox);
    }
	
	public function eXpOnEndConstruct()
    {
        $this->setSize(0, 0);
        $this->setPosition(900, 900);
    }
}
