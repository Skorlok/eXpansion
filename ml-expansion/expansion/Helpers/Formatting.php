<?php

namespace ManiaLivePlugins\eXpansion\Helpers;

/*
 * Info: pattern (?<!\$)((?:\$\$)*) match an even number of $
 */
abstract class Formatting
{
	/**
	 * Removes each single character code in $codes string
	 * Adding l, h or p will also strip links
	 * Adding an hexadecimal char will also strip colors
	 */
	public static function stripCodes($string, $codes)
	{
		if (preg_match('/[hlp]/iu', $codes))
			$string = self::stripLinks($string);
		if (preg_match('/[0-9a-f]/iu', $codes))
			$string = self::stripColors($string);
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[' . $codes . ']/iu', '$1', $string);
	}

	/**
	 * Removes wide, bold and shadowed
	 */
	public static function stripWideFonts($string)
	{
		return self::stripCodes($string, 'wos');
	}

	/**
	 * Removes links
	 */
	public static function stripLinks($string)
	{
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[hlp](?:\[.*?\]|\[.*?$)?(.*?)(?:\$[hlp]|(\$z)|$)/iu', '$1$2$3', $string);
	}

	/**
	 * Removes colors
	 */
	public static function stripColors($string)
	{
		return preg_replace('/(?<!\$)((?:\$\$)*)\$(?:g|[0-9a-f][^\$]{0,2})/iu', '$1', self::completeColorCode($string));
	}

	/**
	 * Removes all styles
	 */
	public static function stripStyles($string)
	{
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		$string = self::stripLinks($string);
		$string = self::stripColors($string);
		return $string;
	}

	/**
	 * Fix color codes by adding missing 0, for example $f becomes $f0 and $f1 becomes $f10, as ManiaPlanet does ingame
	 * fixes stripColors and toHtml by ensuring that each color code is 3 characters long, so that it can be properly removed
	 */
	public static function completeColorCode($nick)
	{
		$valid = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "A", "B", "C", "D", "E", "F");
		$ni = str_split($nick);
		$newNick = "";

		$check = 0;
		$sc = false;
		foreach ($ni as $n) {
			if ($sc) {
				if ($check === 2) {
					if (!in_array($n, $valid)) {
						$newNick .= "0";
					}
					$check = 0;
					$sc = false;
				} else if ($check === 1) {
					if (in_array($n, $valid)) {
						$check++;
					} else {
						$newNick .= "00";
						$check = 0;
						$sc = false;
					}
				} else if ($check === 0 && in_array($n, $valid)) {
					$check++;
				} else {
					$sc = false;
				}
			}
			if ($n == "$") {
				$sc = true;
			}
			$newNick .= $n;
		}
		return $newNick;
	}

	/**
	 * Fixes $<, $>, $[ and $] by adding missing closing tags at the end of the string, so it don't break the ingame chat
	 */
	public static function fixTags($nick)
	{
		$nbTag = array(0, 0);
		$ni = str_split($nick);
		$newNick = "";

		foreach ($ni as $p => $n) {
			if ($p > 0 && $n == '<' && $ni[$p - 1] == '$') {
				$nbTag[0]++;
			} else if ($p > 0 && $n == '>' && $ni[$p - 1] == '$') {
				$nbTag[0]--;
			} else if ($p > 1 && $n == '[' && ($ni[$p - 1] == 'l' || $ni[$p - 1] == 'L' || $ni[$p - 1] == 'p' || $ni[$p - 1] == 'P' || $ni[$p - 1] == 'h' || $ni[$p - 1] == 'H') && $ni[$p - 2] == '$') {
				$nbTag[1]++;
			} else if ($n == ']' && $nbTag[1] > 0) {
				$nbTag[1]--;
			}
		}
		if ($nbTag[0] < 0) {
			$newNick .= str_repeat('<', -$nbTag[0]);
		}
		if ($nbTag[1] < 0) {
			$newNick .= str_repeat('[', -$nbTag[1]);
		}
		$newNick .= $nick;
		// Remove last $ if it's alone at the end of the string, to avoid problems ingame
		if ($newNick[strlen($newNick) - 1] == '$') {
			$newNick = substr($newNick, 0, -1);
		}
		if ($nbTag[1] > 0) {
			$newNick .= str_repeat('$>', $nbTag[1]);
		}
		if ($nbTag[0] > 0) {
			$newNick .= str_repeat(']', $nbTag[0]);
		}
		return $newNick;
	}

