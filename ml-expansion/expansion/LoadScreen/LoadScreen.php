<?php

namespace ManiaLivePlugins\eXpansion\LoadScreen;

use Exception;
use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\LoadScreen\Gui\Windows\LScreen;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap;

/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of Widgets_Speedometer
 *
 * @author Reaby
 */
class LoadScreen extends ExpPlugin
{
    private $startTime = 0;
    private $isActive = false;
    private $mxImage = "";

    /** @var DataAccess */
    private $dataAccess;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->disableDedicatedEvents(Event::ON_END_MATCH);
        Dispatcher::register(Event::getClass(), $this, Event::ON_END_MATCH, 10);

        $this->dataAccess = DataAccess::getInstance();
        $config = Config::getInstance();
        foreach ($config->screens as $url) {
            Gui::preloadImage($url);
        }
        Gui::preloadUpdate();

        if (Config::getInstance()->screensMx) {
            $this->syncMxImage();
        }
    }

    public function onSettingsChanged(Variable $var)
    {
        if ($var->getName() == "screens") {
            $config = Config::getInstance();
            foreach ($config->screens as $url) {
                Gui::preloadImage($url);
            }
        }
        Gui::preloadUpdate();
    }

    public function onTick()
    {

        $delay = intval(Config::getInstance()->screensDelay);


        if ($this->isActive == true && time() > ($this->startTime + $delay)) {

            $url = "";
            $this->isActive = false;
            $this->startTime = 0;
            $config = Config::getInstance();
            if (count($config->screens) > 0) {
                $index = mt_rand(0, (count($config->screens) - 1));
                $url = $config->screens[$index];
            }

            if (Config::getInstance()->screensMx) {
                if (!empty($this->mxImage)) {
                    $url = $this->mxImage;
                }
            }

            $widget = LScreen::Create(null);
            $widget->setName("loading Screen");
            $widget->setImage($url);
            $widget->show();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->startTime = time();
        $this->isActive = true;

        /*$xml = '<manialink id="ls" version="2" layer="LoadingScreen">
        <quad posn="-160 90 100" sizen="320 180" image="https://cdn.skorlok.com/image/fonde52.jpg"/>
        </manialink>';
        $this->connection->sendDisplayManialinkPage(null, $xml);*/
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->isActive = false;
        LScreen::EraseAll();
    }

    public function onBeginMatch()
    {
        $this->isActive = false;
        LScreen::EraseAll();
        Gui::preloadRemove($this->mxImage);
        Gui::preloadUpdate();

        if (Config::getInstance()->screensMx) {
            $this->syncMxImage();
        }
    }

    private function syncMxImage()
    {
        $this->mxImage = "";

        $uid = urlencode($this->storage->nextMap->uId);

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?fields=MapId,Images&uid=' . $uid;

        $options = array(CURLOPT_HTTPHEADER => array(
            "X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin,
            "Content-Type" => 'application/json',
        ));
        $this->dataAccess->httpCurl($query, array($this, 'xGetImage'), array(), $options);
    }

    public function xGetImage($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        if ($data === false || $code !== 200) {
            return;
        }

        $json = json_decode($data, true);
        if ($json == false || !array_key_exists("Results", $json)) {
            return;
        }

        $map = MxMap::fromArray($json['Results'][0]);

        if (!$map->images || !isset($map->images[0])) {
            return;
        }

        if ($map->images[0]['Width'] > 0 && $map->images[0]['Height'] > 0) {
            $this->mxImage = "https://" . strtolower($this->expStorage->simpleEnviTitle) . ".mania.exchange/mapimage/" . $map->mapId . "/1?hq=true&.webp";
        } else {
            $this->mxImage = "https://" . strtolower($this->expStorage->simpleEnviTitle) . ".mania.exchange/mapimage/" . $map->mapId . "/1?hq=true&.png";
        }

        Gui::preloadImage($this->mxImage);
        Gui::preloadUpdate();
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(Event::getClass(), $this, Event::ON_END_MATCH);
    }
}
