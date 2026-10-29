<?php

namespace ManiaLivePlugins\eXpansion\Core;

class Install
{

    /**
     * Updates the bootstrapper.php of older installations, which still loads libraries/autoload.php
     * (now done by vendor/autoload.php and \ManiaLive\Application\Application).
     */
    public static function postUpdate()
    {
        /*$badWords = file_get_contents('https://github.com/shutterstock/List-of-Dirty-Naughty-Obscene-and-Otherwise-Bad-Words/archive/master.zip');

        if (!is_dir('tmp')) {
            mkdir('tmp', 0777, true);
        }

        if (!is_dir('config/bad_words')) {
            mkdir('config/bad_words', 0777, true);
        }

        file_put_contents('tmp/tmp.zip', $badWords);

        $zip = new \ZipArchive();
        $zip->open('tmp/tmp.zip');
        $zip->extractTo('config/bad_words');*/

        if (!file_exists(APP_ROOT . "bootstrapper.php") || !is_writable(APP_ROOT . "bootstrapper.php")) {
            return;
        }

        $newFile  = "<?php" . PHP_EOL;
        $newFile .= "require_once __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';" . PHP_EOL;
        $newFile .= "\\ManiaLive\\Application\\Application::getInstance()->run();" . PHP_EOL;
        $newFile .= "?>" . PHP_EOL;

        $file = file_get_contents(APP_ROOT . 'bootstrapper.php');
        if (strpos($file, 'libraries') !== false || strpos($file, 'vendor') === false) {
            $tmpFile = APP_ROOT . 'bootstrapper.php.' . getmypid() . '.tmp';
            if (file_put_contents($tmpFile, $newFile) !== false) {
                rename($tmpFile, APP_ROOT . 'bootstrapper.php');
            }
        }
    }
}
