<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Windows;

use ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Controls\LinePlotter;

class PlotterWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $plotter;

    public function setDatas($datas, $limitX, $limitY, $labelsX, $labelsY, $color0 = null, $color1 = null)
    {
        $this->plotter = new LinePlotter($limitX, $limitY, $labelsX, $labelsY);
        $this->addComponent($this->plotter);

        foreach ($datas as $i => $data) {
            foreach ($data as $x => $val) {
                $val = $this->getNumber($val);
                $this->plotter->add($i, $x . ".0", $val);
            }
        }

        $this->plotter->setLineColor(0, $color0);
        if ($color1 != null) {
            $this->plotter->setLineColor(1, $color1);
        }

        //$this->addScriptToMain($this->plotter->getScript());
    }

    private function getNumber($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
