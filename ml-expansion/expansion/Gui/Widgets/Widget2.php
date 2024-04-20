<?php

namespace ManiaLivePlugins\eXpansion\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Helper;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;

class Widget2 extends ManiaLink
{

    public function __construct($path, $pluginsRoot = false)
    {
        parent::__construct($path, $pluginsRoot);
        $this->registerScript(new Script("Gui\Scripts\PlainWidgetScript"));
    }

    /**
     * @param string $name The name of the parameter.
     * @param string $value The value
     */
    public function setParam($name, $value)
    {
        $this->$name = $value;
    }
}
