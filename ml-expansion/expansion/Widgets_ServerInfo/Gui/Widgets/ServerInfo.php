<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ServerInfo\Gui\Widgets;

class ServerInfo extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected function eXpOnBeginConstruct()
    {
        $this->setName("Server info Widget");
    }

    public function setLadderLimits($min, $max)
    {
        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($edgeWidget);
        }

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Widgets_ServerInfo\Gui\Scripts_Infos");
        $script->setParam("maxPlayers", \ManiaLive\Data\Storage::getInstance()->server->currentMaxPlayers);
        $script->setParam("maxSpec", \ManiaLive\Data\Storage::getInstance()->server->currentMaxSpectators);
        $this->registerScript($script);

        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<quad sizen="60 10" halign="left" valign="top" bgcolor="0000" action="' . \ManiaLivePlugins\eXpansion\Core\Core::$action_serverInfo . '"/>');
        $this->addComponent($widget);

        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<label id="serverName" posn="2 0 1.0E-5" sizen="60 6" halign="left" valign="top" style="TextRaceMessageBig" textsize="2" textcolor="fff" textprefix="$s"/>');
        $this->addComponent($widget);

        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<frame posn="1 -6.5 2.0E-5">
            <frame>
                <quad sizen="5 5" halign="left" valign="center2" style="Icons128x128_1" substyle="Buddies"/>
                <label id="nbPlayer" posn="6 0 1.0E-5" sizen="12 6" halign="left" valign="center" style="TextCardScores2" textsize="2" textcolor="fff"/>
                <quad posn="19 -0.5 2.0E-5" sizen="5 5" halign="left" valign="center" style="Icons64x64_1" substyle="TV"/>
                <label id="nbSpec" posn="25 0 3.0E-5" sizen="12 6" halign="left" valign="center" style="TextCardScores2" textsize="2" textcolor="fff"/>
                <quad posn="38 0 4.0E-5" sizen="5 5" halign="left" valign="center2" style="Icons128x128_1" substyle="LadderPoints"/>
                <label posn="44 0 5.0E-5" sizen="16 6" halign="left" valign="center" style="TextCardScores2" textsize="2" textcolor="fff" text="' . ($min / 1000) . " - " . ($max / 1000) . 'k"/>
            </frame>
        </frame>');
        $this->addComponent($widget);
    }
}
