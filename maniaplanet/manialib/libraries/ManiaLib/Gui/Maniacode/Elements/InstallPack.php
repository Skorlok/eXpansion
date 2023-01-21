<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Gui\Maniacode\Elements;

/**
 * Install skin
 */
class InstallPack extends \ManiaLib\Gui\Maniacode\Elements\FileDownload
{
	/**#@+
	 * @ignore
	 */
	protected $xmlTagName = 'install_pack';
	protected $file;

	function __construct($name='', $file='', $url='')
	{
		parent::__construct($name, $url);
		$this->setFile($file);
	}

	/**
	 * This method sets the path to install the skin
	 *
	 * @param string $file The path to the skin
	 * @return void
	 *
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * This method gets the path to install the skin
	 *
	 * @return string The path to the skin
	 *
	 */
	public function getFile()
	{
		return $this->file;
	}

	protected  function postFilter()
	{
		parent::postFilter();
		if (isset($this->file))
		{
			$elem  = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('file');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->file);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}
}

?>