<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Gui;

/**
 * Description of Group
 */
class Group implements \Iterator
{
    private static $groups = array();

    private $internalName;
    private $logins = array();

    static function Create($publicName, $logins = array())
    {
        if (isset(self::$groups[$publicName])) {
            $group = self::$groups[$publicName];
            foreach ($logins as $login)
                $group->add(strval($login));
            return $group;
        }

        $group = new Group($publicName, $logins);
        self::$groups[$publicName] = $group;
        return $group;
    }

    static function Get($publicName)
    {
        if (isset(self::$groups[$publicName]))
            return self::$groups[$publicName];
        return null;
    }

    static function Erase($publicName)
    {
        if (isset(self::$groups[$publicName])) {
            $group = self::$groups[$publicName];
            Window::Erase($group);
            unset(self::$groups[$publicName]);
        }
    }

    protected function __construct($publicName, $logins)
    {
        $this->internalName = '$' . $publicName;
        foreach ($logins as $login)
            $this->logins[$login] = strval($login);
    }

    function __toString()
    {
        return (string)$this->internalName;
    }

    function add($login, $showWindows = false)
    {
        if (!isset($this->logins[$login])) {
            $this->logins[$login] = strval($login);
            if ($showWindows)
                foreach (Window::Get($this) as $window) {
                    if ($window->isVisible()) {
                        $window->show($login);
                    }
                }
        }
    }

    function contains($logins)
    {
        if ($logins instanceof Group || is_array($logins)) {
            foreach ($logins as $login)
                if (!isset($this->logins[$login]))
                    return false;
            return true;
        }
        return isset($this->logins[(string)$logins]);
    }

    function count()
    {
        return count($this->logins);
    }

    function remove($login)
    {
        if (isset($this->logins[$login])) {
            foreach (Window::Get($this) as $window)
                $window->hide($login);
            unset($this->logins[$login]);
        }
    }

    function toArray()
    {
        return $this->logins;
    }

    // #Iterator implementation

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->logins);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->logins);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->logins);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->logins);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return key($this->logins) !== null;
    }
}

?>