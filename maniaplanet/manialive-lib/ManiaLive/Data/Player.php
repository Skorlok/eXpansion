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

namespace ManiaLive\Data;

class Player extends \Maniaplanet\DedicatedServer\Structures\Player
{
	/** @var bool */
	public $isConnected = true;

	////////////////////////////////////////
	// Info                               //
	////////////////////////////////////////
	/** @var string */
	public $nickName;
	/** @var int */
	public $playerId;
	/** @var int */
	public $teamId;
	/** @var bool */
	public $isSpectator;
	/** @var bool */
	public $isInOfficialMode;
	/** @var int */
	public $ladderRanking;
	/** @var int */
	public $spectatorStatus;
	/** @var int */
	public $flags;

	//Flags details
	/** @var int */
	public $forceSpectator;
	/** @var bool */
	public $isReferee;
	/** @var bool */
	public $isPodiumReady;
	/** @var bool */
	public $isUsingStereoscopy;
	/** @var bool */
	public $isManagedByAnOtherServer;
	/** @var bool */
	public $isServer;
	/** @var bool */
	public $hasPlayerSlot;
	/** @var bool */
	public $isBroadcasting;
	/** @var bool */
	public $hasJoinedGame = false;

	//SpectatorStatus details
	/** @var bool */
	public $spectator;
	/** @var bool */
	public $temporarySpectator;
	/** @var bool */
	public $pureSpectator;
	/** @var bool */
	public $autoTarget;
	/** @var int */
	public $currentTargetId;

	////////////////////////////////////////
	// DetailedInfo                       //
	////////////////////////////////////////
	/** @var string */
	public $path;
	/** @var string */
	public $language;
	/** @var string */
	public $clientVersion;
	/** @var string */
	public $clientTitleVersion;
	/** @var string */
	public $iPAddress;
	/** @var int */
	public $downloadRate;
	/** @var int */
	public $uploadRate;
	/** @var FileDesc */
	public $avatar;
	/** @var Skin[] */
	public $skins;
	/** @var mixed[] */
	public $ladderStats;
	/** @var int */
	public $hoursSinceZoneInscription;
	/** @var string */
	public $broadcasterLogin;
	/** @var string[] */
	public $allies = array();
	/** @var string */
	public $clubLink;
	/**
	 * @deprecated
	 * @var int
	 */
	public $onlineRights;

	////////////////////////////////////////
	// Ranking                            //
	////////////////////////////////////////
	/** @var int */
	public $rank;
	/** @var int */
	public $bestTime;
	/** @var int[] */
	public $bestCheckpoints;
	/** @var int */
	public $score;
	/** @var int */
	public $nbrLapsFinished;
	/** @var float */
	public $ladderScore;

	/**
	 * @param \Maniaplanet\DedicatedServer\Structures\Player $data
	 */
	function merge(\Maniaplanet\DedicatedServer\Structures\Player $data)
	{
		foreach($data as $key => $value)
			$this->$key = $value;
	}
}
