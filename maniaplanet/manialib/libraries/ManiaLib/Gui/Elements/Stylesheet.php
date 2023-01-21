<?php

/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLib\Gui\Elements;

class Stylesheet extends Frame
{
	protected $xmlTagName = 'stylesheet';

	/**
	 * @var Frame3dStyles
	 */
	protected $frame3dStyle;
	
	function __construct()
	{
		parent::__construct(0, 0);
		$this->frame3dStyle = new Frame3dStyles();
		$this->children[] = $this->frame3dStyle;
	}

	function add(Style3d $style)
	{
		$this->frame3dStyle->add($style);
	}
}

?>