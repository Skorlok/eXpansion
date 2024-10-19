<?php

namespace ManiaLivePlugins\eXpansion\Gui;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;

/**
 * Description of MetaData
 *
 * @author Petri
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Core: Graphical User Interface");
        $this->setDescription("");
        $this->setGroups(array('Core'));

        $config = Config::getInstance();

        $var = new Boolean("disablePersonalHud", "Disable personalized hud", $config);
        $var->setDescription("if disable this, server admin defined positions are forced to all players");
        $var->setGroup("GUI");
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new ColorCode("windowTitleColor", "Window Title Text color", $config);
        $var->setDescription("you can use short 3 (+1 for alpha) or full 6 (+2 for alpha) color code for this value");
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("fff");
        $this->registerVariable($var);

        $var = new ColorCode("windowTitleBackgroundColor", "Window Title Background color", $config);
        $var->setDescription("you can use short 3 (+1 for alpha) or full 6 (+2 for alpha) color code for this value");
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("573170");
        $this->registerVariable($var);


        $var = new ColorCode("windowBackgroundColor", "Window Background color", $config);
        $var->setDescription("you can use short 3 (+1 for alpha) or full 6 (+2 for alpha) color code for this value");
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("112035");
        $this->registerVariable($var);


        $var = new ColorCode("buttonTitleColor", "Button Text color", $config);
        $var->setDescription("you can use short 3 (+1 for alpha) or full 6 (+2 for alpha) color code for this value");
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("fff");
        $this->registerVariable($var);


        $var = new ColorCode("buttonBackgroundColor", "Button Background color", $config);
        $var->setDescription("you can use short 3 (+1 for alpha) or full 6 (+2 for alpha) color code for this value");
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("A54B4B");
        $this->registerVariable($var);


        $var = new Boolean("disableAnimations", "Disable window animations", $config);
        $var->setGroup("GUI");
        $var->setDefaultValue(false);
        $var->setVisible(false);
        $this->registerVariable($var);

        $var = new TypeString("style_widget_bgStyle", "background style", $config);
        $var->setGroup("GUI");
        $var->setDefaultValue("BgsPlayerCard");
        $var->setVisible(false);
        $this->registerVariable($var);

        $var = new TypeString("style_widget_bgSubStyle", "background substyle", $config);
        $var->setGroup("GUI");
        $var->setDefaultValue("BgRacePlayerName");
        $var->setVisible(false);
        $this->registerVariable($var);

        $var = new ColorCode("style_widget_bgColorize", "Widget Background", $config);
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("222");
        $this->registerVariable($var);

        $var = new BoundedTypeFloat("style_widget_bgOpacity", "Widget Background Opacity", $config);
        $var->setGroup("GUI");
        $var->setMin(0.0);
        $var->setMax(1.0);
        $var->setDefaultValue(0.2);
        $this->registerVariable($var);

        $var = new ColorCode("style_widget_title_bgColorize", "Widget Titlebar Background color", $config);
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("8E0CA4");
        $this->registerVariable($var);

        $var = new ColorCode("style_widget_title_lbColor", "Widget Titlebar Text color", $config);
        $var->setGroup("GUI");
        $var->setUsePrefix(false);
        $var->setUseFullHex(true);
        $var->setDefaultValue("fff");
        $this->registerVariable($var);

        $var = new TypeString("style_widget_title_lbStyle", "Widget Titlebar font", $config);
        $var->setGroup("GUI");
        $var->setDescription('see the $hstyles$h for available fonts');
        $var->setDefaultValue("TextCardScores2");
        $this->registerVariable($var);

        $var = new BoundedTypeFloat("style_widget_title_lbSize", "Widget Titlebar font size", $config);
        $var->setGroup("GUI");
        $var->setMin(0.5);
        $var->setMax(5.0);
        $var->setDefaultValue(1);
        $this->registerVariable($var);

        $var = new HashList("teamParams", "Parameters for team color and name (do not edit)", $config, false, true);
        $var->setKeyType(new TypeString(""));
        $var->setDefaultValue(array());
        $var->setGroup("GUI");
        $var->setDescription('Automatically generated from //team display');
        $this->registerVariable($var);
    }
}
