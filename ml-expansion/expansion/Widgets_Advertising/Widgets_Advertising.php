<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Advertising;

use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\PlainWidget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Maniascript;

/**
 * Description of Widgets_Advertising
 *
 * @author Petri
 */
class Widgets_Advertising extends ExpPlugin
{

    /** @var Config */
    private $config;

    private $settingsChanged = false;

    /** @var PlainWidget[] slot number => widget */
    private $widgets = array();

    /** @var Script[] slot number => script */
    private $scripts = array();

    public function eXpOnReady()
    {
        $this->config = Config::GetInstance();

        for ($x = 1; $x <= 10; $x++) {
            $name = "Advertising " . $x;

            $script = new Script('Widgets_Advertising\Gui\Script');
            $script->setParam("hide", "Text[]");

            $widget = new PlainWidget('Widgets_Advertising\Gui\Widgets\WidgetAd.xml');
            $widget->setName($name);
            $widget->setLayer("normal");
            $widget->registerScript($script);
            if ($this->expStorage->simpleEnviTitle == "TM") {
                $widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
            }

            $this->widgets[$x] = $widget;
            $this->scripts[$x] = $script;
        }

        $this->displayWidget();
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
            $this->displayWidget();
            $this->settingsChanged = false;
        }
    }

    public function displayWidget()
    {
        $noAds = "Text[]";
        if (count($this->config->noAdUsers) > 0) {
            $noAds = Maniascript::stringifyAsStringList($this->config->noAdUsers);
        }

        for ($x = 1; $x <= 10; $x++) {
            $widget = $this->widgets[$x];

            $varActive = "active_$x";
            if (!isset($this->config->$varActive) || !$this->config->$varActive) {
                $widget->erase();
                continue;
            }

            $varX = "x_$x";
            $varY = "y_$x";
            $varImageUrl = "imageUrl_$x";
            $varImageFocusUrl = "imageFocusUrl_$x";
            $varUrl = "url_$x";
            $varManialink = "manialink_$x";
            $varSize = "size_$x";
            $varImageSizeX = "imageSizeX_$x";
            $varImageSizeY = "imageSizeY_$x";

            $size = $this->getImageSize($this->config->$varImageSizeX, $this->config->$varImageSizeY, $this->config->$varSize);

            $this->scripts[$x]->setParam("hide", $noAds);

            $widget->setPosition($this->config->$varX, $this->config->$varY, 20);
            $widget->setParam("sizeX", $size[0]);
            $widget->setParam("sizeY", $size[1]);
            $widget->setParam("imageUrl", $this->config->$varImageUrl);
            $widget->setParam("imageFocusUrl", $this->config->$varImageFocusUrl);
            $widget->setParam("url", $this->config->$varUrl);
            $widget->setParam("manialink", $this->config->$varManialink);
            $widget->show(null, true);
        }
    }

    private function getImageSize($sizeX, $sizeY, $preferredSize)
    {
        $wantedAspectRatio = $sizeX / $sizeY;
        $currentAspectRatio = 16 / 9;

        return array(
            $preferredSize * ($currentAspectRatio / $wantedAspectRatio) * $wantedAspectRatio,
            $preferredSize * ($currentAspectRatio / $wantedAspectRatio),
        );
    }

    public function eXpOnUnload()
    {
        foreach ($this->widgets as $widget) {
            $widget->erase();
        }
        $this->widgets = array();
        $this->scripts = array();

        parent::eXpOnUnload();
    }
}
