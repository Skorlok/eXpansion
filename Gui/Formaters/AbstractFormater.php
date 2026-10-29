<?php

namespace ManiaLivePlugins\eXpansion\Gui\Formaters;

/**
 * Description of AbstractFormater
 *
 * @author De Cramer Oliver
 */
abstract class AbstractFormater extends \ManiaLive\Utilities\Singleton
{

    abstract public function format($val);
}
