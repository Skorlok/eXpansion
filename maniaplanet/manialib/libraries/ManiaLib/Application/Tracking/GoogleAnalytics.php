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

use ManiaLib\Utils\Arrays;

/**
 * Google Analytics tracking in Manialinks
 */
class GoogleAnalytics
{

	const GA_TRACKING_URL = 'http://www.google-analytics.com/__utm.gif';

	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $trackingQuad;

	/**
	 * Urchin version
	 */
	public $utmwv = '4.8.6';

	/**
	 * Hostname
	 */
	public $utmhn;

	/**
	 * Charsert
	 */
	public $utmcs = 'UTF-8';

	/**
	 * Screen resolution
	 */
	public $utmsr = '-';

	/**
	 * Color-depth
	 */
	public $utmsc = '-';

	/**
	 * Language
	 */
	public $utmul;

	/**
	 * Java enabled
	 */
	public $utmje = 0;

	/**
	 * Flash version
	 */
	public $utmfl = 0;

	/**
	 * Random
	 */
	public $utmhid;

	/**
	 * Referer
	 */
	public $utmr = 0;

	/**
	 * Route
	 */
	public $utmp;

	/**
	 * Google Analytics account
	 */
	public $utmac;

	/**
	 * Random
	 */
	public $utmn;

	/**
	 * @see http://stackoverflow.com/questions/6236895/what-is-utmu-parameter-in-google-analytics-utm-gif-request
	 */
	public $utmu = null;

	/**
	 * Carriage return (?)
	 */
	public $utmcr = 1;

	/**
	 * Document title
	 */
	public $utmdt;

	/**
	 * Cookie
	 */
	public $utmcc;

	/**
	 * Cookie var
	 */
	public $__utma;

	/**
	 * Cookie var
	 */
	public $__utmb;

	/**
	 * Cookie var
	 */
	public $__utmc;

	/**
	 * Cookie var
	 */
	public $__utmz;

	/**
	 * Event tracking
	 */
	public $utme;
	protected $domainHash;
	protected $visitorId;
	protected $cookieNameSuffix;

	function __construct($account, $cookieNameSuffix = null, $domainName = null)
	{
		$this->utmac = $account;
		$this->cookieNameSuffix = $cookieNameSuffix;
		$this->utmhid = rand(1000000000, 9999999999);
		$this->utmn = rand(1000000000, 9999999999);
		$this->utmul = Arrays::get($_SERVER, 'HTTP_ACCEPT_LANGUAGE');
		$this->utmr = Arrays::get($_SERVER, 'HTTP_REFERER');
		$this->utmhn = $domainName ? : Arrays::get($_SERVER, 'HTTP_HOST');
		$this->utmp = Arrays::get($_SERVER, 'REQUEST_URI');
	}

	/**
	 * Loads cookie information
	 * @see http://services.google.com/analytics/breeze/en/ga_cookies/index.html
	 */
	function loadCookie()
	{
		$domainHash = $this->getDomainHash();
		$cookieRandom = rand(1000000000, 2147483647); //number under 2147483647

		$cookieUtma = '__utma'.$this->cookieNameSuffix;
		$cookieUtmb = '__utmb'.$this->cookieNameSuffix;
		$cookieUtmc = '__utmc'.$this->cookieNameSuffix;
		$cookieUtmz = '__utmz'.$this->cookieNameSuffix;

		$utma = Arrays::get($_COOKIE, $cookieUtma, '');
		$utma = $utma ? explode('.', $utma) : array();

		$utmb = Arrays::get($_COOKIE, $cookieUtmb, '');
		$utmb = $utmb ? explode('.', $utmb) : array();

		$utmc = Arrays::get($_COOKIE, $cookieUtmc, '');
		$utmc = $utmc ? explode('.', $utmc) : array();

		$utmz = array();

		$utma[0] = $domainHash; // Domain hash
		$utma[1] = Arrays::get($utma, 1, $cookieRandom); // Random unique ID
		$utma[2] = Arrays::get($utma, 2, time()); // Time of initial visit
		$utma[3] = Arrays::get($utma, 3, time()); // Begining of previous session
		$utma[4] = Arrays::get($utma, 4, time()); // Begining of current session
		$utma[5] = Arrays::get($utma, 5, 0); // Session counter

		if(!$utmb || !$utmc)
		{
			// New session has started
			$utma[5]++;
			$utma[3] = $utma[4];
			$utma[4] = time();
		}

		$utmb[0] = $domainHash;

		$utmc[0] = $domainHash;

		$utmz[0] = $domainHash; // Domain hash
		$utmz[1] = time(); // Timestamp
		$utmz[2] = $utma[5]; // Session number
		$utmz[3] = 1; // Campaign number
		$utmz[4] = // Campaign information
			'utmcsr=(direct)|'.//utm_source
			'utmccn=(direct)|'.//utm_campaign
			'utmcmd=(none)'; //utm_medium'

		$__utma = implode('.', $utma);
		$__utmb = implode('.', $utmb);
		$__utmc = implode('.', $utmc);
		$__utmz = implode('.', $utmz);

		setcookie($cookieUtma, $__utma, strtotime('+2 years'));
		setcookie($cookieUtmb, $__utmb, strtotime('+30 minutes'));
		setcookie($cookieUtmc, $__utmb, 0);
		setcookie($cookieUtmz, $__utmz, strtotime('+6 months'));

		$this->__utma = $__utma.';';
		$this->__utmb = $__utmb.';';
		$this->__utmc = $__utmc.';';
		$this->__utmz = $__utmz.';';
	}

