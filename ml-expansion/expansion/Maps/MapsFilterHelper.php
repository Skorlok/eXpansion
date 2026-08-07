<?php

namespace ManiaLivePlugins\eXpansion\Maps;

use ManiaLive\Database\Connection;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode;

class MapsFilterHelper
{
    public static $availableFilters = array(
        "canyon" => "Canyon",                // $map->environnement == "Canyon"
        "stadium" => "Stadium",              // $map->environnement == "Stadium"
        "valley" => "Valley",                // $map->environnement == "Valley"
        "lagoon" => "Lagoon",                // $map->environnement == "Lagoon"
        "nofinish" => "Not Finished",        // !isset($map->localRecords[$login])
        "finished" => "Finished",            // isset($map->localRecords[$login])
        "novote" => "Not Rated Maps",        // NOT IMPLEMENTED
        "voted" => "Rated Maps",             // NOT IMPLEMENTED
        "recent" => "Recently Played",       // isset($history[X][uId])
        "norecent" => "Not Recently Played", // !isset($history[X][uId])
        "noauthor" => "No Author time",      // isset($map->localRecords[$login]) && $map->localRecords[$login] < DATABASE: `exp_maps`.`challenge_authorTime`
        "nogold" => "No Gold time",          // isset($map->localRecords[$login]) && $map->localRecords[$login] < $map->goldTime
        "nosilver" => "No Silver time",      // isset($map->localRecords[$login]) && $map->localRecords[$login] < DATABASE: `exp_maps`.`challenge_silverTime`
        "nobronze" => "No Bronze time",      // isset($map->localRecords[$login]) && $map->localRecords[$login] < DATABASE: `exp_maps`.`challenge_bronzeTime`
        "multilap" => "Only Multilap",       // DATABASE: `exp_maps`.`challenge_lapRace` == 1
        "nomultilap" => "No Multilap",       // DATABASE: `exp_maps`.`challenge_lapRace` == 0
        "sunrise" => "Sunrise",              // DATABASE: `exp_maps`.`challenge_mood` includes "Sunrise"
        "day" => "Day",                      // DATABASE: `exp_maps`.`challenge_mood` includes "Day"
        "sunset" => "Sunset",                // DATABASE: `exp_maps`.`challenge_mood` includes "Sunset"
        "night" => "Night",                  // DATABASE: `exp_maps`.`challenge_mood` includes "Night"
        "behindlogin" => "Behind player",    // isset($map->localRecords[$login]) && DATABASE: $map->localRecords[$login] > `exp_records`.`record_score`
    );

    public static $availableSortModes = array(
        "name" => "Map Name",
        "author" => "Map Author",
        "goldTime" => "Gold Time",
        "localrecord" => "Local Record",
        "rating" => "Map Rating",
        "addTime" => "Add Time",
        "playervote" => "My rating",
        "copperPrice" => "Display Cost"
    );

    /** @var MapSortMode[] */
    public static $playerSortModes = array();

