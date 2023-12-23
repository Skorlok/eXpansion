<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Config;

class ColorChooser extends Control implements \ManiaLivePlugins\eXpansion\Gui\Structures\ScriptedContainer
{
    /** @var int */
    protected static $counter = 0;

    /** @var \ManiaLivePlugins\eXpansion\Gui\Structures\Script */
    protected static $script = null;

    /**
     *
     * @param string $inputboxName
     * @param int $sizeX
     * @param int $digits
     * @param bool $hasPrefix
     */
    public function __construct($inputboxName, $sizeX = 35, $digits = 3, $hasPrefix = true, $color = null)
    {
        $config = Config::getInstance();

        if (self::$script == null) {
            self::$script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\ColorScript();
        }

        $buttonId = self::$counter++;
        if (self::$counter > 100000) {
            self::$counter = 0;
        }

        $openButtonColor = ($color != null) ? (ltrim($color, '$')) : ('000');
        $openButton = new \ManiaLive\Gui\Elements\Xml();
        $openButton->setContent('<quad id="preview_' . $buttonId . '" posn="0 0 5" sizen="4 4" halign="left" valign="center" bgcolor="' . $openButtonColor . '" scriptevents="1" class="colorchooser"/>');
        $this->addComponent($openButton);

        $inputbox = new Inputbox($inputboxName, $sizeX - 4, true);
        $inputbox->setPosition(4, 0);
        $inputbox->setId("output_" . $buttonId);
        $inputbox->setClass("color_input");
        if ($color != null) {
            $inputbox->setText($color);
        }
        $this->addComponent($inputbox);

        $frame = new \ManiaLive\Gui\Elements\Xml();
        $frame->setContent('<frame posn="6 4 10">
            <quad id="bg_' . $buttonId . '" posn="-2 2 0" sizen="64 42" bgcolor="222" hidden="1" class="colorSelection"/>
            <quad id="chooser_' . $buttonId . '" posn="0 0 1.0E-5" sizen="32 32" halign="left" valign="top" image="' . $config->colorPreview . '" scriptevents="1" hidden="1" class="colorSelection"/>
            <quad id="hue_' . $buttonId . '" posn="36 0 2.0E-5" sizen="4 32" halign="left" valign="top" image="' . $config->colorHue . '" scriptevents="1" hidden="1" class="colorSelection"/>
            <quad id="selectionBox_' . $buttonId . '" posn="0 0 3.0E-5" sizen="2 2" halign="center" valign="center" style="Bgs1InRace" substyle="BgColorContour" scriptevents="1" hidden="1" class="colorSelection" colorize="000"/>
            <quad id="selectionBoxHue_' . $buttonId . '" posn="36 0 4.0E-5" sizen="8 3" scale="0.5" halign="left" valign="center" style="Bgs1InRace" substyle="BgColorContour" scriptevents="1" hidden="1" class="colorSelection" colorize="fff"/>
            <frame posn="48 0 5.0E-5">
                <frame>
                    <entry id="h_' . $buttonId . '" sizen="8 6" style="" scriptevents="1" hidden="1" class="colorSelection" default="h"/>
                    <entry id="s_' . $buttonId . '" posn="0 -7 1.0E-5" sizen="8 6" style="" scriptevents="1" hidden="1" class="colorSelection" default="s"/>
                    <entry id="v_' . $buttonId . '" posn="0 -14 2.0E-5" sizen="8 6" style="" scriptevents="1" hidden="1" class="colorSelection" default="v"/>
                    <quad id="ok_' . $buttonId . '" posn="0 -21 3.0E-5" sizen="8 8" style="Icons64x64_1" substyle="Save" scriptevents="1" hidden="1" class="colorSelection"/>
                    <quad id="cancel_' . $buttonId . '" posn="0 -30 4.0E-5" sizen="8 8" style="Icons64x64_1" substyle="Refresh" scriptevents="1" hidden="1" class="colorSelection"/>
                    <entry id="v_' . $buttonId . '" posn="0 -39 5.0E-5" sizen="8 6" style="" hidden="1" class="colorSelection" default="v"/>
                    <entry id="settings_' . $buttonId . '" posn="0 -46 6.0E-5" sizen="8 6" style="" hidden="1" class="colorSelection" default="' . ($hasPrefix ? 1 : 0) . "," . $digits . '"/>
                </frame>
            </frame>
        </frame>');
        $this->addComponent($frame);
    }

    public function getScript()
    {
        return self::$script;
    }
}
