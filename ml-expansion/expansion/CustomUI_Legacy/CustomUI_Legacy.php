<?php

namespace ManiaLivePlugins\eXpansion\CustomUI_Legacy;

use ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Maniascript;

class CustomUI_Legacy extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var ManiaLink */
    private $widget;

    /** @var Script */
    private $script;

    public function eXpOnReady()
    {
        $this->script = new Script("CustomUI_Legacy\\Gui\\Script");

        $this->widget = new ManiaLink("CustomUI_Legacy\\Gui\\Customizer.xml");
        $this->widget->setName("Customizer");
        $this->widget->setLayer("normal");
        $this->widget->setScripts($this->script);

        $this->displayWidget();
    }

    protected function displayWidget()
    {
        if (!$this->widget instanceof ManiaLink) {
            return;
        }

        $code = "";

        foreach ($this->getMetaData()->getAllVariables() as $variable) {
            $varName = 'ClientUI.' . ucfirst($variable->getName());
            if ($variable instanceof Boolean) {
                $code .= $varName . ' = ' . (($variable->getRawValue()) ? 'True' : 'False') . ";\n";
            } else if ($variable instanceof TypeInt) {
                $code .= $varName . ' = ' . $variable->getRawValue() . ";\n";
            } else if ($variable instanceof BasicList) {
                $value = $variable->getRawValue();
                if (count($value) == 2) {
                    $code .= $varName . ".X" . ' = ' . Maniascript::convertType((float)$value[0]) . ";\n";
                    $code .= $varName . ".Y" . ' = ' . Maniascript::convertType((float)$value[1]) . ";\n";
                }
            }
        }

        $this->script->setParam('code', $code);

        $this->widget->show(null, true);
    }

    public function onSettingsChanged(Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->displayWidget();
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof ManiaLink) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;
    }
}