    /**
     * Filters maps based on search term and sort mode.
     * 
     * @param string $login The login of the player requesting the filtered maps.
     * @param array $history The history of maps played on the server.
     * @return array The filtered and sorted list of maps.
     */
    public static function filterMaps($login, $history)
    {
        /** @var \ManiaLive\Data\Storage $storage */
        $storage = Storage::getInstance();
        $maps    = $storage->maps;
        $filteredMaps = array();

        if (!isset(self::$playerSortModes[$login])) {
            self::$playerSortModes[$login] = new \ManiaLivePlugins\eXpansion\Maps\Structures\MapSortMode();
        }

        $sortMode = self::$playerSortModes[$login];
        $searchTerm = $sortMode->searchTerm;
        $searchField = $sortMode->searchField;
        $searchFilter = $sortMode->searchFilter;

        if (strlen($searchTerm) > 2) {
            $needle = strtolower($searchTerm);
            foreach ($maps as $map) {
                if ($searchField == "name" || $searchField == "author") {
                    $field = ($searchField == "name") ? "strippedName" : "author";
                    if (self::fuzzyMatch($needle, strtolower(Formatting::stripStyles($map->{$field})))) {
                        $filteredMaps[] = $map;
                    }
                } else {
                    if (self::fuzzyMatch($needle, strtolower(Formatting::stripStyles($map->strippedName))) ||
                        self::fuzzyMatch($needle, strtolower(Formatting::stripStyles($map->author)))) {
                        $filteredMaps[] = $map;
                    }
                }
            }
        } else {
            $filteredMaps = $maps;
        }

        if ($searchFilter && isset(self::$availableFilters[$searchFilter])) {
            $historyUids = array();
            if ($searchFilter === 'recent' || $searchFilter === 'norecent') {
                foreach ($history as $entry) {
                    $historyUids[$entry->uId] = true;
                }
            }

            $voteList = array();
            if ($searchFilter === 'voted' || $searchFilter === 'novote') {
                $db = self::getDatabaseConnection();
                $votes = $db->execute('SELECT * FROM exp_ratings WHERE login = "' . $login . '";')->fetchArrayOfObject();
                foreach ($votes as $vote) {
                    $voteList[$vote->uid] = true;
                }
            }

            $dbData = array();
            $dbFilters = array('noauthor', 'nosilver', 'nobronze', 'multilap', 'nomultilap', 'sunrise', 'day', 'sunset', 'night');
            if (in_array($searchFilter, $dbFilters)) {
                $db = self::getDatabaseConnection();
                $rows = $db->execute('SELECT challenge_uid, challenge_authorTime, challenge_silverTime, challenge_bronzeTime, challenge_lapRace, challenge_mood FROM exp_maps;')->fetchArrayOfObject();
                foreach ($rows as $row) {
                    $dbData[$row->challenge_uid] = $row;
                }
            }

            $targetRecords = array();
            if ($searchFilter === 'behindlogin' && !empty($sortMode->filterParam)) {
                $db = self::getDatabaseConnection();
                $targetLogin = $db->quote($sortMode->filterParam);
                $rows = $db->execute('SELECT `record_challengeuid`, `record_score` FROM `exp_records` WHERE `record_playerlogin`=' . $targetLogin . ';')->fetchArrayOfObject();
                foreach ($rows as $row) {
                    $targetRecords[$row->record_challengeuid] = (int)$row->record_score;
                }
            }

            $result = array();
            foreach ($filteredMaps as $map) {
                switch ($searchFilter) {
                    case 'canyon':
                    case 'stadium':
                    case 'valley':
                    case 'lagoon':
                    case 'storm':
                        if ($map->environnement === ucfirst($searchFilter)) {
                            $result[] = $map;
                        }
                        break;
                    case 'finished':
                        if (isset($map->localRecords[$login])) {
                            $result[] = $map;
                        }
                        break;
                    case 'nofinish':
                        if (!isset($map->localRecords[$login])) {
                            $result[] = $map;
                        }
                        break;
                    case 'recent':
                        if (isset($historyUids[$map->uId])) {
                            $result[] = $map;
                        }
                        break;
                    case 'norecent':
                        if (!isset($historyUids[$map->uId])) {
                            $result[] = $map;
                        }
                        break;
                    case 'voted':
                        if (isset($voteList[$map->uId])) {
                            $result[] = $map;
                        }
                        break;
                    case 'novote':
                        if (!isset($voteList[$map->uId])) {
                            $result[] = $map;
                        }
                        break;
                    case 'nogold':
                        if (isset($map->localRecords[$login]) && $map->localRecords[$login][1] > $map->goldTime) {
                            $result[] = $map;
                        }
                        break;
                    case 'noauthor':
                        if (isset($map->localRecords[$login]) && isset($dbData[$map->uId])) {
                            if ($map->localRecords[$login][1] > $dbData[$map->uId]->challenge_authorTime) {
                                $result[] = $map;
                            }
                        }
                        break;
                    case 'nosilver':
                        if (isset($map->localRecords[$login]) && isset($dbData[$map->uId])) {
                            if ($map->localRecords[$login][1] > $dbData[$map->uId]->challenge_silverTime) {
                                $result[] = $map;
                            }
                        }
                        break;
                    case 'nobronze':
                        if (isset($map->localRecords[$login]) && isset($dbData[$map->uId])) {
                            if ($map->localRecords[$login][1] > $dbData[$map->uId]->challenge_bronzeTime) {
                                $result[] = $map;
                            }
                        }
                        break;
                    case 'multilap':
                        if (isset($dbData[$map->uId]) && $dbData[$map->uId]->challenge_lapRace == 1) {
                            $result[] = $map;
                        }
                        break;
                    case 'nomultilap':
                        if (isset($dbData[$map->uId]) && $dbData[$map->uId]->challenge_lapRace == 0) {
                            $result[] = $map;
                        }
                        break;
                    case 'sunrise':
                    case 'day':
                    case 'sunset':
                    case 'night':
                        if (isset($dbData[$map->uId]) && stripos($dbData[$map->uId]->challenge_mood, $searchFilter) !== false) {
                            $result[] = $map;
                        }
                        break;
                    case 'behindlogin':
                        if (empty($sortMode->filterParam)) {
                            $result[] = $map;
                            break;
                        }
                        if (!isset($map->localRecords[$login])) {
                            break;
                        }
                        if (!isset($targetRecords[$map->uId])) {
                            break;
                        }
                        if ($map->localRecords[$login][1] > $targetRecords[$map->uId]) {
                            $result[] = $map;
                        }
                        break;
                    default:
                        $result[] = $map;
                        break;
                }
            }
            $filteredMaps = $result;
        }

        if ($sortMode->column) {
            switch ($sortMode->column) {
                case "rating":
                    if ($sortMode->sortMode == 1) { self::sortByRankingDesc($filteredMaps); }
                    if ($sortMode->sortMode == 2) { self::sortByRankingAsc($filteredMaps); }
                    break;
                case "playervote":
                    if ($sortMode->sortMode == 1) { self::sortByPlayerVoteDesc($filteredMaps, $login); }
                    if ($sortMode->sortMode == 2) { self::sortByPlayerVoteAsc($filteredMaps, $login); }
                    break;
                case "localrecord":
                    if ($sortMode->sortMode == 1) { self::sortByRecordAsc($filteredMaps, $login); }
                    if ($sortMode->sortMode == 2) { self::sortByRecordDesc($filteredMaps, $login); }
                    break;
                case "name":
                    if ($sortMode->sortMode == 1) { ArrayOfObj::asortAsc($filteredMaps, "strippedName"); }
                    if ($sortMode->sortMode == 2) { ArrayOfObj::asortDesc($filteredMaps, "strippedName"); }
                    break;
                default:
                    if ($sortMode->sortMode == 1) {
                        \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortAsc($filteredMaps, $sortMode->column);
                    }
                    if ($sortMode->sortMode == 2) {
                        \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::asortDesc($filteredMaps, $sortMode->column);
                    }
                    break;
            }
        }
        return $filteredMaps;
    }

