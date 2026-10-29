<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\eXpansion\Helpers;

class StorageEvent extends \ManiaLive\Event\Event
{
	const ON_PLAYER_CHANGE_SIDE    = 1;
	const ON_PLAYER_FINISH_LAP     = 2;

	protected $params;

	function __construct($onWhat)
	{
		parent::__construct($onWhat);
		$params = func_get_args();
		array_shift($params);
		$this->params = $params;
	}

	function fireDo($listener)
	{
		$p = $this->params;
		// Explicit calls are always *a lot* faster than using call_user_func() even if longer to write
		switch($this->onWhat)
		{
			case self::ON_PLAYER_CHANGE_SIDE: $listener->onPlayerChangeSide($p[0], $p[1]); break;
			case self::ON_PLAYER_FINISH_LAP: $listener->onPlayerFinishLap($p[0], $p[1], $p[2], $p[3]); break;
		}
	}
}
?>