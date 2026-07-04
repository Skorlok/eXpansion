<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;

/**
 * Description of CheckboxScripted
 *
 * @author De Cramer Oliver
 */
class CheckboxScripted
{

    private static $counter = 0;
    private static $script = null;

    public static function getScriptML()
    {
        if (self::$script === null) {
            self::$script = new \ManiaLivePlugins\eXpansion\Gui\Scripts\CheckboxScript();
        }
        return self::$script;
    }

    public static function getXML($name, $active = false, $textWidth = 25, $enabled = true, $text = "", $isTextId = false)
    {
        $id = self::$counter++;
        if (self::$counter > 100000) {
            self::$counter = 0;
        }

        if (!$enabled) {
            $colorize = $active ? '7f7' : 'f77';
        } else {
            $colorize = $active ? '0f0' : 'f00';
        }

        $xml  = '<frame>';
        $xml .= '<quad id="eXp_CheckboxQ_' . $id . '" sizen="5 5" halign="left" valign="center2" style="Icons64x64_1" substyle="GenericButton" scriptevents="1" colorize="' . $colorize . '"/>';
        $xml .= '<entry id="eXp_CheckboxE_' . $id . '" posn="4000 0 1.0E-5" sizen="20 4" style="" scriptevents="1" name="' . $name . '" default="' . ($active ? '1' : '0') . '"/>';
        if ($isTextId) {
            $xml .= '<label posn="5 0 2.0E-5" sizen="' . $textWidth . ' 5" scale="1.1" halign="left" valign="center" style="TextCardInfoSmall" textsize="1" textcolor="fff" textid="' . $text . '"/>';
        } else {
            $xml .= '<label posn="5 0 2.0E-5" sizen="' . $textWidth . ' 5" scale="1.1" halign="left" valign="center" style="TextCardInfoSmall" textsize="1" textcolor="fff" text="' . $text . '"/>';
        }
        $xml .= '</frame>';

        return $xml;
    }
}
