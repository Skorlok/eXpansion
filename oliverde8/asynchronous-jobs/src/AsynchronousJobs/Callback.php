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

namespace oliverde8\AsynchronousJobs;


trait Callback
{
    protected $__callback = null;

    public function setCallback($newCallback = null)
    {
        $this->__callback = $newCallback;

        return $this;
    }

    public function getCallback() {
        return $this->__callback;
    }

    public function callCallback($jobData)
    {
        call_user_func($this->__callback, $this, $jobData);
    }

    public function end(JobData $jobData) {
        $this->callCallback($jobData);
    }
}