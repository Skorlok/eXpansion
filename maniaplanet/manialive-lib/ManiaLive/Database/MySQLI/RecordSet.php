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

namespace ManiaLive\Database\MySQLI;

class RecordSet extends \ManiaLive\Database\RecordSet
{
	const FETCH_ASSOC = MYSQLI_ASSOC;
	const FETCH_NUM = MYSQLI_NUM;
	const FETCH_BOTH = MYSQLI_BOTH;
	
	protected $result;
	
	function __construct($result)
	{
		$this->result = $result;
	}
	
	function fetchRow()
	{
		return mysqli_fetch_row($this->result);
	}
	
	function fetchAssoc()
	{
		return mysqli_fetch_assoc($this->result);
	}
	
	function fetchArray($resultType = self::FETCH_ASSOC)
	{
		return mysqli_fetch_array($this->result, $resultType);
	}
	
	/**
	 * @deprecated
	 */
	function fetchStdObject()
	{
		return $this->fetchObject(null);
	}
	
	function fetchObject($className='\\stdClass', array $params=array())
	{
		if($className)
		{
			if($params)
			{
				return mysqli_fetch_object($this->result, $className, $params);
			}
			else
			{
				return mysqli_fetch_object($this->result, $className);
			}
		}
		else
		{
			return mysqli_fetch_object($this->result);
		}
	}
	
	function recordCount()
	{
		return mysqli_num_rows($this->result);
	}
	
	function recordAvailable()
	{
		return mysqli_num_rows($this->result) > 0;
	}
}

?>