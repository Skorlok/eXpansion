<?php

namespace ManiaLivePlugins\eXpansion\Widgets_RecordSide\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Config;
use \ManiaLivePlugins\eXpansion\Gui\Elements\Button as myButton;
use ManiaLivePlugins\eXpansion\Widgets_RecordSide\Gui\Controls\Recorditem;
use ManiaLivePlugins\eXpansion\Widgets_RecordSide\Widgets_RecordSide;

class LocalPanel extends \ManiaLivePlugins\eXpansion\Gui\Windows\Widget {

    /** @var \ManiaLive\Gui\Controls\Frame */
    protected $frame;
    protected $items = array();
    protected $bg, $layer;
    protected $lbl_title, $bg_title;
    protected $_windowFrame;

    /** @var \ManiaLive\Data\Storage */
    public $storage;
    public $timeScript;

    protected function onConstruct() {
	parent::onConstruct();
	$sizeX = 46;
	$sizeY = 95;
	$this->setName("LocalRecords Panel");
	$this->storage = \ManiaLive\Data\Storage::getInstance();
	/* $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/TrayWidget");
	  $script->setParam('isMinimized', 'True');
	  $script->setParam('autoCloseTimeout', '3500');
	  $script->setParam('posXMin', -32);
	  $script->setParam('posX', -32);
	  $script->setParam('posXMax', -6);
	  $this->registerScript($script); */

	$script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Widgets_RecordSide/Gui/Scripts/PlayerFinish");
	$this->timeScript = $script;
	$this->timeScript->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
	$this->timeScript->setParam("playerTimes", "[]");
	$this->registerScript($script);

	$this->storage = \ManiaLive\Data\Storage::getInstance();

	$this->_windowFrame = new \ManiaLive\Gui\Controls\Frame();
	$this->_windowFrame->setAlign("left", "top");
	$this->_windowFrame->setId("Frame");
	$this->_windowFrame->setScriptEvents(true);
	$this->addComponent($this->_windowFrame);

	$this->bg = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround($sizeX, $sizeY);
	$this->_windowFrame->addComponent($this->bg);

	$this->lbl_title = new \ManiaLib\Gui\Elements\Label(20, 5);
	$this->lbl_title->setTextSize(1);
	$this->lbl_title->setTextColor("fff");
	$this->lbl_title->setAlign("center", "center");
	$this->_windowFrame->addComponent($this->lbl_title);

	/* $this->bg_title = new \ManiaLib\Gui\Elements\Quad(,5);
	  $this->bg_title->setStyle("BgsPlayerCard");
	  $this->bg_title->setSubStyle(\ManiaLib\Gui\Elements\BgsPlayerCard::BgRacePlayerName);
	  $this->bg_title->setAlign("center", "center");
	  $this->_windowFrame->addComponent($this->bg_title); */

	$this->frame = new \ManiaLive\Gui\Controls\Frame();
	$this->frame->setAlign("left", "top");
	$this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column(-1));
	$this->_windowFrame->addComponent($this->frame);
	
	$this->layer = new \ManiaLib\Gui\Elements\Quad(5,5);
	$this->layer->setStyle("Icons128x32_1");
	$this->layer->setSubStyle(\ManiaLib\Gui\Elements\Icons128x32_1::ManiaLinkSwitch);	
	$this->layer->setId("setLayer");
	$this->layer->setScriptEvents();
	$this->addComponent($this->layer);
    }

    function onResize($oldX, $oldY) {
	parent::onResize($oldX, $oldY);
	$this->_windowFrame->setSize($this->sizeX, $this->sizeY);
	$this->bg->setSize($this->sizeX, $this->sizeY + 6);
	$this->bg->setPosition(0, -($this->sizeY / 2));
	$this->frame->setPosition(($this->sizeX / 2) + 1, -6);
	$this->lbl_title->setPosition(($this->sizeX / 2), 0);
	$this->layer->setPosition($this->sizeX - 5, 0);
	//  $this->bg_title->setPosition($this->sizeX / 2, 0);
    }

    function update() {
	$login = $this->getRecipient();
	foreach ($this->items as $item)
	    $item->destroy();
	$this->items = array();
	$this->frame->clearComponents();

	$index = 1;

	$this->lbl_title->setText(__('Local Records', $login));


	$recsData = "";
	$nickData = "";

	for ($index = 1; $index <= 30; $index++) {
	    $this->items[$index - 1] = new Recorditem($index, false);
	    $this->frame->addComponent($this->items[$index - 1]);
	}

	$index = 1;
	foreach (Widgets_RecordSide::$localrecords as $record) {
	    if ($index > 1) {
		$recsData .= ', ';
		$nickData .= ', ';
	    }
	    $recsData .= '"' . $record->login . '"=>' . $record->time;
	    $nickData .= '"' . $record->login . '"=>"' . $record->nickName . '"';
	    $index++;
	}
	$this->timeScript->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);

	if (empty($recsData)) {
	    $recsData = 'Integer[Text]';
	    $nickData = 'Text[Text]';
	} else {
	    $recsData = '[' . $recsData . ']';
	    $nickData = '[' . $nickData . ']';
	}

	$this->timeScript->setParam("playerTimes", $recsData);
	$this->timeScript->setParam("playerNicks", $nickData);
    }

    function destroy() {
	foreach ($this->items as $item)
	    $item->destroy();

	$this->items = array();

	$this->frame->clearComponents();
	$this->frame->destroy();
	$this->clearComponents();
	parent::destroy();
    }

}

?>
