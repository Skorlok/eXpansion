<?php

namespace ManiaLivePlugins\eXpansion\Endurance;

class Config extends \ManiaLib\Utils\Singleton
{
    public $rounds = 3;
    public $maps = 1;
    public $decreaser = 0.95;
    public $auto_reset = false;
    public $points = "750,720,690,660,645,630,615,600,585,570,558,546,534,522,510,498,486,474,462,450,440,430,420,410,400,390,380,370,360,351,342,333,324,315,306,298,290,282,274,266,258,251,244,237,230,224,218,212,206,200,195,190,185,180,175,170,166,162,158,154,150,146,142,138,134,130,127,124,121,118,115,112,109,106,103,100,98,96,94,92,90,88,86,84,82,80,78,76,74,72,70,68,66,64,62,60,58,56,54,52,50,49,48,47,46,45,44,43,42,41,40,39,38,37,36,35,34,33,32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1";
    public $points_last = 1;
    public $wu = 15;
    public $wustart = 23;
    public $save_csv = "enduro_results.csv";
    public $save_total_points = false;

    public $enduroPointPanel_PosX = -160;
    public $enduroPointPanel_PosY = 67;
    public $enduroPointPanel_nbFields = 13;
    public $enduroPointPanel_nbFirstFields = 3;
}
