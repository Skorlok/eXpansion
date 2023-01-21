<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @see         http://code.google.com/p/manialib/
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Elements;

class Music extends \ManiaLib\Gui\Element
{

	protected $xmlTagName = 'music';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
	protected $data;
	protected $dataId;
	protected $music;
	protected $volume;

	function __construct()
	{
		
	}

	/**
	 * Sets the data to play
	 * @param string The data filename (or URL)
	 * @param bool Whether to prefix the filename with the default media dir URL 
	 */
	function setData($filename, $absoluteUrl = false)
	{
		if(!$absoluteUrl)
		{
			$this->data = \ManiaLib\Gui\Manialink::$mediaURL.$filename;
		}
		else
		{
			$this->data = $filename;
		}
	}

	/**
	 * Sets the data id to play
	 * @param string The data id
	 */
	function setDataId($dataId)
	{
		$this->dataId = $dataId;
	}
	
	/**
	 * Fi set to true will send the sound in the music mix 
	 * (so that the volume gets controlled by the music slider instead of the sound volume slider)
	 * @param bool $music
	 */
	function setMusic($music)
	{
		$this->music = $music;
	}
	
	/**
	 * Will change the sound attenuation. default value is 0.707 (-3dB)
	 * @param float $volume
	 */
	function setVolume($volume)
	{
		$this->volume = $volume;
	}

	/**
	 * Returns the data URL
	 * @return string
	 */
	function getData()
	{
		return $this->data;
	}

	/**
	 * Returns the data id
	 * @return string
	 */
	function getDataId()
	{
		return $this->dataId;
	}
	
	function getMusic()
	{
		return $this->music;
	}
	
	function getVolume()
	{
		return $this->volume;
	}

	protected function postFilter()
	{
		if($this->data !== null)
			$this->xml->setAttribute('data', $this->data);
		if($this->dataId !== null)
			$this->xml->setAttribute('dataId', $this->dataId);
		if($this->music !== null)
			$this->xml->setAttribute('music', $this->music);
		if($this->volume !== null)
			$this->xml->setAttribute('volume', $this->volume);
	}

}

?>