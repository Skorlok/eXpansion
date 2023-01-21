<?php

namespace oliverde8\AsynchronousJobs\Job;
use oliverde8\AsynchronousJobs\Job;
use oliverde8\AsynchronousJobs\JobData;

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
class Curl extends Job
{
    protected $method = "GET";

    protected $url = null;

    protected $parameters = array();

    protected $options = array();

    protected $response = null;

    protected $curlInfo = null;

    protected $curlError = null;

    /**
     * Method called by the new instance to run the job.
     *
     * @return mixed
     */
    public function run()
    {
        $ch = curl_init();

        $query = '';
        if (!empty($this->parameters)) {
            $query = '?' . http_build_query ($this->parameters);
        }
        $this->query = $this->url . $query;
        if ($this->method == "GET") {
            curl_setopt($ch, CURLOPT_URL, $this->url . $query);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        foreach ($this->options as $key => $value)
        {
            curl_setopt($ch, $key, $value);
        }

        $this->response = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        $this->curlError = curl_error($ch);
    }

    /**
     * Method called by the original instance when the job has ran.
     *
     * @return mixed
     */
    public function end(JobData $data)
    {
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the method to use (GET or POST)
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * St the url to call
     *
     * @param null $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the options to apply to the curl.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set the parameters to send. If get will add to the url, if post will be put into the post
     *
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Get the string response of the server.
     *
     * @return null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get hte curl information.
     *
     * @return array
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @return array
     */
    public function getCurlError()
    {
        return $this->curlError;
    }
}