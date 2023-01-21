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

namespace ManiaLib\ManiaScript;

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Bgs1InRace;

/**
 * Application filter to force the client to up-to-date
 */
class VersionCheck implements \ManiaLib\Application\Filterable
{

	static $errorPageCallback = array('\ManiaLib\ManiaScript\VersionCheck', 'displayErrorPage');

	static function version2timestamp($version)
	{
		$v = $version;
		return mktime(substr($v, 14, 2), substr($v, 11, 2), 0, substr($v, 5, 2), substr($v, 8, 2), substr($v, 0, 4));
	}

	static function getVersion()
	{
		if(\ManiaLib\Application\Filters\UserAgentCheck::isManiaplanet())
		{
			$userAgent = \ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_USER_AGENT');
			// FIXME use the rv: xxxxxxxx pattern instead, and introduce "version" and "revision"
			// User Agent format: ManiaPlanet/3.2.0 (Windows; rv: 2012-07-25_18_15; context: browser)

			$regexp = '/ \(([0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}_[0-9]{2})\)/u';
			if(preg_match($regexp, $userAgent, $matches) == 1)
			{
				return $matches[1];
			}
		}
	}

	static function displayedInStation()
	{
		$userAgent = \ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_USER_AGENT');
		return (strstr($userAgent, 'context: tablet') !== false);
	}

	static function displayErrorPage()
	{
		Manialink::load();
		{
			Manialink::beginFrame(-70, 35, 0.1);
			{

				$ui = new Bgs1InRace(143, 63);
				$ui->setSubStyle(Bgs1InRace::Shadow);
				$ui->save();

				$ui = new \ManiaLib\Gui\Elements\Quad(140, 60);
				$ui->setPosition(1.5, -1.5, 0.1);
				$ui->setBgcolor('fffe');
				$ui->save();

				$ui = new Label(110);
				$ui->setPosition(6, -6, 0.2);
				$ui->setStyle(Label::TextButtonMedium);
				$ui->setText(''.'Please update maniaplanet');
				$ui->save();

				$ui = new Label(131);
				$ui->setPosition(6, -13, 0.2);
				$ui->enableAutonewline();
				$ui->setStyle(Label::TextTips);
				$ui->setText(
					'You cannot display this content because your Maniaplanet '.
					'version is not up-to-date. Please update Maniaplanet to '.
					'the latest version. While it should be automatic, you can '.
					'find help and more information on the Maniaplanet Wiki: ');
				$ui->save();

				$ui = new Label(131);
				$ui->setAlign('center', 'center');
				$ui->setPosition(71.5, -35, 0.2);
				$ui->setStyle(Label::TextChallengeNameMedalNone);
				$ui->setUrl('http://wiki.maniaplanet.com/en/Changelog');
				$ui->setText('wiki.maniaplanet.com/en/Changelog');
				$ui->save();

				$ui = new Label(131);
				$ui->setValign('bottom');
				$ui->setPosition(5, -57, 0.2);
				$ui->enableAutonewline();
				$ui->setStyle(Label::TextTips);
				$ui->setText(
					'Thank you for your understanding,'."\n".
					'Nadeo Team');
				$ui->save();
			}
			Manialink::endFrame();
		}
		Manialink::render();
	}

	function preFilter()
	{
		$version = self::getVersion();
		$minVersion = Config::getInstance()->minVersion;
		if($version !== null && $minVersion !== null)
		{
			$versionTimestamp = self::version2timestamp($version);
			$minVersionTimestamp = self::version2timestamp($minVersion);
			if($versionTimestamp < $minVersionTimestamp)
			{
				\ManiaLib\Utils\Logger::user('Maniaplanet not up-to-date: '.$version);
				call_user_func(self::$errorPageCallback);
				exit;
			}
		}
	}

	function postFilter()
	{
		
	}

}

?>