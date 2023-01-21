<?php

namespace oliverde8\AsynchronousJobs;

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
class JobRunner
{
    /** @var JobRunner */
    private static $_instance = null;

    protected static $_phpExecutable;

    private $_id = null;

    protected static $_tmpPath;

    private $pendingJobs = [];

    /** @var JobData[] */
    private $runningJobs = [];

    private $exec = false;

    /**
     * Get the job runner instance. Parameters are used only on first creation.
     *
     * @return JobRunner
     */
    public static function getInstance($id = null, $phpExecutable = 'php', $tmpPath = 'tmp/')
    {
        if (is_null(self::$_instance)) {
            self::$_phpExecutable = $phpExecutable;
            self::$_tmpPath = $tmpPath;

            $jobRunner = new JobRunner($id);
            self::$_instance = $jobRunner;
        }

        return self::$_instance;
    }

    protected function getCmd($cmd) {        
        return '"'. PHP_BINARY .'" '. $cmd;
    }

    /**
     * JobRunner constructor.
     */
    protected function __construct($id)
    {
        if ($id) {
            $this->_id = $id;
        } else {
            $this->_id = md5(spl_object_hash($this) . microtime());
        }
        // Check if exec is enabled on this server.
        if (substr(php_uname(), 0, 7) == "Windows") {
            try {                
                $WshShell = new \COM("WScript.Shell");
                $WshShell->Run($this->getCmd('-v /C dir /S %windir%'), 0, false);
                $this->exec = true;
            } catch (\Exception $e) {
                // nothing exec is disabled.
                throw $e;
            }
        } else if(exec('echo EXEC') == 'EXEC'){
            $this->exec = true;
        }
    }

    /**
     * Prepare a directory by creating it.
     *
     * @param $dir
     */
    protected function _prepareDirectory($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Locks a job so that another with the same Id can't work at the same time.
     *
     * @param $jobDir
     * @return bool|resource
     */
    protected function _lockJob($jobDir)
    {
        $fp = fopen("$jobDir/lock", "w+");
        if (flock($fp, LOCK_EX)) {
            return $fp;
        } else {
            return false;
        }
    }

    /**
     * Get the directory where a job will work.
     *
     * @param Job $job The job
     * @return string
     */
    protected function _getJobDirectory(Job $job)
    {
        $jobDir = $this->getDirectory() . DIRECTORY_SEPARATOR . $job->getId();
        $this->_prepareDirectory($jobDir);

        return $jobDir;
    }

    /**
     * Get the id of the runner.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Get directory used for processing.
     *
     * @return string
     */
    public function getDirectory()
    {
        return self::$_tmpPath . $this->_id;
    }

    /**
     * Start the execution of a job.
     *
     * @param Job $job
     */
    public function start(Job $job)
    {
        $jobData = new JobData();

        if ($this->exec) {
            $jobDir = $this->_getJobDirectory($job);
            $logFile = realpath($this->getDirectory()) . '/run.log';
            $lockFile = $this->_lockJob($jobDir);

            if ($lockFile) {
                // $jobdir = str_replace("/", DIRECTORY_SEPARATOR, $jobDir);

                $jobData->lockFile = $lockFile;
                $jobData->job = $job;
                $jobData->jobDir = $jobDir;

                $this->runningJobs[spl_object_hash($job)] = $jobData;

                $data = $job->getData();
                $data['___class'] = get_class($job);

                file_put_contents("$jobDir/in.serialize", serialize($data));

                $cmd = $this->getCmd('"'.realpath(__DIR__ . "/../../bin/AsynchronousJobsRun.php") . "\" $jobDir >> $logFile");
                if (substr(php_uname(), 0, 7) == "Windows") {
                    $WshShell = new \COM("WScript.Shell");
                    $WshShell->Run("$cmd /C dir /S %windir%", 0, false);
                } else {
                    exec($cmd . " &");
                }

            } else {
                $this->pendingJobs[] = $job;
            }
        }else {
            $job->run();
            $job->end($jobData);
        }
    }

    /**
     * Check if a job is terminated, handle the finish & return true or false if finished or not.
     *
     * @param Job $job The job to check.
     *
     * @throws \Exception
     *
     * @return bool
     */
    /**
     * Check if a job is terminated, handle the finish & return true or false if finished or not.
     *
     * @param Job $job The job to check.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function _getJobResult(Job $job)
    {
        $jobHash = spl_object_hash($job);
        if (isset($this->runningJobs[$jobHash])) {
            $jobDir = $this->_getJobDirectory($job);
            if (file_exists("$jobDir/out.serialize")) {
                $data = unserialize(file_get_contents("$jobDir/out.serialize"));

                $jobData = $this->runningJobs[$jobHash];

                $job->setData($data);
                $job->end($jobData);

                // Delete data on this job.
                flock($jobData->lockFile, LOCK_UN);
                fclose($jobData->lockFile);
                $this->rm($jobData->jobDir);

                unset($this->runningJobs[$jobHash]);
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir Path to the directory.
     *
     * @return bool
     */
    protected function rm($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->rm($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    /**
     * Check if a job is running or not.
     *
     * @param Job $job
     * @return bool
     */
    public function isRunning(Job $job)
    {
        return !$this->_getJobResult($job);
    }

    /**
     * Process all running jobs and check if they have finished.
     *
     * This method should be called every second or less !
     */
    public function proccess()
    {
        foreach ($this->runningJobs as $jobData) {
            $this->isRunning($jobData->job);
        }

        foreach ($this->pendingJobs as $job) {
            $this->start($job);
        }
    }

    public function wait(Job $job, $sleepTime = 1)
    {
        while ($job->isRunning()){
            $this->sleep($sleepTime);
        }
    }

    /**
     * Wait for all the jobs to be terminated.
     *
     * @param int $sleepTime Time to sleep.
     */
    public function waitForAll($sleepTime = 1)
    {
        while (!empty($this->runningJobs)) {
            $this->proccess();
            $this->sleep($sleepTime);
        }
    }

    protected function sleep($sleepTime) {
        if (is_float($sleepTime)) {
            usleep((int) ($sleepTime * 1000000));
        } else {
            sleep($sleepTime);
        }
    }

    function __destruct()
    {
        $this->waitForAll();
        $this->rm($this->getDirectory());
    }


}
