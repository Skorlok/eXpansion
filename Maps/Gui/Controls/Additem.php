<?php

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Controls;

use ManiaLivePlugins\eXpansion\Gui\Elements\Button as myButton;
use \ManiaLib\Utils\Formatting;

require_once(__DIR__ . "/gbxdatafetcher.inc.php");

class Additem extends \ManiaLive\Gui\Control {

    private $bg;
    private $mapNick;
    private $addButton;
    private $label;
    private $time;
    private $addMapAction;
    private $frame;

    function __construct($indexNumber, $filename, $controller, $sizeX) {
        $sizeY = 4;
        $this->addMapAction = $this->createAction(array($controller, 'addMap'), $filename);

        $gbx = new \GBXChallMapFetcher(true, false, false);
        try {
            $gbx->processFile($filename);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->bg = new \ManiaLib\Gui\Elements\Quad($sizeX, $sizeY);
        $this->bg->setAlign('left', 'center');

        if ($indexNumber % 2 == 0) {
            $this->bg->setBgcolor('fff4');
        } else {
            $this->bg->setBgcolor('7774');
        }
        $this->addComponent($this->bg);
        
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
        $this->label->setText(\ManiaLib\Utils\Formatting::stripColors($gbx->name, "fff"));
        $this->label->setScale(0.8);
        $this->frame->addComponent($this->label);

        $this->mapNick = new \ManiaLib\Gui\Elements\Label(50, 4);
        $this->mapNick->setAlign('left', 'center');

        $this->mapNick->setText(\ManiaLib\Utils\Formatting::stripColors($gbx->authorNick, "fff"));
        $this->mapNick->setScale(0.8);
        $this->frame->addComponent($this->mapNick);

        $this->time = new \ManiaLib\Gui\Elements\Label(16, 4);
        $this->time->setAlign('left', 'center');
        $this->time->setScale(0.8);
        $this->time->setText(\ManiaLive\Utilities\Time::fromTM($gbx->authorTime));
        $this->frame->addComponent($this->time);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(4, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);

        $this->frame->addComponent($spacer);


        $this->addButton = new MyButton(18, 5);
        $this->addButton->setText(__("Add map"));
        $this->addButton->setAction($this->addMapAction);
        $this->addButton->setScale(0.5);
        $this->addComponent($this->addButton);

        $this->addComponent($this->frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
    }

    protected function onResize($oldX, $oldY) {
        $this->bg->setSize($this->sizeX, $this->sizeY);
        $this->bg->setPosX(-2);
        $this->frame->setSize($this->sizeX, $this->sizeY);
        $this->addButton->setPosX($this->sizeX - 13);
        
        //  $this->button->setPosx($this->sizeX - $this->button->sizeX);
    }

    function onDraw() {
        
    }

    function destroy() {
        $this->frame->clearComponents();
        $this->frame->destroy();
        $this->addButton->destroy();
        $this->clearComponents();

        parent::destroy();
    }

}
?>

