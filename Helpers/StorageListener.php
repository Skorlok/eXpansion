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

interface StorageListener extends \ManiaLive\Event\Listener
{
	/**
	 * Event lauch when the player change to spectator or to player
	 * @param Player $player
	 * @param string $oldSide - it can take 2 values spectator or player
	 */
	function onPlayerChangeSide($player, $oldSide);

	/**
	 * Event launched when a player finishes a lap.
	 * @param Player $player
	 * @param integer $timeOrScore
	 * @param array $checkpoints
	 * @param integer $nbLap
	 */
	function onPlayerFinishLap($player, $timeOrScore, $checkpoints, $nbLap);
}

?>