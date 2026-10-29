<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\types;

use ManiaLive\Utilities\Singleton;

/**
 * Description of absChecker
 *
 * @author oliverde8
 * @abstract
 */
abstract class absChecker extends Singleton
{

    abstract public function check($data);

    abstract public function getErrorMsg();
}
