<?php

namespace ManiaLivePlugins\eXpansion\MusicBox\Gui\Windows;

class CurrentTrackWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    public static $musicBoxPlugin;

    public function onConstruct()
    {
        $this->setName("Music Widget");
        parent::onConstruct();
    }

    public function setSong(\ManiaLivePlugins\eXpansion\MusicBox\Structures\Song $song)
    {
        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<frame><quad sizen="100 8" halign="center" valign="top" style="UiSMSpectatorScoreBig" substyle="PlayerSlotCenter" action="' . $this->createAction(array(self::$musicBoxPlugin, "musicList")) . '" colorize="ff0"/>
            <frame posn="-45 -3.5 1.0E-5">
            <frame>
            <label sizen="16 8" halign="left" valign="center" style="TextCardSmallScores2" textsize="1" textcolor="fff" text="Now Playing: "/>
            <label posn="16 0 1.0E-5" sizen="80 8" halign="left" valign="center" style="TextCardSmallScores2" textsize="1" textcolor="fff" text="' . $this->handleSpecialChars($song->artist) . " - " . $this->handleSpecialChars($song->title) . '"/>
            </frame>
            </frame></frame>');
        $this->addComponent($widget);
    }

    private function handleSpecialChars($string)
    {
        return str_replace(
			array(
				'&',
				'"',
				"'",
				'>',
				'<'
			),
			array(
				'&amp;',
				'&quot;',
				'&apos;',
				'&gt;',
				'&lt;'
			),
			$string
	    );
    }
}
