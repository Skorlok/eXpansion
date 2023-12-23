<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Controls;

class Recorditem extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    public function __construct($index, $highlite, $moreInfo = false)
    {
        $colors = \ManiaLivePlugins\eXpansion\Core\ColorParser::getInstance();

        $item = new \ManiaLive\Gui\Elements\Xml();
        $item->setContent('<quad id="RecBgBlink_' . $index . '" posn="-1.5 1.5 0" sizen="42 3.5" halign="left" valign="top" bgcolor="0f0" opacity="0.25" hidden="1"/>');
        $this->addComponent($item);

        $item = new \ManiaLive\Gui\Elements\Xml();
        $item->setContent('<quad id="RecBg_' . $index . '" posn="-1.5 1.5 1.0E-5" sizen="42 3.5" halign="left" valign="top" bgcolor="000" opacity="0.55" hidden="1"/>');
        $this->addComponent($item);

        $item = new \ManiaLive\Gui\Elements\Xml();
        $item->setContent('<label id="RecRank_' . $index . '" posn="3.25 0 2.0E-5" sizen="4 4" halign="right" valign="center" style="TextRaceChat" textsize="1.5" textcolor="' . str_replace('$', "", $colors->getColor("#rank#")) . '" textemboss="1"/>');
        $this->addComponent($item);

        $item = new \ManiaLive\Gui\Elements\Xml();
        $item->setContent('<label id="RecTime_' . $index . '" posn="3.5 0 3.0E-5" sizen="11 5" halign="left" valign="center" style="TextRaceChat" scriptevents="1" class="nickLabel" textsize="1" textcolor="' . str_replace('$', "", $colors->getColor("#time#")) . '"/>');
        $this->addComponent($item);

        $item = new \ManiaLive\Gui\Elements\Xml();
        $item->setContent('<label id="RecNick_' . $index . '" posn="15.5 0 4.0E-5" sizen="24 4" halign="left" valign="center" style="TextRaceChat" scriptevents="1" class="nickLabel" textsize="1" textcolor="fff" textemboss=""/>');
        $this->addComponent($item);

        if ($moreInfo) {
            $item = new \ManiaLive\Gui\Elements\Xml();
            $item->setContent('<label id="RecCp2_' . $index . '" posn="59 0 5.0E-5" sizen="6 4" halign="right" valign="center" style="TextRaceChat" textsize="1" textcolor="ff0" hidden="1"/>');
            $this->addComponent($item);

            $item = new \ManiaLive\Gui\Elements\Xml();
            $item->setContent('<label id="RecCp1_' . $index . '" posn="-18 0 6.0E-5" sizen="6 4" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="ff0" hidden="1"/>');
            $this->addComponent($item);

            $item = new \ManiaLive\Gui\Elements\Xml();
            $item->setContent('<label id="RecInfo2_' . $index . '" posn="50 0 7.0E-5" sizen="11 4" halign="right" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" hidden="1"/>');
            $this->addComponent($item);

            $item = new \ManiaLive\Gui\Elements\Xml();
            $item->setContent('<label id="RecInfo1_' . $index . '" posn="-12 0 8.0E-5" sizen="11 4" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" hidden="1"/>');
            $this->addComponent($item);
        }

        $this->setSize(41, 4);
        $this->setAlign("center", "top");
    }
}
