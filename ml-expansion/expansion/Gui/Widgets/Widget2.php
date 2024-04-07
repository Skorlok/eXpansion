<?php

namespace ManiaLivePlugins\eXpansion\Gui\Widgets;

class Widget2
{

    private $relPath = "";
    private $name = "";
    private $layer = "";
    private $position = array(0, 0, 0);
    private $size = array(0, 0);

    /**
     * construct a widget
     *
     * example for external plugins
     * $this->widget = new Widget("libraries/ManiaLivePlugins/authorName/pluginName/Gui/Widget/Widget.xmlm", true);
     *
     * @param string $path relative path to your widget
     * @param bool $pluginsRoot set the relative path pointer to ManialiveRoot instead of
     *                            Vendor\path
     *
     *
     */
    public function __construct($path, $layer, $name, $pluginsRoot = false)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        if ($pluginsRoot) {
            $this->relPath = realpath(APP_ROOT . DIRECTORY_SEPARATOR . $path);
        }

        $this->name = $name;
        $this->layer = $layer;
    }

    /**
     * @return string The path to the widget name
     */
    public function getRelPath()
    {
        return $this->relPath;
    }

    /**
     * @return string The name of the widget
     */
    public function getName()
    {
        return $this->name;
    }

    public function simpleHashName()
    {
        $hash = "";
        for ($i = 0; $i < strlen($this->name); $i++) {
            $hash .= ord($this->name[$i]);
        }
        return $hash;
    }

    /**
     * @return string The layer of the widget
     */
    public function getLayer()
    {
        if (strtolower($this->layer == "scorestable")) {
            return "scorestable";
        } else {
            return "normal";
        }
    }

    /**
     * @param $relPath
     */
    public function setRelPath($relPath)
    {
        $this->relPath = $relPath;
    }

    /**
     * @param string $name The name of the parameter.
     * @param string $value The value
     */
    public function setParam($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * @return string The code of the widget
     */
    final protected function getWidget()
    {
        $path = $this->relPath . '/widget.txtm';
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (file_exists($path)) {
            ob_start();
            include $path;

            $script = ob_get_contents();
            ob_end_clean();

            return $script;
        }
    }
}
