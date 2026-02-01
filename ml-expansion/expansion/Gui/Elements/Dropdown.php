<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;

class Dropdown extends \ManiaLivePlugins\eXpansion\Gui\Control implements \ManiaLivePlugins\eXpansion\Gui\Structures\ScriptedContainer
{

    protected $items = array();
    protected $dropdown;
    protected $output;
    protected $frame;
    protected $label;
    protected $xml;
    protected $values;
    protected $name;
    protected $selectedIndex;

    /** @var \ManiaLivePlugins\eXpansion\Gui\Structures\Script */
    private $script = null;

    public function __construct($name, $items = array("initial"), $selectedIndex = 0, $sizeX = 35)
    {
        if (!is_array($items)) {
            throw new \Exception("Dropdown constructor needs array of values");
        }

        $this->script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\DropDownScript();
        $this->script->setParam("name", $name);

        $this->name = $name;
        $this->setSize($sizeX, 10);

        $this->output = new \ManiaLib\Gui\Elements\Entry($sizeX, 6);
        $this->output->setName($name);
        $this->output->setTextColor('000');
        $this->output->setTextSize(1);
        $this->output->setId($name . "e");
        $this->output->setScriptEvents(true);
        $this->output->setPosition(1000, 1000);
        $this->addComponent($this->output);

        $this->label = new \ManiaLib\Gui\Elements\Label($sizeX, 4);
        $this->label->setId($name . 'l');
        $this->label->setStyle("TextValueMedium");
        $this->label->setTextSize(1);
        $this->label->setFocusAreaColor1('000');
        $this->label->setFocusAreaColor2('3af');
        $this->label->setBgcolor("000");
        $this->label->setBgcolorFocus("3af");
        $this->label->setAlign('left', 'center');
        $this->label->setScriptEvents(true);
        $this->label->setPosZ(4);
        $this->addComponent($this->label);

        $this->frame = new \ManiaLive\Gui\Controls\Frame(0, 0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Flow($sizeX, 6));
        $this->frame->setScriptEvents(true);
        $this->frame->setId($name . "f");
        $this->frame->setAlign("center", "center");
        $this->frame->setSizeY((sizeof($items) + 1) * 6);
        $this->frame->setScale(0.9);

        $this->addItems($items);
        $this->addComponent($this->frame);
        $this->setSelected($selectedIndex);
    }

    public function addItems($items)
    {
        $this->values = array();
        $this->items = array();
        foreach ($items as $item) {
            $x = count($this->items);
            $obj = new \ManiaLib\Gui\Elements\Label($this->sizeX);
            $obj->setText($item);
            $obj->setAlign("left", "center");
            $obj->setStyle("TextValueMedium");
            $obj->setScriptEvents(true);
            $obj->setTextSize(1);
            $obj->setFocusAreaColor1('000');
            $obj->setFocusAreaColor2('3af');
            $obj->setBgcolor("000");
            $obj->setBgcolorFocus("3af");
            $obj->setId($this->name . $x);
            $obj->setPosZ($this->getPosZ() + 5);
            $this->items[$x] = $obj;
            $this->frame->addComponent($this->items[$x]);
            $this->values[$x] = $item;
            $x++;

            $this->script->setParam("values", $this->values);
        }
    }

    public function setSelected($index)
    {
        $this->selectedIndex = $index;
        $this->label->setText($this->values[intval($index)]);
        $this->script->setParam("selected", intval($index));
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function getScript()
    {
        return $this->script;
    }

    public static function getXML($callerClass, $name, $items = array("initial"), $selectedIndex = 0, $sizeX = 35)
    {
        $xml = '<frame>
            <entry id="' . $name . 'e" posn="1000 1000 0" sizen="' . $sizeX . ' 6" style="" scriptevents="1" textsize="1" textcolor="000" name="' . $name . '"/>
            <label id="' . $name . 'l" posn="0 0 4" sizen="' . $sizeX . ' 4" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $items[$selectedIndex] . '" focusareacolor1="000" focusareacolor2="3af"/>
            <frame posn="0 5.4 2" scale="0.9" id="' . $name . 'f">';

        foreach ($items as $index => $item) {
            $xml .= '<label id="' . $name . $index . '" posn="0 -' . ($index*7) . ' 5" sizen="' . $sizeX . ' 7" halign="left" valign="center" bgcolor="000" bgcolorfocus="3af" scriptevents="1" textsize="1" text="' . $item . '" focusareacolor1="000" focusareacolor2="3af"/>';
        }

        $xml .='</frame>
        </frame>';

        $script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\DropDownScript();
        $script->setParam("name", $name);
        $script->setParam("values", $items);
        $script->setParam("selected", $selectedIndex);
        if ($callerClass instanceof Widget) {
            $callerClass->registerScript($script);
        } else {
            return array($xml, $script);
        }

        return $xml;
    }
}
