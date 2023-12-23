<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Windows;

use ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\RecItem;
use ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;

class mxInfos extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $connection;
    protected $frame;
    protected $pager;
    protected $gbxInfo;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->connection = Singletons::getInstance()->getDediConnection();

        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = (($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) ? "sm" : "tm");


        $frame = new \ManiaLive\Gui\Controls\Frame(183);
        $frame->setSize(25, 6);
        $frame->setPosY(-5);
        $frame->setLayout(new \ManiaLib\Gui\Layouts\Column());
        foreach (ManiaExchange::$mxReplays as $rec_login => $rec) {
            $frame->addComponent(new RecItem($rec->Position - 1, $rec->Username, $rec->ReplayTime));
        }
        $this->mainFrame->addComponent($frame);


        $label_mx_text = new \ManiaLive\Gui\Elements\Xml();
        $label_mx_text->setContent('<label posn="188 0 0" sizen="27 6" halign="left" valign="center" style="TextStaticSmall" text="Top 25 MX Records"/>');
        $this->mainFrame->addComponent($label_mx_text);


        $quadImage = new \ManiaLib\Gui\Elements\Quad(60, 37);
        $quadImage->setPosition(-4, 0);
        if (ManiaExchange::$mxInfo->HasScreenshot) {
            $quadImage->setImage("http://" . $title . ".mania-exchange.com/maps/" . ManiaExchange::$mxInfo->TrackID . "/image/1?.png", true);
        } else {
            $quadImage->setImage("http://" . $title . ".mania-exchange.com/tracks/screenshot/normal/" . ManiaExchange::$mxInfo->TrackID . "/?.jpg", true);
        }
        $this->mainFrame->addComponent($quadImage);


        $button_visit = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
        $button_visit->setText(__("Visit the map page", $login));
        $button_visit->setPosition(58, -2.6);
        $action = $this->createAction(array($this, 'handleButtonVisit'));
        $button_visit->setAction($action);
        $this->mainFrame->addComponent($button_visit);

        $button_award = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
        $button_award->setText(__("Award this map", $login));
        $button_award->setPosition(88, -2.6);
        $action = $this->createAction(array($this, 'handleButtonAward'));
        $button_award->setAction($action);
        $this->mainFrame->addComponent($button_award);


        $mapData = array("Name" => "Name:", "Username" => "Author:", "UploadedAt" => "Uploaded:", "UpdatedAt" => "Updated:", "AwardCount" => "Awards:", "DifficultyName" => "Difficulty:", "LengthName" => "Length:", "Mood" => "Mood:", "StyleName" => "Style:", "TitlePack" => "TitlePack:", "RouteName" => "Routes:", "MapType" => "MapType:");
        $y = -39;
        foreach ($mapData as $field => $descr) {

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="-2 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $descr . '"/>');
            $this->mainFrame->addComponent($lbl);

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            if ($descr == "Uploaded:" || $descr == "Updated:") {
                $lbl->setContent('<label posn="20 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . substr(str_replace('T', " ", ManiaExchange::$mxInfo->{$field}), 0, 16) . '"/>');
            } else {
                $lbl->setContent('<label posn="20 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . ManiaExchange::$mxInfo->{$field} . '"/>');
            }
            $this->mainFrame->addComponent($lbl);

            $y -= 5;
        }



        $text = ManiaExchange::$mxInfo->Comments;

        $text = preg_replace('#\[url=#i', '$L[', $text);
        $text = preg_replace('#\[/url\]#i', '$L', $text);
        $text = preg_replace('#\[[a-z=]+\]#Ui', '', $text);
        $text = preg_replace('#\[/[a-z]+\]#Ui', '', $text);
        $text = preg_replace('#<.*>#Ui', '', $text);
        $text = preg_replace('#</.*>#Ui', '', $text);
        
        $lbl = new \ManiaLive\Gui\Elements\Xml();
        $lbl->setContent('<label posn="58 -9.5 0" sizen="150 0" scale="0.8" style="TextStaticSmall" autonewline="1" maxline="9" text="' . $text . '"/>');
        $this->mainFrame->addComponent($lbl);



        $storage = Storage::getInstance();
        $map = ArrayOfObj::getObjbyPropValue($storage->maps, "uId", $storage->currentMap->uId);
        $this->connection = Singletons::getInstance()->getDediConnection();
        $mapPath = $this->connection->getMapsDirectory();

        if ($map !== false && file_exists($mapPath . DIRECTORY_SEPARATOR . $map->fileName)) {

            $map->{"nick"} = "n/a";

            try {
                $gbxInfo = new GBXChallMapFetcher(true, false, false);
                $gbxInfo->processFile($mapPath . DIRECTORY_SEPARATOR . $map->fileName);

                if ($gbxInfo) {
                    $this->gbxInfo = $gbxInfo;
                    $map->mood = $gbxInfo->mood;
                    $map->nbLap = $gbxInfo->nbLaps;
                    $map->nbCheckpoint = $gbxInfo->nbChecks;
                    $map->authorTime = $gbxInfo->authorTime;
                    $map->silverTime = $gbxInfo->silverTime;
                    $map->bronzeTime = $gbxInfo->bronzeTime;
                    $map->songFile = $gbxInfo->songFile;
                    $map->modName = $gbxInfo->modName;
                    $map->{"nick"} = $gbxInfo->authorNick;
                }
            } catch (Exception $ex) {
                \ManiaLive\Utilities\Console::println("Info: Map not found or error while reading gbx info for map.");
            }

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="58 -39 0" sizen="35 6" style="TextStaticSmall" text="UID:"/>');
            $this->mainFrame->addComponent($lbl);

            $lbl = new Inputbox("");
            $lbl->setPosition(80, -39);
            $lbl->setSize(45, 6);
            $lbl->setText($map->uId);
            $this->mainFrame->addComponent($lbl);


            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="58 -44 0" sizen="35 6" style="TextStaticSmall" text="File Name:"/>');
            $this->mainFrame->addComponent($lbl);

            $lbl = new Inputbox("");
            $lbl->setPosition(80, -45);
            $lbl->setSize(45, 6);
            $lbl->setText($map->fileName);
            $this->mainFrame->addComponent($lbl);


            $mapData = array("name" => "Name:", "author" => "Author:", "nick" => "Author Nick:", "mood" => "Mood:", "mapStyle" => "Map Style:", "mapType" => "Map Type:", "environnement" => "Environment:");
            $y = -49;
            foreach ($mapData as $field => $descr) {

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="58 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $descr . '"/>');
                $this->mainFrame->addComponent($lbl);

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="80 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $map->{$field} . '"/>');
                $this->mainFrame->addComponent($lbl);

                $y -= 5;
            }

            // player model
            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="58 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="Car type:"/>');
            $this->mainFrame->addComponent($lbl);

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="80 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $gbxInfo->vehicle . '"/>');
            $this->mainFrame->addComponent($lbl);



            // add time
            $y = -39;
            $date = new \DateTime();
            $date->setTimestamp((int)$map->addTime);

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="130 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="Add Date:"/>');
            $this->mainFrame->addComponent($lbl);

            $lbl = new \ManiaLive\Gui\Elements\Xml();
            $lbl->setContent('<label posn="152 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $date->format("d.m.Y") . '"/>');
            $this->mainFrame->addComponent($lbl);
            // now the rest

            $mapData = array("authorTime" => "Author Time:", "goldTime" => "Gold Time:", "silverTime" => "Silver Time:", "bronzeTime" => "Bronze Time:");
            $y = -44;
            foreach ($mapData as $field => $descr) {

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="130 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $descr . '"/>');
                $this->mainFrame->addComponent($lbl);

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="152 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . Time::fromTM($map->{$field}) . '"/>');
                $this->mainFrame->addComponent($lbl);

                $y -= 5;
            }


            $mapData = array("nbCheckpoint" => "Checkpoints:", "nbLap" => "Laps:", "copperPrice" => "Display Cost:", "songFile" => "Song Name:", "modName"=> "Mod Name:");
            foreach ($mapData as $field => $descr) {

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="130 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . $descr . '"/>');
                $this->mainFrame->addComponent($lbl);

                $lbl = new \ManiaLive\Gui\Elements\Xml();
                $lbl->setContent('<label posn="152 ' . $y . ' 0" sizen="35 6" style="TextStaticSmall" text="' . strval($map->{$field}) . '"/>');
                $this->mainFrame->addComponent($lbl);

                $y -= 5;
            }



            // Mod file
            if ($gbxInfo->modUrl) {
                $button_mod = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
                $button_mod->setText(__("Download Mod", $login));
                $button_mod->setPosition(118, -2.6);
                $action = $this->createAction(array($this, 'handleButtonMod'));
                $button_mod->setAction($action);
                $this->mainFrame->addComponent($button_mod);
            }

            // Song file
            if ($gbxInfo->songUrl) {
                $button_song = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
                $button_song->setText(__("Download Song", $login));
                $button_song->setPosition(148, -2.6);
                $action = $this->createAction(array($this, 'handleButtonSong'));
                $button_song->setAction($action);
                $this->mainFrame->addComponent($button_song);
            }
        }
    }

    public function handleButtonVisit($login)
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = (($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) ? "sm" : "tm");

        $link = "http://" . $title . ".mania-exchange.com/tracks/view/" . ManiaExchange::$mxInfo->TrackID;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function handleButtonAward($login)
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

        $title = (($storage->simpleEnviTitle == \ManiaLivePlugins\eXpansion\Helpers\Storage::TITLE_SIMPLE_SM) ? "sm" : "tm");

        $link = "http://" . $title . ".mania-exchange.com/awards/add/" . ManiaExchange::$mxInfo->TrackID;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function handleButtonMod($login)
    {
        $this->connection->sendOpenLink($login, $this->gbxInfo->modUrl, 0);
    }

    public function handleButtonSong($login)
    {
        $this->connection->sendOpenLink($login, $this->gbxInfo->songUrl, 0);
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