	/**
	 * Parses a string with ManiaPlanet formatting codes and returns a string with nickname in html format
	 * THIS CODE IS STOLEN FROM AdminServ, Thanks to Kev717 for his work
	 */
	public static function toHtml($nick)
	{
		$nick = self::completeColorCode($nick);
		$m_css = array(
			'h' => 'tmnick_internallink',
			'p' => 'tmnick_internallink_withlogin',
			'l' => 'tmnick_externallink',
			'o' => 'tmnick_bold',
			'g' => 'tmnick_defaultcolor',
			't' => 'tmnick_upper',
			'w' => 'tmnick_wide',
			'm' => 'tmnick_normal',
			'n' => 'tmnick_short',
			'i' => 'tmnick_italic',
			's' => 'tmnick_shadowed',
			'z' => 'tmnick_raz'
		);

		$linkProtocol = strtolower("maniaplanet");

		$strHtml = '<span class="tmnick_global">';
		$lastLink = false;
		$lastLinkTag = false;
		$stack = self::parse($nick);
		foreach ($stack as $k => $ss) {
			$lstClass = $lstStyle = array();
			$prefix = $suffix = '';
			$preContent = $postContent = '';

			foreach ($ss['tagsOpen'] as $tag => $open) {
				if ((is_string($open) and !empty($open)) or (is_bool($open) and $open)) {
					if (($tag == 'h' or $tag == 'p' or $tag == 'l') and $lastLink !== $open) {
						$scheme = parse_url($open, PHP_URL_SCHEME);
						if (is_null($scheme)) {
							if ($tag == 'l') {
								$open = 'http://' . $open;
							} else if ($tag == 'h') {
								$open = $linkProtocol . ':///:' . $open;
							} else if ($tag == 'p') {
								$open = $linkProtocol . ':///:' . $open . '?playerlogin=&lang=&nickname=&path=';
							}
						}

						$prefix .= '<a href="' . $open . '" target="_blank" class="' . $m_css[$tag] . '">';
						$lastLinkTag = $tag;
						$lastLink = $open;
					} else if ($tag == 'color') {
						$lstStyle[] = 'color:' . $open;
					} else {
						$lstClass[] = $m_css[$tag];
					}
				} else {
					if ($tag == $lastLinkTag and $lastLink !== $open) {
						$prefix .= '</a>';
						$lastLink = false;
					}
				}
			}

			$strHtml .= $prefix;
			if (!empty($ss['content'])) {
				if ($lstClass) {
					$strHtml .= '<span class="{$class$}" style="{$style$}">';
				} else {
					$strHtml .= '<span style="{$style$}">';
				}
				$strHtml .= $preContent . '{$content$}' . $postContent;
				$strHtml .= '</span>';
			}
			$strHtml .= $suffix;

			$strHtml = str_replace('{$content$}', $ss['content'], $strHtml);
			$strHtml = str_replace('{$class$}', implode(' ', $lstClass), $strHtml);
			$strHtml = str_replace('{$style$}', implode(';', $lstStyle), $strHtml);
		}

		$tags = array('h', 'p', 'l');
		foreach ($tags as $k => $tag) {
			if (isset($ss) and $ss['tagsOpen'][$tag]) {
				$strHtml .= '</a>';
			}
		}

		$strHtml .= '</span>';

		return $strHtml;
	}

