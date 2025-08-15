<?php

namespace ManiaLivePlugins\eXpansion\Database\Gui\Controls;

use ManiaLivePlugins\eXpansion\Database\Database;

class SqlFile extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    private $bg;
    private $label;
    private $frame;
    public $checkBox = null;
    public $btnRestore;
    public $actionRestore = null;
    public $btnDelete;
    public $actionDelete = null;

    /**
     *
     * @param int $indexNumber
     * @param Database $controller
     * @param string $filename
     * @param int $sizeX
     */
    public function __construct($indexNumber, $controller, $filename, $sizeX)
    {
        $sizeY = 6;
        $this->actionRestore = $this->createAction(array($controller, 'restoreFile'), $filename);
        $this->actionDelete = $this->createAction(array($controller, 'deleteFile'), $filename);


        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround($indexNumber, $sizeX - 8, $sizeY);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame(4, 0);
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        //$this->frame->addComponent($spacer);

        $this->label = new \ManiaLib\Gui\Elements\Label(120, 4);
        $this->label->setAlign('left', 'center');
        $file = explode('/', $filename);
        $text = end($file);
        $text = str_replace(".txt", "", $text);
        $this->label->setText($text);
        $this->label->setScale(0.8);
        $this->frame->addComponent($this->label);


        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->frame->addComponent($spacer);

        $this->btnRestore = new \ManiaLive\Gui\Elements\Xml();
        $this->btnRestore->setContent('<frame posn="100 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Restore", null, null, "dd0", null, null, $this->actionRestore, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->btnRestore);

        $this->btnDelete = new \ManiaLive\Gui\Elements\Xml();
        $this->btnDelete->setContent('<frame posn="117 0 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, '$dd0Delete', null, null, "222", null, null, $this->actionDelete, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->btnDelete);

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        $this->frame->setSize($this->sizeX, $this->sizeY);
    }

    /**
     * manialive 3.1 override to do nothing.
     */
    public function destroy()
    {

    }

    /*
     * custom function to remove contents.
     */

    public function erase()
    {
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
