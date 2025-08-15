<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\ButtonHook;

class MxMap extends Control
{

    protected $bg;

    protected $buttons = array();
    protected $actionSearch;
    protected $line1;
    protected $line2;

    /**
     * @param                                                            $indexNumber
     * @param \ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap $map
     * @param                                                            $controller
     * @param ButtonHook[] $buttons
     * @param                                                            $sizeX
     */
    public function __construct(
        $indexNumber,
        \ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap $map,
        $controller,
        $buttons,
        $sizeX
    ) {
        $sizeY = 12;

        $this->bg = new ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);

        $this->actionSearch = $this->createAction(array($controller, 'search'), "", $map->getUploader(), null, null);

        $this->line1 = new Frame(0, 3);
        $this->line1->setAlign("left", "top");
        $this->line1->setSize($sizeX, $sizeY);
        $this->line1->setLayout(new Line());

        $this->line2 = new Frame(0, -3);
        $this->line2->setAlign("left", "top");
        $this->line2->setSize($sizeX, $sizeY);
        $this->line2->setLayout(new Line());

        $label = new Label(36, 6);
        $label->setAlign('left', 'center');
        $pack = str_replace("TM", "", $map->titlePack);
        if (empty($pack) || $pack == "TMAll") {
            $pack = $map->getEnvironment();
        }
        $label->setText($pack);
        $this->line1->addComponent($label);

        $label = new Label(36, 6);
        $label->setAlign('left', 'center');
        $label->setText("");
        if ($map->vehicleName) {
            $vehicle = str_replace("Car", "", $map->vehicleName);
            if ($vehicle != $pack) {
                $label->setText("Car: " . $vehicle);
            }
        }
        $this->line2->addComponent($label);


        $label = new Label(80, 6);
        $label->setAlign('left', 'center');
        $label->setStyle("TextCardSmallScores2");
        $label->setTextEmboss();
        $label->setText(Gui::fixString($map->gbxMapName));
        $this->line1->addComponent($label);

        $info = new Label(80, 6);
        $info->setAlign('left', 'center');
        $info->setText('$fff' . Gui::fixString($map->getUploader()));
        $info->setAction($this->actionSearch);
        $info->setStyle("TextCardSmallScores2");

        $info->setScriptEvents(true);
        $this->line2->addComponent($info);


        $info = new Label(24, 4);
        $info->setAlign('left', 'center');
        $info->setText($map->getDifficulty());
        $this->line1->addComponent($info);

        $info = new Label(24, 4);
        $info->setAlign('left', 'center');
        $info->setText($map->moodFull);
        $this->line2->addComponent($info);

        $info = new Label(18, 4);
        $info->setAlign('left', 'center');
        $info->setText($map->getStyle());
        $this->line1->addComponent($info);


        $info = new Label(18, 4);
        $info->setAlign('left', 'center');
        $info->setText($map->getLength());
        $this->line2->addComponent($info);


        if (!empty($buttons)) {
            $x = 158;
            foreach ($buttons as $button) {
                $newButton = new \ManiaLive\Gui\Elements\Xml();
                $newButton->setContent('<frame posn="' . $x . ' 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(24, 5, __($button->label), null, null, $button->buttonColorize, null, null, $this->createAction($button->callback, $map->mapId), null, null, null, null, null, null) . '</frame>');
                $this->line1->addComponent($newButton);
                $this->buttons[] = $newButton;
                $x += 19.5;
            }
        }

        if ($map->awardCount > 0) {
            $info = new Quad(4, 4);
            $info->setPosY(3);
            $info->setStyle("Icons64x64_1");
            $info->setSubStyle("OfficialRace");
            $info->setAlign('center', 'center');
            $info->setPosZ(1.5);
            $this->line2->addComponent($info);

            $info = new Label(12, 5);
            $info->setPosY(3);
            $info->setAlign('center', 'center');
            $info->setText($map->awardCount);
            $info->setPosZ(1.5);
            $this->line2->addComponent($info);
        }
        $this->addComponent($this->line1);
        $this->addComponent($this->line2);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
    }

    // override destroy method not to destroy its contents on manialive 3.1
    public function destroy()
    {

    }

    /**
     * custom function to destroy contents when needed.
     */
    public function erase()
    {
        $this->line1->clearComponents();
        $this->line1->destroy();
        $this->line2->clearComponents();
        $this->line2->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
