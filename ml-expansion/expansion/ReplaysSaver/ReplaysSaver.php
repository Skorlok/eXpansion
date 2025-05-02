<?php

namespace ManiaLivePlugins\eXpansion\ReplaysSaver;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;

class ReplaysSaver extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public function eXpOnReady()
    {
        Dispatcher::register(LocalEvent::getClass(), $this);
    }

    public function onRecordsLoaded($data)
    {
    }

    public function onUpdateRecords($data)
    {
    }

    public function onNewRecord($data)
    {
    }

    public function onRecordPlayerFinished($login)
    {
    }

    public function onRecordDeleted($removedRecord, $records)
    {
    }

    public function onPersonalBestRecord($data)
    {
        try {
            $this->connection->saveBestGhostsReplay($data->login, $this->storage->serverLogin . DIRECTORY_SEPARATOR . $this->storage->currentMap->uId . DIRECTORY_SEPARATOR . $data->login . ".Replay.Gbx");
        } catch (\Exception $e) {
            $this->console("Error saving personal best replay: " . $e->getMessage());
            return;
        }
        
        try {
            $vReplay = $this->connection->getValidationReplay($data->login);
            file_put_contents($this->connection->gameDataDirectory() . 'Replays' . DIRECTORY_SEPARATOR . $this->storage->serverLogin . DIRECTORY_SEPARATOR . $this->storage->currentMap->uId . DIRECTORY_SEPARATOR . $data->login . ".VReplay.Gbx", $vReplay);
        } catch (\Exception $e) {
            $this->console("Error saving validation replay: " . $e->getMessage());
            return;
        }
        
        $this->console("Replay saved for " . $data->login);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(LocalEvent::getClass(), $this);
    }
}