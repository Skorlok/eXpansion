<?php

namespace ManiaLivePlugins\eXpansion\TM_HiddenLocalRecords;

/**
 * Same Meta data as the local records just name and compatibility changes settings are common
 *
 */
class MetaData extends \ManiaLivePlugins\eXpansion\LocalRecords\MetaData
{
    public function initName()
    {
        $this->setName('Records: HiddenLocalRecords');
        $this->setDescription('Same as LocalRecords but all others records are hidden.');
        $this->setGroups(array('Records'));
    }
}
