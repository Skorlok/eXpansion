<?php

namespace ManiaLivePlugins\eXpansion\Statistics\Gui\Windows;

class TopActive extends StatsWindow
{

    public static $labelTitles = array('#', 'NickName', 'Last connection');

    protected function getKeys()
    {
        return array(0, 'nickname', 'days');
    }

    protected function getLabel($i)
    {
        return isset(self::$labelTitles[$i]) ? self::$labelTitles[$i] : "";
    }

    protected function getWidths()
    {
        return array(1, 5, 3);
    }

    protected function getFormaters()
    {
        return array(null, null, \ManiaLivePlugins\eXpansion\Gui\Formaters\DaysDiff::getInstance());
    }
}
