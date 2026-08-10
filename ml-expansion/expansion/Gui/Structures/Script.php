<?php

namespace ManiaLivePlugins\eXpansion\Gui\Structures;

/**
 * Description of Script
 *
 * @author De Cramer Oliver
 * @author Petri
 */
class Script
{

    private static $templateCache = array();

    private $_relPath = "";
    private $params = array();

    /**
     * construct a script
     *
     * example for external plugins
     * $this->script = new Script("libraries/ManiaLivePlugins/authorName/pluginName/Gui/Script", true);
     *
     * @param string $path relative path to your script
     * @param bool $pluginsRoot set the relative path pointer to ManialiveRoot instead of
     *                            Vendor\path
     */
    public function __construct($path, $pluginsRoot = false)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->_relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        if ($pluginsRoot) {
            $this->_relPath = realpath(APP_ROOT . DIRECTORY_SEPARATOR . $path);
        }
    }

    /**
     * @return string The path to the script name
     */
    public function getRelPath()
    {
        return $this->_relPath;
    }

    /**
     * @param string $name The name of the parameter.
     * @param string $value The value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return string The code of the script
     */
    public function getDeclarationScript($win = null, $component = null)
    {
        return $this->renderFile($this->_relPath . '/declarationScript.txtm');
    }

    /**
     * @return string The code of the script
     */
    public function getlibScript($win = null, $component = null)
    {
        return $this->renderFile($this->_relPath . '/libScript.txtm');
    }

    /**
     * @return string The code of the script
     */
    public function getWhileLoopScript($win = null, $component = null)
    {
        return $this->renderFile($this->_relPath . '/whileLoopScript.txtm');
    }

    /**
     * @return string The code of the script
     */
    public function getEndScript($win = null)
    {
        return $this->renderFile($this->_relPath . '/endDeclarationScript.txtm');
    }

    /**
     * @param int $number The integer
     *
     * @return string The int transformed into a string
     */
    public function getNumber($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    /**
     * Read a template file (cached), then apply {{ key }} substitution.
     * @param string $path Path to the script
     * 
     * @return string The code of the script
     */
    private function renderFile($path)
    {
        if (!file_exists($path)) return '';

        if (!isset(self::$templateCache[$path])) {
            self::$templateCache[$path] = file_get_contents($path);
        }
        $raw = self::$templateCache[$path];

        $search  = array();
        $replace = array();
        foreach ($this->params as $key => $value) {
            if (!is_scalar($value) && $value !== null) continue;
            $search[]  = '{{ ' . $key . ' }}';
            $replace[] = $value !== null ? (string)$value : '';
        }
        $output = !empty($search) ? str_replace($search, $replace, $raw) : $raw;

        return $output;
    }

    public function __destruct()
    {
        if (isset(self::$templateCache[$this->_relPath . '/declarationScript.txtm'])) {
            unset(self::$templateCache[$this->_relPath . '/declarationScript.txtm']);
        }
        if (isset(self::$templateCache[$this->_relPath . '/libScript.txtm'])) {
            unset(self::$templateCache[$this->_relPath . '/libScript.txtm']);
        }
        if (isset(self::$templateCache[$this->_relPath . '/whileLoopScript.txtm'])) {
            unset(self::$templateCache[$this->_relPath . '/whileLoopScript.txtm']);
        }
        if (isset(self::$templateCache[$this->_relPath . '/endDeclarationScript.txtm'])) {
            unset(self::$templateCache[$this->_relPath . '/endDeclarationScript.txtm']);
        }
    }
}
