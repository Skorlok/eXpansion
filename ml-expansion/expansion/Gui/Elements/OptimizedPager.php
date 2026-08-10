<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;

class OptimizedPager extends \ManiaLivePlugins\eXpansion\Gui\Control implements \ManiaLivePlugins\eXpansion\Gui\Structures\ScriptedContainer
{

    protected $frame;

    protected $clickAction;

    protected $iitems = array();

    protected $data = array();

    protected $scroll;
    protected $bg;
    protected $scrollBg;

    protected $scrollDown;
    protected $scrollUp;

    protected $myScript;

    protected $ContentLayout;

    protected $nbElemParColumn;

    protected $index = 0;

    protected $rowPerPage = 1;

    protected $script;

    protected $xml;

    public function __construct()
    {
        $this->bg = new \ManiaLib\Gui\Elements\Quad();
        $this->bg->setBgcolor('$f00');
        $this->bg->setId("menuBg");
        $this->bg->setScriptEvents();
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column());
        $this->frame->setId("Pager");
        $this->frame->setScriptEvents();
        $this->addComponent($this->frame);
        $this->clickAction = $this->createAction(array($this, "handleClick"));

        $this->scrollBg = new \ManiaLib\Gui\Elements\Quad(4, 40);
        $this->scrollBg->setAlign("center", "top");
        $this->scrollBg->setStyle("Bgs1InRace");
        $this->scrollBg->setSubStyle('BgPlayerCard');
        $this->scrollBg->setId("ScrollBg");
        $this->scrollBg->setOpacity(0.9);
        $this->addComponent($this->scrollBg);

        $this->scroll = new \ManiaLib\Gui\Elements\Quad(3, 15);
        $this->scroll->setAlign("center", "top");
        $this->scroll->setStyle("BgsPlayerCard");
        $this->scroll->setSubStyle('BgRacePlayerName');
        $this->scroll->setId("ScrollBar");
        $this->scroll->setScriptEvents();
        $this->addComponent($this->scroll);

        $this->scrollDown = new \ManiaLib\Gui\Elements\Quad(6.5, 6.5);
        $this->scrollDown->setAlign("center", "top");
        $this->scrollDown->setStyle("Icons64x64_1");
        $this->scrollDown->setSubStyle("ArrowDown");
        $this->scrollDown->setId("ScrollDown");
        $this->scrollDown->setScriptEvents();
        $this->addComponent($this->scrollDown);

        $this->scrollUp = new \ManiaLib\Gui\Elements\Quad(6.5, 6.5);
        $this->scrollUp->setAlign("center", "bottom");
        $this->scrollUp->setStyle("Icons64x64_1");
        $this->scrollUp->setSubStyle("ArrowUp");
        $this->scrollUp->setId("ScrollUp");
        $this->scrollUp->setScriptEvents();
        $this->addComponent($this->scrollUp);

        $this->xml = new \ManiaLive\Gui\Elements\Xml();

        $entry = new \ManiaLive\Gui\Elements\Xml();
        $entry->setContent('<entry posn="0 900 0" id="entry" scriptevents="1" class="isTabIndex isEditable" name="item" hidden="1"/>');
        $this->addComponent($entry);

