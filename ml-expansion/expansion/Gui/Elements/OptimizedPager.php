<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;

class OptimizedPager
{

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
