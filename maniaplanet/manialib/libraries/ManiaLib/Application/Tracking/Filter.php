<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 *
 * @see         http://code.google.com/p/manialib/
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Application\Tracking;

class Filter implements \ManiaLib\Application\Filterable
{

	/**
	 * @var \ManiaLib\Application\Tracking\GoogleAnalytics
	 */
	protected $tracker;
	protected $account;
	protected $domainName;
	protected $cookieNameSuffix;

	function __construct($trackingAccount = null, $cookieNameSuffix=null)
	{
		if($trackingAccount)
		{
			$this->account = $trackingAccount;
		}
		$this->cookieNameSuffix = $cookieNameSuffix;
	}

	function preFilter()
	{
		if(!$this->account)
		{
			$config = Config::getInstance();
			$this->account = $config->account;
			$this->domainName = $config->domainName;
		}

		if($this->account)
		{
			$this->tracker = new GoogleAnalytics($this->account, $this->cookieNameSuffix, $this->domainName);
			$this->tracker->loadCookie();
		}
	}

	function postFilter()
	{
		$response = \ManiaLib\Application\Response::getInstance();
		if($this->account)
		{
			$trackingURL = $response->get('trackingURL');
			if ($trackingURL)
			{
				$trackingURL[] = $this->tracker->getTrackingURL();
				$response->trackingURL = $trackingURL;
			}
			else
			{
				$response->trackingURL = array($this->tracker->getTrackingURL());
			}
		}
		$response->registerView('\ManiaLib\Application\Tracking\View');
	}

}

?>