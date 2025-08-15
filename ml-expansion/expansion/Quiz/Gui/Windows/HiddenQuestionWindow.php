<?php

namespace ManiaLivePlugins\eXpansion\Quiz\Gui\Windows;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Quiz\Structures\Question;

class HiddenQuestionWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $ok;
    /** @var  Frame */
    protected $frame;
    /** @var  Quad */
    protected $quad;

    protected $main;

    protected $checkbox;
    /** @var  Question */
    protected $question;

    protected $entry;

    protected $script;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->setTitle("Set order");
        $this->quad = new Quad();
        $this->addComponent($this->quad);

        $this->frame = new Frame();
        $this->addComponent($this->frame);

        $undo = new \ManiaLive\Gui\Elements\Xml();
        $undo->setContent('<frame posn="64 -30 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Undo", null, null, null, null, null, null, null, null, null, "undo", null, null) . '</frame>');
        $this->addComponent($undo);

        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="66 -81 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0d0", null, null, $this->createAction(array($this, "Ok")), null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->ok);

        $this->script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Quiz/Gui/Scripts/ClickScript");
        $this->registerScript($this->script);

        $this->entry = new Inputbox("boxOrder");
        $this->entry->setPosition(900, 900);
        $this->entry->setId("boxOrder");
        $this->addComponent($this->entry);

    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
    }

    public function setQuestion(\ManiaLivePlugins\eXpansion\Quiz\Structures\Question $question)
    {
        $this->quad->setImage($question->getImage(), true);
        $this->quad->setSize($question->sizeX, $question->sizeY);
        $this->question = $question;

        $this->frame->setSize($question->sizeX, $question->sizeY);

        $x = $question->sizeX / 3;
        $y = $question->sizeY / 3;

        $c = 0;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $quad = new Quad();
                $quad->setScriptEvents();
                $quad->setBgcolor("0000");
                $quad->setBgcolorFocus("3af4");
                $quad->setSize($x, $y);
                $quad->setPosition($i * $x, -$j * $y);
                $quad->setId("quad_" . $c);
                $quad->setAttribute("class", "quad");
                $this->frame->addComponent($quad);

                $label = new Label();
                $label->setSize($x, $y);
                $label->setAlign("center", "center");
                $label->setId("lbl_" . $c);
                $label->setTextSize(3);
                $label->setStyle("TextRaceMessageBig");
                $label->setAttribute("class", "label");
                $label->setPosition(($i * $x) + $x / 2, -$j * $y - $y / 2);
                $this->frame->addComponent($label);
                $c++;
            }
        }

    }

    public function setMain($main)
    {
        $this->main = $main;
    }

    public function Ok($login, $data)
    {
        $data = explode(",", rtrim($data['boxOrder'], ","));
        $order = array();

        foreach ($data as $value) {
            $tmp = explode("_", $value);
            $order[] = intval($tmp[1]);
        }

        $this->question->setBoxOrder($order);
        $this->main->addQuestion($this->question);
        $this->erase($login);
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
