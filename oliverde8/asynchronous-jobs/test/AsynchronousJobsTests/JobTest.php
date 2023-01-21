<?php

namespace  oliverde8\AsynchronousJobsTests;
use oliverde8\AsynchronousJobs\Job\Sleep;
use oliverde8\AsynchronousJobs\JobRunner;
use oliverde8\AsynchronousJobs\Job\Sum;
use oliverde8\AsynchronousJobsTests\Jobs\error;

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
class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that 2 sleeping jobs will run properly asynchronously
     */
    public function testTwoSleepJobs()
    {
        $startTime = time();

        // Create 2 sleeping jobs
        $job1 = new Sleep();
        $job1->time = 10;

        $job2 = new Sleep();
        $job2->time = 10;

        $job1->start();
        $job2->start();

        // Starting the jobs should be super quick even throught each job needs 10 second to run.
        $runTime = time() - $startTime;
        $this->assertLessThan(2, $runTime);

        JobRunner::getInstance()->waitForAll(1);

        // If 2 jobs of 10 second run in less then 19 all is good.
        $runTime = time() - $startTime;
        $this->assertLessThan(19, $runTime);
    }

    /**
     * Creating multiple sleeping jobs. 10 job sleeps 10 second should require 100 seconds
     * but with asynchronous it goes fast.
     */
    public function testMultiSleepJobs()
    {
        $startTime = time();
        for($i = 0; $i < 10; $i++) {
            $job = new Sleep();
            $job->time = 10;
            $job->start();
        }

        $runTime = time() - $startTime;
        $this->assertLessThan(10, $runTime);

        JobRunner::getInstance()->waitForAll(1);

        // If 10 jobs of 10 second run in less then 50 all is good.
        $runTime = time() - $startTime;
        $this->assertLessThan(50, $runTime);
    }

    /**
     * Test that jobs get's the paramters inputed to them and that the result of the job is available after.
     */
    public function testInputOutPut()
    {
        $job1 = new Sum();
        $job1->a = 5;
        $job1->b = 7;

        $job2 = new Sum();
        $job2->a = 8;
        $job2->b = 9;

        $job1->start();
        $job2->start();

        JobRunner::getInstance()->waitForAll(1);

        $this->assertEquals(12, $job1->result);
        $this->assertEquals(17, $job2->result);
    }

    /**
     * Test the single job wait.
     */
    public function testWait()
    {
        $startTime = time();

        $job1 = new Sleep();
        $job1->time = 5;
        $job1->start();
        $job1->wait();

        $runTime = time() - $startTime;

        // Check that the job is terminated
        $this->assertFalse($job1->isRunning(), "Job should have stopped");
        // Check that it did take more then 5 second.
        $this->assertGreaterThanOrEqual(5, $runTime);
    }

    /**
     * Is running can be called multiple times it needs to work in all cases
     */
    public function testIsRunning() {

        $job1 = new Sleep();
        $job1->time = 1;
        $job1->start();

        $this->assertTrue($job1->isRunning(), 'Job should be running still');

        $job1->wait();
        $this->assertFalse($job1->isRunning(), 'Job should have stopped');
        $this->assertFalse($job1->isRunning(), 'Calling this multiple times should have no effect');
        $this->assertFalse(JobRunner::getInstance()->isRunning($job1), "or using the longer method");
    }

    public function testException() {
        // Create job that we know will throw an exception
        $job = new error();
        $job->start();

        // wait for it to end. Won't use the wait jobs as it might wait for ever !
        sleep(2);

        // Check that the job is terminated
        $this->assertFalse(JobRunner::getInstance()->isRunning($job), "Job should have stopped");
        // Check the exception
        $this->assertNotNull($job->getException());
        // Check the exception message
        $this->assertEquals("test", $job->getException()->getMessage());
    }

}