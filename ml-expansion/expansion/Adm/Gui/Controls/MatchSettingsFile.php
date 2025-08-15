<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Controls;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Control;

class MatchSettingsFile extends Control
{
    private $bg;

    private $mapNick;

    private $saveButton;

    private $loadButton;

    private $label;

    private $time;

    private $saveAction;

    private $loadAction;

    private $deleteButton;

    private $deleteButtonf;

    private $deleteAction;

    private $frame;

    public function __construct($indexNumber, $filename, $controller, $login, $sizeX)
    {
        $sizeY = 6;
        $this->saveAction = $this->createAction(array($controller, 'saveSettings'), $filename);
        $this->loadAction = $this->createAction(array($controller, 'loadSettings'), $filename);

        $this->deleteActionf = ActionHandler::getInstance()->createAction(array($controller, "deleteSetting"), $filename);
        $this->deleteAction = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($this->deleteActionf);

        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());


        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setAlign("center", "center2");
        $spacer->setStyle("Icons128x128_1");
        $spacer->setSubStyle("Challenge");
        $this->frame->addComponent($spacer);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        //$this->frame->addComponent($spacer);

        $this->label = new \ManiaLib\Gui\Elements\Label(90, 4);
        $this->label->setAlign('left', 'center');
        $file = explode(DIRECTORY_SEPARATOR, $filename);
        $text = end($file);
        //$text = str_replace(".txt", "", $text);
        $this->label->setText($text);
        $this->label->setTextSize(1);
        $this->label->setStyle("TextCardSmallScores2");
        $this->label->setTextColor("fff");
        $this->frame->addComponent($this->label);


        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);

        $this->frame->addComponent($spacer);

        if (AdminGroups::hasPermission($login, Permission::GAME_MATCH_SETTINGS)) {
            $this->loadButton = new \ManiaLive\Gui\Elements\Xml();
            $this->loadButton->setContent('<frame posn="98 0 3.0E-5" scale="0.8">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(26, 5, __("Load", $login), null, null, null, null, null, $this->loadAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->loadButton);
        }

        if (AdminGroups::hasPermission($login, Permission::GAME_MATCH_SAVE)) {
            $this->saveButton = new \ManiaLive\Gui\Elements\Xml();
            $this->saveButton->setContent('<frame posn="114.8 0 3.0E-5" scale="0.8">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(26, 5, __("Save", $login), null, null, "0d0", null, null, $this->saveAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->saveButton);
        }

        if (AdminGroups::hasPermission($login, Permission::GAME_MATCH_DELETE)) {
            $this->deleteButton = new \ManiaLive\Gui\Elements\Xml();
            $this->deleteButton->setContent('<frame posn="131.6 0 3.0E-5" scale="0.8">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(26, 5, __("Delete", $login), null, null, "d00", null, null, $this->deleteAction, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->deleteButton);
        }

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        $this->bg->setSize($this->sizeX + 2, $this->sizeY);
        $this->bg->setPosX(0);

        $this->frame->setPosX(2);
        $this->frame->setSize($this->sizeX, $this->sizeY);
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /*
     * custom function to remove contents.
     */
    public function erase()
    {
        ActionHandler::getInstance()->deleteAction($this->deleteAction);
        ActionHandler::getInstance()->deleteAction($this->deleteActionf);
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();

        parent::destroy();
    }
}
