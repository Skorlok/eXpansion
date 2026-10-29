<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Button
{

    protected static $counter = 0;
    protected static $script = null;

    public static function getScriptML()
    {
        if (self::$script === null) {
            self::$script = new Script("Gui/Scripts/Button");
        }
        return self::$script;
    }

    public static function getXML(
        $sizeX = 32,
        $sizeY = 6,
        $text = null,
        $active = null,
        $colorize = null,
        $textcolor = null,
        $value = null,
        $action = null,
        $manialink = null,
        $url = null,
        $id = null,
        $class = null,
        $attribute = null,
        $isTextId = false,
        $labelId = null
    )
    {
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
		$xml .='<quad id="' . ($id ? $id : "backGround_" . $buttonId) . '" sizen="' . ($sizeX+2) . ' ' . ($sizeY+1) . '" halign="left" valign="center2" style="Bgs1InRace" substyle="BgCard" ' . $action . 'scriptevents="1" ' . $url . $manialink . $class . $colorizeBackground . $attributeXml . '/>';
        if ($active) {
            $xml .= '<quad posn="-0.5 0 1" sizen="' . ($sizeX+3) . ' ' . ($sizeY+2.5) . '" halign="left" valign="center" style="Icons128x128_Blink" substyle="ShareBlink"/>';
        }
        if (!empty($text) || $labelId) {
            if ($labelId) {
                $labelIdAttr = 'id="' . $labelId . '" ';
            } elseif ($id) {
                $labelIdAttr = 'id="' . "eXp_ButtonLabel_" . $id . '" ';
            } else {
                $labelIdAttr = '';
            }
            if ($isTextId) {
                $xml .='<label ' . $labelIdAttr . 'posn="' . (($sizeX+2)/2) . ' 0 2" sizen="' . $sizeX . ' ' . ($sizeY-2) . '" halign="center" valign="center2" style="TextValueSmallSm" textsize="2" ' . $textcolor . 'textemboss="1" textid="' . $text . '"/>';
            } else if (!empty($text)) {
                $xml .='<label ' . $labelIdAttr . 'posn="' . (($sizeX+2)/2) . ' 0 2" sizen="' . $sizeX . ' ' . ($sizeY-2) . '" halign="center" valign="center2" style="TextValueSmallSm" textsize="2" ' . $textcolor . 'textemboss="1" text="' . $text . '"/>';
            } else {
                $xml .='<label ' . $labelIdAttr . 'posn="' . (($sizeX+2)/2) . ' 0 2" sizen="' . $sizeX . ' ' . ($sizeY-2) . '" halign="center" valign="center2" style="TextValueSmallSm" textsize="2" ' . $textcolor . 'textemboss="1"/>';
            }
        }

        $xml .='</frame>';

        return $xml;
    }
}
