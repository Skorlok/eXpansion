<?php

namespace ManiaLivePlugins\eXpansion\ScriptTester\Gui\Widgets;

/**
 * Description of TestWidget
 *
 * @author Petri
 */
class TestWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    protected $scriptt;
    protected $xmlData;
    protected $title;
    protected $frame;
    protected $frame2;
    protected $closeButton;

    protected function eXpOnBeginConstruct()
    {
        parent::eXpOnBeginConstruct();
        $this->setName('Test widget');

        $this->title = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle(100, 4, "Preview of widget");
        $this->addComponent($this->title);

        $this->frame = new \ManiaLib\Gui\Elements\Quad(100, 100);
        $this->frame->setStyle("Bgs1");
        $this->frame->setSubStyle("BgColorContour");
        $this->frame->setColorize("f00");
        $this->addComponent($this->frame);

        $this->closeButton = new \ManiaLive\Gui\Elements\Xml();
        $this->closeButton->setContent('<frame posn="74 3.5 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Close", null, null, null, null, null, $this->createAction(array($this, 'close')), null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->closeButton);
    }

    public function eXpOnEndConstruct()
    {
        parent::eXpOnEndConstruct();
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSize($this->sizeX, $this->sizeY);
        $this->title->setPosY(6);
        $this->title->setSize($this->sizeX, 6);
    }

    public function setScriptContent($script)
    {
        $this->scriptt = new \ManiaLivePlugins\eXpansion\ScriptTester\Gui\TesterScript($script);
        $this->registerScript($this->scriptt);
    }

    public function setXmlData($xml)
    {
        $this->xmlData = new \ManiaLive\Gui\Elements\Xml();
        $this->xmlData->setContent($xml);
        $this->addComponent($this->xmlData);
    }

    public function close($login)
    {
        $this->closeWindow();
    }
}
