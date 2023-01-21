<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Windows;

use ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\RecItem;
use ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLivePlugins\eXpansion\Gui\Gui;

class mxInfos extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $connection;
    protected $frame;
    protected $pager;
    protected $items = array();

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->connection = Singletons::getInstance()->getDediConnection();

        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = "tm";
        if ($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) {
            $title = "sm";
        }

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(25, 6);
        $this->frame->setPosY(-5);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column());
        $this->mainFrame->addComponent($this->frame);

        $this->frameImage = new \ManiaLive\Gui\Controls\Frame();
        $this->frameImage->setAlign("left", "top");
        $this->frameImage->setId("Frame");
        $this->quadImage = new \ManiaLib\Gui\Elements\Quad(80, 50);
        $this->quadImage->setAlign("left", "top");
        if (ManiaExchange::$mxInfo->HasScreenshot) {
            $this->quadImage->setImage("http://" . $title . ".mania-exchange.com/maps/" . ManiaExchange::$mxInfo->TrackID . "/image/1?.png", true);
        } else {
            $this->quadImage->setImage("http://" . $title . ".mania-exchange.com/tracks/screenshot/normal/" . ManiaExchange::$mxInfo->TrackID . "/?.jpg", true);
        }
        $this->frameImage->addComponent($this->quadImage);
        $this->mainFrame->addComponent($this->frameImage);

        $this->label_mx_text = new \ManiaLib\Gui\Elements\Label(20, 6);
        $this->label_mx_text->setAlign('left', 'center');
        $this->label_mx_text->setSizeX(27);
        $this->label_mx_text->setText("Top 25 MX Records");
        $this->label_mx_text->setPosX(180);
        $this->mainFrame->addComponent($this->label_mx_text);

        foreach (ManiaExchange::$mxReplays as $rec_login => $rec) {            
            $this->items[$rec->Position - 1] = new RecItem($rec->Position - 1, $rec->Username, $rec->ReplayTime);
            $this->frame->addComponent($this->items[$rec->Position - 1]);
        }



        $this->button_visit = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
        $this->button_visit->setText(__("Visit the map page", $login));
        $this->button_visit->setPosition(90, -3);
        $action = $this->createAction(array($this, 'handleButtonVisit'));
        $this->button_visit->setAction($action);
        $this->mainFrame->addComponent($this->button_visit);

        $this->button_award = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
        $this->button_award->setText(__("Award this map", $login));
        $this->button_award->setPosition(125, -3);
        $action = $this->createAction(array($this, 'handleButtonAward'));
        $this->button_award->setAction($action);
        $this->mainFrame->addComponent($this->button_award);



        $this->label_map_name = new \ManiaLib\Gui\Elements\Label(37.5, 6.25);
        $this->label_map_name->setAlign('left', 'center');
        $this->label_map_name->setPosition(85, -11);
        $this->label_map_name->setText(ManiaExchange::$mxInfo->Name);
        $this->label_map_name->setScale(2);
        $this->mainFrame->addComponent($this->label_map_name);

        $this->label_map_author = new \ManiaLib\Gui\Elements\Label(37.5, 6.25);
        $this->label_map_author->setAlign('left', 'center');
        $this->label_map_author->setPosition(85, -18);
        $this->label_map_author->setText(ManiaExchange::$mxInfo->Username);
        $this->label_map_author->setScale(1);
        $this->mainFrame->addComponent($this->label_map_author);

        $this->label_map_date = new \ManiaLib\Gui\Elements\Label(37.5, 6.25);
        $this->label_map_date->setAlign('left', 'center');
        $this->label_map_date->setPosition(132, -18);
        $date = substr(str_replace('T', " ", ManiaExchange::$mxInfo->UploadedAt), 0, 16);
        $this->label_map_date->setText("Uploaded: " . $date);
        $this->label_map_date->setScale(0.90);
        $this->mainFrame->addComponent($this->label_map_date);

        $this->label_map_date2 = new \ManiaLib\Gui\Elements\Label(37.5, 6.25);
        $this->label_map_date2->setAlign('left', 'center');
        $this->label_map_date2->setPosition(132, -22);
        $date = substr(str_replace('T', " ", ManiaExchange::$mxInfo->UpdatedAt), 0, 16);
        $this->label_map_date2->setText("Updated: " . $date);
        $this->label_map_date2->setScale(0.90);
        $this->mainFrame->addComponent($this->label_map_date2);

        $this->label_map_comment = new \ManiaLib\Gui\Elements\Label(65, 10);
        $this->label_map_comment->setAlign('left', 'center');
        $this->label_map_comment->setPosition(85, -27);
        $text = wordwrap(ManiaExchange::$mxInfo->Comments, 70, "\n");
        $textSplit = explode("\n", $text);
        if (count($textSplit) > 18) {
            $text = array_slice($textSplit, 0, 18);
            $text = implode("\n", $text);
            $text .= "\n ....";
        }
        $this->label_map_comment->setText($text);
        $this->label_map_comment->setScale(1.2);
        $this->label_map_comment->setSizeY(10);
        $this->mainFrame->addComponent($this->label_map_comment);


        $mapData = array("AwardCount" => "Awards:", "DifficultyName" => "Difficulty:", "LengthName" => "Length:", "Mood" => "Mood:", "StyleName" => "Style:", "TitlePack" => "TitlePack:", "RouteName" => "Routes:", "MapType" => "MapType:");
        $x = 0;
        $y = -55;
        foreach ($mapData as $field => $descr) {
            $lbl = new \ManiaLib\Gui\Elements\Label($x, 6);
            $lbl->setPosition($x, $y);
            $lbl->setScale(1.5);
            $lbl->setText($descr);
            $this->mainFrame->addComponent($lbl);
            $lbl = new \ManiaLib\Gui\Elements\Label("", $x, 6);
            $lbl->setPosition($x + 50, $y);
            $lbl->setScale(1.5);
            $lbl->setText(ManiaExchange::$mxInfo->{$field});
            $this->mainFrame->addComponent($lbl);
            $y -= 5;
        }
    }

    public function handleButtonVisit($login)
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = "tm";
        if ($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) {
            $title = "sm";
        }

        $link = "http://" . $title . ".mania-exchange.com/tracks/view/" . ManiaExchange::$mxInfo->TrackID;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function handleButtonAward($login)
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = "tm";
        if ($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) {
            $title = "sm";
        }

        $link = "http://" . $title . ".mania-exchange.com/awards/add/" . ManiaExchange::$mxInfo->TrackID;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        foreach ($this->items as $item) {
            $item->setPosX(170);
            $item->setSizeX(50);
        }
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