        $this->myScript = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui\Scripts\OptimizedPager");
    }

    public function handleClick($login, $entries)
    {
        if (!empty($entries['item'])) {
            // do some magic
            /** @var \ManiaLive\Data\Storage */
            $storage = \ManiaLive\Data\Storage::getInstance();
            $player = $storage->getPlayerObject($login);

            /** @var \ManiaLive\Gui\ActionHandler */
            $actionHandler = \ManiaLive\Gui\ActionHandler::getInstance();
            $actionHandler->onPlayerManialinkPageAnswer(intval($player->playerId), $login, intval($entries['item']), array());
        }
    }

    public function clearItems()
    {
        $this->iitems = array();
        $this->data = array();
        $this->index = 0;
    }

    public function addSimpleItems($items)
    {
        foreach ($items as $text => $action) {
            $this->iitems[$this->index][] = '"' . Gui::fixString($text) . '"';
            $this->data[$this->index][] = '"' . Gui::fixString($action) . '"';
        }
        $this->index++;
    }

    public function setSize()
    {
        $args = func_get_args();
        $this->sizeX = $args[0];
        $this->sizeY = $args[1];
        $this->scroll->setPosition($this->sizeX - 3, 0);
        $this->scrollBg->setPosition($this->sizeX - 3);
        $this->scrollBg->setSizeY($this->sizeY - 4);

        $this->scrollDown->setPosition($this->sizeX - 3, -($this->sizeY - 5));
        $this->scrollUp->setPosition($this->sizeX - 3, -1);
    }

    public function setContentLayout($className)
    {
        $this->ContentLayout = $className;
    }

    public function update($login)
    {

        $className = $this->ContentLayout;
        $layout = new $className(0, $login, $this->clickAction);

        $sizeY = $layout->getSizeY() * ($layout->getScale() == 0.0 ? 1 : $layout->getScale());

        $this->frame->destroyComponents();
        $layout = null;

        $limit = (int)($this->getSizeY() / $sizeY);

        for ($x = 0; $x < $limit; $x++) {
            $className = $this->ContentLayout;
            $layout = new $className($x, $login, $this->clickAction);
            $this->frame->addComponent($layout);
        }
        $this->rowPerPage = $limit;
        $this->nbElemParColumn = $layout->getNbTextColumns();
    }

    public function destroy()
    {
        if (isset($this->frame) && $this->frame !== null) {
            $this->frame->destroyComponents();
        }
        $this->clearItems();
        unset($this->script);

        parent::destroy();
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function getScript()
    {
        $totalRows = 0;
        $items = "";
        foreach ($this->iitems as $row => $elem) {
            $totalRows++;
            $items .= $row . ' => [ ' . implode(",", $elem) . '],';
        }
        if (empty($items)) {
            $this->myScript->setParam("items", "");
        } else {
            $items = "= [" . trim($items, ",") . "]";
            $this->myScript->setParam("items", $items);
        }

        $data = "";
        foreach ($this->data as $row => $elem) {
            $data .= $row . ' => [ ' . implode(",", $elem) . '],';
        }
        if (empty($data)) {
            $data = "";
        } else {
            $data = "= [" . trim($data, ",") . "]";
        }

        $this->myScript->setParam("data", $data);
        $this->myScript->setParam("itemsPerRow", $this->nbElemParColumn);
        $this->myScript->setParam("totalRows", $totalRows);
        $this->myScript->setParam("rowPerPage", $this->rowPerPage);

        return $this->myScript;
    }

    /**
     * Generate the OptimizedPager XML structure.
     *
     * @param \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window $mlClass   ManiaLink class name (e.g. "ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window")
     * @param string $login     Player login or null for all players
     * @param float  $sizeX    Total width (including scrollbar)
     * @param float  $sizeY    Total height
     * @param string $itemsVarName Name of the parameter set in ManiaLink instance [rowIndex => [col0text, col1text, ...]]
     * @param string $dataVarName  Name of the parameter set in ManiaLink instance [rowIndex => [col0action, col1action, ...]]
     * @param int    $rowsPerPage  Number of visible rows in the DOM
     * @param int    $itemsPerRow  Number of columns per row
     * @param string $varName      Prefix applied to both ManiaScript variable names: {varName}textData_N and {varName}data_N. (mandatory for chunked mode, not required for non-chunked mode)
     * @return string
     */
    public static function getXML($mlClass, $login, $sizeX, $sizeY, $itemsVarName, $dataVarName, $rowsPerPage, $itemsPerRow, $varName = null)
    {
        if (!is_object($mlClass)) {
            Helper::logError('OptimizedPager: Invalid $mlClass parameter', array("Gui", "OptimizedPager"));
            return "";
        }
        if (!$mlClass instanceof \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window) {
            Helper::logError('OptimizedPager: $mlClass parameter must be an instance of ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window', array("Gui", "OptimizedPager"));
            return "";
        }
        $items = $mlClass->getParam($itemsVarName);
        $data  = $mlClass->getParam($dataVarName);

        if (!is_array($items)) {
            Helper::logError('OptimizedPager: Invalid items parameter for $itemsVarName', array("Gui", "OptimizedPager"));
            return "";
        }
        if (!is_array($data)) {
            Helper::logError('OptimizedPager: Invalid data parameter for $dataVarName', array("Gui", "OptimizedPager"));
            return "";
        }

        /** @var Config $config */
        $config     = Config::getInstance();
        $totalRows  = count($items);
        $useChunk  = ($totalRows > $config->chunkSize);

        if ($useChunk) {
            if (empty($varName)) {
                Helper::logError('OptimizedPager: $varName parameter is required when using chunked mode', array("Gui", "OptimizedPager"));
                return "";
            }
            $chunkCount = max(1, (int)ceil($totalRows / $config->chunkSize));

            $allItems    = array_values((array)$items);
            $allData     = array_values((array)$data);
            $chunk0Items = array_slice($allItems, 0, $config->chunkSize);
            $chunk0Data  = array_slice($allData,  0, $config->chunkSize);

            $vn = $varName . '_';
            $chunkDeclarations = "";
            $chunkWindowCases  = "";
            $chunkRowDataCases = "";
            for ($c = 1; $c < $chunkCount; $c++) {
                $chunkDeclarations .= "declare Text[][Integer] {$vn}textData_{$c} for UI = Text[][Integer];\n";
                $chunkDeclarations .= "declare Text[][Integer] {$vn}data_{$c} for UI = Text[][Integer];\n";
            }
            for ($c = 0; $c < $chunkCount; $c++) {
                $chunkWindowCases  .= "case {$c}: { if ({$vn}textData_{$c}.existskey(inChunk)) windowItems[i] = {$vn}textData_{$c}[inChunk]; }\n";
                $chunkRowDataCases .= "case {$c}: { if ({$vn}data_{$c}.existskey(inChunk)) rowData = {$vn}data_{$c}[inChunk]; }\n";
            }

            $script = new Script("Gui\Scripts\OptimizedPagerChunked");
            $script->setParam("chunkSize",         $config->chunkSize);
            $script->setParam("chunkCount",        $chunkCount);
            $script->setParam("rowPerPage",        (int)$rowsPerPage);
            $script->setParam("itemsPerRow",       (int)$itemsPerRow);
            $script->setParam("totalRows",         (int)$totalRows);
            $script->setParam("varName",           $vn);
            $script->setParam("chunk0items",       self::formatMsArray($chunk0Items));
            $script->setParam("chunk0data",        self::formatMsArray($chunk0Data));
            $script->setParam("chunkDeclarations", $chunkDeclarations);
            $script->setParam("chunkWindowCases",  $chunkWindowCases);
            $script->setParam("chunkRowDataCases", $chunkRowDataCases);

            $mlClass->registerOrOverrideScript($script);
            self::sendChunkUpdate($varName, $allItems, $allData, $totalRows, $login);
        } else {
            $script = new Script("Gui\Scripts\OptimizedPager");
            $script->setParam("items",       self::formatMsArray($items));
            $script->setParam("data",        self::formatMsArray($data));
            $script->setParam("rowPerPage",  (int)$rowsPerPage);
            $script->setParam("itemsPerRow", (int)$itemsPerRow);
            $script->setParam("totalRows",   (int)$totalRows);

            $mlClass->registerOrOverrideScript($script);
        }


        $scrollX   = $sizeX - 3;
        $scrollBgH = $sizeY - 4;
        $scrollDnY = $sizeY - 5;

        $xml  = '<frame>';
        $xml .= '<quad id="menuBg" sizen="' . $sizeX . ' ' . $sizeY . '" bgcolor="$f00" scriptevents="1"/>';
        $xml .= '<quad id="ScrollBg" posn="' . $scrollX . ' 0 0" sizen="4 ' . $scrollBgH . '" halign="center" valign="top" style="Bgs1InRace" substyle="BgPlayerCard" opacity="0.9"/>';
        $xml .= '<quad id="ScrollBar" posn="' . $scrollX . ' 0 1" sizen="3 15" halign="center" valign="top" style="BgsPlayerCard" substyle="BgRacePlayerName" scriptevents="1"/>';
        $xml .= '<quad id="ScrollDown" posn="' . $scrollX . ' -' . $scrollDnY . ' 0" sizen="6.5 6.5" halign="center" valign="top" style="Icons64x64_1" substyle="ArrowDown" scriptevents="1"/>';
        $xml .= '<quad id="ScrollUp" posn="' . $scrollX . ' -1 0" sizen="6.5 6.5" halign="center" valign="bottom" style="Icons64x64_1" substyle="ArrowUp" scriptevents="1"/>';
        $xml .= '<entry posn="0 900 0" id="entry" scriptevents="1" class="isTabIndex isEditable" name="eXpOptimizedPager" hidden="1"/>';
        $xml .= '</frame>';
        return $xml;
    }

    private static function sendChunkUpdate($varName, $items, $data, $totalRows, $login)
    {
        /** @var Config $config */
        $config     = Config::getInstance();
        /** @var \Maniaplanet\DedicatedServer\Connection $connection */
        $connection = Singletons::getInstance()->getDediConnection();

        $chunkSize  = $config->chunkSize;
        $chunkCount = (int)ceil($totalRows / $chunkSize);

        if ($chunkCount <= 1) return;

        // $items/$data are already array_values'd by the caller — split once up front
        $itemChunks = array_chunk($items, $chunkSize);
        $dataChunks = array_chunk($data,  $chunkSize);

        // Queue all chunks 1-N into the multicall buffer (no round-trip per chunk)
        for ($c = 1; $c < $chunkCount; $c++) {
            $xml = self::getChunkUpdateXML("pagerChunk_" . $varName . '_' . $c, $c, $itemChunks[$c], $dataChunks[$c], $varName);
            $connection->sendDisplayManialinkPage($login, $xml, 0, false, true);
        }

        // Flush all queued chunks in a single system.multicall round-trip
        try {
            $connection->executeMulticall();
        } catch (\Exception $e) {
            Helper::log('Cannot send chunk update for pager: "' . $varName . '" to player: "' . $login . '" , server said: ' . $e->getMessage(), array("Gui", "OptimizedPager"));
        }
    }

    private static function getChunkUpdateXML($carrierId, $chunkIdx, $items, $data, $varName = '')
    {
        $itemsStr = self::formatMsArray(array_values((array)$items));
        $dataStr  = self::formatMsArray(array_values((array)$data));

        $vi = $varName . '_textData_' . (int)$chunkIdx;
        $vd = $varName . '_data_' . (int)$chunkIdx;

        // Pattern from LocalRecords: declare for UI with empty init, clear, then assign value
        $ms  = "main () {\n";
        $ms .= "    declare Text[][Integer] {$vi} for UI = Text[][Integer];\n";
        $ms .= "    {$vi}.clear();\n";
        if ($itemsStr !== '') {
            $ms .= "    {$vi} {$itemsStr};\n";
        }
        $ms .= "    declare Text[][Integer] {$vd} for UI = Text[][Integer];\n";
        $ms .= "    {$vd}.clear();\n";
        if ($dataStr !== '') {
            $ms .= "    {$vd} {$dataStr};\n";
        }
        $ms .= "}";

        return '<manialink id="' . $carrierId . '" name="' . $carrierId . '" version="2"><script><!--' . "\n"
             . $ms . "\n"
             . '--></script></manialink>';
    }

    private static function formatMsArray($arr)
    {
        if (empty($arr)) {
            return "";
        }
        $rows = array();
        foreach ($arr as $rowIdx => $cols) {
            $colStrs = array();
            foreach ((array)$cols as $col) {
                $colStrs[] = '"' . Gui::fixString((string)$col) . '"';
            }
            $rows[] = (int)$rowIdx . ' => [' . implode(", ", $colStrs) . ']';
        }
        return "= [" . implode(",", $rows) . "]";
    }
}
