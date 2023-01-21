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
abstract class Job
{
    /** @var \Exception Will be null if there are no exceptions */
    protected $exception = null;

    /**
     * Create the new execution, O yeaah.
     */
    final public function start() {
        $jobRunner = JobRunner::getInstance();
        $jobRunner->start($this);
    }

    /**
     * Unique id, multiple jobs with the same id can't run at the sma time.
     * You may use this to prevent some jobs to run at the same time.
     *
     * @return string
     */
    public function getId() {
        return md5(spl_object_hash($this));
    }

    /**
     * Get the data of the job for sending to the new process.
     *
     * @return array
     */
    public function getData()
    {
        $data = array();
        foreach (get_object_vars($this) as $key => $value) {
            if (strpos($key, '__') !== 0) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * But back the job together from what comes from the other process.
     *
     * @param $data
     */
    public function setData($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Check if this job is still running.
     *
     * @return bool
     */
    public function isRunning()
    {
        $jobRunner = JobRunner::getInstance();
        return $jobRunner->isRunning($this);
    }

    /**
     * Wait for this job to finish.
     */
    public function wait()
    {
        $jobRunner = JobRunner::getInstance();
        $jobRunner->wait($this);
    }

    /**
     * Get exception that accured on the job
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $__exception
     */
    public function setException($__exception)
    {
        $this->exception = $__exception;
    }

    /**
     * Method called by the new instance to run the job.
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * Method called by the original instance when the job has ran.
     *
     * @param JobData $jobData Data about the job
     *
     * @return mixed
     */
    abstract public function end(JobData $jobData);
}