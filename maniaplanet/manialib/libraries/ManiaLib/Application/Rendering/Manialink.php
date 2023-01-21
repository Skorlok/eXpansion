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

namespace ManiaLib\Application\Rendering;

class Manialink implements RendererInterface
{

	static function exists($viewName)
	{
		return class_exists($viewName);
	}

	static function render($viewName)
	{
		if(!self::exists($viewName))
		{
			throw new ViewNotFoundException('View not found: '.$viewName);
		}

		$view = new $viewName();
		$view->display();

		if($view instanceof \ManiaLib\Application\Views\Dialogs\DialogInterface)
		{
			\ManiaLib\Gui\Manialink::disableLinks();
		}
	}

	static function redirect($URL)
	{
		if(\ManiaLib\Application\Filters\UserAgentCheck::isManiaplanet())
		{
			\ManiaLib\Gui\Manialink::redirect($URL);
		}
		else
		{
			header('Location: '.$URL);
		}
		exit;
	}

	static function header()
	{
		header('Content-Type: application/xml');
		header('X-Powered-By: ManiaLib 2');
	}
	
	static function registerException(\Exception $e)
	{
		
	}

}

?>