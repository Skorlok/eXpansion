<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'autoload.php';

echo '
 _|      _|                      _|            _|        _|
 _|_|  _|_|    _|_|_|  _|_|_|          _|_|_|  _|            _|    _|    _|_|
 _|  _|  _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|    _|  _|_|_|_|
 _|      _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|  _|    _|
 _|      _|    _|_|_|  _|    _|  _|    _|_|_|  _|_|_|_|  _|    _|        _|_|_|
';
echo '--------------------------------------------------------------'.PHP_EOL;
echo 'Running in Docker, skipping PHP Environment Compatibility Test'.PHP_EOL;
echo '--------------------------------------------------------------'.PHP_EOL;

date_default_timezone_set("Europe/Paris"); // Need to change this with a ENV var

ini_set('display_errors', 1);
error_reporting(E_ALL);
gc_enable();

\ManiaLive\Application\Application::getInstance()->run();

?>
