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

    /** @todo make queue and process it onPostLoop */
    final public function save($filename, $data, $append = false)
    {
        clearstatcache();
        if (!is_file($filename)) {
            if (!touch($filename)) {
                chmod($filename, 0755);

                return false;
            }
        }
        clearstatcache();
        if (is_writable($filename)) {
            try {
                if ($append === true) {
                    return file_put_contents($filename, $data, LOCK_EX | FILE_APPEND);
                }

                return file_put_contents($filename, $data, LOCK_EX);
            } catch (\Exception $e) {
                Console::println("File write exception:" . $e->getMessage());

                return false;
            }
        }
    }

    /** @todo make queue and process it onPostLoop */
    final public function load($filename)
    {
        clearstatcache();
        if (!is_file($filename)) {
            return false;
        }
        if (is_readable($filename)) {
            try {
                return file_get_contents($filename);
            } catch (\Exception $e) {
                Console::println("File read exception:" . $e->getMessage());

                return false;
            }
        }
    }
}
