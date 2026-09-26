<?php

namespace ManiaLivePlugins\eXpansion\MapSuggestion;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class MapSuggestion extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    protected $mapWishWindow;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);
        
        $this->registerManialinkCallback('mapWishOk', true);
        $this->registerManialinkCallback('showMapWishWindow');
        $this->registerManialinkCallback('addMapToWish', true, true);

        $this->mapWishWindow = new Window("MapSuggestion\Gui\Windows\MapWish.xml");
        $this->mapWishWindow->setName("MapSuggestion window");
        $this->mapWishWindow->setSize(90, 60);
        $this->mapWishWindow->setTitle('Wish a map');

        $this->registerChatCommand("mapwish", "showMapWishWindow", 0, true);
        $this->setPublicMethod("showMapWishWindow");
    }

    public function showMapWishWindow($login)
    {
        $player = $this->storage->getPlayerObject($login);
        $from = $player->nickName . '$z$s$fff (' . $login . ')';
        $this->mapWishWindow->setParam("from", $from);
        $this->mapWishWindow->show($login);
    }

    public function mapWishOk($login, $entries)
    {
        $mxid = isset($entries['mxid']) ? $entries['mxid'] : '';
        $description = isset($entries['description']) ? $entries['description'] : null;
        $this->addMapToWish($login, $mxid, $description);
    }

    public function addMapToWish($login, $mxid, $description = null)
    {
        if (is_array($mxid)) {
            $mxid = $mxid[0];
        }

        if ($description == null || is_array($description)) {
            $description = 'Add with MX Search Window';
        }

        $player = $this->storage->getPlayerObject($login);
        $from = '"' . $player->nickName . '$z$s$fff (' . $login . ')' . '"';
        $data = "";

        /** @var \ManiaLivePlugins\eXpansion\Core\DataAccess $dataAccess */
        $dataAccess = \ManiaLivePlugins\eXpansion\Core\DataAccess::getInstance();

        if (is_numeric($mxid)) {
            $mxid = intval($mxid);
            if (empty($description)) {
                Gui::showNotice(eXpGetMessage("Looks like you have not entered any description."), $login);
                return;
            }

            $gameData = \ManiaLivePlugins\eXpansion\Helpers\Helper::getPaths()->getGameDataPath();
            $file = $gameData . DIRECTORY_SEPARATOR . "Maps/map_suggestions.txt";

            $data .= $mxid . ";" . $from . ";\"" . $description . "\"\r\n";
            $dataAccess->save($file, $data, true);
            Gui::showNotice(eXpGetMessage("Your wish has been saved\nThe server admin will review the wish\nand add the map if it's good enough."), $login);
            $this->mapWishWindow->erase($login);

            return;
        }
        Gui::showNotice(eXpGetMessage("Looks like mx id is missing or is invalid."), $login);
    }

    public function eXpOnUnload()
    {
        if ($this->mapWishWindow instanceof Window) {
            $this->mapWishWindow->erase();
        }
        $this->mapWishWindow = null;
    }
}
