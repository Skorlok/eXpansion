<?php

namespace ManiaLivePlugins\eXpansion\Core;

/**
 * Dedicated storage for variable scope handlers.
 * Uses a backing array to avoid dynamic property deprecation warnings on Config.
 */
class ScopeStorage extends \ManiaLib\Utils\Singleton implements \IteratorAggregate
{
    private $data = array();

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
        // echo "ScopeStorage: Set $name to " . var_export($value, true) . PHP_EOL;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
