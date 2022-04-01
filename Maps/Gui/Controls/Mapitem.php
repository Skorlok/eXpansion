<?php

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Controls;

use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button as MyButton;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\OptimizedPagerElement;
use ManiaLivePlugins\eXpansion\Maps\Gui\Windows\Maplist;

class Mapitem extends Control implements OptimizedPagerElement
{
    public static $ColumnWidths;
    protected $bg;
    protected $queueButton;
    protected $goButton;
    protected $showRecsButton;
    protected $removeButton;
    protected $trashButton;
    protected $jumpButton;
    public $label_map;
    public $label_envi;
    public $label_author;
    public $label_authortime;
    public $label_localrec;
    public $label_rating;
    protected $frame;
    protected $actionsFrame;

    public function __construct($indexNumber, $login, $action)
    {
        $sizeY = 6.5;
        $sizeX = 190;

        $scaledSizes = Gui::getScaledSize(self::$ColumnWidths, ($sizeX) - 7);

        /* @var $config \ManiaLivePlugins\eXpansion\Gui\Config */
        $config = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $this->bg = new ListBackGround($indexNumber, $sizeX, $sizeY);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, $sizeY);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->label_author = new \ManiaLib\Gui\Elements\Label($scaledSizes[1], 4);
        $this->label_author->setAlign('left', 'center');
        $this->label_author->setId('column_' . $indexNumber . '_1');
        $this->frame->addComponent($this->label_author);

        $this->label_map = new \ManiaLib\Gui\Elements\Label($scaledSizes[0], 4);
        $this->label_map->setAlign('left', 'center');
        $this->label_map->setStyle('TextValueSmall');
        $this->label_map->setTextSize(1);
        $this->label_map->setBgcolor("000");
        $this->label_map->setFocusAreaColor1('0000');
        $this->label_map->setFocusAreaColor2($config->style_widget_title_bgColorize);
        $this->label_map->setTextPrefix('$s');
        $this->label_map->setAction($action);
        $this->label_map->setId('column_' . $indexNumber . '_0');
        $this->label_map->setAttribute("class", "eXpOptimizedPagerAction");
        $this->label_map->setScriptEvents(1);
        $this->frame->addComponent($this->label_map);

        $this->label_envi = new \ManiaLib\Gui\Elements\Label($scaledSizes[2], 4);
        $this->label_envi->setAlign('left', 'center');
        $this->label_envi->setId('column_' . $indexNumber . '_2');
        $this->frame->addComponent($this->label_envi);

        $this->label_authortime = new \ManiaLib\Gui\Elements\Label($scaledSizes[3], 4);
        $this->label_authortime->setAlign('left', 'center');
        $this->label_authortime->setId('column_' . $indexNumber . '_3');
        $this->frame->addComponent($this->label_authortime);

        $this->label_localrec = new \ManiaLib\Gui\Elements\Label($scaledSizes[4], 4);
        $this->label_localrec->setAlign('center', 'center');
        $this->label_localrec->setId('column_' . $indexNumber . '_4');
        $this->frame->addComponent($this->label_localrec);

        $this->label_rating = new \ManiaLib\Gui\Elements\Label($scaledSizes[5], 4);
        $this->label_rating->setAlign('center', 'center');
        $this->label_rating->setId('column_' . $indexNumber . '_5');
        $this->frame->addComponent($this->label_rating);

        $this->actionsFrame = new \ManiaLive\Gui\Controls\Frame();
        $this->actionsFrame->setSize($scaledSizes[5], 4);
        $this->actionsFrame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->frame->addComponent($this->actionsFrame);

        $this->showInfoButton = new MyButton(5, 5);
        $this->showInfoButton->setDescription(__('Map Info', $login), 40);
        $this->showInfoButton->setAction($action);
        $this->showInfoButton->setIcon('Icons64x64_1', 'TrackInfo');
        $this->showInfoButton->setId('column_' . $indexNumber . '_6');
        $this->showInfoButton->setClass("eXpOptimizedPagerAction");
        $this->actionsFrame->addComponent($this->showInfoButton);

