<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLive\Utilities\Time;

class RecItem extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $bg;

    public function __construct($indexNumber, $name, $score) {
        $this->sizeY = 3.5;
        $this->bg = new ListBackGround($indexNumber, 42, 3.5);
        $this->addComponent($this->bg);

        $xml = new \ManiaLive\Gui\Elements\Xml();
        $xml->setContent('<frame>
        <label sizen="10 6" scale="0.8" halign="left" valign="center" style="TextStaticSmall" text="' . ($indexNumber + 1) . '."/>
        <label posn="6 0 1.0" sizen="30 6" scale="0.8" halign="left" valign="center" style="TextStaticSmall" text="' . $name . '"/>
        <label posn="28 0 1.0" sizen="15 6" scale="0.8" halign="left" valign="center" style="TextStaticSmall" text="' . Time::fromTM($score) . '"/>
        </frame>');
        $this->addComponent($xml);
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /**
     * custom function to remove contents.
     */
    public function erase()
    {
        parent::destroy();
    }
}