	/**
	 * Parses a string with ManiaPlanet formatting codes and returns an array of content with their styles and links
	 * THIS CODE IS STOLEN FROM AdminServ, Thanks to Kev717 for his work
	 */
	private static function parse($nick, $defaultColor = '#fff')
	{

		$m_pattern = '/\\$\\$|\\$[0-9a-fA-F][0-9a-zA-Z]{2}|\\$[twmgonishplzTWMGONISHLZ]/';
		$m_patternLink = '/(?:\\[(?P<url>[^\\[\\]]*)\\]){0,1}(?P<label>.+){0,1}/';
		$m_lstTags = array('h', 'p', 'l', 'o', 'g', 't', 'w', 'm', 'n', 'i', 's', 'z', 'color');

		if (!is_string($nick) or empty($nick)) {
			return array();
		}

		$nb = preg_match_all($m_pattern, $nick, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
		$matches = $matches[0];

		$strContent = '';
		$lastPos = 0;

		$tagsOpen = array(
			'h' => false,
			'p' => false,
			'l' => false,
			'o' => false,
			'g' => false,
			't' => false,
			'w' => false,
			'm' => false,
			'n' => false,
			'i' => false,
			's' => false,
			'color' => $defaultColor
		);

		$stack = array();
		foreach ($matches as $num => $matche) {
			list($code, $pos) = $matche;

			$tag = strtolower(substr($code, 1));
			$param = null;
			if (strlen($tag) == 3) {
				// color
				$param = $tag;
				$tag = 'color';
			}

			if (!in_array($tag, $m_lstTags)) {
				continue;
			}

			$strContent = substr($nick, $lastPos, $pos - $lastPos);
			if (!empty($strContent)) {
				array_push($stack, array('content' => $strContent, 'tagsOpen' => $tagsOpen));
			}

			switch ($tag) {
				// raz (close)
				case 'z':
					$tagsOpen = array_merge(
						$tagsOpen,
						array(
							'o' => false,
							'g' => false,
							't' => false,
							'w' => false,
							'm' => false,
							'n' => false,
							'i' => false,
							's' => false,
							'color' => $defaultColor
						)
					);
					break;

				// defaultcolor (close)
				case 'g':
					$tagsOpen['color'] = $defaultColor;
					break;

				// internal link (open)
				case 'h':
					// internal link with login (open)
				case 'p':
					// external link (open)
				case 'l':
					if (!$tagsOpen[$tag]) {
						// open
						$tagsOpen[$tag] = true;
					} else {
						// close
						$strLink = '';
						$i = count($stack) - 1;
						while ($i >= 0 and $stack[$i]['tagsOpen'][$tag]) {
							$strLink = $stack[$i]['content'] . $strLink;
							$i--;
						}

						$url = null;
						$nb = preg_match($m_patternLink, $strLink, $regs, PREG_OFFSET_CAPTURE);

						if (!empty($regs['label'][0])) {
							if (empty($regs['url'][0])) {
								$url = $regs['label'][0];
							} else {
								$url = $regs['url'][0];
							}
						}

						$nb = count($stack) - 1;
						$lastMax = strlen($strLink);
						for ($j = $nb; $j >= ($i + 1); $j--) {
							if (!is_null($url)) {
								$subLabel = $stack[$j]['content'];
								$max = max($lastMax - strlen($subLabel), $regs['label'][1]);
								$subLabel = substr($strLink, $max, $lastMax - $max);
								$lastMax = $max;
								$stack[$j]['content'] = $subLabel;

								$stack[$j]['tagsOpen'][$tag] = $url;
							} else {
								// poubelle
								unset($stack[$j]);
							}
						}

						$tagsOpen[$tag] = false;
					}
					break;

				// shadow (open)
				case 's':
					// upper
				case 't':
					// bold
				case 'o':
					// italic
				case 'i':
					if (!$tagsOpen[$tag]) {
						// open
						$tagsOpen[$tag] = true;
					} else {
						// close
						$tagsOpen[$tag] = false;
					}
					break;

				// wide (open)
				case 'w':
					// normal (open)
				case 'm':
					// short (open)
				case 'n':
					if (!$tagsOpen[$tag]) {
						// open
						$tagsOpen['w'] = false;
						$tagsOpen['m'] = false;
						$tagsOpen['n'] = false;
						$tagsOpen[$tag] = true;
					} else {
						// close
						$tagsOpen[$tag] = false;
					}
					break;

				case 'color':
					// color (open)
					$tagsOpen[$tag] = str_replace('$', '#', $code);
					break;

				default:
					$stack[(count($stack) - 1)]['content'] .= $code;
					break;
			}

			foreach ($stack as $s => $st) {
				if (empty($st['content'])) {
					unset($stack[$s]);
				}
			}

			$lastPos = $pos + strlen($matche[0]);
		}

		$strContent = substr($nick, $lastPos);
		if (!empty($strContent)) {
			array_push($stack, array('content' => $strContent, 'tagsOpen' => $tagsOpen));
		}

		$tags = array('h', 'p', 'l');
		foreach ($tags as $k => $tag) {
			if ($tagsOpen[$tag]) {
				// close
				$strLink = '';
				$i = count($stack) - 1;
				while ($i >= 0 and $stack[$i]['tagsOpen'][$tag]) {
					$strLink = $stack[$i]['content'] . $strLink;
					$i--;
				}

				$url = null;
				$nb = preg_match($m_patternLink, $strLink, $regs, PREG_OFFSET_CAPTURE);

				if (!empty($regs['label'][0])) {
					if (empty($regs['url'][0])) {
						$url = $regs['label'][0];
					} else {
						$url = $regs['url'][0];
					}
				}

				$nb = count($stack) - 1;
				$lastMax = strlen($strLink);
				for ($j = $nb; $j >= ($i + 1); $j--) {
					if (!is_null($url)) {
						$subLabel = $stack[$j]['content'];
						$max = max($lastMax - strlen($subLabel), $regs['label'][1]);
						$subLabel = substr($strLink, $max, $lastMax - $max);
						$lastMax = $max;
						$stack[$j]['content'] = $subLabel;

						$stack[$j]['tagsOpen'][$tag] = $url;
					} else {
						// poubelle
						unset($stack[$j]);
					}
				}

				$tagsOpen[$tag] = false;
			}
		}

		return $stack;
	}
}
