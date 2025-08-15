<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;

class ExpSettingListElement extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    protected $bg;
    protected $label_key;
    protected $label_value;
    protected $button_remove;
    public static $large = false;

    public function __construct($indexNumber, $login, $action)
    {

        $this->bg = new ListBackGround($indexNumber, 100, 4);
        $this->addComponent($this->bg);

        $this->label_key = new \ManiaLib\Gui\Elements\Label(40, 5);
        $this->label_key->setPosY(2);
        $this->label_key->setId('column_' . $indexNumber . '_0');
        $this->addComponent($this->label_key);

        $this->label_value = new \ManiaLib\Gui\Elements\Label(40, 5);
        $this->label_value->setPosY(2);
        $this->label_value->setId('column_' . $indexNumber . '_1');
        $this->addComponent($this->label_value);

        $this->button_remove = new \ManiaLive\Gui\Elements\Xml();
        $this->button_remove->setContent('<frame posn="139 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(25, 6, __('Remove', $login), array(__('Removes this value', $login), 40), null, null, null, null, $action, null, null, null, 'column_' . $indexNumber . '_2', "eXpOptimizedPagerAction", null) . '</frame>');
        $this->addComponent($this->button_remove);


        $this->setSize(160, 7);
        $this->setScale(0.8);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        if (self::$large) {
            $this->label_key->setSizeX(($this->getSizeX() - 25) / 2 - 1);
            $this->label_value->setSizeX(($this->getSizeX() - 25) / 2);
            $this->label_value->setPositionX(($this->getSizeX() - 25) / 2);
        } else {
            $this->label_key->setSizeX(10, -1);
            $this->label_value->setSizeX($this->getSizeX() - 25);
            $this->label_value->setPositionX(12);
        }

        $this->bg->setSize($this->getSizeX(), $this->getSizeY());
    }

    public function getNbTextColumns()
    {
        return 2;
    }
}
