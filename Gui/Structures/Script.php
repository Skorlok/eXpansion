<?php

namespace ManiaLivePlugins\eXpansion\Gui\Structures;

/**
 * Description of Script
 *
 * @author De Cramer Oliver
 */
class Script {
    
    private $_relPath = "";
    
    function __construct($relPath) {
        $this->_relPath = __DIR__.'/../../'.$relPath;
    }

    public function getRelPath() {
        return $this->_relPath;
    }

    public function setRelPath($relPath) {
        $this->_relPath = $relPath;
    }

    public function setParam($name, $value){
        $this->$name = $value;
    }
    
    public function getDeclarationScript($win, $component){
        return $this->getScript($this->_relPath.'/declarationScript.php', $win, $component);
    }
    
    public function getlibScript($win, $component){
        return $this->getScript($this->_relPath.'/libScript.php', $win, $component);
    }
    
    public function getWhileLoopScript($win, $component){
       return $this->getScript($this->_relPath.'/whileLoopScript.php', $win, $component);
    }
    
    public function getEndScript($win){
        return $this->getScript($this->_relPath.'/endDeclarationScript.php', $win, null);
    }
    
    final protected function getScript($path, $win, $component){
         if(file_exists($path)){
            ob_start();
            
            include $path;
            
            $script = ob_get_contents();
            ob_end_clean();
            return $script;
        }
    }
    
    public function multiply(){
        return false;
    }
    
    
    function getNumber($number) {
        return number_format((float) $number, 2, '.', '');
    }
}

?>