    // --- Sort helpers (from old Maplist.php) ---

    private static function sortByRankingAsc(&$array)
    {
        usort($array, function ($a, $b) {
            if (!isset($a->mapRating) && !isset($b->mapRating)) { return 0; }
            elseif (!isset($a->mapRating)) { return -1; }
            elseif (!isset($b->mapRating)) { return 1; }
            else { return $a->mapRating->rating > $b->mapRating->rating ? 1 : -1; }
        });
    }

    private static function sortByRankingDesc(&$array)
    {
        usort($array, function ($a, $b) {
            if (!isset($a->mapRating) && !isset($b->mapRating)) { return 0; }
            elseif (!isset($a->mapRating)) { return 1; }
            elseif (!isset($b->mapRating)) { return -1; }
            else { return $a->mapRating->rating > $b->mapRating->rating ? -1 : 1; }
        });
    }

    private static function sortByRecordAsc(&$array, $login)
    {
        usort($array, function ($a, $b) use ($login) {
            if (!isset($a->localRecords) && !isset($b->localRecords)) { return 0; }
            elseif (!isset($a->localRecords)) { return 1; }
            elseif (!isset($b->localRecords)) { return -1; }
            elseif (!isset($a->localRecords[$login]) && !isset($b->localRecords[$login])) { return 0; }
            elseif (!isset($a->localRecords[$login])) { return 1; }
            elseif (!isset($b->localRecords[$login])) { return -1; }
            else { return $a->localRecords[$login][0] > $b->localRecords[$login][0] ? 1 : -1; }
        });
    }

