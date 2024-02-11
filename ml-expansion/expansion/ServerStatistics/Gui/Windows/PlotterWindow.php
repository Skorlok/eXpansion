<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Windows;

class PlotterWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    protected $plotter;

    protected $labelsY = array();
    protected $labelsX = array();

    protected $graph;
    protected $plots = array();
    protected $limitY = 100;
    protected $colors = array();
    protected $sizes = array();

    public function setDatas($datas, $limitY, $labelsX, $labelsY, $color0 = null, $color1 = null)
    {
        $this->setPosX(5);
        $this->setPosY(-5);

        $this->limitY = $limitY;
        
        $this->colors[0] = $color0;
        if ($color1 != null) {
            $this->colors[1] = $color1;
        }

        $this->graph = new \ManiaLive\Gui\Elements\Xml();
        $this->graph->setContent('<graph id="graph" posn="8 -10 10" sizen="152 96" halign="left" valign="top" scriptevents="1"/>');
        $this->addComponent($this->graph);
        

        $size = sizeof($labelsX);
        for ($i = 0; $i < $size; $i++) {
            $this->labelsX[$i] = new \ManiaLive\Gui\Elements\Xml();
            $this->labelsX[$i]->setContent('<label posn="' . (($i * (195) / $size) + 5) . ' -106 1.0" sizen="15 7" halign="left" valign="right" style="TextStaticSmall" text="' . $labelsX[$i] . '"/>');
            $this->addComponent($this->labelsX[$i]);
        }

        
        if ($labelsY !== null) {

            $size = sizeof($labelsY);
            for ($i = 0; $i < $size; $i++) {
                $this->labelsY[$i] = new \ManiaLive\Gui\Elements\Xml();
                $this->labelsY[$i]->setContent('<label posn="7 ' . ((-1 * $i * ((95) / $size)) - 8) . ' 5.0" sizen="8 7" halign="right" valign="right" style="TextStaticSmall" text="' . $labelsY[$i] . '"/>');
                $this->addComponent($this->labelsY[$i]);
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $this->labelsY[$i] = new \ManiaLive\Gui\Elements\Xml();
                $this->labelsY[$i]->setContent('<label posn="7 ' . ((-1 * $i * ((95) / 5)) - 8) . ' 5.0" sizen="8 7" halign="right" valign="right" style="TextStaticSmall" text="' . ((int)(5 - $i) * (($limitY - 0) / 5)) . '"/>');
                $this->addComponent($this->labelsY[$i]);
            }
        }

        foreach ($datas as $i => $data) {
            foreach ($data as $x => $val) {
                $this->plots[$i][] = array($x . ".0", number_format((float)$val, 2, '.', ''));
            }
        }

        $this->registerMainScript($this->getScript());
    }

    public function getScript()
    {
        $val = '
declare CMlGraph Graph = (Page.GetFirstChild("graph") as CMlGraph);

Graph.CoordsMin = <0.00,0.00>;
Graph.CoordsMax = <720.00, ' . number_format((float)$this->limitY, 2, '.', '') . '>;

declare CMlGraphCurve[] Curves;
declare CMlGraphCurve[] scaleX;
';
        $index = 0;
        foreach ($this->plots as $u => $plot) {
            $val .= 'Curves.add(Graph.AddCurve());' . "\n";
            foreach ($plot as $i => $vals) {
                $val .= "Curves[" . $index . "].Points.add(<" . number_format((float)$this->plots[$u][$i][0], 2, '.', '') . "," . number_format((float)$this->plots[$u][$i][1], 2, '.', '') . ">);\n";
            }
            $index++;
        }


        foreach ($this->colors as $u => $color) {
            $val .= 'Curves[' . $u . '].Color = ' . $color . ';';
        }

        $val .= '       
declare min = (Graph.CoordsMin[1]);
declare max = (Graph.CoordsMax[1]);
declare diff = Graph.CoordsMax[1] - Graph.CoordsMin[1];
declare Real base = MathLib::Ln(diff)/2.303; 
declare Real power =MathLib::ToReal(MathLib::NearestInteger(base));
declare Real base_unit = MathLib::Pow(10.0,power);
declare Real step = base_unit / 5.00;
declare Integer i;
 
scaleX.add(Graph.AddCurve());

scaleX[0].Points.add(<Graph.CoordsMin[0], Graph.CoordsMin[1]>);
scaleX[0].Points.add(<Graph.CoordsMin[0]+0.001, Graph.CoordsMax[1]>);
scaleX[0].Color = <0.0, 0.0, 0.0>;
scaleX[0].Width = 0.5; 

declare Real index = min;
while (index < max) {
    scaleX.add(Graph.AddCurve());
    i = scaleX.count-1;
    scaleX[i].Points.add(<Graph.CoordsMin[0], index>);
    scaleX[i].Points.add(<Graph.CoordsMax[0], index>);
    scaleX[i].Color = <0.0, 0.0, 0.0>;
    index = index + step;
}
';

        return $val;
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
