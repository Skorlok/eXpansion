<?php

namespace ManiaLivePlugins\eXpansion\MapSuggestion;

use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\ButtonHook;
use ManiaLivePlugins\eXpansion\ManiaExchange\Hooks\ListButtons;
use ManiaLivePlugins\eXpansion\ManiaExchange\Hooks\ListButtons_Event;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\HookData;

class MapSuggestion extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin implements ListButtons_Event
{

    protected $mapWishWindow;
    protected $actions = array();

    public function eXpOnReady()
    {
        $ah = ActionHandler::getInstance();
        $this->actions['mapWishOk'] = $ah->createAction(array($this, 'mapWishOk'));

        $this->mapWishWindow = new Window("MapSuggestion\Gui\Windows\MapWish.xml");
        $this->mapWishWindow->setName("MapSuggestion window");
        $this->mapWishWindow->setSize(90, 60);
        $this->mapWishWindow->setTitle('Wish a map');
        $this->mapWishWindow->setParam("okAction", $this->actions['mapWishOk']);

        $this->registerChatCommand("mapwish", "showMapWishWindow", 0, true);
        $this->setPublicMethod("showMapWishWindow");
        Dispatcher::register(ListButtons::getClass(), $this);
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
            Gui::showNotice(
                eXpGetMessage(
                    "Your wish has been saved\nThe server "
                    ."admin will review the wish\nand add the map if it's good enough."
                ),
                $login
            );
            $this->mapWishWindow->erase($login);

            return;
        }
        Gui::showNotice(eXpGetMessage("Looks like mx id is missing or is invalid."), $login);
    }

    /**
     *
     * @param HookData $buttons
     * @param          $login
     *
     * @return mixed
     */
    public function hook_ManiaExchangeListButtons($buttons, $login)
    {
        if (isset($buttons->data['queue'])) {
            unset($buttons->data['queue']);
        }

        $button = new ButtonHook();
        $button->callback = array($this, 'addMapToWish');
        $button->label = 'Suggest';
        $buttons->data['suggest'] = $button;
    }


    public function eXpOnUnload()
    {
        $ah = ActionHandler::getInstance();
        foreach ($this->actions as $action) {
            $ah->deleteAction($action);
        }

        if ($this->mapWishWindow instanceof Window) {
            $this->mapWishWindow->erase();
        }
        $this->mapWishWindow = null;
        Dispatcher::unregister(ListButtons::getClass(), $this);
        parent::eXpOnUnload();

    }
}
