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

foreach (array(__DIR__ . '/../../../autoload.php', __DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        define('PHPUNIT_COMPOSER_INSTALL', $file);
        break;
    }
}

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    fwrite(STDERR,
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
    die(1);
}

require PHPUNIT_COMPOSER_INSTALL;

if (empty($argv[1])) {
    die();
}

$data = unserialize(file_get_contents($argv[1] . '/in.serialize'));

$class = $data['___class'];

try {
    /** @var \oliverde8\AsynchronousJobs\Job $job */
    echo "[" . date('Y-m-d H:i:s') . "] Starting Job with class : $class\n";
    $job = new $class();
    $job->setData($data);
    $job->run();
    $data = $job->getData();
    echo "[" . date('Y-m-d H:i:s') . "] Ended Sucessfully Job with class : $class\n";
} catch (Exception $e) {
    $job->setException($e);
    echo "[" . date('Y-m-d H:i:s') . "] Error with job with class : $class\n";
    echo $e->getTraceAsString();
    echo "\n";
}

file_put_contents($argv[1] . '/out_temp.serialize', serialize($job->getData()));
rename($argv[1] . '/out_temp.serialize', $argv[1] . '/out.serialize');
