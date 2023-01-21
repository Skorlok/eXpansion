<?php

namespace  oliverde8\AsynchronousJobsTests;
use oliverde8\AsynchronousJobs\Job;
use oliverde8\AsynchronousJobs\Job\Curl;
use oliverde8\AsynchronousJobs\Job\CallbackCurl;
use oliverde8\AsynchronousJobs\JobRunner;

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
class CurlTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $curlJob = new Curl();
        $curlJob->setMethod('GET');
        $curlJob->setUrl('http://jsonplaceholder.typicode.com/posts');

        $curlJob->start();
        JobRunner::getInstance()->waitForAll(1);

        $info = $curlJob->getCurlInfo();

        $this->assertEquals(200, $info['http_code']);
        $this->assertJson($curlJob->getResponse());
    }

    public function testGetParams()
    {
        $curlJob = new Curl();
        $curlJob->setMethod('GET');
        $curlJob->setUrl('http://jsonplaceholder.typicode.com/posts');
        $curlJob->setParameters(array('userId' => '1'));

        $curlJob->start();
        JobRunner::getInstance()->waitForAll(1);

        $info = $curlJob->getCurlInfo();

        $this->assertEquals(200, $info['http_code']);
        $this->assertJson($curlJob->getResponse());

        $response = json_decode($curlJob->getResponse());
        foreach ($response as $user) {
            $this->assertEquals(1, $user->userId);
        }
    }

    public function testPost()
    {
        $curlJob = new Curl();
        $curlJob->setMethod('POST');
        $curlJob->setUrl('http://jsonplaceholder.typicode.com/posts');
        $curlJob->setData(array(
            'title' => 'foo',
            'body'  => 'toto',
            'userId' => '1'
        ));

        $curlJob->start();
        JobRunner::getInstance()->waitForAll(1);

        $info = $curlJob->getCurlInfo();

        $this->assertEquals(201, $info['http_code']);
        $this->assertJson($curlJob->getResponse());
    }

    private $_testCallbackCalled = false;
    private $_testCallbackCalled2 = false;

    public function testCallback()
    {
        $curlJob = new CallbackCurl();
        $curlJob->setMethod('GET');
        $curlJob->setUrl('http://jsonplaceholder.typicode.com/posts');
        $curlJob->setCallback(array($this, '_testCallbackCallback'));
        $curlJob->start();

        $curlJob = new CallbackCurl();
        $curlJob->setMethod('GET');
        $curlJob->setUrl('http://jsonplaceholder.typicode.com/posts');
        $curlJob->setCallback(array($this, '_testCallbackCallback2'));
        $curlJob->start();

        JobRunner::getInstance()->waitForAll(1);

        $this->assertEquals(true, $this->_testCallbackCalled);
        $this->assertEquals(true, $this->_testCallbackCalled2);
    }

    public function _testCallbackCallback(Job $curlJob)
    {
        $info = $curlJob->getCurlInfo();

        $this->assertEquals(200, $info['http_code']);
        $this->assertJson($curlJob->getResponse());

        $this->_testCallbackCalled = true;
    }

    public function _testCallbackCallback2(Job $curlJob)
    {
        $info = $curlJob->getCurlInfo();

        $this->assertEquals(200, $info['http_code']);
        $this->assertJson($curlJob->getResponse());

        $this->_testCallbackCalled2 = true;
    }

}