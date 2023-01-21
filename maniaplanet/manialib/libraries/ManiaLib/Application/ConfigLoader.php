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

namespace ManiaLib\Application;

use ManiaLib\Cache\Cache;

class ConfigLoader extends \ManiaLib\Utils\Singleton
{
	protected $INIConfigFilename;
	protected $INIConfigDirectory;
	protected $PHPConfigFilename;
	protected $hostname;
	protected $enableCache = true;
	protected $aliases = array(
		'application' => 'ManiaLib\Application\Config',
		'database' => 'ManiaLib\Database\Config',
		'log' => 'ManiaLib\Utils\LoggerConfig',
		'tracking' => 'ManiaLib\Application\Tracking\Config',
		'webservices' => 'ManiaLib\WebServices\Config',
		'path' => 'ManiaLib\Utils\Path'
	);

	function setINIConfigFilename($filename)
	{
		$this->INIConfigFilename = $filename;
	}

	function getINIConfigFilename()
	{
		if(!$this->INIConfigFilename)
		{
			$this->INIConfigFilename = MANIALIB_APP_PATH.'config/app.ini';
		}
		return $this->INIConfigFilename;
	}
	
	function getINIConfigDirectory()
	{
		if(!$this->INIConfigDirectory)
		{
			$this->INIConfigDirectory = MANIALIB_APP_PATH.'config/app.d/';
		}
		return $this->INIConfigDirectory;
	}

	function getPHPConfigFilename()
	{
		if(!$this->PHPConfigFilename)
		{
			$this->PHPConfigFilename = MANIALIB_APP_PATH.'config/app.php';
		}
		return $this->PHPConfigFilename;
	}

	function setHostname($hostname)
	{
		$this->hostname = $hostname;
	}

	function getHostname()
	{
		if(!$this->hostname)
		{
			$this->hostname = \ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_HOST');
		}
		return $this->hostname;
	}

	function disableCache()
	{
		$this->enableCache = false;
	}
	
	static function load()
	{
		static::getInstance()->run();
	}

	function run()
	{
		if(file_exists($this->getPHPConfigFilename()))
		{
			require_once $this->getPHPConfigFilename();
		}
		else
		{
			$key = Cache::getPrefix().get_called_class();
			$cache = $this->getCache();

			$values = $cache->fetch($key);
			if($values === false)
			{
				$values = $this->loadFile($this->getINIConfigFilename());
				if (is_dir($this->getINIConfigDirectory()))
				{
					foreach(glob($this->getINIConfigDirectory().'*.ini') as $filename)
					{
						$values = array_merge($values, $this->loadFile($filename));
					}
				}
				$cache->add($key, $values, 600);
                $cached = true;
			}
			$this->arrayToSingletons($values);
            if(isset($cached))
            {
                \ManiaLib\Utils\Logger::log('Config cached at '.$key);
            }
		}
	}

    protected function getCache()
    {
        $params = new \ManiaLib\Cache\Drivers\MemcacheConnectionParams();
		$params->id = 'local';
		$params->hosts = array('127.0.0.1');

		try
		{
			return \ManiaLib\Cache\Drivers\Memcache::factory($params);
		}
		catch (\Exception $e)
		{
			return \ManiaLib\Cache\Drivers\NoCache::getInstance();
		}
    }

	protected function loadFile($filename)
	{
		$values = parse_ini_file($filename, true);
		list($values, $overrides) = $this->scanOverrides($values);
		$values = $this->processOverrides($values, $overrides);
		$values = $this->loadAliases($values);
		$values = $this->replaceAliases($values);
		return $values;
	}

	protected function loadAliases(array $values)
	{
		foreach($values as $key => $value)
		{
			if(preg_match('/^\s*alias\s+(\S+)$/iu', $key, $matches))
			{
				if(isset($matches[1]))
				{
					$this->aliases[$matches[1]] = $value;
					unset($values[$key]);
				}
			}
		}
		return $values;
	}

	protected function replaceAliases(array $values)
	{
		$newValues = array();
		foreach($values as $key => $value)
		{
			$callback = explode('.', $key, 2);
			if(count($callback) == 2)
			{
				$className = reset($callback);
				$propertyName = end($callback);
				if(isset($this->aliases[$className]))
				{
					$className = $this->aliases[$className];
				}
				$newValues[$className.'.'.$propertyName] = $value;
			}
			else
			{
				$newValues[$key] = $value;
			}
		}
		return $newValues;
	}

	protected function scanOverrides(array $array)
	{
		$values = array();
		$overrides = array();

		foreach($array as $key => $value)
		{
			if(strstr($key, ':'))
			{
				$overrides[$key] = $value;
			}
			else
			{
				$values[$key] = $value;
			}
		}
		return array($values, $overrides);
	}

	protected function processOverrides(array $values, array $overrides)
	{
		foreach($overrides as $key => $override)
		{
			$matches = null;
			if(preg_match('/^hostname: (.+)$/iu', $key, $matches))
			{
				if($matches[1] == $this->getHostname())
				{
					$values = $this->overrideArray($values, $override);
					break;
				}
			}
		}
		return $values;
	}

	protected function overrideArray(array $source, array $override)
	{
		foreach($override as $key => $value)
		{
			$source[$key] = $value;
		}
		return $source;
	}

	protected function arrayToSingletons($values)
	{
		foreach($values as $key => $value)
		{
			$callback = explode('.', $key, 2);
			if(count($callback) != 2)
			{
				continue;
			}
			$className = reset($callback);
			$propertyName = end($callback);
			if(class_exists($className))
			{
				if(is_subclass_of($className, '\\ManiaLib\\Utils\\Singleton'))
				{
					if(property_exists($className, $propertyName))
					{
						$instance = call_user_func(array($className, 'getInstance'));
						$instance->$propertyName = $value;
					}
				}
			}
		}
	}

}

?>
