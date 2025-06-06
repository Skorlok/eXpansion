<?php

namespace ManiaLivePlugins\eXpansion\Gui;

class Config extends \ManiaLib\Utils\Singleton
{
    public $logo = "http://reaby.kapsi.fi/ml/exp.png";
    public $windowTitleColor = "fff";
    public $windowTitleBackgroundColor = "573170";
    public $windowBackgroundColor = "112035";
    public $buttonTitleColor = "fff";
    public $buttonBackgroundColor = "A54B4B";
    public $style_list_bgColor = array('aaa8', 'eee8');
    public $style_list_bgStyle = array('BgsPlayerCard', 'BgsPlayerCard');
    public $style_list_bgSubStyle = array('BgRacePlayerName', 'BgRacePlayerName');
    public $style_list_posXOffset = -1;
    public $style_list_sizeXOffset = 0;
    public $style_list_posYOffset = 0;
    public $style_list_sizeYOffset = -0.25;
    public $style_title_bgColor = 'ddd4';
    public $style_title_bgStyle = 'Bgs1';
    public $style_title_bgSubStyle = 'BgCard';
    public $style_title_posXOffset = -1;
    public $style_title_sizeXOffset = 2;
    public $style_title_posYOffset = 0;
    public $style_title_sizeYOffset = 0;
    public $style_widget_bgColor = '';
    public $style_widget_bgStyle = 'Bgs1InRace';
    public $style_widget_bgSubStyle = 'NavButtonBlink'; // BgList
    public $style_widget_bgColorize = '222'; // BgList
    public $style_widget_bgOpacity = 0.6;
    public $style_widget_bgXOffset = 0;
    public $style_widget_bgYOffset = 0;
    public $style_widget_title_bgColorize = '912324'; // BgList
    public $style_widget_title_bgOpacity = 0.6;
    public $style_widget_title_bgXOffset = -0.1;
    public $style_widget_title_bgYOffset = 0.25;
    public $style_widget_title_lbStyle = 'TextCardScores2';
    public $style_widget_title_lbSize = 1;
    public $style_widget_title_lbColor = 'fff';
    public $disableAnimations = false;
    public $disablePersonalHud = false;
    public $colorPreview = "http://reaby.kapsi.fi/ml/ui3/colorchooser/1.png";
    public $colorHue = "http://reaby.kapsi.fi/ml/ui3/colorchooser/2.png";
    public $teamParams = array();
}
