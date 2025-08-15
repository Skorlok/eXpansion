<?php

namespace ManiaLivePlugins\eXpansion\Gui\Windows;

use ManiaLib\Gui\Elements\Label;

class ConfirmDialog extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $ok;
    protected $cancel;
    protected $label;

    protected $actionOk;
    protected $actionCancel;

    protected $title;
    private $action;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->actionOk = $this->createAction(array($this, "Ok"));
        $this->actionCancel = $this->createAction(array($this, "Cancel"));


        $this->label = new Label(57, 12);
        $this->label->setPosition(3);

        $this->mainFrame->addComponent($this->label);

        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="4 -6 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Yes", $login), null, null, "0D0", null, null, $this->actionOk, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);

        $this->cancel = new \ManiaLive\Gui\Elements\Xml();
        $this->cancel->setContent('<frame posn="30 -6 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("No", $login), null, null, "D00", null, null, $this->actionCancel, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->cancel);

        $this->setSize(57, 16);
        $this->setTitle(__("Really do this ?", $login));
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
    }

    public function setText($text)
    {
        $this->label->setText($text);
    }

    public function setInvokeAction($action)
    {
        $this->action = $action;
    }

    public function Ok($login)
    {
        $action = ConfirmProxy::Create($login);
        $action->setInvokeAction($this->action);
        $action->setTimeOut(1);
        $action->show();
        $this->Erase($login);
    }

    public function Cancel($login)
    {
        $this->erase($login);
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
