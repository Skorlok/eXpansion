<?php

namespace ManiaLivePlugins\eXpansion\Widgets_EndRankings;

/**
 * Description of MetaData
 *
 * @author Petri
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Widget: Podium Infos and Statistics");
        $this->setDescription("Server ranks, top playtime and top donators during podium");
        $this->setGroups(array('Records', 'Widgets'));
    }
}
