<?php

namespace ManiaLivePlugins\eXpansion\Votes\Gui\Controls;

class ManagedVoteLimit extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    private $bg;
    private $label;
    private $frame;
    private $limit;

    /**
     * ManagedVoteLimit constructor.
     * @param type $indexNumber
     * @param $name
     * @param $desc
     * @param $value
     * @param $sizeX
     */
    public function __construct($indexNumber, $name, $desc, $value, $sizeX)
    {
        $sizeY = 10;
        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->label = new \ManiaLib\Gui\Elements\Label(50, 4);
        $this->label->setAlign('left', 'center');
        $this->label->setText($desc);
        $this->frame->addComponent($this->label);

        $this->limit = new \ManiaLive\Gui\Elements\Xml();
        $this->limit->setContent('<frame posn="50 -1 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("!_" . $name, 14, true, "Limit", $value, null, null) . '</frame>');
        $this->frame->addComponent($this->limit);
        
        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    public function destroy()
    {
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
