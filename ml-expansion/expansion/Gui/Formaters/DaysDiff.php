<?php

namespace ManiaLivePlugins\eXpansion\Gui\Formaters;

/**
 * Description of AbstractFormater
 *
 * @author Skorlok
 */
class DaysDiff extends AbstractFormater
{

    public function format($val)
    {
        return (($val <= 0) ? 'Today' : "-" . $val .' d');
    }
}
