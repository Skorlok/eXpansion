<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Storage as eXpStorage;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ManiaLink extends Singletons
{
    private static $templateCache = array();

    private static $components = array(
        'optimizedpager' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\OptimizedPager',
            'method' => 'getXML',
            'ml'     => true,
            'login'  => true,
            'params' => array('sizeX', 'sizeY', 'itemsVarName', 'dataVarName', 'rowsPerPage', 'itemsPerRow', 'varName'),
        ),
        'pager' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Pager',
            'method' => 'getXML',
            'ml'     => true,
            'params' => array('sizeX', 'sizeY', 'items', 'posX', 'posY'),
        ),
        // ml = true : $this (ManiaLink instance) is prepended as first argument
        'dropdown' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown',
            'method' => 'getXML',
            'ml'     => true,
            'params' => array('name', 'itemsVarName', 'selectedIndex', 'sizeX'),
        ),
        'inputboxmasked' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\InputboxMasked',
            'method' => 'getXML',
            'ml'     => true,
            'params' => array('name', 'sizeX', 'editable', 'label', 'text', 'showClearText', 'id', 'class', 'isTextId'),
        ),
        // ml = false (or absent) : called without $this
        // script is optional, if present the script is registered to the ManiaLink instance
        'button' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Button',
            'method' => 'getButtonXML',
            /*'script' => 'getScriptML',*/ // IGNORED, the hoover is broken and i'm lazy to fix it xD
            'params' => array('sizeX', 'sizeY', 'text', 'active', 'colorize', 'textcolor', 'value', 'action', 'manialink', 'url', 'id', 'class', 'attribute', 'isTextId'),
        ),
        'checkbox' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Checkbox',
            'method' => 'getXML',
            'script' => 'getScriptML',
            'params' => array('name', 'active', 'textWidth', 'enabled', 'text', 'isTextId'),
        ),
        'colorchooser' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\ColorChooser',
            'method' => 'getXML',
            'script' => 'getScriptML',
            'params' => array('inputboxName', 'sizeX', 'digits', 'hasPrefix', 'color'),
        ),
        'icon' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Icon',
            'method' => 'getXML',
            'script' => 'getScriptML',
            'params' => array('sizeX', 'sizeY', 'description', 'descSizeX', 'descSizeY', 'descLines', 'colorize', 'value', 'action', 'manialink', 'url', 'icon', 'iconSub', 'id', 'class', 'attribute', 'isTextId'),
        ),
        'ratiobutton' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Ratiobutton',
            'method' => 'getXML',
            'script' => 'getScriptML',
            'params' => array('group', 'index', 'entryName', 'active', 'textWidth', 'text', 'isTextId'),
        ),
        'scrollablearea' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\ScrollableArea',
            'method' => 'getXML',
            'script' => 'getScriptML',
            'params' => array('sizeX', 'sizeY', 'content'),
        ),
        // no script, no $this
        'inputbox' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox',
            'method' => 'getXML',
            'params' => array('name', 'sizeX', 'editable', 'label', 'text', 'id', 'class', 'isTextId'),
        ),
        'listbg' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround',
            'method' => 'getXML',
            'params' => array('indexNumber', 'sizeX', 'sizeY', 'action'),
        ),
        'widgetbg' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround',
            'method' => 'getXML',
            'params' => array('sizeX', 'sizeY', 'action'),
        ),
        'widgetbutton' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\WidgetButton',
            'method' => 'getWidgetButtonXML',
            'params' => array('sizeX', 'sizeY', 'action', 'row0', 'row1', 'row2'),
        ),
        'widgettitle' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle',
            'method' => 'getXML',
            'params' => array('sizeX', 'sizeY', 'text', 'id'),
        )
    );
    
    protected $relPath;
    protected $maniaLinkPath;

    protected $name;
    protected $layer;
    protected $position;

    protected $scripts;
    protected $widgetScript;
    protected $elementsScript;
    protected $dicoMessages;

    /** @var \ManiaLive\Data\Storage\Storage $storage */
    protected $storage;
    protected $eXpStorage;
    protected $connection;

    protected $parameters;

    public function __construct($path)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        $this->maniaLinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "Gui\ManiaLink\Head.xml";
        $this->widgetScript = new Script("Gui\Scripts\PlainManialinkScript");
        $this->storage = Storage::getInstance();
        $this->eXpStorage = eXpStorage::getInstance();
        $this->connection = $this->getDediConnection();

        $this->name = "";
        $this->layer = "normal";
        $this->position = array(0, 0, 0);
        $this->dicoMessages = array();

        $this->parameters = array();
        $this->elementsScript = array();
    }

    // Getters

    public function getWidgetName()
    {
        return $this->name;
    }

    public function getWidgetHashName()
    {
        return $this->simpleHashName($this->name . $this->getLayer());
    }

    public function getPosX()
    {
        return $this->position[0];
    }

    public function getPosY()
    {
        return $this->position[1];
    }

    public function getPosZ()
    {
        return $this->position[2];
    }

    public function getLayer()
    {
        if (strtolower($this->layer == "scorestable")) {
            return "scorestable";
        } else {
            return "normal";
        }
    }

    // Setters

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setLayer($layer)
    {
        $this->layer = $layer;
    }

    public function setPosition($x, $y, $z)
    {
        $this->position = array($x, $y, $z);
    }

    public function setScripts($scripts)
    {
        $this->scripts = $scripts;
    }

    public function registerElementScript(Script $script, $param = null)
    {
        if (!isset($this->elementsScript[$script->getRelPath() . ($param !== null ? ':' . $param : '')])) {
            $this->elementsScript[$script->getRelPath() . ($param !== null ? ':' . $param : '')] = $script;
            return;
        }
    }

    public function getParam($key)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return null;
    }

    public function setParam($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function addLang($lang, $param = null)
    {
        if ($lang == "") {
            return "";
        }
        $this->dicoMessages[$lang] = eXpGetMessage($lang);
        if ($param !== null) {
            $this->dicoMessages[$lang]->setArgs($param);
        }
        return "l" . $this->simpleHashName($lang);
    }

    // Others

    public function simpleHashName($name)
    {
        if ($name == "") {
            return "";
        }
        $hash = "";
        for ($i = 0; $i < strlen($name); $i++) {
            $hash .= ord($name[$i]);
        }
        return $hash;
    }

    public function getBoolean($boolean)
    {
        if ($boolean) {
            return "True";
        }

        return "False";
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    /**
     * Read a template file (cached), optionally execute its PHP, then apply {key} substitution.
     */
    private function renderFile($path, array $params, $login)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (!file_exists($path)) return '';

        if (!isset(self::$templateCache[$path]) /*|| true*/) {
            self::$templateCache[$path] = file_get_contents($path);
        }
        $raw = self::$templateCache[$path];

        if (strpos($raw, '<?') !== false) {
            ob_start();
            eval('?>' . $raw);
            $output = ob_get_clean();
        } else {
            $output = $raw;
        }

        $search  = array();
        $replace = array();
        foreach ($params as $key => $value) {
            if (!is_scalar($value) && $value !== null) continue;
            $search[]  = '{' . $key . '}';
            $replace[] = $value !== null ? (string)$value : '';
        }
        $output = !empty($search) ? str_replace($search, $replace, $output) : $output;

        if (strpos($output, '{@') !== false) {
            $output = preg_replace_callback('/\{@([^}]+)\}/', function($m) {
                return $this->addLang($m[1]);
            }, $output);
        }

        return $this->expandComponents($output, $login);
    }

    private function expandComponents($output, $login)
    {
        if (strpos($output, '<exp:') === false) return $output;
        $self = $this;
        return preg_replace_callback(
            '/<exp:([a-zA-Z_][a-zA-Z0-9_]*)((?:\s+[a-zA-Z_][a-zA-Z0-9_]*="[^"]*")*)\s*\/>/',
            function($m) use ($self, $login) { return $self->expandComponent($m, $login); },
            $output
        );
    }

    private function expandComponent($m, $login)
    {
        $name = $m[1];
        if (!isset(self::$components[$name])) return $m[0];

        $def = self::$components[$name];

        $attrs = array();
        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)="([^"]*)"/', $m[2], $pairs, PREG_SET_ORDER);
        foreach ($pairs as $p) $attrs[$p[1]] = $p[2];

        $args = !empty($def['ml']) ? array($this) : array();
        if (!empty($def['login'])) $args[] = $login;
        if (!empty($def['script'])) {
            $script = call_user_func(array($def['class'], $def['script']), $this);
            if ($script instanceof Script) {
                $this->registerElementScript($script);
            }
        }
        foreach ($def['params'] as $pName) {
            if (!array_key_exists($pName, $attrs)) { $args[] = null; continue; }
            $v = $attrs[$pName];
            if ($v === '' || $v === 'null')                    $args[] = null;
            elseif ($v === 'true')                             $args[] = true;
            elseif ($v === 'false')                            $args[] = false;
            elseif (is_numeric($v) && (string)($v + 0) === $v) $args[] = $v + 0;
            elseif ($v[0] === '@')                             $args[] = $this->addLang(substr($v, 1));
            else                                               $args[] = $v;
        }

        while (!empty($args) && end($args) === null) array_pop($args);

        return call_user_func_array(array($def['class'], $def['method']), $args);
    }

    /**
     * @return string The code of the widget
     */
    final protected function getWidget($login)
    {
        $params = array(
            'widgetHashName' => $this->getWidgetHashName(),
            'layer'          => $this->getLayer(),
            'widgetName'     => $this->getWidgetName(),
            'pos'            => $this->getPosX() . ' ' . $this->getPosY() . ' ' . $this->getPosZ(),
            'xml'            => (string)$this->getUserXML($login),
            'languages'      => (string)$this->getLanguages(),
            'mlScripts'      => (string)$this->getMlScripts(),
        );

        if (method_exists($this, 'getSizeX') && method_exists($this, 'getSizeY')) {
            $sx = $this->getSizeX();
            $sy = $this->getSizeY();
            $params['size']          = $sx . ' ' . $sy;
            $params['sizeHalfPos']   = ($sx / 2) . ' ' . (-$sy / 2);
            $params['frameSize']     = ($sx + 12) . ' ' . ($sy + 6);
            $params['fullFrameSize'] = ($sx + 12) . ' ' . ($sy + 12);
            $params['frameW']        = $sx + 12;
            $params['contentH']      = $sy + 4;
            $params['labelW']        = $sx - 10;
            $params['closeX']        = $sx + 4;
        }
        if (method_exists($this, 'getTitle')) {
            $params['title'] = $this->getTitle();
        }
        if (isset($this->guiConfig)) {
            $params['windowBgColor']      = $this->guiConfig->windowBackgroundColor;
            $params['windowTitleBgColor'] = $this->guiConfig->windowTitleBackgroundColor;
            $params['windowTitleColor']   = $this->guiConfig->windowTitleColor;
        }

        return $this->renderFile($this->maniaLinkPath, $params, $login);
    }

    protected function getUserXML($login)
    {
        return $this->renderFile($this->relPath, $this->parameters, $login);
    }

    protected function getMlScripts() {
        $this->widgetScript->setParam("dDeclares", $this->scripts->getDeclarationScript() . $this->scripts->getEndScript());
        $this->widgetScript->setParam("scriptLib", $this->scripts->getlibScript());
        $this->widgetScript->setParam("wLoop", $this->scripts->getWhileLoopScript());

        return $this->widgetScript->getDeclarationScript();
    }

    protected function getLanguages() {
        $dico = array();
        foreach ($this->dicoMessages as $key => $value) {
            $lang = $value->getMultiLangArray();
            foreach ($lang as $l) {
                $dico[$l['Lang']]["l" . $this->simpleHashName($key)] = $l['Text'];
            }
        }

        $dicoXml = "";
        foreach ($dico as $lang => $values) {
            $dicoXml .= "<language id=\"" . $lang . "\">" . PHP_EOL;
            foreach ($values as $key => $value) {
                $dicoXml .= "<" . $key . ">" . $value . "</" . $key . ">" . PHP_EOL;
            }
            $dicoXml .= "</language>" . PHP_EOL;
        }
        
        if ($dicoXml) {
            return "<dico>" . $dicoXml . "</dico>";
        }
    }

    public function show($login = null, $persistant = false)
    {
        $xml = $this->getWidget($login);
        if ($this->name == "XXX") {
            echo preg_replace('/<script.*?>.*?<\/script>/is', '', $xml);
        }
        if ($login !== null) {
            if (is_array($login)) {
                // check if login exists in $this->storage->players and $this->storage->spectators
                $login = array_filter($login, function ($l) {
                    return isset($this->storage->players[$l]) || isset($this->storage->spectators[$l]);
                });
            }
            try {
                $this->connection->sendDisplayManialinkPage($login, $xml, 0, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                if (is_array($login)) {
                    Helper::log('Cannot send widget: "' . $this->name . '" to players, retrying each login individually, server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));

                    foreach ($login as $l) {
                        try {
                            $this->connection->sendDisplayManialinkPage($l, $xml, 0, false, false);
                        } catch (\Exception $e2) {
                            Helper::log('Cannot send widget: "' . $this->name . '" to player: "' . $l . '" , server said: ' . $e2->getMessage(), array("Gui", "ManiaLink"));
                        }
                    }
                } else {
                    Helper::log('Cannot send widget: "' . $this->name . '" to player: "' . $login . '" , server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));
                }
            }
        } else {
            $this->connection->sendDisplayManialinkPage(null, $xml, 0, false, true);
        }

        if ($persistant && $login == null) {
            Gui::$persistentWidgets[$this->getWidgetHashName()] = $xml;
        }
    }

    public function erase($login = null)
    {
        if ($login !== null) {
            try {
                $this->connection->sendDisplayManialinkPage($login, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                Helper::log("Cannot erase player widget, server said: " . $e->getMessage(), array("Gui", "ManiaLink"));
            }
        } else {
            $this->connection->sendDisplayManialinkPage(null, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, true);
        }

        if (isset(Gui::$persistentWidgets[$this->getWidgetHashName()])) {
            unset(Gui::$persistentWidgets[$this->getWidgetHashName()]);
        }
    }

    public function __destruct()
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $this->relPath);
        if (isset(self::$templateCache[$path])) {
            unset(self::$templateCache[$path]);
        }
    }
}
