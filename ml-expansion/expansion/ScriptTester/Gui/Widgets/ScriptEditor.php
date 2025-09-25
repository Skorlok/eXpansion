<?php

namespace ManiaLivePlugins\eXpansion\ScriptTester\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Widgets\PlainWidget;
use ManiaLivePlugins\eXpansion\ScriptTester\Config;

/**
 * Description of ScriptEditor
 *
 * @author Petri
 */
class ScriptEditor extends PlainWidget
{

    protected $lbl_ml;
    protected $lbl_script;
    protected $background;
    protected $btn_apply;
    protected $btn_close;


    protected function onConstruct()
    {
        parent::onConstruct();
        $config = Config::getInstance();
        $this->setName("Script Editor");
        $this->setScale(0.75);
        $this->setPosition(-150, -10);

        $this->background = new WidgetBackGround(395, 75);
        $this->addComponent($this->background);

        $this->lbl_ml = new WidgetTitle(180, 4, 'Manialink editor');
        $this->lbl_ml->setPosition(12);
        $this->addComponent($this->lbl_ml);

        $entry = new \ManiaLive\Gui\Elements\Xml();
        $entry->setContent('<textedit id="input_manialink" posn="12 -5 3.0E-5" sizen="180 60" halign="left" valign="top" scriptevents="1" default="' . $this->handleSpecialChars($config->tester_manialink) . '" textformat="script" name="manialink" showlinenumbers="1" autonewline="1"/>');
        $this->addComponent($entry);

        $this->lbl_script = new WidgetTitle(180, 4, 'Maniascript editor');
        $this->lbl_script->setPosX(210);
        $this->addComponent($this->lbl_script);

        $entry = new \ManiaLive\Gui\Elements\Xml();
        $entry->setContent('<textedit id="input_maniascript" posn="210 -5 6.0E-5" sizen="180 60" halign="left" valign="top" scriptevents="1" default="' . $this->handleSpecialChars($config->tester_maniascript) . '" textformat="script" name="script" showlinenumbers="1" autonewline="1"/>');
        $this->addComponent($entry);

        $this->btn_apply = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_apply->setContent('<frame posn="190 -70 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, "Save &amp; Test", null, null, '0d0', null, null, null, null, null, null, "apply", null, null) . '</frame>');
        $this->addComponent($this->btn_apply);

        $this->btn_close = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_close->setContent('<frame posn="365 -70 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Close", null, null, 'd00', null, null, $this->createAction(array($this, 'close')), null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->btn_close);
    }

    public function setActionId($actionId)
    {
        $this->btn_apply->setContent('<frame posn="190 -70 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, "Save &amp; Test", null, null, '0d0', null, null, $actionId, null, null, null, "apply", null, null) . '</frame>');
    }

    public function close($login)
    {
        $this->closeWindow();
    }

    private function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;'), $string);
    }
}
