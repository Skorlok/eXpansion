<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\Gui\Scripts;

class PermissionsInheritScript extends \ManiaLivePlugins\eXpansion\Gui\Structures\Script
{
    public function __construct($firstInheritId, $lastInheritId)
    {
        parent::__construct("AdminGroups/Gui/Scripts/PermissionsInherit");
        $this->setParam("firstInheritId", (int)$firstInheritId);
        $this->setParam("lastInheritId",  (int)$lastInheritId);
    }
}