        if (Maplist::$localrecordsLoaded) {
            $this->showRecsButton = new MyButton(5, 5);
            $this->showRecsButton->setDescription(__('Show Records', $login), 40);
            $this->showRecsButton->setAction($action);
            $this->showRecsButton->setIcon('BgRaceScore2', 'ScoreLink');
            $this->showRecsButton->setId('column_' . $indexNumber . '_7');
            $this->showRecsButton->setClass("eXpOptimizedPagerAction");
            $this->actionsFrame->addComponent($this->showRecsButton);
        }

        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_JUKEBOX_ADMIN)) {
            $this->jumpButton = new MyButton(5, 5);
            $this->jumpButton->setDescription(__('Skip to this map', $login), 70);
            $this->jumpButton->setAction($action);
            $this->jumpButton->colorize('a22');
            $this->jumpButton->setIcon('Icons64x64_1', 'ClipPlay');
            $this->jumpButton->setId('column_' . $indexNumber . '_8');
            $this->jumpButton->setClass("eXpOptimizedPagerAction");
            $this->actionsFrame->addComponent($this->jumpButton);
        }

        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $this->removeButton = new MyButton(5, 5);
            $this->removeButton->setDescription(__('$F22Remove this map from server', $login), 70);
            $this->removeButton->setAction($action);
            $this->removeButton->colorize('a22');
            $this->removeButton->setIcon('Icons128x32_1', 'Close');
            $this->removeButton->setId('column_' . $indexNumber . '_9');
            $this->removeButton->setClass("eXpOptimizedPagerAction");
            $this->actionsFrame->addComponent($this->removeButton);

            $this->trashButton = new MyButton(5, 5);
            $this->trashButton->setDescription(__('$F22Erase this map and the file from server', $login), 70);
            $this->trashButton->setAction($action);
            $this->trashButton->colorize('a22');
            $this->trashButton->setIcon('Icons64x64_1', 'Close');
            $this->trashButton->setId('column_' . $indexNumber . '_10');
            $this->trashButton->setClass("eXpOptimizedPagerAction");
            $this->actionsFrame->addComponent($this->trashButton);
        }

        $this->addComponent($this->frame);
        $this->setSize($sizeX, $sizeY);
    }

    protected function onResize($oldX, $oldY)
    {
        $this->bg->setSize($this->getSizeX(), $this->getSizeY());
        $scaledSizes = Gui::getScaledSize(self::$ColumnWidths, ($this->getSizeX()));
        $this->label_author->setSizeX($scaledSizes[0]);
        $this->label_map->setSizeX($scaledSizes[1]);
        $this->label_envi->setSizeX($scaledSizes[2]);
        $this->label_authortime->setSizeX($scaledSizes[3]);
        $this->label_localrec->setSizeX($scaledSizes[4]);
        $this->label_rating->setSizeX($scaledSizes[5]);
        $this->actionsFrame->setSizeX($scaledSizes[6]);
        $this->frame->setSize($this->getSizeX(), $this->getSizeY());
    }

    public function destroy()
    {
        if (is_object($this->queueButton)) {
            $this->queueButton->destroy();
        }
        if (is_object($this->goButton)) {
            $this->goButton->destroy();
        }
        if (is_object($this->removeButton)) {
            $this->removeButton->destroy();
        }
        if (is_object($this->trashButton)) {
            $this->trashButton->destroy();
        }
        if (is_object($this->jumpButton)) {
            $this->jumpButton->destroy();
        }
        if (is_object($this->showRecsButton)) {
            $this->showRecsButton->destroy();
        }

        $this->destroyComponents();
        parent::destroy();
    }

    public function getNbTextColumns()
    {
        return 6;
    }
}
