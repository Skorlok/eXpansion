<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Resskip\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetButton;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

class ResSkipButtons extends Widget
{

    /**
     * @var WidgetButton
     */
    protected $btn_res;
    protected $btn_skip;
    protected $edgeWidget;

    protected function eXpOnBeginConstruct()
    {
        parent::eXpOnBeginConstruct();
        $line = new \ManiaLive\Gui\Controls\Frame(6, 0);
        $line->setAlign("center", "top");
        $line->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->btn_skip = new WidgetButton(10, 10);
        $this->btn_skip->setPositionZ(-1);
        $line->addComponent($this->btn_skip);

        $this->btn_res = new WidgetButton(10, 10);
        $this->btn_res->setPositionZ(-1);
        $line->addComponent($this->btn_res);

        $this->addComponent($line);

        $this->setName("Skip and Res Buttons");

        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }
    }

    public function setActions($res, $skip)
    {
        if (is_object($this->btn_res)) {
            $this->btn_res->setAction($res);
        }
        if (is_object($this->btn_skip)) {
            $this->btn_skip->setAction($skip);
        }
    }

    public function setResAmount($amount)
    {
        if (is_numeric($amount)) {
            $this->btn_res->setText(
                array(
                    eXpGetMessage('$s$fffBuy'),
                    eXpGetMessage('$s$fffRestart'),
                    '$s$fff' . $amount . 'p'
                )
            );
        }

        if ($amount == "max") {
            $this->btn_res->setText(
                array(
                    eXpGetMessage('$s$fffMax'),
                    eXpGetMessage('$s$fffrestarts'),
                    eXpGetMessage('$s$fffreached'),
                )
            );
            $this->btn_res->setAction(null);
        }
    }

    public function setSkipAmount($amount)
    {
        if (is_numeric($amount)) {
            $this->btn_skip->setText(
                array(
                    eXpGetMessage('$s$fffBuy'),
                    eXpGetMessage('$s$fffSkip'),
                    '$s$fff' . $amount . 'p')
            );
        }

        if ($amount == "max") {
            $this->btn_skip->setText(
                array(
                    eXpGetMessage('$s$fffMax'),
                    eXpGetMessage('$s$fffskips'),
                    eXpGetMessage('$s$fffreached'),
                )
            );
            $this->btn_skip->setAction(null);
        }
    }
}
