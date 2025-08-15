<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

class Button
{

    protected static $counter = 0;
    protected static $script = null;

    public static function getScriptML()
    {
        return self::$script;
    }

    public static function getXML(
        $sizeX = 32,
        $sizeY = 6,
        $text = null,
        $description = null,
        $active = null,
        $colorize = null,
        $textcolor = null,
        $value = null,
        $action = null,
        $manialink = null,
        $url = null,
        $icon = null,
        $id = null,
        $class = null,
        $attribute = null
    )
    {
        if (self::$script === null) {
            self::$script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\ButtonScript();
        }

        /** @var Config $config */
        $config = Config::getInstance();
        $buttonId = self::$counter++;
        if (self::$counter > 100000) {
            self::$counter = 0;
        }

        if ($colorize && !$textcolor) {
            $textcolor = 'fff';
        }

        $colorizeBackground = 'colorize="' . ($colorize ? $colorize : $config->buttonBackgroundColor) . '" ';
        $class = ($class ? 'class="' . $class . '" ' : '');
        $action = ($action ? 'action="' . $action . '" ' : '');
        $textcolor = 'textcolor="' . ($textcolor ? $textcolor : $config->buttonTitleColor) . '" ';
        $url = ($url ? 'url="' . $url . '" ' : '');
        $manialink = ($manialink ? 'manialink="' . $manialink . '" ' : '');
        $attributeXml = '';
        if (is_array($attribute)) {
            foreach ($attribute as $key => $value) {
                if ($key != null) {
                    $attributeXml .= ' ' . $key . '="' . $value . '"';
                }
            }
        }

        $xml = '<frame scale="0.75">';
		if (!$icon) {
            $xml .='<quad id="' . ($id ? $id : "backGround_" . $buttonId) . '" sizen="' . ($sizeX+2) . ' ' . ($sizeY+1) . '" halign="left" valign="center2" style="Bgs1InRace" substyle="BgCard" ' . $action . 'scriptevents="1" ' . $url . $manialink . $class . $colorizeBackground . $attributeXml . '/>';
        }
        if ($active) {
            $xml .= '<quad posn="-0.5 0 1" sizen="' . ($sizeX+3) . ' ' . ($sizeY+2.5) . '" halign="left" valign="center" style="Icons128x128_Blink" substyle="ShareBlink"/>';
        }
        if (!empty($text)) {
            $xml .='<label ' . ($id ? 'id="' . "eXp_ButtonLabel_" . $id . '" ' : '') . 'posn="' . (($sizeX+2)/2) . ' 0 2" sizen="' . $sizeX . ' ' . ($sizeY-2) . '" halign="center" valign="center2" style="TextValueSmallSm" textsize="2" ' . $textcolor . 'textemboss="1" text="' . $text . '"/>';
        }

        if (is_array($description)) {
            if (is_array($description[0])) {
                $maxLine = count($description[0]);
                $description[0] = implode("&#10;", $description[0]);
            } else {
                $maxLine = (isset($description[3]) ? $description[3] : 1);
            }
            $sizeXDesc = (isset($description[1]) ? $description[1] : 30);
            $sizeYDesc = (isset($description[2]) ? $description[2] : 5 * $maxLine);
            $xml .='<frame posn="0 0 10" class="exp_button">';
            $xml .='<label id="eXp_ButtonDescText_' . ($id ? $id : "Icon_" . $buttonId) . '" posn="7 3 5" sizen="' . $sizeXDesc . ' ' . $sizeYDesc . '" halign="left" valign="center2" style="TextStaticSmall" hidden="1" text="$000' . $description[0] . '" maxline="' . $maxLine . '"/>';
            $xml .='<quad id="eXp_ButtonDescBg_' . ($id ? $id : "Icon_" . $buttonId) . '" posn="5 3 1" sizen="' . ($sizeXDesc+4) . ' ' . $sizeYDesc . '" halign="left" valign="center" style="Bgs1" substyle="BgMetalBar" hidden="1" colorize="fff"/>';
            $xml .='</frame>';
        }

        if ($icon) {
            $colorizeIcon = ($colorize ? 'colorize="' . $colorize . '" ' : '');
            if (is_array($icon)) {
                $iconXml = 'style="' . $icon[0] . '" ' . (isset($icon[1]) ? 'substyle="' . $icon[1] . '" ' : '');
            } else {
                $iconXml = 'image="' . $icon . '" ';
            }
            $xml .='<quad id="' . ($id ? $id : "Icon_" . $buttonId) . '" sizen="' . ($sizeX+2) . ' ' . ($sizeY+2) . '" halign="left" valign="center" ' . $iconXml . $action . $colorizeIcon . $url . $manialink . $class . $attributeXml . 'scriptevents="1"/>';
        }

        $xml .='</frame>';

        return $xml;
    }
}
