<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;

class Widget extends ManiaLink
{

    protected $userScript;
    protected $widgetScript;
    protected $scripts;
    protected $eXpWidgetScript;
    protected $axisDisabled;
    protected $size;

    public function __construct($path)
    {
        parent::__construct($path);
        $this->maniaLinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "Gui\ManiaLink\Widget.xml";
        $this->widgetScript = new Script("Gui\Scripts\PlainManialinkScript");

        $this->userScript = array();
        $this->scripts = array('declarationScript' => "", 'whileLoopScript' => "", 'libScript' => "", 'endDeclarationScript' => "");
        $this->axisDisabled = "";

        $this->eXpWidgetScript = new Script("Gui\Scripts\\templateWidgetScript");
        $this->eXpWidgetScript->setParam('disablePersonalHud', guiConfig::getInstance()->disablePersonalHud ? 'True' : 'False');
        $this->registerScript($this->eXpWidgetScript);
        $this->size = array(0, 0);
    }

    public function getSizeX()
    {
        return $this->size[0];
    }

    public function getSizeY()
    {
        return $this->size[1];
    }

    public function setSize($x, $y)
    {
        $this->size = array($x, $y);
    }

    public function setDisableAxis($axis)
    {
        $this->axisDisabled = $axis;
    }

    // For users to add their own elements
    
    public function registerScript(Script $script)
    {
        $this->userScript[] = $script;
    }

    // Others

    protected function getMlScripts()
    {
        $this->scripts = array('declarationScript' => "", 'whileLoopScript' => "", 'libScript' => "", 'endDeclarationScript' => "");

        $this->eXpWidgetScript->setParam("name", $this->getWidgetName());
        $this->eXpWidgetScript->setParam("axisDisabled", $this->axisDisabled);
        $this->eXpWidgetScript->setParam("gameMode", $this->storage->getCleanGamemodeName());
        $this->eXpWidgetScript->setParam("activeLayer", $this->getLayer());
        $this->eXpWidgetScript->setParam("visibleLayerInit", $this->getLayer());
        $this->eXpWidgetScript->setParam("forceReset", $this->getBoolean(DEBUG));

        foreach ($this->userScript as $userScript) {
            $this->scripts['declarationScript'] .= $userScript->getDeclarationScript($this, false);
            $this->scripts['endDeclarationScript'] .= $userScript->getEndScript($this, false);
            $this->scripts['whileLoopScript'] .= $userScript->getWhileLoopScript($this, false);
            $this->scripts['libScript'] .= $userScript->getlibScript($this, false);
        }

        $this->widgetScript->setParam("dDeclares", $this->scripts['declarationScript'] . $this->scripts['endDeclarationScript']);
        $this->widgetScript->setParam("scriptLib", $this->scripts['libScript']);
        $this->widgetScript->setParam("wLoop", $this->scripts['whileLoopScript']);

        return $this->widgetScript->getDeclarationScript(false, false);
    }
}