	/**
	 * Computes the tracking URL and returns it. Its is a 1*1 gif image that
	 * should be called by the client.
	 * @return string
	 */
	function getTrackingURL()
	{
		$params = array(
			'utmwv' => $this->utmwv,
			'utmhn' => $this->utmhn,
			'utmcs' => $this->utmcs,
			'utmsr' => $this->utmsr,
			'utmsc' => $this->utmsc,
			'utmul' => $this->utmul,
			'utmje' => $this->utmje,
			'utmfl' => $this->utmfl,
			'utmhid' => $this->utmhid,
			'utmr' => $this->utmr,
			'utmp' => $this->utmp,
			'utmac' => $this->utmac,
			'utmn' => $this->utmn,
			'utmu' => $this->utmu,
			'utmcr' => $this->utmcr,
			'utmdt' => $this->utmdt,
			'utmcc' =>
			'__utma='.$this->__utma.'+'.
//					'__utmb='.$this->__utmb.'+'.
//					'__utmc='.$this->__utmc.'+'.
			'__utmz='.$this->__utmz
		);

		return self::GA_TRACKING_URL.'?'.http_build_query($params);
	}

	// FIXME ManiaLib If anyone knows how to encode data in Google Analytics's utme param' we'd be glad to hear :)
	/**
	 * Experimentation about using Event Tracking in manialinks.
	 * Because I don't know the exact algorithm to encode data in utme, you should
	 * only use alphanumeric chars in the parameters for now.
	 * To quote official doc: "Value is encoded. Used for events and custom variables."
	 *
	 * @see https://developers.google.com/analytics/resources/articles/gaTrackingTroubleshooting#gifParameters
	 * @beta
	 */
	function getEventTrackingURL($category, $action, $label, $value = null)
	{
		$this->utme = sprintf('5(%s*%s*%s)', $category, $action, $label);
		if($value !== null)
		{
			$this->utme .= sprintf('(%d)', $value);
		}

		$params = array(
			'utmwv' => $this->utmwv,
			//'utms' => null ?
			'utmn' => $this->utmn,
			'utmhn' => $this->utmhn,
			'utmt' => 'event',
			'utme' => $this->utme,
			'utmcs' => $this->utmcs,
			'utmsr' => $this->utmsr,
			//'utmvp' => $this->utmvp, view port resolution
			'utmsc' => $this->utmsc,
			'utmul' => $this->utmul,
			'utmje' => $this->utmje,
			'utmfl' => $this->utmfl,
			'utmdt' => $this->utmdt,
			'utmhid' => $this->utmhid,
			'utmr' => $this->utmr,
			'utmp' => $this->utmp,
			'utmac' => $this->utmac,
			'utmcc' =>
			'__utma='.$this->__utma.'+'.
//					'__utmb='.$this->__utmb.'+'.
//					'__utmc='.$this->__utmc.'+'.
			'__utmz='.$this->__utmz,
		);

		return self::GA_TRACKING_URL.'?'.http_build_query($params);
	}

	protected function getDomainHash()
	{
		if(!$this->domainHash)
		{
			$domain = $this->utmhn;
			if(!$domain)
			{
				return 1;
			}
			$h = 0;
			$g = 0;
			$length = strlen($domain) - 1;
			for($i = $length; $i >= 0; $i--)
			{
				$c = (int) (ord($domain[$i]));
				$h = (($h << 6) & 0xfffffff) + $c + ($c << 14);
				$g = ($h & 0xfe00000);
				if($g != 0) $h = ($h ^ ($g >> 21));
			}
			$this->domainHash = $h;
		}
		return $this->domainHash;
	}

}

?>