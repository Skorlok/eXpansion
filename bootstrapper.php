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
require_once __DIR__.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'autoload.php';

$phpOk = (function_exists('version_compare') && version_compare(phpversion(), '5.5.0', '>='));
$jsonOk = (extension_loaded('json') && function_exists('json_encode') && function_exists('json_decode'));
$splOk = extension_loaded('spl');
$curlOk = function_exists('curl_version');
if($curlOk)
{
    $curlVersion = curl_version();
    $curlSslOk = (function_exists('curl_exec') && in_array('https', $curlVersion['protocols'], true));
}
$mysqliOk = extension_loaded('mysqli');
$sqliteOk = extension_loaded('sqlite3');
$mbstringOk = extension_loaded('mbstring');
$gdOk = extension_loaded('gd');
$pcreOk = extension_loaded('pcre') && function_exists('preg_match') && function_exists('preg_match_all');
$xmlrpcOk = extension_loaded('xmlrpc');
$opensslOk = extension_loaded('openssl');

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $com_dotnetOk = extension_loaded('com_dotnet');
}

echo '
 _|      _|                      _|            _|        _|
 _|_|  _|_|    _|_|_|  _|_|_|          _|_|_|  _|            _|    _|    _|_|
 _|  _|  _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|    _|  _|_|_|_|
 _|      _|  _|    _|  _|    _|  _|  _|    _|  _|        _|  _|  _|    _|
 _|      _|    _|_|_|  _|    _|  _|    _|_|_|  _|_|_|_|  _|    _|        _|_|_|
';
echo '-----------------------------------------------------'.PHP_EOL;
echo 'PHP Environment Compatibility Test'.PHP_EOL;
echo '-----------------------------------------------------'.PHP_EOL;
echo 'PHP 5.5.0 or newer    -> required  -> '.($phpOk ? ('[ Yes ] '.phpversion()) : '[ No  ]').PHP_EOL;
echo 'Standard PHP Library  -> required  -> '.($splOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'JSON                  -> required  -> '.($jsonOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'mbString              -> required  -> '.($mbstringOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'gd                    -> required  -> '.($gdOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'pcre                  -> required  -> '.($pcreOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'cURL with SSL         -> required  -> '.($curlOk ? ($curlSslOk ? '[ Yes ] '.$curlVersion['version'].' (with '.$curlVersion['ssl_version'].')' : '[ No  ] '.$curlVersion['version'].' (without SSL)') : '[ No  ]').PHP_EOL;
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo 'com_dotnet            -> required  -> '.($com_dotnetOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
}
echo 'MySQLI                -> optional  -> '.($mysqliOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'SQLite3               -> optional  -> '.($sqliteOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'xmlrpc                -> optional  -> '.($xmlrpcOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo 'openssl               -> optional  -> '.($opensslOk ? '[ Yes ]' : '[ No  ]').PHP_EOL;
echo '-----------------------------------------------------'.PHP_EOL;


if(!$curlOk)
{
    echo 'You should install cURL PHP extension'.PHP_EOL;
    echo '  on debian/ubuntu : sudo apt-get install php5-curl'.PHP_EOL;
}
if(!$phpOk || !$curlOk || !$splOk || !$jsonOk || !$mbstringOk || !$gdOk || !$pcreOk || (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && !$com_dotnetOk))
{
    echo 'Your system is not compatible, check your php configuration.'.PHP_EOL;
    exit;
}

// better checking if timezone is set
if(!ini_get('date.timezone'))
{
    $timezone = @date_default_timezone_get();
    echo 'Timezone is not set in php.ini. Please edit it and change/set "date.timezone" appropriately. ' .'Setting to default: \''.$timezone.'\''.PHP_EOL;
    date_default_timezone_set($timezone);
}

if(!$mysqliOk && !$sqliteOk)
{
    echo 'Your system is not compatible, you must have a database extension.'.PHP_EOL;
    exit;
}

if(!$xmlrpcOk)
{
    echo 'xmlrpc is disabled, ManiaLive may encounter some perfomance trouble.'.PHP_EOL;
}

if(!$opensslOk)
{
    echo 'openssl is disabled, some features may not works.'.PHP_EOL;
}

// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
gc_enable();

\ManiaLive\Application\Application::getInstance()->run();

?>
