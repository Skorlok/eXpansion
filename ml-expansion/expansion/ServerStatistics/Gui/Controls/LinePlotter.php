<?php

namespace ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Controls;

class LinePlotter extends \ManiaLivePlugins\eXpansion\Gui\Control
{
    protected $label_minX;
    protected $label_maxX;
    protected $labelsY = array();
    protected $labelsX = array();

    protected $label_graphtitle;
    protected $graph_element;
    protected $graph;
    protected $plots = array();
    protected $limits = array();
    protected $colors = array();
    protected $sizes = array();
    protected $tickSize = 5;

    public function __construct($limitX, $limitY, $labelsX, $labelsY = null)
    {
        $sizeX = 160;
        $sizeY = 100;

        $this->setPosX(5);
        $this->setPosY(-5);

        $this->tickSize = 5;

        $this->plots = array();
        $this->colors = array();

        $this->graph = new \ManiaLive\Gui\Elements\Xml();
        $this->graph->setContent('<graph id="graph" posn="8 0 ' . $this->getPosZ() + 10 . '" sizen="' . $sizeX - 8 . ' ' . $sizeY - 4 . '" halign="left" valign="top" scriptevents="1"/>');
        $this->addComponent($this->graph);

        $this->limits = array(0, 0, $limitX, $limitY);
        $this->setLineColor(0);



        foreach ($this->labelsX as $label) {
            $this->removeComponent($label);
        }
        $this->labelsX = array();

        $size = sizeof($labelsX);

        for ($i = 0; $i < $size; $i++) {
            $this->labelsX[$i] = new \ManiaLib\Gui\Elements\Label(15);
            $this->labelsX[$i]->setPosition(($i * ($sizeX + 35) / $size) + 5, -$sizeY + 4);

            $this->labelsX[$i]->setAlign("left", "right");

            $this->labelsX[$i]->setText($labelsX[$i]);
            $this->addComponent($this->labelsX[$i]);
        }

        
        if ($labelsY !== null) {
            foreach ($this->labelsY as $label) {
                $this->removeComponent($label);
            }
            $this->labelsY = array();
    
            $size = sizeof($labelsY);
    
            for ($i = 0; $i < $size; $i++) {
                $this->labelsY[$i] = new \ManiaLib\Gui\Elements\Label(8);
                $this->labelsY[$i]->setPosition(7, (-1 * $i * (($sizeY - 5) / $size)) + 2);
                $this->labelsY[$i]->setAlign("right", "right");
                $this->labelsY[$i]->setText($labelsY[$i]);
                $this->addComponent($this->labelsY[$i]);
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $this->labelsY[$i] = new \ManiaLib\Gui\Elements\Label(8);
                $this->labelsY[$i]->setPosition(7, (-1 * $i * (($sizeY - 5) / 5)) + 2);
                $this->labelsY[$i]->setAlign("right", "right");
                $this->labelsY[$i]->setText((int)(5 - $i) * (($limitY - 0) / 5));
                $this->addComponent($this->labelsY[$i]);
            }
        }
    }

    public function add($line = 0, $x = 0, $y = 0)
    {
        $this->plots[$line][] = array($x, $y);
    }

    public function setLineColor($line, $color = "000")
    {
        $r = (float)base_convert(substr($color, 0, 1), 16, 10) / 15;
        $g = (float)base_convert(substr($color, 1, 1), 16, 10) / 15;
        $b = (float)base_convert(substr($color, 2, 1), 16, 10) / 15;
        $r = $this->getNumber($r);
        $g = $this->getNumber($g);
        $b = $this->getNumber($b);
        $this->colors[$line] = array($r, $g, $b);
    }

    private function getNumber($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    public function getScript()
    {
        $val = '
declare CMlGraph Graph = (Page.GetFirstChild("graph") as CMlGraph);

Graph.CoordsMin = <' . $this->getNumber($this->limits[0]) . ',' . $this->getNumber($this->limits[1]) . '>;
Graph.CoordsMax = <' . $this->getNumber($this->limits[2]) . ', ' . $this->getNumber($this->limits[3]) . '>;

declare CMlGraphCurve[] Curves;
declare CMlGraphCurve[] scaleX;
';
        $index = 0;
        foreach ($this->plots as $u => $plot) {
            $val .= 'Curves.add(Graph.AddCurve());' . "\n";
            foreach ($plot as $i => $vals) {
                $val .= "Curves[" . $index . "].Points.add(<" . $this->getNumber($this->plots[$u][$i][0])
                    . "," . $this->getNumber($this->plots[$u][$i][1]) . ">);\n";
            }
            $index++;
        }


        foreach ($this->colors as $u => $color) {
            $val .= 'Curves[' . $u . '].Color = <' . $color[0] . ', ' . $color[1] . ', ' . $color[2] . '>;';
        }

        $val .= '       
declare min = (Graph.CoordsMin[1]);
declare max = (Graph.CoordsMax[1]);
declare diff = Graph.CoordsMax[1] - Graph.CoordsMin[1];
declare Real base = MathLib::Ln(diff)/2.303; 
declare Real power =MathLib::ToReal(MathLib::NearestInteger(base));
declare Real base_unit = MathLib::Pow(10.0,power);
declare Real step = base_unit / ' . $this->getNumber($this->tickSize) . ';
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

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        $this->labelsY = array();
        $this->labelsX = array();
        parent::onIsRemoved($target);
        parent::destroy();
    }
}
