<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

class Inputbox extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $name;

    protected $label;
    protected $button;

    public function __construct($name, $sizeX = 35, $editable = true)
    {
        $this->name = $name;



        if ($editable) {
            $this->button = new \ManiaLib\Gui\Elements\Entry($this->sizeX, 5);
            $this->button->setAttribute("class", "isTabIndex isEditable");
            $this->button->setAttribute("textformat", "default");
            $this->button->setName($this->name);
            $this->button->setId($this->name);
            $this->button->setDefault("");
            $this->button->setScriptEvents(true);
            $this->button->setTextSize(1);
            $this->button->setFocusAreaColor1("222");
            $this->button->setFocusAreaColor2("000");
        } else {
            $this->button = new \ManiaLib\Gui\Elements\Label($this->sizeX, 5);
            $this->button->setText("");
            $this->button->setTextSize(2);
        }

        $this->button->setAlign('left', 'center');
        $this->button->setTextColor('fff');
        $this->button->setPosition(2, -7, 0.05);
        $this->button->setSize($this->getSizeX() - 3, 4);
        $this->addComponent($this->button);



        $this->label = new \ManiaLib\Gui\Elements\Label(30, 3);
        $this->label->setAlign('left', 'top');
        $this->label->setTextSize(1);
        $this->label->setStyle("SliderVolume");
        $this->label->setTextColor('fff');
        $this->label->setTextEmboss();
        $this->addComponent($this->label);

        $this->setSize($sizeX, 12);
    }

    protected function onDraw()
    {
        $this->button->setSize($this->getSizeX() - 2, 5);
        $this->button->setPosition(1, 0);

        $this->label->setSize($this->getSizeX(), 3);
        $this->label->setPosition(1, 5);

        parent::onDraw();
    }

    public function getLabel()
    {
        return $this->label->getText();
    }

    public function setLabel($text)
    {
        $this->label->setText($text);
    }

    public function getText()
    {
        if ($this->button instanceof \ManiaLib\Gui\Elements\Entry) {
            return $this->button->getDefault();
        } else {
            return $this->button->getText();
        }
    }

    public function setText($text)
    {
        if ($this->button instanceof \ManiaLib\Gui\Elements\Entry) {
            $this->button->setDefault($text);
        } else {
            $this->button->setText($text);
        }
    }

    public function getName()
    {
        return $this->button->getName();
    }

    public function setName($text)
    {
        $this->button->setName($text);
    }

    public function setId($id)
    {
        $this->button->setId($id);
        $this->button->setScriptEvents();
    }

    public function setClass($class)
    {
        $this->button->setAttribute("class", "isTabIndex isEditable " . $class);
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        parent::destroy();
    }

    public static function getXML($name, $sizeX = 35, $editable = true, $label = null, $text = null, $id = null, $class = null)
    {
        if ($class) {
            $class = " " . $class;
        }
        
        $xml = '<frame>';
        if ($editable) {
            $xml .= '<entry posn="1 0 0.05" id="' . $name . '" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="" scriptevents="1" class="isTabIndex isEditable' . $class . '" textformat="default" textsize="1" textcolor="fff" focusareacolor1="222" focusareacolor2="000" name="' . ($id ? $id : $name) . '" default="' . $text . '"/>';
        } else {
            $xml .= '<label posn="1 0 0.05" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="TextStaticSmall" textsize="2" textcolor="fff" text="' . $text . '"/>';
        }
        $xml .= '<label posn="1 5 1.0E-5" sizen="' . $sizeX . ' 3" halign="left" valign="top" style="SliderVolume" textsize="1" textcolor="fff" textemboss="1" text="' . $label . '"/>';

        $xml .= '</frame>';
        
        return $xml;
    }
}
