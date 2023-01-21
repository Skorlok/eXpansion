<?php
/**
 * @author      Oliver de Cramer (oliverde8 at gmail.com)
 * @copyright    GNU GENERAL PUBLIC LICENSE
 *                     Version 3, 29 June 2007
 *
 * PHP version 5.3 and above
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see {http://www.gnu.org/licenses/}.
 */

namespace ManiaLive\Application;


use ManiaLive\Event\Event as disEvent;
use ManiaLive\Event\Listener as disListener;

interface ApplicationListener {

	/**
	 * @param disListener $listener
	 * @param disEvent $event
	 *
	 * @return mixed
	 */
	public function beforeFireDo($listener, $event);

	/**
	 * @param disListener $listener
	 * @param disEvent $event
	 *
	 * @return mixed
	 */
	public function afterFireDo($listener, $event);

} 