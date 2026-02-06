<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Windows;

use ManiaLivePlugins\eXpansion\Helpers\Storage as eXpStorage;

class PlotterWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    public function setDatas($datas, $limitY, $labelsX, $labelsY, $color0 = null, $color1 = null)
    {
        $this->setPosX(5);
        $this->setPosY(-5);

        $size = sizeof($labelsX);
        for ($i = 0; $i < $size; $i++) {
            $labelX = new \ManiaLive\Gui\Elements\Xml();
            $labelX->setContent('<label posn="' . (($i * (195) / $size) + 5) . ' -106 1.0" sizen="15 7" halign="left" valign="right" style="TextStaticSmall" text="' . $labelsX[$i] . '"/>');
            $this->addComponent($labelX);
        }


        if ($labelsY !== null) {

            $size = sizeof($labelsY);
            for ($i = 0; $i < $size; $i++) {
                $labelY = new \ManiaLive\Gui\Elements\Xml();
                $labelY->setContent('<label posn="7 ' . ((-1 * $i * ((95) / $size)) - 8) . ' 5.0" sizen="8 7" halign="right" valign="right" style="TextStaticSmall" text="' . $labelsY[$i] . '"/>');
                $this->addComponent($labelY);
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $labelY = new \ManiaLive\Gui\Elements\Xml();
                $labelY->setContent('<label posn="7 ' . ((-1 * $i * ((95) / 5)) - 8) . ' 5.0" sizen="8 7" halign="right" valign="right" style="TextStaticSmall" text="' . ((int)(5 - $i) * (($limitY - 0) / 5)) . '"/>');
                $this->addComponent($labelY);
            }
        }

        $plots = array();
        foreach ($datas as $i => $data) {
            foreach ($data as $x => $val) {
                $plots[$i][] = array($x, $val);
            }
        }

        $plotter = new \ManiaLive\Gui\Elements\Xml();
        $plotterData = '<graph id="graph" min="0 0" max="720 ' . $limitY . '" posn="8 -10 10" sizen="152 96" halign="left" valign="top" scriptevents="1">';


        $i = 0;
        foreach ($plots as $u => $plot) {
            if (substr(eXpStorage::getInstance()->version->build, 0, 4) >= 2017) {
                $plotterData .= '<curve color="' . ($i == 0 ? $color0 : $color1) . '" style="thin">';
            } else {
                $plotterData .= '<curve color="' . ($i == 0 ? $color0 : $color1) . '">';
            }
            foreach ($plot as $i => $vals) {
                $plotterData .= '<point coords="' . $plots[$u][$i][0] . ' ' . $plots[$u][$i][1] . '" />' . "\n";
            }
            $plotterData .= '</curve>';
        }

        if (substr(eXpStorage::getInstance()->version->build, 0, 4) >= 2017) {
            $plotterData .= '<curve color="000" width="0.5" style="thin">';
        } else {
            $plotterData .= '<curve color="000" width="0.5">';
        }
        $plotterData .= '<point coords="0.00 0.00" />';
        $plotterData .= '<point coords="0.001 ' . $limitY . '" />';
        $plotterData .= '</curve>';

        $index = 0;
        $step = (pow(10, round(log($limitY)/2.303))/5);
        while ($index < $limitY) {
            $plotterData .= '<curve color="000" width="0.5">';
            $plotterData .= '<point coords="0.00 ' . $index . '" />';
            $plotterData .= '<point coords="720.00 ' . $index . '" />';
            $plotterData .= '</curve>';
            $index = $index + $step;
        }


        $plotterData .= '</graph>';
        $plotter->setContent($plotterData);
        $this->addComponent($plotter);
    }
}
