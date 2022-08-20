<?php

namespace ManiaLivePlugins\eXpansion\Core;

/**
 * Description of DataStorage
 *
 * @author Reaby
 */
class DataAccess extends \ManiaLib\Utils\Singleton
{
    /** @var Classes\AsynchronousCurl */
    private $asyncCurl;

    public function __construct()
    {
        $this->asyncCurl = Classes\AsynchronousCurl::getInstance();
        $this->asyncCurl->start();
    }

    /**
     * Asynchromous curl query
     *
     * Use this if you need to access for example https
     * note: This may block the main loop for short period of time.
     *
     * @param string $url
     * @param callable $callback
     * @param mixed $addionalData additional data passed for the query, like login, map-object, whatever
     * @param array $options curl options array
     */
    public function httpCurl($url, $callback, $addionalData = null, $options = array())
    {
        $this->asyncCurl->query($url, $callback, $addionalData, $options);
    }
}
