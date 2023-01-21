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

namespace ManiaLib\WebServices;

/**
 * Automatic authentication using ManiaConnect. You need to specify your API
 * username/password in the config file using the following:
 * 
 * webservices.username = api_username
 * webservices.password = api_password
 * 
 * @see http://code.google.com/p/maniaplanet-ws-sdk/
 */
class ManiaConnectFilter extends \ManiaLib\Application\AdvancedFilter
{
	const SESS_AUTH_KEY = 'manialib-oauth2';
	const SESS_NONCE_KEY = 'manialib-nonce';
	const RETRY = 3;

	/**
	 * @var \Maniaplanet\WebServices\ManiaConnect\Player
	 */
	protected $oauth2;

	static function logout($redirectUri = null)
	{
		$request = \ManiaLib\Application\Request::getInstance();
		$maniaplanet = new \Maniaplanet\WebServices\ManiaConnect\Player();
		$maniaplanet->logout();

		$logoutURL = $maniaplanet->getLogoutURL($redirectUri ? : $request->createLink('/'));

		$request->redirectAbsolute($logoutURL);
	}

	function preFilter()
	{
		if($this->session->get(static::SESS_AUTH_KEY))
		{
			return;
		}

		if($this->request->get('code'))
		{
			if($this->request->get(self::SESS_NONCE_KEY))
			{
				if($this->request->get(self::SESS_NONCE_KEY) != $this->session->get(self::SESS_NONCE_KEY))
				{
					$this->request->delete('code');
					$this->request->delete(self::SESS_NONCE_KEY);
					$this->request->redirect('');
					return;
				}
			}
			$tries = $this->session->get(static::SESS_AUTH_KEY.'-tries', 0);
			$tries++;
			if($tries > static::RETRY)
			{
				$this->session->delete(static::SESS_AUTH_KEY.'-tries');
				throw new \ManiaLib\Application\UserException('Authentication failed');
			}
			$this->session->set(static::SESS_AUTH_KEY.'-tries', $tries);
		}

		$config = Config::getInstance();
		$username = $config->username;
		$password = $config->password;

		$this->oauth2 = new \Maniaplanet\WebServices\ManiaConnect\Player($username, $password);
		try
		{
			$player = $this->oauth2->getPlayer();
		}
		catch(\Maniaplanet\WebServices\Exception $e)
		{
			\ManiaLib\Utils\Logger::info(sprintf("MPWS Exception: HTTP %d %s - %s %d",
					$e->getHTTPStatusCode(), $e->getHTTPStatusMessage(), $e->getMessage(),
					$e->getCode()));

			$this->response->errorManialink = $this->request->createLink();
			$this->response->errorButtonMessage = 'Try again';

			throw new \ManiaLib\Application\SilentUserException('Authentication failed', 0, $e);
		}

		if(!$player)
		{
			$nonce = md5(uniqid());
			$this->session->set(self::SESS_NONCE_KEY, $nonce);
			$this->request->set(self::SESS_NONCE_KEY, $nonce);
			$loginURL = $this->oauth2->getLoginURL(Config::getInstance()->scope,
				$this->request->createLink('.'));
			$this->request->redirectAbsolute($loginURL);
			return;
		}

		$this->request->delete('code');
		$this->request->delete(self::SESS_NONCE_KEY);

		$this->session->set('login', $player->login);
		$this->session->set('nickname', $player->nickname);
		$this->session->set('path', $player->path);
		$this->session->set(static::SESS_AUTH_KEY, 1);

		$this->session->delete(static::SESS_AUTH_KEY.'-tries');
	}

}

?>