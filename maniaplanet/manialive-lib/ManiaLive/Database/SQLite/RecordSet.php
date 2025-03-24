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

namespace ManiaLive\Database\SQLite;

class RecordSet extends \ManiaLive\Database\RecordSet
{
	const FETCH_ASSOC = SQLITE3_ASSOC;
	const FETCH_NUM = SQLITE3_NUM;
	const FETCH_BOTH = SQLITE3_BOTH;
	
	/** @var \SQLite3Result */
	protected $result;
	/** @var bool */
	protected $recordAvailable;
	
	function __construct($result)
	{
		$this->result = $result;
		$this->recordAvailable = $this->result->fetchArray() !== false;
		$this->result->reset();
	}
	
	function fetchRow()
	{
		return $this->result->fetchArray(self::FETCH_NUM);
	}
	
	function fetchAssoc()
	{
		return $this->result->fetchArray(self::FETCH_ASSOC);
	}
	
	function fetchArray($resultType = self::FETCH_ASSOC)
	{
		return $this->result->fetchArray($resultType);
	}
	
	
	function fetchObject($className='\\stdClass', array $params=array())
	{
		$row = $this->result->fetchArray(self::FETCH_ASSOC);
		if ($row) {
			return (object) $row;
		}
		return null;
	}
	
	function recordCount()
	{
		$rowCount = 0;
		while ($row = $this->result->fetchArray(self::FETCH_ASSOC)) {
			$rowCount++;
		}
		return $rowCount;
	}
	
	function recordAvailable()
	{
		return $this->recordAvailable;
	}
}
?>