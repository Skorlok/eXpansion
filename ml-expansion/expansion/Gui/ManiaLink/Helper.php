<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

class Helper
{
    public function simpleHashName($name)
    {
        $hash = "";
        for ($i = 0; $i < strlen($name); $i++) {
            $hash .= ord($name[$i]);
        }
        return $hash;
    }
}
