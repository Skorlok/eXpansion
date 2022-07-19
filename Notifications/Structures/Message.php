<?php

namespace ManiaLivePlugins\eXpansion\Notifications\Structures;

class Message
{

    public $login;
    public $message;
    public $icon;
    public $callback;
    public $timestamp;

    public function __construct($login, $icon, $message, $callback)
    {
        $this->login = $login;
        $this->message = $message;
        $this->icon = $icon;
        $this->callback = $callback;
        $this->timestamp = time();
    }
}
