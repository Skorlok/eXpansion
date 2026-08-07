<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

class WidgetButton
{
    public static function getWidgetButtonXML($sizeX, $sizeY, $action = null, $row0 = null, $row1 = null, $row2 = null)
    {
        $text = array();
        if ($row0 !== null) $text[] = $row0;
        if ($row1 !== null) $text[] = $row1;
        if ($row2 !== null) $text[] = $row2;
        return self::getXML($sizeX, $sizeY, $text, $action);
    }

    public static function getXML($sizeX, $sizeY, Array $text, $action = null)
    {
        $xml = '<frame posn="-5 0 -1">';
        $xml .= \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround::getXML($sizeX, $sizeY);
        $xml .= '</frame>';
        $xml .= '<quad posn="0 0 1" sizen="'. $sizeX . ' ' . $sizeY . '" halign="center" valign="top" bgcolor="0000" bgcolorfocus="fff6" ' . ($action ? 'action="' . $action . '"' : '') . ' scriptevents="1"/>';

        $y = 0.5;
        foreach ($text as $row) {
            $xml .= '<label posn="0 ' . (-($y * 3.2)) . ' 2.0E-5" sizen="' . ($sizeX - 2) . ' 3" halign="center" valign="center2" style="TextStaticSmall" textsize="1" textid="' . $row . '"/>';
            $y++;
        }

        return $xml;
    }
}
