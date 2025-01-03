<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Windows;

use Exception;
use ManiaLib\Gui\Elements\Label;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Helpers\GbxReader\Map;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use Maniaplanet\DedicatedServer\Connection;

/**
 * Description of MapInfo
 *
 * @author Petri Järvisalo <petri.jarvisalo@gmail.com>
 */
class MapInfo extends Window
{
    protected $frame;
    protected $frame2;

    /** @var Connection */
    protected $connection;

    protected $gbxInfo;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->frame = new Frame();
        $this->frame->setPosition(-35, -6);

        $this->addComponent($this->frame);

        $this->frame2 = clone $this->frame;
        $this->frame2->setPosition(40);
        $this->addComponent($this->frame);
        $this->addComponent($this->frame2);
    }

    public function setMap($uid = null)
    {
        $storage = Storage::getInstance();
        if ($uid == null) {
            $uid = $storage->currentMap->uId;
        }

        $x = 35;
        $y = 0;
        $this->frame->clearComponents();
        $this->frame2->clearComponents();

        $map = ArrayOfObj::getObjbyPropValue($storage->maps, "uId", $uid);
        if ($map === false) {
            return false;
        }
        $map->{"nick"} = "n/a";

        $this->setTitle("Map Info", $map->name);
        $lbl = new Label($x, 6);
        $lbl->setPosition($x, $y);
        $lbl->setText("Unique id");
        $this->frame->addComponent($lbl);
        $lbl = new Inputbox("", $x, 5);
        $lbl->setPosition($x * 2, $y);
        $lbl->setText($map->uId);

        $this->frame->addComponent($lbl);

        $model = "commonCar";
        try {
            $this->connection = Singletons::getInstance()->getDediConnection();
            $mapPath = $this->connection->getMapsDirectory();

            if (!file_exists($mapPath . DIRECTORY_SEPARATOR . $map->fileName)) {
                return;
            }

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

        $y -= 5;
        $mapData = array("fileName" => "File Name", "name" => "Name", "author" => "Author", "nick" => "Author Nick",
            "mood" => "Mood", "mapStyle" => "Map Style", "mapType" => "Map Type", "environnement" => "Environment");


        foreach ($mapData as $field => $descr) {
            $lbl = new Label($x, 6);
            $lbl->setPosition($x, $y);
            $lbl->setText($descr);
            $this->frame->addComponent($lbl);
            $lbl = new Label("", $x, 6);
            $lbl->setPosition($x * 2, $y);
            $lbl->setText($map->{$field});
            $this->frame->addComponent($lbl);
            $y -= 5;
        }

        // player model
        $lbl = new Label($x, 6);
        $lbl->setPosition($x, $y);
        $lbl->setText("Car type");
        $this->frame->addComponent($lbl);
        $lbl = new Label("", $x, 6);
        $lbl->setPosition($x * 2, $y);
        $lbl->setText($gbxInfo->vehicle);
        $this->frame->addComponent($lbl);

        // Mod file
        if ($gbxInfo->modUrl) {
            $this->button_mod = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
            $this->button_mod->setText(__("Download Mod"));
            $this->button_mod->setPosition(80, -85);
            $action = $this->createAction(array($this, 'handleButtonMod'));
            $this->button_mod->setAction($action);
            $this->frame->addComponent($this->button_mod);
        }

        // Song file
        if ($gbxInfo->songUrl) {
            $this->button_song = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(37.5, 6.25);
            $this->button_song->setText(__("Download Song"));
            $this->button_song->setPosition(110, -85);
            $action = $this->createAction(array($this, 'handleButtonSong'));
            $this->button_song->setAction($action);
            $this->frame->addComponent($this->button_song);
        }

        $lbl = new Label($x, 6);
        $lbl->setPosition($x, $y);
        $this->frame->addComponent($lbl);

        // frame 2
        $y = 0;

        // add time
        $lbl = new Label($x, 6);
        $lbl->setPosition($x, $y);
        $lbl->setText("Add Date");
        $this->frame2->addComponent($lbl);
        $lbl = new Label("", $x, 6);
        $lbl->setPosition($x * 2, $y);
        $date = new \DateTime();
        $date->setTimestamp((int)$map->addTime);

        $lbl->setText($date->format("d.m.Y"));
        $this->frame2->addComponent($lbl);
        $y -= 5;

        // time datas
        $mapData = array(
            "authorTime" => "Author Time", "goldTime" => "Gold Time",
            "silverTime" => "Silver Time", "bronzeTime" => "Bronze Time"
        );
        foreach ($mapData as $field => $descr) {
            $lbl = new Label($x, 6);
            $lbl->setPosition($x, $y);
            $lbl->setText($descr);
            $this->frame2->addComponent($lbl);
            $lbl = new Label("", $x, 6);
            $lbl->setPosition($x * 2, $y);
            $lbl->setText(Time::fromTM($map->{$field}));
            $this->frame2->addComponent($lbl);
            $y -= 5;
        }

        // integer values
        $mapData = array("nbCheckpoint" => "Checkpoints", "nbLap" => "Laps", "copperPrice" => "Display Cost", "songFile" => "Song Name", "modName"=> "Mod Name");
        foreach ($mapData as $field => $descr) {
            $lbl = new Label($x, 6);
            $lbl->setPosition($x, $y);
            $lbl->setText($descr);
            $this->frame2->addComponent($lbl);
            $lbl = new Label("", $x, 6);
            $lbl->setPosition($x * 2, $y);
            $lbl->setText(strval($map->{$field}));
            $this->frame2->addComponent($lbl);
            $y -= 5;
        }

        return true;
    }

    public function handleButtonMod($login)
    {
        $this->connection->sendOpenLink($login, $this->gbxInfo->modUrl, 0);
    }

    public function handleButtonSong($login)
    {
        $this->connection->sendOpenLink($login, $this->gbxInfo->songUrl, 0);
    }

    protected function onHide()
    {
        parent::onHide();
        $this->connection = null;
    }
}
