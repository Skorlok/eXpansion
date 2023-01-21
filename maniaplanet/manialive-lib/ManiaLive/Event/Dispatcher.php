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

namespace ManiaLive\Event;

use ManiaLive\Application\ApplicationListener;
use ManiaLive\Application\ErrorHandling;

abstract class Dispatcher
{
    static protected $listeners = array();
    static protected $eventsByClass = array();
    static protected $priorityListener = array();

	/** @var ApplicationListener */
	static protected $listener = null;

	public static function setApplicationListener(ApplicationListener $applistener)
	{
		self::$listener = $applistener;
	}

    public static function register($eventClass, Listener $listener, $events = Event::ALL, $priority = null)
    {
        $listenerId = spl_object_hash($listener);

        if (!isset(self::$eventsByClass[$eventClass])) {
            $rc = new \ReflectionClass($eventClass);

            self::$eventsByClass[$eventClass] = $rc->getConstants();
            self::$listeners[$eventClass]     = array();
            foreach (self::$eventsByClass[$eventClass] as $event)
                self::$listeners[$eventClass][$event] = array();
        }

        foreach (self::$eventsByClass[$eventClass] as $event)
            if ($event & $events) {
                if ($priority != null) {
                    if (!isset($priorityListener[$eventClass][$event])){
                        self::$priorityListener[$eventClass][$event] = new \SplPriorityQueue();
                    }
                    $class = get_class($listener);
                    self::$priorityListener[$eventClass][$event]->insert($listener, $priority);

                } else {
                    self::$listeners[$eventClass][$event][$listenerId] = $listener;
                }
            }
    }

    public static function unregister($eventClass, Listener $listener, $events = Event::ALL)
    {
        $listenerId = spl_object_hash($listener);


        if (isset(self::$eventsByClass[$eventClass]))
            foreach (self::$eventsByClass[$eventClass] as $event)
                if ($event & $events) {
                    unset(self::$listeners[$eventClass][$event][$listenerId]);

                    if(isset(self::$priorityListener[$eventClass][$event])
						&& !self::$priorityListener[$eventClass][$event]->isEmpty()
					){

                        $plist = self::$priorityListener[$eventClass][$event];

                        $plist->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
                        $plist->top();

                        $newPriority = new \SplPriorityQueue();

                        while ($plist->valid()) {
                            $vars = $plist->current();
                            $listener = $vars["data"];
                            $priority = $vars["priority"];
                            if(spl_object_hash($listener) != $listenerId){
                                $newPriority->insert($listener, $priority);
                            }
                            $plist->next();
                        }

                        self::$priorityListener[$eventClass][$event] =  $newPriority;
                    }
                }
    }

    public static function dispatch(Event $event)
    {
        $eventClass = get_class($event);

        if (isset(self::$priorityListener[$eventClass])
            && isset(self::$priorityListener[$eventClass][$event->getMethod()])){
        }

        if (isset(self::$priorityListener[$eventClass])
            && isset(self::$priorityListener[$eventClass][$event->getMethod()])
            && !self::$priorityListener[$eventClass][$event->getMethod()]->isEmpty()
        ) {
            $priority = clone self::$priorityListener[$eventClass][$event->getMethod()];

            $priority->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
            $priority->top();
            while ($priority->valid()) {

                $listener = $priority->current();
                try {
                    $event->fireDo($listener);
                } catch (StopperException $e) {
                    break;
                } catch (\Exception $e) {
                    ErrorHandling::processModuleException($e);
                }

                $priority->next();
            }
        }

        if (isset(self::$listeners[$eventClass]) && isset(self::$listeners[$eventClass][$event->getMethod()]))
            foreach (self::$listeners[$eventClass][$event->getMethod()] as $listener)
                try {
					if (self::$listener != null) {
						self::$listener->beforeFireDo($listener, $event);
					}
                    $event->fireDo($listener);
					if (self::$listener != null) {
						self::$listener->afterFireDo($listener, $event);
					}
                } catch (StopperException $e) {
                    break;
                } catch (\Exception $e) {
                    ErrorHandling::processModuleException($e);
                }
    }
}

?>