    private static function sortByRecordDesc(&$array, $login)
    {
        usort($array, function ($a, $b) use ($login) {
            if (!isset($a->localRecords) && !isset($b->localRecords)) { return 0; }
            elseif (!isset($a->localRecords)) { return -1; }
            elseif (!isset($b->localRecords)) { return 1; }
            elseif (!isset($a->localRecords[$login]) && !isset($b->localRecords[$login])) { return 0; }
            elseif (!isset($a->localRecords[$login])) { return -1; }
            elseif (!isset($b->localRecords[$login])) { return 1; }
            else { return $a->localRecords[$login][0] > $b->localRecords[$login][0] ? -1 : 1; }
        });
    }

    private static function sortByPlayerVoteDesc(&$array, $login)
    {
        usort($array, function ($a, $b) use ($login) {
            $aVote = (isset($a->mapRating) && isset($a->mapRating->playerVotes[$login])) ? $a->mapRating->playerVotes[$login] : null;
            $bVote = (isset($b->mapRating) && isset($b->mapRating->playerVotes[$login])) ? $b->mapRating->playerVotes[$login] : null;
            if ($aVote === null && $bVote === null) { return 0; }
            elseif ($aVote === null) { return 1; }
            elseif ($bVote === null) { return -1; }
            else { return $aVote > $bVote ? 1 : -1; }
        });
    }

    private static function sortByPlayerVoteAsc(&$array, $login)
    {
        usort($array, function ($a, $b) use ($login) {
            $aVote = (isset($a->mapRating) && isset($a->mapRating->playerVotes[$login])) ? $a->mapRating->playerVotes[$login] : null;
            $bVote = (isset($b->mapRating) && isset($b->mapRating->playerVotes[$login])) ? $b->mapRating->playerVotes[$login] : null;
            if ($aVote === null && $bVote === null) { return 0; }
            elseif ($aVote === null) { return 1; }
            elseif ($bVote === null) { return -1; }
            else { return $aVote > $bVote ? -1 : 1; }
        });
    }

    // --- Search helpers ---

    private static function fuzzyMatch($needle, $haystack)
    {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
        $nLen = strlen($needle);
        $hLen = strlen($haystack);
        if ($hLen < $nLen - 1) {
            return levenshtein($needle, $haystack) < 2;
        }
        for ($wLen = max(1, $nLen - 1); $wLen <= $nLen + 1; $wLen++) {
            for ($start = 0; $start + $wLen <= $hLen; $start++) {
                if (levenshtein($needle, substr($haystack, $start, $wLen)) < 2) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function getDatabaseConnection()
	{
        /** @var \ManiaLive\Database\Config $config */
		$config = \ManiaLive\Database\Config::getInstance();
		return Connection::getConnection($config->host, $config->username, $config->password, $config->database, $config->type, $config->port);
	}
}
