<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Map\Gui\Widgets;

use \ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange;

class Map extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected function eXpOnBeginConstruct()
    {
        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($edgeWidget);
        }        

        $this->setName("Mapinfo Widget");
        
        $clockBg = new \ManiaLive\Gui\Elements\Xml();
        $clockBg->setContent('<quad posn="58 0 0" sizen="45 15" halign="right" valign="top" bgcolor="0000" action="' . $this->createAction(array($this, "showMapInfo")) . '"/>');
        $this->addComponent($clockBg);
        
        $map = new \ManiaLive\Gui\Elements\Xml();
        $map->setContent('<label id="mapName" posn="58 0 1.0E-5" sizen="60 6" halign="right" valign="top" style="TextRaceMessageBig" textsize="2" textcolor="fff" textprefix="$s"/>');
        $this->addComponent($map);
        
        $author = new \ManiaLive\Gui\Elements\Xml();
        $author->setContent('<label id="mapAuthor" posn="58 -4.5 2.0E-5" sizen="60 6" halign="right" valign="top" style="TextRaceMessageBig" textsize="2" textcolor="fff" textprefix="$s"/>');
        $this->addComponent($author);
        
        $author = new \ManiaLive\Gui\Elements\Xml();
        $author->setContent('<label id="authorTime" posn="58 -9 3.0E-5" sizen="60 6" halign="right" valign="top" style="TextRaceMessageBig" textsize="2" textcolor="fff" textprefix="$s"/>');
        $this->addComponent($author);

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Widgets_Map\Gui\Scripts_Map");
        $this->registerScript($script);
    }

    public function showMapInfo($login)
    {
        if (ManiaExchange::$mxInfo) {
            \call_user_func(ManiaExchange::$openInfosAction, $login);
        } else {
            $window = \ManiaLivePlugins\eXpansion\Maps\Gui\Windows\MapInfo::create($login);
            $window->setMap(null);
            $window->setSize(160, 90);
            $window->show($login);
        }
    }
}
