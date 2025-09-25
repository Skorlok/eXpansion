<?php

namespace ManiaLivePlugins\eXpansion\Votes\Gui\Controls;

class ManagedVoteControl extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    private $bg;
    private $label;
    private $frame;
    private $ratio;
    private $timeout;
    private $voters;

    /**
     *
     * @param type $indexNumber
     * @param \ManiaLivePlugins\eXpansion\Votes\Structures\ManagedVote $vote
     * @param type $sizeX
     */
    public function __construct($indexNumber, \ManiaLivePlugins\eXpansion\Votes\Structures\ManagedVote $vote, $sizeX)
    {
        $sizeY = 10;
        $this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->label = new \ManiaLib\Gui\Elements\Label(50, 4);
        $this->label->setAlign('left', 'center');
        $this->label->setText($vote->command);
        $this->frame->addComponent($this->label);

        $this->timeout = new \ManiaLive\Gui\Elements\Xml();
        $this->timeout->setContent('<frame posn="50 -1 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML($vote->command . "_timeouts", 14, true, "Timeout", $vote->timeout, null, null) . '</frame>');
        $this->frame->addComponent($this->timeout);

        $this->ratio = new \ManiaLive\Gui\Elements\Xml();
        $this->ratio->setContent('<frame posn="68 -1 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML($vote->command . "_ratios", 14, true, "Ratio", $vote->ratio, null, null) . '</frame>');
        $this->frame->addComponent($this->ratio);

        $this->voters = new \ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown($vote->command . "_voters", array("Select", "Active Players", "Players", "Everybody"), ($vote->voters + 1), 20);
        $this->voters->setPosX(36);
        $this->voters->setPosY(-1);
        $this->frame->addComponent($this->voters);

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    public function destroy()
    {
        $this->voters->destroy();

        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
