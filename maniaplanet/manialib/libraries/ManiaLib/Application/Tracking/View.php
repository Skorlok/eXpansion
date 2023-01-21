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

namespace ManiaLib\Application\Tracking;

use ManiaLib\Gui\Elements\Quad;

class View extends \ManiaLib\Application\View
{
	static $rendered = false;
	
	const PAGEVIEW_QUAD_ID = 'manialib-ga-pageview';
	const EVENT_QUAD_ID = 'manialib-ga-event';
	
	function display()
	{
		if (!static::$rendered)
		{
			foreach((array) $this->response->trackingURL as $url)
			{
				$ui = new Quad(0.1, 0.1);
				$ui->setPosition(400, 300);
				$ui->setImage($url, true);
				$ui->setId(self::PAGEVIEW_QUAD_ID);
				$ui->save();

				$ui = new Quad(0.1, 0.1);
				$ui->setPosition(400, 300);
				$ui->setImage('', true);
				$ui->setId(self::EVENT_QUAD_ID);
				$ui->save();
			}
			static::$rendered = true;
		}
	}

}

?>