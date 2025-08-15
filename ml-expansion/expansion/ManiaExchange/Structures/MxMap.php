<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Structures;

class MxMap extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{
    public $mapUid;

    public $mapId;

    public $titlePack;

    public $environment;

    public $vehicleName;

    public $gbxMapName;

    public $uploader;

    public $difficulty;

    public $moodFull;

    public $tags;

    public $length;

    public $awardCount;

    public $updatedAt;

    public $name;

    public $uploadedAt;

    public $images;

    public $mapType;

    public $routes;

    public $replayCount;

    public $feature;

    public function getEnvironment()
    {
        switch ($this->environment) {
            case 1:
                return "Canyon";
            case 3:
                return "Valley";
            case 4:
                return "Lagoon";
            case 2:
                return "Stadium";
            case "Storm":
                return "Storm";
            default:
                return "Unknown";
        }
    }

    public function getUploader()
    {
        return $this->uploader["Name"];
    }

    public function getDifficulty()
    {
        switch ($this->difficulty) {
            case 0:
                return "Beginner";
            case 1:
                return "Intermediate";
            case 2:
                return "Advanced";
            case 3:
                return "Expert";
            case 4:
                return "Lunatic";
            case 5:
                return "Impossible";
            default:
                return "Unknown";
        }
    }

    public function getStyle()
    {
        if (isset($this->tags[0]["Name"])) {
            return $this->tags[0]["Name"];
        }
        return "";
    }

    public function getLength()
    {
        return $this->timeString($this->length);
    }

    public function getRouteType()
    {
        switch ($this->difficulty) {
            case 0:
                return "Single";
            case 1:
                return "Multiple";
            case 2:
                return "Symmetrical";
            default:
                return "Unknown";
        }
    }

    // function to convert time in milliseconds to a string
    public function timeString($time)
    {
        $time = $time / 1000;
        $minutes = floor($time / 60);
        $seconds = intval($time) % 60;
        $seconds = round($seconds, 2);
        if ($minutes) {
            return $minutes . "min " . $seconds . "s";
        }
        return $seconds . "s";
    }
}
