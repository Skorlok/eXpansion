<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Windows;

use ManiaLib\Application\ErrorHandling;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Structures\ButtonHook;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\ManiaExchange\Config;
use ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxMap;
use ManiaLivePlugins\eXpansion\ManiaExchange\Hooks\ListButtons;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\HookData;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap as Map;
use oliverde8\AsynchronousJobs\Job\Curl;

class MxSearch extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    /** @var \ManiaLive\Gui\Controls\Pager */
    protected $pager;

    /** @var  \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var  \ManiaLive\Data\Storage */
    protected $storage;
    protected $maps;
    protected $frame;
    protected $searchframe;
    protected $inputAuthor;
    protected $inputMapName;
    protected $buttonSearch;
    protected $actionSearch;
    protected $header;
    protected $style;
    protected $lenght;
    protected $items = array();
    /** @var  CheckboxScripted */
    protected $filter;

    public $mxPlugin;
    public $fields = "fields=MapId,TitlePack,Environment,VehicleName,GbxMapName,Difficulty,MoodFull,Tags,Length,AwardCount,Uploader.Name";

    protected function onConstruct()
    {
        parent::onConstruct();

        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->setTitle("ManiaExchange");

        $this->searchframe = new \ManiaLive\Gui\Controls\Frame();
        $this->searchframe->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->inputMapName = new Inputbox("mapName");
        $this->inputMapName->setLabel("Map name");
        $this->searchframe->addComponent($this->inputMapName);
        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(3, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->searchframe->addComponent($spacer);

        $this->inputAuthor = new Inputbox("author");
        $this->inputAuthor->setLabel("Author name");
        $this->searchframe->addComponent($this->inputAuthor);
        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(3, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->searchframe->addComponent($spacer);

        $items = array("All", "Race", "Fullspeed", "Tech", "RPG", 'LOL', 'PressForward', 'SpeedTech', 'Multilap', 'Offroad');
        $this->style = new \ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown("style", $items);
        $this->searchframe->addComponent($this->style);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(3, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->searchframe->addComponent($spacer);

        $items = array("All", "0-15sec", "15-30sec", "30-45sec", "45-1min", "1min+");
        $this->lenght = new \ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown("length", $items);
        $this->searchframe->addComponent($this->lenght);

        $this->filter = new CheckboxScripted();
        $this->filter->setText("Maps from all titles pack");
        $this->searchframe->addComponent($this->filter);

        $spacer = new \ManiaLib\Gui\Elements\Quad();
        $spacer->setSize(8, 4);
        $spacer->setStyle(\ManiaLib\Gui\Elements\Icons64x64_1::EmptyIcon);
        $this->searchframe->addComponent($spacer);

        $this->actionSearch = ActionHandler::getInstance()->createAction(array($this, "actionOk"));

        $this->buttonSearch = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonSearch->setContent('<frame posn="186 0 1" scale="0.8">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(24, 6, "Search", null, null, '0a0', null, null, $this->actionSearch, null, null, null, null, null, null) . '</frame>');
        $this->searchframe->addComponent($this->buttonSearch);

        $this->mainFrame->addComponent($this->searchframe);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->frame->addComponent($this->pager);

        $this->items = array();
        $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "Click Search with empty terms to get the most recent maps", $this->sizeX - 6));

        $this->mainFrame->addComponent($this->frame);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setSizeX($this->sizeX);
        $this->pager->setSize($this->sizeX - 3, $this->sizeY - 12);
        $this->searchframe->setPosition(0, -3);
        $this->frame->setPosition(0, -6);
    }

    public function setPlugin($plugin)
    {
        $this->mxPlugin = $plugin;
    }

    public function search($login, $trackname = "", $author = "", $style = null, $length = null, $filter = false)
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->pager->clearItems();
        $this->items = array();
        $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "Searching, please wait", $this->sizeX - 6));
        $this->redraw($this->getRecipient());

        $info = $this->connection->getVersion();

        /**
         * @var Storage $storage
         */
        $storage = Storage::getInstance();

        if ($storage->simpleEnviTitle == Storage::TITLE_SIMPLE_SM) {
            $out = "";
            if ($trackname != null) {
                $out .= "&name=" . rawurlencode($trackname);
            }
            if ($author != null) {
                $out .= "&author=" . rawurlencode($author);
            }
            if ($style != null) {
                $out .= "&tag=" . $style;
            }
            if (!$filter) {
                $pack = explode("@", $info->titleId);
                $out .= "&titlepack=" . $pack[0];
            }

            $query = 'https://sm.mania.exchange/api/maps?' . $this->fields . "&" . $out . '&order1=0&count=100';
        } else {
            $out = "";
            if ($trackname != null) {
                $out .= "&name=" . rawurlencode($trackname);
            }
            if ($author != null) {
                $out .= "&author=" . rawurlencode($author);
            }
            if ($style != null) {
                $out .= "&tag=" . $style;
            }
            if ($length != null) {
                switch ($length) {
                    case 0:
                        $out .= "&lengthmin=0&lengthmax=15000";
                        break;
                    case 1:
                        $out .= "&lengthmin=15000&lengthmax=30000";
                        break;
                    case 2:
                        $out .= "&lengthmin=30000&lengthmax=45000";
                        break;
                    case 3:
                        $out .= "&lengthmin=45000&lengthmax=60000";
                        break;
                    case 4:
                        $out .= "&lengthmin=60000";
                        break;
                }
            }
            if (!$filter) {
                $pack = explode("@", $info->titleId);
                $out .= "&titlepack=" . $pack[0];
            }


            $query = "https://tm.mania.exchange/api/maps?" . $this->fields . "&" . $out . '&order1=0&count=100';
        }

        $access = \ManiaLivePlugins\eXpansion\Core\DataAccess::getInstance();

        $options = array(CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => array("Content-Type" => "application/json"));
        if ($length !== null) {
            $this->lenght->setSelected(intval($length) + 1);
        }
        if ($style !== null) {
            $this->style->setSelected(intval($style));
        }
        $key = "";
        $config = Config::getInstance();

        if ($config->key) {
            $key = "&key=" . $config->key;
        }

        $access->httpCurl($query . $key, array($this, "xSearch"), null, $options);

        return;
    }

    /**
     * @param Curl $job
     * @param      $jobData
     */
    public function xSearch($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        $data = $job->getResponse();

        // if user has closed the window... return, since otherwise we have fatal error.
        if ($this->pager == null) {
            return;
        }

        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->pager->clearItems();
        $this->items = array();

        if ($code !== 200) {
            $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "search returned a http error " . $code, $this->sizeX - 6));
            $this->redraw();
            return;
        }

        try {
            if (!$data) {
                $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "search returned no data", $this->sizeX - 6));
                $this->redraw();
                return;
            }
            $json = json_decode($data, true);

            if (isset($json[0]) && !isset($json['Results'])) {
                $newArray['Results'] = $json;
                $json = $newArray;
            }

            if ($json === false) {
                $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "Error while processing json data from MX.", $this->sizeX - 6));
                $this->redraw();
                return;
            }
            if (!array_key_exists("Results", $json)) {
                $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "Error: MX returned no results.", $this->sizeX - 6));
                $this->redraw();
                return;
            }

            $this->maps = Map::fromArrayOfArray($json['Results']);


            $login = $this->getRecipient();
            $isadmin = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_ADD_MX);

            $buttons = $this->hookButtons($isadmin);

            $x = 0;
            if (empty($this->maps)) {
                $this->pager->addItem(new \ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Controls\MxInfo(0, "No maps found with this search terms.", $this->sizeX - 6));
            } else {
                foreach ($this->maps as $map) {
                    $this->items[$x] = new MxMap($x, $map, $this, $buttons, $this->sizeX - 9);
                    $this->pager->addItem($this->items[$x]);
                    $x++;
                }
            }

            $this->redraw();
        } catch (\Exception $ex) {
            Helper::logError(ErrorHandling::computeMessage($ex));
        }
    }

    protected function hookButtons($isadmin)
    {
        $buttons = array();

        $config = Config::getInstance();

        if ($isadmin) {
            $buttons['install'] = new ButtonHook();
            $buttons['install']->callback = array($this, 'addMap');
            $buttons['install']->label = 'Install';
        }

        if ($config->mxVote_enable) {
            $buttons['queue'] = new ButtonHook();
            $buttons['queue']->callback = array($this, 'mxVote');
            $buttons['queue']->label = 'Queue';
        }

        $hook = new HookData();
        $hook->data = $buttons;

        \ManiaLive\Event\Dispatcher::dispatch(new ListButtons(ListButtons::ON_BUTTON_LIST_CREATE, $hook, 'test'));

        return $hook->data;
    }

    public function addMap($login, $mapId)
    {
        $this->mxPlugin->addMap($login, $mapId);
    }

    public function mxVote($login, $mapId)
    {
        $this->mxPlugin->mxVote($login, $mapId);
    }

    public function actionOk($login, $args)
    {
        $style = null;
        $length = null;
        if ($args['style']) {
            $style = intval($args['style']);
        }

        if (intval($args['length']) != 0) {
            $length = intval($args['length']) - 1;
        }

        $this->filter->setArgs($args);
        $this->search($login, $args['mapName'], $args['author'], $style, $length, $this->filter->getStatus());
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = array();
        $this->maps = null;
        $this->style->destroy();
        $this->lenght->destroy();
        $this->inputMapName->destroy();
        $this->inputAuthor->destroy();
        $this->pager->destroy();
        $this->pager = null;
        $this->connection = null;
        $this->storage = null;
        $this->searchframe->clearComponents();
        $this->searchframe->destroy();
        $this->destroyComponents();
        parent::destroy();
    }
}
