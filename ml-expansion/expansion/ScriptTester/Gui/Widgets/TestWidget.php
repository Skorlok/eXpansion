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

        $this->title = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle(100, 4);
        $this->title->setText("Preview of widget");
        $this->addComponent($this->title);

        $this->frame = new \ManiaLib\Gui\Elements\Quad(100, 100);
        $this->frame->setStyle("Bgs1");
        $this->frame->setSubStyle("BgColorContour");
        $this->frame->setColorize("f00");
        $this->addComponent($this->frame);

        $this->xmlData = new \ManiaLivePlugins\eXpansion\Gui\Elements\Xml();
        $this->xmlData->setErrorLogin($this->getRecipient());
        $this->addComponent($this->xmlData);

        $this->closeButton = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button();
        $this->closeButton->setText("Close");
        $this->closeButton->setAction($this->createAction(array($this, 'close')));
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
        $this->closeButton->setPosition($this->sizeX - 26, 3.5);
    }

    public function setScriptContent($script)
    {
        $this->scriptt = new \ManiaLivePlugins\eXpansion\ScriptTester\Gui\TesterScript($script);
        $this->registerScript($this->scriptt);
    }

    public function setXmlData($xml)
    {
        $this->xmlData->setContent($xml);
    }

    public function close($login)
    {
        $this->closeWindow();
    }
}
