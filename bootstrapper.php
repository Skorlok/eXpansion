<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'autoload.php';

$required = array('spl' => 'Standard PHP Library', 'json' => 'JSON', 'mbstring' => 'mbString', 'curl' => 'cURL with SSL', 'xml' => 'xml', 'simplexml' => 'simplexml', 'dom' => 'dom', 'pcre' => 'pcre');
$optional = array('gd' => 'gd', 'phar' => 'phar', 'openssl' => 'openssl', 'zlib' => 'zlib', 'sockets' => 'sockets', 'xmlrpc' => 'xmlrpc');
$atLastOne = array('sqlite3' => 'SQLite3', 'mysqli' => 'MySQLI');

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $required['com_dotnet'] = 'com_dotnet';
} else {
    $optional['pcntl'] = 'pcntl';
}

$phpOk = (function_exists('version_compare') && version_compare(phpversion(), '5.5.0', '>='));

$requiredPass = true;
$optionalPass = true;
$atLastOnePass = false;

echo '
 _|      _|                      _|            _|        _|
 _|_|  _|_|    _|_|_|  _|_|_|          _|_|_|  _|            _|    _|    _|_|
 _|  _|  _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|    _|  _|_|_|_|
 _|      _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|  _|    _|
 _|      _|    _|_|_|  _|    _|  _|    _|_|_|  _|_|_|_|  _|    _|        _|_|_|
';
echo '-----------------------------------------------------' . PHP_EOL;
echo 'PHP Environment Compatibility Test' . PHP_EOL;
echo '-----------------------------------------------------' . PHP_EOL;
echo 'PHP 5.5.0 or newer    -> required  -> '.($phpOk ? ('[ Yes ] ' . phpversion()) : '[ No  ]') . PHP_EOL;
foreach ($required as $ext => $name) {
    if ($ext == 'curl') {
        
        $curlVersion = null;
        $curlSslOk = false;
        if(extension_loaded('curl') && function_exists('curl_version')) {
            $curlVersion = curl_version();
            $curlSslOk = (function_exists('curl_exec') && in_array('https', $curlVersion['protocols'], true));
        }
        
        echo 'cURL with SSL         -> required  -> '.($curlVersion ? ($curlSslOk ? '[ Yes ] '.$curlVersion['version'].' (with '.$curlVersion['ssl_version'].')' : '[ No  ] '.$curlVersion['version'].' (without SSL)') : '[ No  ]') . PHP_EOL;
        
        if (!extension_loaded($ext) || !$curlSslOk) {
            $requiredPass = false;
        }
        
    } else {
        echo str_pad($name, 22) . '-> required  ->' . (extension_loaded($ext) ? ' [ Yes ]' : ' [ No  ]') . PHP_EOL;
        if (!extension_loaded($ext)) {
            $requiredPass = false;
        }
    }
}
foreach ($optional as $ext => $name) {
    echo str_pad($name, 22) . '-> optional  ->' . (extension_loaded($ext) ? ' [ Yes ]' : ' [ No  ]') . PHP_EOL;
    if (!extension_loaded($ext)) {
        $optionalPass = false;
    }
}
foreach ($atLastOne as $ext => $name) {
    echo str_pad($name, 22) . '-> optional  ->' . (extension_loaded($ext) ? ' [ Yes ]' : ' [ No  ]') . PHP_EOL;
    if (extension_loaded($ext)) {
        $atLastOnePass = true;
    }
}
echo '-----------------------------------------------------' . PHP_EOL;


if (!$requiredPass || !$phpOk) {
    echo 'Your system is not compatible, check your php configuration.' . PHP_EOL;
    exit;
}

if (!$atLastOnePass) {
    echo 'Your system is not compatible, you must have a database extension.' . PHP_EOL;
    exit;
}

if (!$optionalPass) {
    echo 'Some optional extensions are disabled. eXpansion may encounter some performance trouble, and some features may not work.' . PHP_EOL;
}

date_default_timezone_set("Europe/Paris"); // Need to change this with a ENV var

ini_set('display_errors', 1);
error_reporting(E_ALL);
gc_enable();

\ManiaLive\Application\Application::getInstance()->run();

?>
