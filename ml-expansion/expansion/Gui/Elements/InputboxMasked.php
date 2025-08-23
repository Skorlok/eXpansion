<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Storage as eXpStorage;

class InputboxMasked
{
    public static function getXML($widgetClass, $name, $sizeX = 35, $editable = true, $label = null, $text = null, $showClearText = null, $id = null, $class = null)
    {
        // Debug for ManiaPlanet 4: attribute require capital P, otherwise the password is shown in clear text.
        // I want to keep a backward compatibility with MP3
        $passwordAttribute = (substr(eXpStorage::getInstance()->version->build, 0, 4) >= 2017) ? 'Password' : 'password';
        
        if ($class) {
            $class = " " . $class;
        }
        
        if ($editable) {
            $xml = '<entry id="' . $name . '" posn="1 -7 0" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="" scriptevents="1" class="isTabIndex isEditable' . $class . '" textformat="' . $passwordAttribute . '" textsize="1" textcolor="fff" focusareacolor1="222" focusareacolor2="000" name="' . ($id ? $id : $name) . '" default="' . $text . '"/>';
            
            $script = new Script("Gui/Scripts/InputboxMasked");
            $script->setParam("btName", $name);
            $widgetClass->registerScript($script);
        } else {
            $xml = '<label posn="1 -7 0.05" sizen="' . ($sizeX-2) . ' 5" halign="left" valign="center" style="TextStaticSmall" textsize="1.5" textcolor="fff" text="' . str_repeat("*", strlen($text)) . '"/>';
        }
        $xml .= '<label posn="1 0 1.0E-5" sizen="' . $sizeX . ' 3" halign="left" valign="center" style="TextCardMediumWhite" textsize="1" textemboss="1" text="' . $label . '"/>';

        if ($showClearText) {
            $xml .= '<frame posn="-4 0 1">' . Button::getXML(3, 3, null, array($text), null, null, null, null, null, null, null, array("Icons64x64_1", "ClipPause"), $name . "_bt", null, null) . '</frame>';
        }
        
        return $xml;
    }
}
