<?php

namespace ManiaLivePlugins\eXpansion\Maps\Gui\Windows;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Maps\Gui\Controls\Mapitem;
use ManiaLivePlugins\eXpansion\Maps\Maps;

class Maplist extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    public $records = array();
    public static $mapsPlugin = null;
    public static $localrecordsLoaded = false;
    protected $history = array();
    protected $items = array();

    /** @var \ManiaLive\Gui\Controls\Pager */
    protected $pager;
    protected $btnRemoveAll;
    protected $frame;
    protected $title_mapName;
    protected $title_envi;
    protected $title_authorName;
    protected $title_goldTime;
    protected $title_rank;
    protected $title_rating;
    protected $title_actions;
    protected $searchBox;
    protected $searchframe;
    protected $btn_search;
    protected $btn_search2;
    protected $btn_sortNewest;
    protected $actionRemoveAll;
    protected $actionRemoveAllf;
    protected $currentMap = null;
    protected $titlebg;

    /** @var  \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var  \ManiaLive\Data\Storage */
    protected $storage;
    protected $widths = array(5, 15, 4, 4, 3, 3, 3, 1, 1, 1);

    /** @var \ManiaLivePlugins\eXpansion\Maps\Structures\SortableMap[] */
    protected $maps = array();
    
    public $mapForLogin = array();

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $sizeX = 120;
        $scaledSizes = Gui::getScaledSize($this->widths, $sizeX);

        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->titlebg = new \ManiaLivePlugins\eXpansion\Gui\Elements\TitleBackGround($sizeX, 6);
        $this->mainFrame->addComponent($this->titlebg);


        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize($sizeX, 4);
        $this->frame->setAlign("left", "top");
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->mainFrame->addComponent($this->frame);

        $textStyle = "TextCardRaceRank";
        $textColor = "000";
        $textSize = "1.5";

        $this->title_authorName = new \ManiaLib\Gui\Elements\Label();
        $this->title_authorName->setText(__("Author", $login));
        $this->title_authorName->setStyle($textStyle);
        $this->title_authorName->setTextColor($textColor);
        $this->title_authorName->setAction($this->createAction(array($this, "updateList"), "author"));
        $this->title_authorName->setTextSize($textSize);
        $this->frame->addComponent($this->title_authorName);

        $this->title_mapName = new \ManiaLib\Gui\Elements\Label();
        $this->title_mapName->setText(__("Map name", $login));
        $this->title_mapName->setStyle($textStyle);
        $this->title_mapName->setTextColor($textColor);
        $this->title_mapName->setAction($this->createAction(array($this, "updateList"), "name"));
        $this->title_mapName->setTextSize($textSize);


        $this->frame->addComponent($this->title_mapName);

        $this->title_envi = new \ManiaLib\Gui\Elements\Label();
        $this->title_envi->setText(__("Title", $login));
        $this->title_envi->setStyle($textStyle);
        $this->title_envi->setTextColor($textColor);
        $this->title_envi->setTextSize($textSize);
        $this->frame->addComponent($this->title_envi);

        $this->title_goldTime = new \ManiaLib\Gui\Elements\Label();
        $this->title_goldTime->setText(__("Length", $login));
        $this->title_goldTime->setStyle($textStyle);
        $this->title_goldTime->setTextColor($textColor);
        $this->title_goldTime->setAction($this->createAction(array($this, "updateList"), "goldTime"));
        $this->title_goldTime->setTextSize($textSize);
        $this->frame->addComponent($this->title_goldTime);

        $this->title_rank = new \ManiaLib\Gui\Elements\Label();
        $this->title_rank->setText(__("Record", $login));
        $this->title_rank->setAlign("center");
        $this->title_rank->setStyle($textStyle);
        $this->title_rank->setTextColor($textColor);
        $this->title_rank->setAction($this->createAction(array($this, "updateList"), "localrecord"));
        $this->title_rank->setTextSize($textSize);
        $this->frame->addComponent($this->title_rank);

        $this->title_rating = new \ManiaLib\Gui\Elements\Label();
        $this->title_rating->setText(__("Rating", $login));
        $this->title_rating->setAlign("center");
        $this->title_rating->setStyle($textStyle);
        $this->title_rating->setTextColor($textColor);
        $this->title_rating->setAction($this->createAction(array($this, "updateList"), "rating"));

        $this->title_rating->setTextSize($textSize);
        $this->frame->addComponent($this->title_rating);

        $this->title_actions = new \ManiaLib\Gui\Elements\Label();
        $this->title_actions->setText(__("Actions", $login));

        $this->title_actions->setTextSize($textSize);
        $this->title_actions->setTextColor($textColor);
        $this->title_actions->setStyle($textStyle);
        $this->frame->addComponent($this->title_actions);

        $this->searchframe = new \ManiaLive\Gui\Controls\Frame();
        $this->addComponent($this->searchframe);

        $this->searchBox = new \ManiaLive\Gui\Elements\Xml();
        $this->searchBox->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("searchbox", 35, true, __("Search maps", $login), null, null, null) . '</frame>');
        $this->searchframe->addComponent($this->searchBox);

        $this->btn_search = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_search->setContent('<frame posn="38 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, __("Search Map", $login), null, null, '0a0', null, null, $this->createAction(array($this, "doSearchMap")), null, null, null, null, null, null) . '</frame>');
        $this->searchframe->addComponent($this->btn_search);

        $this->btn_search2 = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_search2->setContent('<frame posn="69.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, __("Search Author", $login), null, null, '0a0', null, null, $this->createAction(array($this, "doSearchAuthor")), null, null, null, null, null, null) . '</frame>');
        $this->searchframe->addComponent($this->btn_search2);

        $this->btn_sortNewest = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_sortNewest->setContent('<frame posn="101 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, __("Sort By Add Date", $login), null, null, null, null, null, $this->createAction(array($this, "updateList"), "addTime"), null, null, null, null, null, null) . '</frame>');
        $this->searchframe->addComponent($this->btn_sortNewest);

        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\OptimizedPager();
        $this->mainFrame->addComponent($this->pager);

        if (array_key_exists($login, Maps::$playerSortModes) == false) {
            Maps::$playerSortModes[$login] = new \ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode();
        }

        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $this->actionRemoveAllf = $this->createAction(array($this, "removeAllMaps"));
            $this->actionRemoveAll = Gui::createConfirm($this->actionRemoveAllf);

            $this->btnRemoveAll = new \ManiaLive\Gui\Elements\Xml();
            $this->btnRemoveAll->setContent('<frame posn="175 2 1" scale="0.666666667">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(35, 6, '$d00' . __("Clear Maplist", $login), null, null, null, null, null, $this->actionRemoveAll, null, null, null, null, null, null) . '</frame>');
            $this->mainFrame->addComponent($this->btnRemoveAll);
        }

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }

    static function Initialize($mapsPlugin)
    {
        self::$mapsPlugin = $mapsPlugin;
    }

    public function removeMap($login, \Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        self::$mapsPlugin->removeMap($login, $map);
        $this->RedrawAll();
    }

    public function trashMap($login, \Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        self::$mapsPlugin->eraseMap($login, $map);
        $this->RedrawAll();
    }

    public function jumpMap($login, \Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        self::$mapsPlugin->gotoMap($login, $map);
    }

    public function queueMap($login, \Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        self::$mapsPlugin->playerQueueMap($login, $map, false);
    }

    public function showRec($login, \Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        self::$mapsPlugin->showRec($login, $map);
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        $this->searchframe->setPosition(2, -7);

        $this->pager->setSize($this->getSizeX() - 6, $this->getSizeY() - 20);

        $this->pager->setPosition(3, -17, 5);

        $this->titlebg->setPosition(3, -9.5);
        $this->titlebg->setSize($this->getSizeX() - 6, 6.5);
        $this->frame->setPosition(3, -8.25);

        $scaledSizes = Gui::getScaledSize($this->widths, ($this->getSizeX() - 10));


        $this->title_authorName->setSizeX($scaledSizes[0]);
        $this->title_mapName->setSizeX($scaledSizes[1]);
        $this->title_envi->setSizeX($scaledSizes[2]);
        $this->title_goldTime->setSizeX($scaledSizes[3]);
        $this->title_rank->setSizeX($scaledSizes[4]);
        $this->title_rating->setSizeX($scaledSizes[5]);
        $this->title_actions->setSizeX($scaledSizes[6]);
    }

    public function removeAllMaps($login)
    {
        $mapsAtServer = array();
        $maps = $this->connection->getMapList(-1, 0);
        $currentMap = $this->connection->getCurrentMapInfo();

        foreach ($maps as $map) {
            if ($map->fileName != $currentMap->fileName) {
                $mapsAtServer[] = $map->fileName;
            }
        }

        try {
            $this->connection->RemoveMapList($mapsAtServer);
            $this->connection->chatSendServerMessage("Maplist cleared with:" . count($mapsAtServer) . " maps!", $login);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("Oops, couldn't clear the map list. server said:" . $e->getMessage());
        }
    }

    public function updateList($login, $column = null, $sortType = null, $maps = null)
    {
        $this->pager->clearItems();

        if ($maps == null) {
            if (isset($this->mapForLogin[$login])) {
                $maps = $this->mapForLogin[$login];
            } else {
                $maps = $this->storage->maps;
            }
        } else {
            $this->mapForLogin[$login] = $maps;
        }

        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = array();


        $isAdmin = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP);

        $this->maps = array();

        $maxrec = \ManiaLivePlugins\eXpansion\LocalRecords\Config::getInstance()->recordsCount;

        foreach ($maps as $map) {
            if (!isset($map->strippedName)) {
                $map->strippedName = \ManiaLib\Utils\Formatting::stripStyles($map->name);
            }

            if (!empty(Maps::$searchTerm[$login])) {

                if (isset(Maps::$searchField[$login])) {
                    $field = Maps::$searchField[$login];
                } else {
                    $field = null;
                }

                if ($field == "name" || $field == "author") {
                    
                    if ($field == "name") {
                        $field = "strippedName";
                    }

                    $substring = $this->shortest_edit_substring(strtolower(Maps::$searchTerm[$login]), strtolower(\ManiaLib\Utils\Formatting::stripStyles($map->{$field})));
                    $dist = $this->edit_distance(strtolower(Maps::$searchTerm[$login]), $substring);
                    if (!empty($substring) && $dist < 2) {
                        $this->maps[] = $map;
                    }
                    
                } else {

                    $substring_name = $this->shortest_edit_substring(strtolower(Maps::$searchTerm[$login]), strtolower(\ManiaLib\Utils\Formatting::stripStyles($map->strippedName)));
                    $dist_name = $this->edit_distance(strtolower(Maps::$searchTerm[$login]), $substring_name);

                    $substring_author = $this->shortest_edit_substring(strtolower(Maps::$searchTerm[$login]), strtolower(\ManiaLib\Utils\Formatting::stripStyles($map->author)));
                    $dist_author = $this->edit_distance(strtolower(Maps::$searchTerm[$login]), $substring_author);

                    if ((!empty($substring_name) && $dist_name < 2) || (!empty($substring_author) && $dist_author < 2)) {
                        $this->maps[] = $map;
                    }

                }
            } else {
                $this->maps[] = $map;
            }
        }
        if (isset(Maps::$searchTerm[$login])) {
            $this->searchBox->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("searchbox", 35, true, __("Search maps", $login), $this->handleSpecialChars(Maps::$searchTerm[$login]), null, null) . '</frame>');
        }

        if ($column !== null) {
            if ($column != Maps::$playerSortModes[$login]->column) {
                Maps::$playerSortModes[$login]->sortMode = 1;
                Maps::$playerSortModes[$login]->column = $column;
            } else {
                Maps::$playerSortModes[$login]->sortMode = (Maps::$playerSortModes[$login]->sortMode + 1) % 3;
            }
        }

        // select sorttype and sort the list
        $sortmode = SORT_STRING;
        switch (Maps::$playerSortModes[$login]->column) {
            case "rating":
                if (Maps::$playerSortModes[$login]->sortMode == 1) {
                    self::sortByRankingDesc($this->maps);
                }
                if (Maps::$playerSortModes[$login]->sortMode == 2) {
                    self::sortByRankingAsc($this->maps);
                }
                break;
            case "localrecord":
                if (Maps::$playerSortModes[$login]->sortMode == 1) {
                    self::sortByRecordAsc($this->maps, $login);
                }
                if (Maps::$playerSortModes[$login]->sortMode == 2) {
                    self::sortByRecordDesc($this->maps, $login);
                }
                break;
            case "name":
                if (Maps::$playerSortModes[$login]->sortMode == 1) {
                    \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortAsc($this->maps, "strippedName");
                }
                if (Maps::$playerSortModes[$login]->sortMode == 2) {
                    \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortDesc($this->maps, "strippedName");
                }
                break;
            default:
                if (Maps::$playerSortModes[$login]->sortMode == 1) {
                    \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortAsc($this->maps, Maps::$playerSortModes[$login]->column);
                }
                if (Maps::$playerSortModes[$login]->sortMode == 2) {
                    \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortDesc($this->maps, Maps::$playerSortModes[$login]->column);
                }
        }


        // add items to display
        $x = 0;


        foreach ($this->maps as $sortableMap) {
            $isHistory = false;
            if (array_key_exists($sortableMap->uId, $this->history)) {
                $isHistory = true;
            }

            $queueMapAction = $this->createAction(array($this, 'queueMap'), $sortableMap);
            $removeMapAction = $this->createAction(array($this, 'removeMap'), $sortableMap);
            $trashMapAction = $this->createAction(array($this, 'trashMap'), $sortableMap);
            $jumpMapAction = $this->createAction(array($this, 'jumpMap'), $sortableMap);
            $showRecsAction = $this->createAction(array($this, 'showRec'), $sortableMap);
            $showInfoAction = $this->createAction(array($this, 'showInfo'), $sortableMap->uId);

            if (isset($sortableMap->mapRating)) {
                $rate = ($sortableMap->mapRating->rating / 5) * 100;
                $rate = round($rate) . "%" . '  $n' . "(" . $sortableMap->mapRating->totalvotes . ")";
                if ($sortableMap->mapRating->rating == -1) {
                    $rate = " - ";
                }
            } else {
                $rate = " - ";
            }

            $localrecord = "-";
            if (isset($sortableMap->localRecords) && isset($sortableMap->localRecords[$login])) {
                $localrecord = $sortableMap->localRecords[$login] + 1;
            }

            $this->pager->addSimpleItems(array(Gui::fixString($sortableMap->name) => $queueMapAction,
                Gui::fixString($sortableMap->author) => -1,
                $sortableMap->environnement => -1,
                \ManiaLive\Utilities\Time::fromTM($sortableMap->goldTime) => -1,
                $localrecord => -1,
                $rate => -1,
                "Info" => $showInfoAction,
                "Recs" => $showRecsAction,
                "Jump" => $jumpMapAction,
                "x" => $removeMapAction,
                "Trash" => $trashMapAction
            ));
            $x++;
        }

        Mapitem::$ColumnWidths = $this->widths;
        $this->pager->setContentLayout('\ManiaLivePlugins\eXpansion\Maps\Gui\Controls\Mapitem');
        $this->pager->update($this->getRecipient());
        $this->redraw($this->getRecipient());
    }

    public function showInfo($login, $uid)
    {
        $window = MapInfo::create($login);
        if (!$window->setMap($uid)) {
            return;
        }
        $window->setSize(160, 90);
        $window->show($login);
    }

    public function setRecords($records)
    {
        self::$localrecordsLoaded = true;
        $this->records = $records;
    }

    /** @param \Maniaplanet\DedicatedServer\Structures\Map[] $history */
    public function setHistory($history)
    {
        $this->history = array();
        foreach ($history as $map) {
            $this->history[$map->uId] = true;
        }
    }

    public function setCurrentMap(\Maniaplanet\DedicatedServer\Structures\Map $map)
    {
        $this->currentMap = $map;
    }

    public function destroy()
    {
        $login = $this->getRecipient();
        if (isset($this->mapForLogin[$login])) {
            unset($this->mapForLogin[$login]);
        }
        foreach ($this->items as $item) {
            $item->erase();
        }
        $this->items = null;
        $this->pager->destroy();
        $this->destroyComponents();

        ActionHandler::getInstance()->deleteAction($this->actionRemoveAll);
        ActionHandler::getInstance()->deleteAction($this->actionRemoveAllf);

        parent::destroy();
    }

    public function doSearchMap($login, $entries)
    {
        Maps::$searchTerm[$login] = $entries['searchbox'];
        Maps::$searchField[$login] = "name";
        $this->updateList($login);
        $this->redraw($login);
    }

    public function doSearchAuthor($login, $entries)
    {
        Maps::$searchTerm[$login] = $entries['searchbox'];
        Maps::$searchField[$login] = "author";
        $this->updateList($login);
        $this->redraw($login);
    }

    // utility function - returns the key of the array minimum
    public function array_min_key($arr)
    {
        $min_key = null;
        $min = PHP_INT_MAX;
        foreach ($arr as $k => $v) {
            if ($v < $min) {
                $min = $v;
                $min_key = $k;
            }
        }

        return $min_key;
    }
    /*
      Following code is from experts-exchange answer:
     */

    // Calculate the edit distance between two strings
    public function edit_distance($string1, $string2)
    {
        $m = strlen($string1);
        $n = strlen($string2);
        $d = array();

        // the distance from '' to substr(string,$i)
        for ($i = 0; $i <= $m; $i++) {
            $d[$i][0] = $i;
        }
        for ($i = 0; $i <= $n; $i++) {
            $d[0][$i] = $i;
        }

        // fill-in the edit distance matrix
        for ($j = 1; $j <= $n; $j++) {
            for ($i = 1; $i <= $m; $i++) {
                // Using, for example, the levenshtein distance as edit distance
                list($p_i, $p_j, $cost) = $this->levenshtein_weighting($i, $j, $d, $string1, $string2);
                $d[$i][$j] = $d[$p_i][$p_j] + $cost;
            }
        }

        return $d[$m][$n];
    }

    // Helper function for edit_distance()
    public function levenshtein_weighting($i, $j, $d, $string1, $string2)
    {
        // if the two letters are equal, cost is 0
        if ($string1[$i - 1] === $string2[$j - 1]) {
            return array($i - 1, $j - 1, 0);
        }

        // cost we assign each operation
        $cost['delete'] = 1;
        $cost['insert'] = 1;
        $cost['substitute'] = 1;

        // cost of operation + cost to get to the substring we perform it on
        $total_cost['delete'] = $d[$i - 1][$j] + $cost['delete'];
        $total_cost['insert'] = $d[$i][$j - 1] + $cost['insert'];
        $total_cost['substitute'] = $d[$i - 1][$j - 1] + $cost['substitute'];

        // return the parent array keys of $d and the operation's cost
        $min_key = $this->array_min_key($total_cost);
        if ($min_key == 'delete') {
            return array($i - 1, $j, $cost['delete']);
        } elseif ($min_key == 'insert') {
            return array($i, $j - 1, $cost['insert']);
        } else {
            return array($i - 1, $j - 1, $cost['substitute']);
        }
    }

    // attempt to find the substring of $haystack most closely matching $needle
    public function shortest_edit_substring($needle, $haystack)
    {
        // initialize edit distance matrix
        $m = strlen($needle);
        $n = strlen($haystack);
        $d = array();
        for ($i = 0; $i <= $m; $i++) {
            $d[$i][0] = $i;
            $backtrace[$i][0] = null;
        }
        // instead of strlen, we initialize the top row to all 0's
        for ($i = 0; $i <= $n; $i++) {
            $d[0][$i] = 0;
            $backtrace[0][$i] = null;
        }

        // same as the edit_distance calculation, but keep track of how we got there
        for ($j = 1; $j <= $n; $j++) {
            for ($i = 1; $i <= $m; $i++) {
                list($p_i, $p_j, $cost) = $this->levenshtein_weighting($i, $j, $d, $needle, $haystack);
                $d[$i][$j] = $d[$p_i][$p_j] + $cost;
                $backtrace[$i][$j] = array($p_i, $p_j);
            }
        }

        // now find the minimum at the bottom row
        $min_key = $this->array_min_key($d[$m]);
        $current = array($m, $min_key);
        $parent = $backtrace[$m][$min_key];

        // trace up path to the top row
        while (!is_null($parent)) {
            $current = $parent;
            $parent = $backtrace[$current[0]][$current[1]];
        }

        // and take a substring based on those results
        $start = $current[1];
        $end = $min_key;

        return substr($haystack, $start, $end - $start);
    }

    protected static function sortByRankingAsc(&$array)
    {
        usort(
            $array,
            function ($a, $b) {
                if (!isset($a->mapRating) && !isset($b->mapRating)) {
                    return 0;
                } elseif (!isset($a->mapRating)) {
                    return -1;
                } elseif (!isset($b->mapRating)) {
                    return 1;
                } else {
                    return $a->mapRating->rating > $b->mapRating->rating ? 1 : -1;
                }
            }
        );
    }

    protected static function sortByRankingDesc(&$array)
    {
        usort(
            $array,
            function ($a, $b) {
                if (!isset($a->mapRating) && !isset($b->mapRating)) {
                    return 0;
                } elseif (!isset($a->mapRating)) {
                    return 1;
                } elseif (!isset($b->mapRating)) {
                    return -1;
                } else {
                    return $a->mapRating->rating > $b->mapRating->rating ? -1 : 1;
                }
            }
        );
    }

    protected static function sortByRecordDesc(&$array, $login)
    {
        usort(
            $array,
            function ($a, $b) use ($login) {
                if (!isset($a->localRecords) && !isset($b->localRecords)) {
                    return 0;
                } elseif (!isset($a->localRecords)) {
                    return -1;
                } elseif (!isset($b->localRecords)) {
                    return 1;
                } elseif (!isset($a->localRecords[$login]) && !isset($b->localRecords[$login])) {
                    return 0;
                } elseif (!isset($a->localRecords[$login])) {
                    return -1;
                } elseif (!isset($b->localRecords[$login])) {
                    return 1;
                } else {
                    return $a->localRecords[$login] > $b->localRecords[$login] ? -1 : 1;
                }
            }
        );
    }

    protected static function sortByRecordAsc(&$array, $login)
    {
        usort(
            $array,
            function ($a, $b) use ($login) {
                if (!isset($a->localRecords) && !isset($b->localRecords)) {
                    return 0;
                } elseif (!isset($a->localRecords)) {
                    return 1;
                } elseif (!isset($b->localRecords)) {
                    return -1;
                } elseif (!isset($a->localRecords[$login]) && !isset($b->localRecords[$login])) {
                    return 0;
                } elseif (!isset($a->localRecords[$login])) {
                    return 1;
                } elseif (!isset($b->localRecords[$login])) {
                    return -1;
                } else {
                    return $a->localRecords[$login] > $b->localRecords[$login] ? 1 : -1;
                }
            }
        );
    }
}
