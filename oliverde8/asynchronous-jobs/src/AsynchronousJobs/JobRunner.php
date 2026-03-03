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

    private $pendingJobs = array();

    /**
     * runningJobs:
     * [
     *   jobHash => [
     *       'process' => resource,
     *       'jobData' => JobData
     *   ]
     * ]
     */
    /** @var JobData[] */
    private $runningJobs = array();

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

            self::$_instance = new JobRunner($id);
        }

        return self::$_instance;
    }

    /**
     * JobRunner constructor.
     */
    protected function __construct($id)
    {
        $this->_id = $id ?: md5(spl_object_hash($this) . microtime(true));

        // proc_open require shell before 7.4, so if php < 7.4, we check if shell is available, otherwise we fallback to direct execution.
        $this->exec = function_exists('proc_open') && (version_compare(PHP_VERSION, '7.4', '>=') || (file_exists('/bin/sh') && is_executable('/bin/sh')));
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
        }
        return false;
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

        if (!$this->exec) {
            $job->run();
            $job->end($jobData);
            return;
        }

        $jobDir = $this->_getJobDirectory($job);
        $this->_prepareDirectory($this->getDirectory());

        $logFile = $this->getDirectory() . '/run.log';
        $lockFile = $this->_lockJob($jobDir);

        if (!$lockFile) {
            $this->pendingJobs[] = $job;
            return;
        }

        $jobData->lockFile = $lockFile;
        $jobData->job = $job;
        $jobData->jobDir = $jobDir;

        $data = $job->getData();
        $data['___class'] = get_class($job);

        file_put_contents("$jobDir/in.serialize", serialize($data));

        $phpBinary = PHP_BINARY;
        $script = realpath(__DIR__ . "/../../bin/AsynchronousJobsRun.php");

        $descriptorSpec = array(
            0 => array("pipe", "r"),
            1 => array("file", $logFile, "a"),
            2 => array("file", $logFile, "a"),
        );

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            // start /B "" is used to start a process without opening a new window, and it works on Windows.
            $command = 'start /B "" ' . escapeshellarg($phpBinary) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobDir);
        } else if (version_compare(PHP_VERSION, '7.4', '>=')) {
            // Linux >= 7.4, shell not needed, proc_open can execute directly
            $command = array($phpBinary, $script, $jobDir);
        } else {
            // Linux <7.4, shell needed
            $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($jobDir);
        }

        $process = proc_open(
            $command,
            $descriptorSpec,
            $pipes
        );

        if (is_resource($process)) {

            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            $jobHash = spl_object_hash($job);

            $this->runningJobs[$jobHash] = array(
                'process' => $process,
                'jobData' => $jobData
            );
        } else {
            $this->pendingJobs[] = $job;
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

        if (!isset($this->runningJobs[$jobHash])) {
            return true;
        }

        $entry = $this->runningJobs[$jobHash];
        $process = $entry['process'];
        $jobData = $entry['jobData'];

        $status = proc_get_status($process);

        // reap zombie process, so not need Tini anymore
        if (!$status['running']) {
            proc_close($process);
        }

        $jobDir = $jobData->jobDir;

        if (file_exists("$jobDir/out.serialize")) {
            $data = unserialize(file_get_contents("$jobDir/out.serialize"));

            $job->setData($data);
            $job->end($jobData);

            // Delete data on this job.
            flock($jobData->lockFile, LOCK_UN);
            fclose($jobData->lockFile);
            $this->rm($jobData->jobDir);

            unset($this->runningJobs[$jobHash]);
            return true;
        }

        return false;
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir Path to the directory.
     *
     * @return bool
     */
    protected function rm($dir)
    {
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
        foreach ($this->runningJobs as $entry) {
            $this->isRunning($entry['jobData']->job);
        }

        foreach ($this->pendingJobs as $job) {
            $this->start($job);
        }

        $this->pendingJobs = array();
    }

    
    public function wait(Job $job, $sleepTime = 1)
    {
        while ($this->isRunning($job)) {
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

    protected function sleep($sleepTime)
    {
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