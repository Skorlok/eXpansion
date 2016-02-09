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

namespace ManiaLivePlugins\eXpansion\Core\Classes;

use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\types\AsynchronousCurlData;

class AsynchronousCurl extends \ManiaLib\Utils\Singleton implements \ManiaLive\Application\Listener, \ManiaLive\Features\Tick\Listener
{
    protected $handle;

    /** @var AsynchronousCurlData[] */
    protected $_queries = array();

    public function start()
    {
        Dispatcher::register(AppEvent::getClass(), $this);
    }

    /**
     * make a http query with options
     * @param string $url
     * @param callable $callback
     * @param mixed $addionalData if you need to pass additional metadata with the query, like login do it here
     * @param array $options  curl options array
     */
    public function query($url, $callback, $additionalData = null, $options = array())
    {
        $data = null;
        // Initialize Multi curl if none is running at the moment.
        if (empty($this->_queries)) {
            $this->handle = curl_multi_init();
        }

        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $data = new AsynchronousCurlData($callback, $data);
        if ($additionalData) {
            $data->setMeta($additionalData);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, "eXpansionPluginPack v ".\ManiaLivePlugins\eXpansion\Core\Core::EXP_VERSION);

        if (!empty($options)) {
            curl_setopt_array($ch, $options);          
        }
        curl_multi_add_handle($this->handle, $ch);

        $this->_queries[(string) $ch] = $data;
    }

    /**
     * Event launch every seconds
     */
    function onTick()
    {

    }

    function onInit()
    {

    }

    function onRun()
    {

    }

    function onPreLoop()
    {
        
    }

    function onPostLoop()
    {
        // note: had to move this here, since slow internet connections downloading a single map took 2 minutes...
        if (!empty($this->_queries)) {
            curl_multi_exec($this->handle, $active);
            if ($state = curl_multi_info_read($this->handle)) {
                $id = (string) $state['handle'];
                
                if (isset($this->_queries[$id])) {
                    $this->_queries[$id]->finalize($state['handle']);
                    unset($this->_queries[$id]);
                }
            }
        }
    }

    function onTerminate()
    {
        curl_multi_close($this->handle);
    }
}