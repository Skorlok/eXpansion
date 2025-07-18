<?php

namespace ManiaLivePlugins\eXpansion\MusicBox;

use Exception;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\MusicBox\Gui\Windows\MusicListWindow;
use ManiaLivePlugins\eXpansion\MusicBox\Structures\Song;

class MusicBox extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;
    private $songs = array();
    private $enabled = true;
    private $wishes = array();
    private $music = null;
    private $ignore = false;
    private $counter = 0;
    private $widget;
    private $action;

    /**
     * onLoad()
     * Function called on loading of ManiaLive.
     *
     * @return void
     */
    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        Gui\Windows\MusicListWindow::$musicPlugin = $this;

        $command = $this->registerChatCommand("music", "mbox", 0, true);
        $command = $this->registerChatCommand("music", "mbox", 1, true);
        $command = $this->registerChatCommand("mlist", "mbox", 0, true); // xaseco
        $command = $this->registerChatCommand("mlist", "mbox", 1, true); // xaseco

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();

        $this->action = $aH->createAction(array($this, "musicList"));

        $this->widget = new Widget("MusicBox\Gui\Widgets\CurrentTrackWidget.xml");
        $this->widget->setName("Music Widget");
        $this->widget->setLayer("scorestable");
        $this->widget->setSize(45, 7);
        $this->widget->setParam("action", $this->action);
    }

    /*
     * onReady()
     * Function called when ManiaLive is ready loading.
     *
     * @return void
     */
    public function eXpOnReady()
    {
        try {
            foreach ($this->getMusicCsv() as $music) {
                $this->songs[] = Structures\Song::fromArray($music);
            }
            if ($this->config->shuffle) {
                shuffle($this->songs);
            }
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('MusicBox $fff»» #error#' . $e->getMessage());
            $this->enabled = false;
        }

        $this->music = $this->connection->getForcedMusic();
        $this->showWidget();
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        /** @var Config $config */
        $this->config = Config::getInstance();

        $this->songs = array();
        try {
            foreach ($this->getMusicCsv() as $music) {
                $this->songs[] = Structures\Song::fromArray($music);
            }
            if ($this->config->shuffle) {
                shuffle($this->songs);
            }
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage('MusicBox $fff»» #error#' . $e->getMessage());
            $this->enabled = false;
        }
    }

    public function eXpOnUnload()
    {
        try {
            $this->widget->erase();
            $this->widget = null;
            MusicListWindow::EraseAll();

            Gui\Windows\MusicListWindow::$musicPlugin = null;
			$this->connection->setForcedMusic(false, "");
		} catch (Exception $e) {
			return;
		}
    }

    public function download($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Manialive/eXpansion MusicBox [getter] ver 0.1");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        $ag = AdminGroups::getInstance();

        if ($data === false) {
            $this->console("Server is down");
            $ag->announceToPermission(Permission::SERVER_ADMIN, "Musicbox error: server is unreachable.");
            return false;
        }

        if ($status["http_code"] !== 200) {
            if ($status["http_code"] == 301) {
                $this->console("Link has moved");
                $ag->announceToPermission(Permission::SERVER_ADMIN, "MusicBox error: link is moved!");
                return false;
            }
            $this->console("Http status : " . $status["http_code"]);
            $msg = eXpGetMessage("MusicBox error http-code: %s");
            $ag->announceToPermission(
                Permission::SERVER_ADMIN,
                $msg,
                array($status["http_code"])
            );
            return false;
        }

        return $data;
    }

    public function getMusicCsv()
    {
        $data = $this->download(rtrim($this->config->url, "/") . "/index.csv");
        if (!$data) {
            $this->enabled = false;
            $this->connection->setForcedMusic(false, "");
            return array();
        } else {
            $this->enabled = true;
        }

        $data = explode("\n", $data);

        $x = 0;
        $keys = array();
        $array = array();

        foreach ($data as $line) {
            $x++;
            if (empty($line)) {
                continue;
            }
            if ($x == 1) {
                $keys = array_map(function ($input) {
                    return ltrim($input, "\xEF\xBB\xBF");
                }, str_getcsv($line, ";"));
                continue;
            }
            $array[] = array_combine($keys, array_map('trim', str_getcsv($line, ";")));
        }

        return $array;
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->music = $this->connection->getForcedMusic();
        $this->showWidget();
    }

    public function onBeginMatch()
    {
        $this->ignore = false;
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        if (!$this->enabled || $this->ignore) {
            return;
        }
        $this->ignore = false;
        try {
            $wish = false;

            if (sizeof($this->wishes) != 0) {
                $wish = array_shift($this->wishes);
                $song = $wish->song;
            } else {
                $total = sizeof($this->songs);
                $this->counter = ($this->counter + 1) % $total;
                $song = $this->songs[$this->counter];
            }

            $folder = urlencode($song->folder);
            $folder = str_replace("%2F", "/", $folder);

            $url = trim($this->config->url, "/") . $folder . rawurlencode($song->filename);

            $this->connection->setForcedMusic($this->config->override, $url);
            if ($wish) {
                $text = eXpGetMessage('#variable# %1$s#music# by#variable#  %2$s #music# is been played next requested by #variable# %3$s');
                $this->eXpChatSendServerMessage($text, null, array($song->title, $song->artist, \ManiaLib\Utils\Formatting::stripCodes($wish->player->nickName, "wos")));
            }
        } catch (\Exception $e) {
            $this->console("On EndMatch Error : " . $e->getMessage());
        }
    }

    public function onMapRestart()
    {
        // set warmup, so musicbox doesn't announce next song at podium
        $this->ignore = true;
    }

    /**
     * @return Song[]
     */
    public function getSongs()
    {
        return $this->songs;
    }

    /**
     * showWidget()
     * Helper function, shows the widget.
     *
     * @return void
     */
    public function showWidget()
    {
        if (!$this->enabled) {
            return;
        }

        $music = $this->music;
        $outsong = new Structures\Song();
        if (!empty($music->url)) {
            foreach ($this->songs as $id => $song) {

                $folder = urlencode($song->folder);
                $folder = str_replace("%2F", "/", $folder);

                $url = trim($this->config->url, "/") . $folder . rawurlencode($song->filename);

                if ($url == $music->url) {
                    $outsong = $song;
                    break;
                }
            }
        }
        
        $this->widget->setPosition($this->config->musicWidget_PosX, $this->config->musicWidget_PosY, 0);
        $this->widget->setParam('artist', str_replace('$', '$$', $this->widget->handleSpecialChars($outsong->artist)));
        $this->widget->setParam('title', str_replace('$', '$$', $this->widget->handleSpecialChars($outsong->title)));
        $this->widget->show(null, true);
    }

    /**
     * mbox()
     * Function providing the /mbox command.
     *
     * @param mixed $login  login
     * @param mixed $number number
     *
     * @return void
     */
    public function mbox($login, $number = null)
    {
        if (!$this->enabled) {
            return;
        }

        if (Config::getInstance()->disableJukebox) {
            $this->eXpChatSendServerMessage("#music# Jukeboxing music is disabled.", $login);
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        if ($number == 'list' || $number == null) { // parametres redirect
            $this->musicList($login);
            return;
        }
        if (!is_numeric($number)) { // check for numeric value
            // show error
            $text = '#music#MusicBox $fff»» #error#Invalid song number!';
            $this->eXpChatSendServerMessage($text, $login);
            return;
        }

        $number = (int)$number - 1; // do type conversion


        if (sizeof($this->songs) == 0) {
            $text = '#music#MusicBox $fff»» #error#No songs at music MusicBox!';
            $this->eXpChatSendServerMessage($text, $login);

            return;
        }

        if (!array_key_exists($number, $this->songs)) {
            $text = '#music#MusicBox $fff»» #error#Number entered is not in music list';
            $this->eXpChatSendServerMessage($text, $player);

            return;
        }
        $song = $this->songs[$number];

        foreach ($this->wishes as $id => $wish) {
            if ($wish->player == $player) {
                unset($this->wishes[$id]);
                $this->wishes[] = new Structures\Wish($song, $player);
                $text = '#music#Dropped last entry and #variable#'
                    . $song->title
                    . " #music# by #variable#"
                    . $song->artist
                    . ' $z$s#music# is added to the MusicBox by #variable#'
                    . \ManiaLib\Utils\Formatting::stripCodes($player->nickName, "wos")
                    . '.';
                $this->eXpChatSendServerMessage($text, null);

                return;
            }
        }
        $this->wishes[] = new Structures\Wish($song, $player);
        $text = '#variable#'
            . $song->title
            . " #music# by #variable#"
            . $song->artist
            . '#music# is added to the MusicBox by #variable#'
            . \ManiaLib\Utils\Formatting::stripCodes($player->nickName, "wos") . '.';
        $this->eXpChatSendServerMessage($text, null);
    }

    public function musicList($login)
    {
        try {
            $info = Gui\Windows\MusicListWindow::Create($login);
            $info->setSize(180, 90);
            $info->setTitle("Music available at server: ", count($this->songs));
            $info->centerOnScreen();
            $info->show();
        } catch (\Exception $e) {
            $this->console(" Error while displaying jukebox window: " . $e->getMessage());
        }
    }
}
