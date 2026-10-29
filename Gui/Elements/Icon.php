<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Icon
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
        $description = null,
        $descSizeX = null,
        $descSizeY = null,
        $descLines = null,
        $colorize = null,
        $value = null,
        $action = null,
        $manialink = null,
        $url = null,
        $icon = null,
        $iconSub = null,
        $id = null,
        $class = null,
        $attribute = null,
        $isTextId = false
    )
    {
        $buttonId = self::$counter++;
        if (self::$counter > 100000) {
            self::$counter = 0;
        }

        $class = ($class ? 'class="' . $class . '" ' : '');
        $action = ($action ? 'action="' . $action . '" ' : '');
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
		if ($description !== null) {
            if (is_array($description)) {
                $maxLine = count($description);
                $description = implode("&#10;", $description);
            } else {
                $maxLine = ($descLines !== null ? $descLines : 1);
            }
            $sizeXDesc = ($descSizeX !== null ? $descSizeX : 30);
            $sizeYDesc = ($descSizeY !== null ? $descSizeY : 5 * $maxLine);
            $xml .='<frame posn="0 0 10" class="exp_button">';
            if ($isTextId) {
                $xml .='<label id="eXp_ButtonDescText_' . ($id ? $id : "Icon_" . $buttonId) . '" posn="7 3 5" sizen="' . $sizeXDesc . ' ' . $sizeYDesc . '" halign="left" valign="center2" style="TextStaticSmall" hidden="1" textcolor="000" textid="' . $description . '" maxline="' . $maxLine . '"/>';
            } else {
                $xml .='<label id="eXp_ButtonDescText_' . ($id ? $id : "Icon_" . $buttonId) . '" posn="7 3 5" sizen="' . $sizeXDesc . ' ' . $sizeYDesc . '" halign="left" valign="center2" style="TextStaticSmall" hidden="1" textcolor="000" text="' . $description . '" maxline="' . $maxLine . '"/>';
            }
            $xml .='<quad id="eXp_ButtonDescBg_' . ($id ? $id : "Icon_" . $buttonId) . '" posn="5 3 1" sizen="' . ($sizeXDesc+4) . ' ' . $sizeYDesc . '" halign="left" valign="center" style="Bgs1" substyle="BgMetalBar" hidden="1" colorize="fff"/>';
            $xml .='</frame>';
        }

        if ($icon) {
            $colorizeIcon = ($colorize ? 'colorize="' . $colorize . '" ' : '');
            if ($iconSub !== null) {
                $iconXml = 'style="' . $icon . '" ' . (isset($iconSub) ? 'substyle="' . $iconSub . '" ' : '');
            } else {
                $iconXml = 'image="' . $icon . '" ';
            }
            $xml .='<quad id="' . ($id ? $id : "Icon_" . $buttonId) . '" sizen="' . ($sizeX+2) . ' ' . ($sizeY+2) . '" halign="left" valign="center" ' . $iconXml . $action . $colorizeIcon . $url . $manialink . $class . $attributeXml . 'scriptevents="1"/>';
        }

        $xml .='</frame>';

        return $xml;
    }
}
