<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Voip;

use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;

/**
 * Description of Widgets_Advertising
 *
 * @author Petri
 */
class Widgets_Voip extends ExpPlugin
{
    /** @var Config */
    private $config;

    private $settingsChanged = false;

    /** @var StaticWidget[] provider => widget */
    private $widgets = array();

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents(Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);

        $this->registerManialinkCallback('gotoMumble');
        $this->registerManialinkCallback('gotoTs');

        $this->config = Config::GetInstance();
        Gui::preloadImage($this->config->mumbleImageUrl);
        Gui::preloadImage($this->config->mumbleImageFocusUrl);
        Gui::preloadImage($this->config->tsImageUrl);
        Gui::preloadImage($this->config->tsImageFocusUrl);
        Gui::preloadUpdate();

        foreach (array("mumble" => "Mumble", "ts" => "TeamSpeak") as $provider => $name) {
            $widget = new StaticWidget('Widgets_Voip\Gui\Widgets\VoipButton.xml');
            $widget->setName($name);
            $widget->setLayer("normal");
            if ($this->storage->simpleEnviTitle == "TM") {
                $widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
            }
            $this->widgets[$provider] = $widget;
        }

        $this->displayWidget(null);
        $this->enableApplicationEvents();
    }

    public function onSettingsChanged(Variable $var)
    {
        $name = $var->getName();

        if (isset($this->config->$name)) {
            $this->settingsChanged = true;
        }
    }

    public function onPreLoop()
    {
        if ($this->settingsChanged) {
            $this->displayWidget(null);
            $this->settingsChanged = false;
        }
    }

    public function displayWidget($login)
    {
        $actions = array(
            "mumble" => 'exp:eXpansion.Widgets_Voip:gotoMumble',
            "ts"     => 'exp:eXpansion.Widgets_Voip:gotoTs',
        );

        foreach ($this->widgets as $provider => $widget) {
            $varActive = $provider . "Active";
            if (!isset($this->config->$varActive) || !$this->config->$varActive) {
                $widget->erase($login);
                continue;
            }

            $varX = $provider . "X";
            $varY = $provider . "Y";
            $varImageUrl = $provider . "ImageUrl";
            $varImageFocusUrl = $provider . "ImageFocusUrl";
            $varSize = $provider . "Size";
            $varImageSizeX = $provider . "ImageSizeX";
            $varImageSizeY = $provider . "ImageSizeY";

            $size = $this->getImageSize($this->config->$varImageSizeX, $this->config->$varImageSizeY, $this->config->$varSize);

            $widget->setPosition($this->config->$varX, $this->config->$varY, -60);
            $widget->setParam("sizeX", $size[0]);
            $widget->setParam("sizeY", $size[1]);
            $widget->setParam("action", $actions[$provider]);
            $widget->setParam("imageUrl", $this->config->$varImageUrl);
            $widget->setParam("imageFocusUrl", $this->config->$varImageFocusUrl);
            $widget->show($login, $login === null);
        }
    }

    /**
     * Fit the image in $preferredSize while keeping its pixel aspect ratio,
     * compensating the 16/9 aspect of the maniaplanet display units.
     *
     * @return array the quad size, x then y
     */
    private function getImageSize($sizeX, $sizeY, $preferredSize)
    {
        $wantedAspectRatio = $sizeX / $sizeY;
        $currentAspectRatio = 16 / 9;

        return array(
            $preferredSize * ($currentAspectRatio / $wantedAspectRatio) * $wantedAspectRatio,
            $preferredSize * ($currentAspectRatio / $wantedAspectRatio),
        );
    }

    public function getNick($login)
    {
        $player = $this->storage->getPlayerObject($login);
        $nick = Formatting::stripStyles($player->cleanNickName);

        return $nick;
    }

    public function gotoMumble($login)
    {
        $link = "mumble://" . rawurlencode($this->getNick($login)) . "@" . $this->config->mumbleHost . ":" . $this->config->mumblePort;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function gotoTs($login)
    {
        $link = "ts3server://" . $this->config->tsHost . "?port=" . $this->config->tsPort . "&nickname=" . rawurlencode($this->getNick($login));
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function eXpOnUnload()
    {
        foreach ($this->widgets as $widget) {
            $widget->erase();
        }
        $this->widgets = array();

        parent::eXpOnUnload();
    }
}
