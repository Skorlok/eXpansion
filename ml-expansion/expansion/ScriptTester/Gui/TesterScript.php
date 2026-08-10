<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\ScriptTester\Gui;

/**
 * Description of TesterScript
 *
 * @author Petri
 */
class TesterScript extends \ManiaLivePlugins\eXpansion\Gui\Structures\Script
{

    protected $str;

    public function __construct($script)
    {
        $this->str = $script;
    }


    public function getlibScript($win = null, $component = null)
    {
        return "";
    }

    public function getDeclarationScript($win = null, $component = null)
    {
        return "";
    }

    public function getEndScript($win = null)
    {
        return $this->str;
    }

    public function getWhileLoopScript($win = null, $component = null)
    {
        return "";
    }
}
