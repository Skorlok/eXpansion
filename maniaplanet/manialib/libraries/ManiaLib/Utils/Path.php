<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLib\Utils;

class Path extends Singleton
{
	public $root;
	public $config;
	public $log;
	public $resources;
	
	public function getRoot($external = false)
	{
		if ($external && \Phar::running(false) != "")
			return dirname(\Phar::running(false));
		if (!$this->root && !defined('MANIALIB_APP_PATH'))
			throw new \Exception();
		return $this->root ? : MANIALIB_APP_PATH;
	}
	
	public function getConfig($external = false)
	{
		return $this->config ? : $this->getRoot($external).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
	}
	
	public function getLog($external = false)
	{
		return $this->log ? : $this->getRoot($external).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
	}
	
	public function getResources($external = false)
	{
		return $this->resources ? : $this->getRoot($external).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR;
	}
}
?>