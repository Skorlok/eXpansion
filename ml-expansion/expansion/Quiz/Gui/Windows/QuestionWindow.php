<?php

namespace ManiaLivePlugins\eXpansion\Quiz\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Gui;

class QuestionWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $ok;

    protected $actionOk;

    protected $IBanswers;

    protected $IBQuestion;

    protected $IBimageUrl;

    protected $frame;

    protected $answerCount = 7;

    /** @var \ManiaLivePlugins\eXpansion\Quiz\Quiz */
    public static $mainPlugin;
    protected $checkbox;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->frame = new \ManiaLive\Gui\Controls\Frame(0, -6);
        $this->frame->setSize(90, 120);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column(90, 6));

        $this->IBQuestion = new \ManiaLive\Gui\Elements\Xml();
        $this->IBQuestion->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("question", 80, true, __("Question", $login), null, null, null) . '</frame>');
        $this->frame->addComponent($this->IBQuestion);

        for ($x = 0; $x < $this->answerCount; $x++) {
            $this->IBanswers[$x] = new \ManiaLive\Gui\Elements\Xml();
            $this->IBanswers[$x]->setContent('<frame posn="0 -' . (12*($x+1)) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("answer." . $x, 80, true, __("Answer", $login) . ($x + 1), null, null, null) . '</frame>');
            $this->frame->addComponent($this->IBanswers[$x]);
        }

        $this->IBimageUrl = new \ManiaLive\Gui\Elements\Xml();
        $this->IBimageUrl->setContent('<frame posn="0 -96 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("imageUrl", 80, true, __("Url for image", $login), null, null, null) . '</frame>');
        $this->frame->addComponent($this->IBimageUrl);

        $this->checkbox = new \ManiaLive\Gui\Elements\Xml();
        $this->checkbox->setContent('<frame posn="0 -108 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Ask hidden", $login), null, null, null, null, null, $this->createAction(array($this, "Hidden")), null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->checkbox);

        $this->mainFrame->addComponent($this->frame);

        $this->actionOk = $this->createAction(array($this, "Ok"));
        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="66 -111 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0d0", null, null, $this->actionOk, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);
    }

    public function setQuestion(\ManiaLivePlugins\eXpansion\Quiz\Structures\Question $question)
    {
        $this->IBQuestion->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("question", 80, true, __("Question", $this->getRecipient()), $question->getQuestion(), null, null) . '</frame>');
        for ($x = 0; $x < $this->answerCount; $x++) {
            if (isset($question->answer[$x])) {
                $this->IBanswers[$x]->setContent('<frame posn="0 -' . (12*($x+1)) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("answer." . $x, 80, true, __("Answer", $this->getRecipient()) . ($x + 1), $question->answer[$x]->answer, null, null) . '</frame>');
            }
        }
    }

    public function Ok($login, $data)
    {

        $storage = \ManiaLive\Data\Storage::getInstance();
        $q = str_replace("?", "", $data['question']);
        $question = new \ManiaLivePlugins\eXpansion\Quiz\Structures\Question(
            $storage->getPlayerObject($login),
            trim($q)
        );
        for ($x = 0; $x < $this->answerCount; $x++) {
            if (trim($data['answer.' . $x]) != "") {
                $question->addAnswer(trim($data['answer.' . $x]));
            }
        }

        if (!empty($data['imageUrl'])) {
            $question->setImage(trim($data['imageUrl']));
        }

        self::$mainPlugin->addQuestion($question);
        $this->erase($login);
    }

    public function Hidden($login, $data)
    {

        $storage = \ManiaLive\Data\Storage::getInstance();
        $q = str_replace("?", "", $data['question']);
        $question = new \ManiaLivePlugins\eXpansion\Quiz\Structures\Question(
            $storage->getPlayerObject($login),
            trim($q)
        );
        for ($x = 0; $x < $this->answerCount; $x++) {
            if (trim($data['answer.' . $x]) != "") {
                $question->addAnswer(trim($data['answer.' . $x]));
            }
        }

        if (!empty($data['imageUrl'])) {
            $question->setImage(trim($data['imageUrl']));
            $question->setHidden(true);
            $this->erase($login);
            self::$mainPlugin->setHiddenQuestionBoxes($question);
        } else {
            Gui::showNotice("To ask hidden question, you have to define url for image", $login);
        }
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
