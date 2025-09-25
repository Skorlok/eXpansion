<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

class Inputbox
{
    public static function getXML($name, $sizeX = 35, $editable = true, $label = null, $text = null, $id = null, $class = null)
    {
        if ($class) {
            $class = " " . $class;
        }
        
        $xml = '<frame>';
        if ($editable) {
            $xml .= '<entry posn="1 0 0.05" id="' . ($id ? $id : $name) . '" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="" scriptevents="1" class="isTabIndex isEditable' . $class . '" textformat="default" textsize="1" textcolor="fff" focusareacolor1="222" focusareacolor2="000" name="' . $name . '" default="' . $text . '"/>';
        } else {
            $xml .= '<label posn="1 0 0.05" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="TextStaticSmall" textsize="2" textcolor="fff" text="' . $text . '"/>';
        }
        $xml .= '<label posn="1 5 1.0E-5" sizen="' . $sizeX . ' 3" halign="left" valign="top" style="SliderVolume" textsize="1" textcolor="fff" textemboss="1" text="' . $label . '"/>';

        $xml .= '</frame>';
        
        return $xml;
    }
}
