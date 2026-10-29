<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Ratiobutton
{

    private static $script = null;

    /**
     * @param string $group      Group name — no underscores allowed
     * @param int    $index      0-based index within the group
     * @param string $entryName  Name attribute of the hidden entry (read by PHP in $options)
     * @param bool   $active     Initial selected state
     * @param int    $textWidth  Label width
     * @param string $text       Display text
     */
    public static function getXML($group, $index, $entryName, $active = false, $textWidth = 25, $text = "", $isTextId = false)
    {
        $colorize      = $active ? '0f0' : 'f00';
        $default       = $active ? '1'   : '0';

        $xml  = '<frame>';
        $xml .= '<quad id="eXp_RatioQ_' . $group . '_' . intval($index) . '" posn="0 -0.5 0" sizen="5 5" halign="center" valign="center" style="Icons64x64_1" substyle="GenericButton" scriptevents="1" colorize="' . $colorize . '"/>';
        $xml .= '<entry id="eXp_RatioE_' . $group . '_' . intval($index) . '" posn="4000 0 1.0E-5" sizen="20 4" style="" name="' . $entryName . '" default="' . $default . '"/>';
        if ($isTextId) {
            $xml .= '<label posn="4 0 2.0E-5" sizen="' . $textWidth . ' 6" halign="left" valign="center" style="TextCardInfoSmall" textsize="1" textid="' . $text . '"/>';
        } else {
            $xml .= '<label posn="4 0 2.0E-5" sizen="' . $textWidth . ' 6" halign="left" valign="center" style="TextCardInfoSmall" textsize="1" text="' . $text . '"/>';
        }
        $xml .= '</frame>';

        return $xml;
    }

    public static function getScriptML()
    {
        if (self::$script === null) {
            self::$script = new Script("Gui/Scripts/Ratiobutton");
        }
        return self::$script;
    }
}
