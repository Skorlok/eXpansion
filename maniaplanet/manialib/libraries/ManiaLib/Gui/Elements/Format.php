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

namespace ManiaLib\Gui\Elements;

class Format extends \ManiaLib\Gui\Element
{
	const AvatarButtonNormal = 'AvatarButtonNormal';
	const StyleTextScriptEditor = 'StyleTextScriptEditor';
	const StyleValueYellowSmall = 'StyleValueYellowSmall';
	const TextButtonBig = 'TextButtonBig';
	const TextButtonMedium = 'TextButtonMedium';
	const TextButtonNav = 'TextButtonNav';
	const TextButtonNavBack = 'TextButtonNavBack';
	const TextButtonSmall = 'TextButtonSmall';
	const TextCardInfoSmall = 'TextCardInfoSmall';
	const TextCardMedium = 'TextCardMedium';
	const TextCardRaceRank = 'TextCardRaceRank';
	const TextCardScores2 = 'TextCardScores2';
	const TextCardSmallScores2 = 'TextCardSmallScores2';
	const TextCardSmallScores2Rank = 'TextCardSmallScores2Rank';
	const TextChallengeNameMedal = 'TextChallengeNameMedal';
	const TextChallengeNameMedalNone = 'TextChallengeNameMedalNone';
	const TextChallengeNameMedium = 'TextChallengeNameMedium';
	const TextChallengeNameSmall = 'TextChallengeNameSmall';
	const TextCongratsBig = 'TextCongratsBig';
	const TextCredits = 'TextCredits';
	const TextCreditsTitle = 'TextCreditsTitle';
	const TextInfoMedium = 'TextInfoMedium';
	const TextInfoSmall = 'TextInfoSmall';
	const TextPlayerCardName = 'TextPlayerCardName';
	const TextPlayerCardScore = 'TextPlayerCardScore';
	const TextRaceChat = 'TextRaceChat';
	const TextRaceChrono = 'TextRaceChrono';
	const TextRaceChronoError = 'TextRaceChronoError';
	const TextRaceChronoOfficial = 'TextRaceChronoOfficial';
	const TextRaceChronoWarning = 'TextRaceChronoWarning';
	const TextRaceMessage = 'TextRaceMessage';
	const TextRaceMessageBig = 'TextRaceMessageBig';
	const TextRaceStaticSmall = 'TextRaceStaticSmall';
	const TextRaceValueSmall = 'TextRaceValueSmall';
	const TextRankingsBig = 'TextRankingsBig';
	const TextSPScoreBig = 'TextSPScoreBig';
	const TextSPScoreMedium = 'TextSPScoreMedium';
	const TextSPScoreSmall = 'TextSPScoreSmall';
	const TextStaticMedium = 'TextStaticMedium';
	const TextStaticSmall = 'TextStaticSmall';
	const TextStaticVerySmall = 'TextStaticVerySmall';
	const TextSubTitle1 = 'TextSubTitle1';
	const TextSubTitle2 = 'TextSubTitle2';
	const TextTips = 'TextTips';
	const TextTitle1 = 'TextTitle1';
	const TextTitle2 = 'TextTitle2';
	const TextTitle2Blink = 'TextTitle2Blink';
	const TextTitle3 = 'TextTitle3';
	const TextTitle3Header = 'TextTitle3Header';
	const TextTitleError = 'TextTitleError';
	const TextValueBig = 'TextValueBig';
	const TextValueMedium = 'TextValueMedium';
	const TextValueSmall = 'TextValueSmall';
	const TrackListItem = 'TrackListItem';
	const TrackListLine = 'TrackListLine';
	const TrackerText = 'TrackerText';
	const TrackerTextBig = 'TrackerTextBig';

	protected $xmlTagName = 'format';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
	protected $style = null;
	protected $subStyle = null;
	protected $textSize;
	protected $textColor;
	protected $textEmboss;
	protected $textPrefix;

	function __construct()
	{
		
	}

	/**
	 * Sets the text size
	 * @param int
	 */
	function setTextSize($textsize)
	{
		$this->textSize = $textsize;
	}

	/**
	 * Sets the text color
	 * @param string 3-digit RGB hexadecimal value
	 */
	function setTextColor($textcolor)
	{
		$this->textColor = $textcolor;
	}
	
	/**
	 * Sets a shadow on the entire text.
	 * Like "$s" modifier, except it is forced and further $s in the label won't cancel it.
	 * @param bool
	 */
	function setTextEmboss($emboss = true)
	{
		$this->textEmboss = $emboss;
	}
	
	/**
	 * Set a prefix to the text
	 * @param string $prefix
	 */
	function setTextPrefix($prefix)
	{
		$this->textPrefix = $prefix;
	}

	/**
	 * Returns the text size
	 * @return int
	 */
	function getTextSize()
	{
		return $this->textSize;
	}

	/**
	 * Returns the text color
	 * @return string 3-digit RGB hexadecimal value
	 */
	function getTextColor()
	{
		return $this->textColor;
	}
	
	/**
	 * Returns if the emboss
	 * @return bool
	 */
	function getTextEmboss()
	{
		return $this->textEmboss;
	}
	
	/**
	 * Returns the text prefix
	 * @return string
	 */
	function getTextPrefix()
	{
		return $this->textPrefix;
	}

	protected function postFilter()
	{
		if($this->textSize !== null)
			$this->xml->setAttribute('textsize', $this->textSize);
		if($this->textColor !== null)
			$this->xml->setAttribute('textcolor', $this->textColor);
		if($this->textEmboss !== null)
			$this->xml->setAttribute('textemboss', $this->textEmboss);
		if($this->textPrefix !== null)
			$this->xml->setAttribute('textprefix', $this->textPrefix);
	}

}

?>