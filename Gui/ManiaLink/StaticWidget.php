<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;

class StaticWidget extends ManiaLink
{

    protected $userScript;
    protected $widgetScript;
    protected $scripts;
    protected $eXpWidgetScript;

    public function __construct($path, $pluginsRoot = false)
    {
        parent::__construct($path, $pluginsRoot = false);

        $this->userScript = array();
        $this->scripts = array('declarationScript' => "", 'whileLoopScript' => "", 'libScript' => "", 'endDeclarationScript' => "");

        $this->eXpWidgetScript = new Script('Gui\Scripts\templateStaticWidgetScript');
        $this->registerScript($this->eXpWidgetScript);
    }

    // For users to add their own elements
    
    public function registerScript(Script $script)
    {
        $this->userScript[] = $script;
    }

    // Others

    protected function getMlScripts()
    {
        /** @var guiConfig $guiConfig */
        $guiConfig = guiConfig::getInstance();

        $this->scripts = array('declarationScript' => "", 'whileLoopScript' => "", 'libScript' => "", 'endDeclarationScript' => "");

        $this->eXpWidgetScript->setParam("name", $this->getWidgetName());
        $this->eXpWidgetScript->setParam("gameMode", $this->storage->getCleanGamemodeName());
        $this->eXpWidgetScript->setParam("activeLayer", $this->getLayer());
        $this->eXpWidgetScript->setParam("visibleLayerInit", $this->getLayer());
        $this->eXpWidgetScript->setParam("forceReset", $this->getBoolean(DEBUG));
        $this->eXpWidgetScript->setParam('disablePersonalHud', $guiConfig->disablePersonalHud ? 'True' : 'False');
        $this->eXpWidgetScript->setParam("posX", $this->eXpWidgetScript->getNumber($this->getPosX()));
        $this->eXpWidgetScript->setParam("posY", $this->eXpWidgetScript->getNumber($this->getPosY()));

        foreach ($this->userScript as $userScript) {
            $this->scripts['declarationScript'] .= $userScript->getDeclarationScript();
            $this->scripts['endDeclarationScript'] .= $userScript->getEndScript();
            $this->scripts['whileLoopScript'] .= $userScript->getWhileLoopScript();
            $this->scripts['libScript'] .= $userScript->getlibScript();
        }

        foreach ($this->overrideScripts as $overrideScript) {
            $this->scripts['declarationScript'] .= $overrideScript->getDeclarationScript();
            $this->scripts['endDeclarationScript'] .= $overrideScript->getEndScript();
            $this->scripts['whileLoopScript'] .= $overrideScript->getWhileLoopScript();
            $this->scripts['libScript'] .= $overrideScript->getlibScript();
        }

        foreach ($this->elementsScript as $elementScript) {
            $this->scripts['declarationScript'] .= $elementScript->getDeclarationScript();
            $this->scripts['endDeclarationScript'] .= $elementScript->getEndScript();
            $this->scripts['whileLoopScript'] .= $elementScript->getWhileLoopScript();
            $this->scripts['libScript'] .= $elementScript->getlibScript();
        }

        $this->widgetScript->setParam("dDeclares", $this->scripts['declarationScript'] . $this->scripts['endDeclarationScript']);
        $this->widgetScript->setParam("scriptLib", $this->scripts['libScript']);
        $this->widgetScript->setParam("wLoop", $this->scripts['whileLoopScript']);

        return $this->widgetScript->getDeclarationScript();
    }
}
