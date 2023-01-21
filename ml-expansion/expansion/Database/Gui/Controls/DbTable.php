<?php

namespace ManiaLivePlugins\eXpansion\Database\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;

class DbTable extends Control
{

    private $bg;

    private $label;

    private $inputbox;

    private $frame;

    public $checkBox = null;

    public $tableName;

    public $type = null;

    /**
     *
     * @param int $indexNumber
     * @param string $tableName
     * @param int $sizeX
     */
    public function __construct($indexNumber, $tableName, $sizeX)
    {
        $sizeY = 6;
        $this->tableName = $tableName;


        $this->bg = new ListBackGround($indexNumber, $sizeX - 8, $sizeY);
        $this->addComponent($this->bg);

        $this->checkBox = new CheckboxScripted(4, 4, 1);
        $this->addComponent($this->checkBox);

        $this->label = new Label(120, 4);
        $this->label->setPosX(6);
        $this->label->setAlign('left', 'center');
        $this->label->setText($tableName);
        $this->label->setScale(0.8);
        $this->addComponent($this->label);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
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
        $this->checkBox->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
