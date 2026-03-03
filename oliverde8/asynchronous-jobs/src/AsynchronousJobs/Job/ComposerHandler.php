<?php

namespace oliverde8\AsynchronousJobs\Job;

use oliverde8\AsynchronousJobs\Job;
use oliverde8\AsynchronousJobs\JobData;

class ComposerHandler extends Job
{

    protected $result;

    protected $directory;

    protected $command;

    /**
     * Method called by the new instance to run the job.
     *
     * @return mixed
     */
    public function run()
    {
        if (!(function_exists('proc_open') && (version_compare(PHP_VERSION, '7.4', '>=') || (file_exists('/bin/sh') && is_executable('/bin/sh'))))) {
            $this->result = array(
                'returnVar' => -1
            );
            return;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            // start /B "" is used to start a process without opening a new window, and it works on Windows.
            $command = 'start /B "" ' . implode(' ', $this->command);
        } else if (version_compare(PHP_VERSION, '7.4', '>=')) {
            // Linux >= 7.4, shell not needed, proc_open can execute directly
            $command = $this->command;
        } else {
            // Linux <7.4, shell needed
            $command = implode(' ', $this->command);
        }

        // We need to change the working directory to the one of the composer.json, otherwise composer will not work.
        chdir($this->directory);

        $descriptorSpec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open($command, $descriptorSpec, $pipes);

        if (!is_resource($process)) {
            $this->result = array(
                'returnVar' => -2
            );
            return;
        }

        fclose($pipes[0]); // stdin

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $returnVar = proc_close($process);

        $this->result = array(
            'output' => explode(PHP_EOL, trim($stdout)),
            'error' => explode(PHP_EOL, trim($stderr)),
            'returnVar' => $returnVar
        );
    }

    /**
     * Method called by the original instance when the job has ran.
     *
     * @param JobData $jobData Data about the job
     *
     * @return mixed
     */
    public function end(JobData $jobData)
    {
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}