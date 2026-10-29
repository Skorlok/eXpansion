<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ManiaLink extends Singletons
{
    /** Safety net for a component returning XML that contains its own tag */
    const MAX_COMPONENT_DEPTH = 5;

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
            'params' => array('sizeX', 'sizeY', 'itemSizeY'),
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
        'scrollablearea' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\ScrollableArea',
            'method' => 'getXML',
            'ml'     => true,
            'params' => array('sizeX', 'sizeY', 'contentVarName', 'contentSizeY'),
        ),
        // ml = false (or absent) : called without $this
        // script is optional, if present the script is registered to the ManiaLink instance
        'button' => array(
            'class'  => 'ManiaLivePlugins\eXpansion\Gui\Elements\Button',
            'method' => 'getXML',
            /*'script' => 'getScriptML',*/ // IGNORED, the hoover is broken and i'm lazy to fix it xD
            'params' => array('sizeX', 'sizeY', 'text', 'active', 'colorize', 'textcolor', 'value', 'action', 'manialink', 'url', 'id', 'class', 'attribute', 'isTextId', 'labelId'),
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

    /** @var Script[] keyed by class name — re-registering replaces */
    protected $overrideScripts;

    /** @var Storage $storage */
    protected $storage;
    protected $connection;

    protected $parameters;

    public function __construct($path, $pluginsRoot = false)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        if ($pluginsRoot) {
            $this->relPath = realpath(APP_ROOT . DIRECTORY_SEPARATOR . $path);
        }
        $this->maniaLinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "Gui\ManiaLink\Head.xml";
        $this->widgetScript = new Script("Gui\Scripts\PlainManialinkScript");
        $this->storage = Storage::getInstance();
        $this->connection = $this->getDediConnection();

        $this->name = "";
        $this->layer = "normal";
        $this->position = array(0, 0, 0);
        $this->dicoMessages = array();

        $this->parameters = array();
        $this->elementsScript = array();
        $this->overrideScripts = array();
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
        if (strtolower($this->layer) == "scorestable") {
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

    /**
     * Register a script, replacing any previously registered script of the same class.
     * Used by the elements that emit their own ManiaScript once per ManiaLink (pagers, scrollable areas).
     */
    public function registerOrOverrideScript(Script $script)
    {
        $this->overrideScripts[get_class($script)] = $script;
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

    private function renderFile($path, array $params, $login)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (!file_exists($path) || !is_readable($path)) return '';

        if (!isset(self::$templateCache[$path])) {
            self::$templateCache[$path] = file_get_contents($path);
        }
        $raw = self::$templateCache[$path];

        $output = $this->renderNodes($this->parseBlocks($raw), $params, array());

        return $this->expandComponents($output, $login);
    }

    private function parseBlocks($template)
    {
        $parts = preg_split(
            '/(<exp:(?:foreach|if)(?:\s+[a-zA-Z_][a-zA-Z0-9_]*="[^"]*")*\s*>|<\/exp:(?:foreach|if)\s*>|<exp:else\s*\/>)/',
            $template,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($parts === false) {
            return array($template);
        }

        $root  = (object)array('type' => 'root', 'attrs' => array(), 'children' => array(), 'else' => array(), 'inElse' => false);
        $stack = array($root);

        foreach ($parts as $i => $part) {
            $current = end($stack);
            $list    = $current->inElse ? 'else' : 'children';

            // even parts are text, odd parts are the captured block tags
            if ($i % 2 === 0) {
                if ($part !== '') $current->{$list}[] = $part;
                continue;
            }

            if (preg_match('/^<\/exp:(\w+)/', $part, $m)) {
                if (count($stack) === 1) {
                    Helper::logError('ManiaLink: unexpected ' . $part . ' in ' . $this->relPath, array("Gui", "ManiaLink"));
                    continue;
                }
                if ($current->type !== $m[1]) {
                    Helper::logError('ManiaLink: ' . $part . ' closes a <exp:' . $current->type . '> in ' . $this->relPath, array("Gui", "ManiaLink"));
                }
                array_pop($stack);
            } elseif (strpos($part, '<exp:else') === 0) {
                if ($current->type !== 'if' || $current->inElse) {
                    Helper::logError('ManiaLink: unexpected <exp:else/> in ' . $this->relPath, array("Gui", "ManiaLink"));
                    continue;
                }
                $current->inElse = true;
            } else {
                preg_match('/^<exp:(\w+)/', $part, $m);
                $attrs = array();
                preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)="([^"]*)"/', $part, $pairs, PREG_SET_ORDER);
                foreach ($pairs as $p) $attrs[$p[1]] = $p[2];

                $node = (object)array('type' => $m[1], 'attrs' => $attrs, 'children' => array(), 'else' => array(), 'inElse' => false);
                $current->{$list}[] = $node;
                $stack[] = $node;
            }
        }

        if (count($stack) > 1) {
            Helper::logError('ManiaLink: unclosed <exp:' . end($stack)->type . '> in ' . $this->relPath, array("Gui", "ManiaLink"));
        }

        return $root->children;
    }

    private function renderNodes(array $nodes, array $params, array $scope)
    {
        $output = '';
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $output .= $this->replacePlaceholders($node, $params, $scope);
            } elseif ($node->type === 'if') {
                $test = isset($node->attrs['test']) ? $node->attrs['test'] : '';
                $output .= $this->renderNodes($this->isTrue($test, $params, $scope) ? $node->children : $node->else, $params, $scope);
            } else {
                $output .= $this->renderLoop($node, $params, $scope);
            }
        }
        return $output;
    }

    private function renderLoop($node, array $params, array $scope)
    {
        if (!isset($node->attrs['items'], $node->attrs['as'])) {
            Helper::logError('ManiaLink: <exp:foreach> needs "items" and "as" attributes in ' . $this->relPath, array("Gui", "ManiaLink"));
            return '';
        }

        $found = false;
        $items = $this->resolveValue($node->attrs['items'], $params, $scope, $found);
        if (!is_array($items) && !($items instanceof \Traversable)) {
            return '';
        }

        $output = '';
        $index  = 0;
        foreach ($items as $item) {
            $loopScope = $scope;
            $loopScope[$node->attrs['as']] = $item;
            if (isset($node->attrs['index'])) {
                $loopScope[$node->attrs['index']] = $index;
            }
            $output .= $this->renderNodes($node->children, $params, $loopScope);
            $index++;
        }
        return $output;
    }

    private function isTrue($test, array $params, array $scope)
    {
        $test   = trim($test);
        $negate = ($test !== '' && $test[0] === '!');
        if ($negate) {
            $test = ltrim(substr($test, 1));
        }

        $found = false;
        $value = $this->resolveValue($test, $params, $scope, $found);
        $result = $found && (bool)$value;

        return $negate ? !$result : $result;
    }

    private function replacePlaceholders($text, array $params, array $scope)
    {
        if (strpos($text, '{') === false) return $text;

        $self = $this;
        return preg_replace_callback('/\{(?:@((?:[^{}]|\{[^{}]*\})+)|([^{}@"<>\s][^{}"<>]*))\}/', function($m) use ($self, $params, $scope) {
            if ($m[1] !== '') {
                $lang = strpos($m[1], '{') !== false ? $self->replacePlaceholders($m[1], $params, $scope) : $m[1];
                return $self->addLang($lang);
            }

            $found = false;
            $value = $self->resolveValue($m[2], $params, $scope, $found);
            if (!$found || (!is_scalar($value) && $value !== null)) {
                return $m[0];
            }
            return $value !== null ? (string)$value : '';
        }, $text);
    }

    private function resolveValue($path, array $params, array $scope, &$found)
    {
        $found = true;
        if (array_key_exists($path, $scope))  return $scope[$path];
        if (array_key_exists($path, $params)) return $params[$path];

        $parts = explode('.', $path);
        $first = array_shift($parts);
        if (empty($parts)) {
            $found = false;
            return null;
        }

        if (array_key_exists($first, $scope)) {
            $value = $scope[$first];
        } elseif (array_key_exists($first, $params)) {
            $value = $params[$first];
        } else {
            $found = false;
            return null;
        }

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && (isset($value->$part) || array_key_exists($part, get_object_vars($value)))) {
                $value = $value->$part;
            } else {
                $found = false;
                return null;
            }
        }
        return $value;
    }

    private function expandComponents($output, $login, $depth = 0)
    {
        if (strpos($output, '<exp:') === false) return $output;
        if ($depth > self::MAX_COMPONENT_DEPTH) {
            Helper::logError('ManiaLink: <exp:> components nested deeper than ' . self::MAX_COMPONENT_DEPTH . ' levels, giving up', array("Gui", "ManiaLink"));
            return $output;
        }
        $self = $this;
        return preg_replace_callback(
            '/<exp:([a-zA-Z_][a-zA-Z0-9_]*)((?:\s+[a-zA-Z_][a-zA-Z0-9_]*="[^"]*")*)\s*\/>/',
            function($m) use ($self, $login, $depth) { return $self->expandComponent($m, $login, $depth); },
            $output
        );
    }

    public function expandComponent($m, $login, $depth = 0)
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

        $xml = call_user_func_array(array($def['class'], $def['method']), $args);

        return $this->expandComponents((string)$xml, $login, $depth + 1);
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
        if ($this->scripts instanceof Script) {
            $this->widgetScript->setParam("dDeclares", $this->scripts->getDeclarationScript() . $this->scripts->getEndScript());
            $this->widgetScript->setParam("scriptLib", $this->scripts->getlibScript());
            $this->widgetScript->setParam("wLoop", $this->scripts->getWhileLoopScript());
        } else {
            $this->widgetScript->setParam("dDeclares", "");
            $this->widgetScript->setParam("scriptLib", "");
            $this->widgetScript->setParam("wLoop", "");
        }

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

    public function show($login = null, $persistant = false, $timeout = 0)
    {
        $xml = $this->getWidget($login);
        if ($this->name == "XXX") {
            echo preg_replace('/<script.*?>.*?<\/script>/is', '', $xml);
        }
        if ($login !== null) {
            if (is_array($login)) {
                // check if login exists in $this->storage->players and $this->storage->spectators
                $connected = array();
                foreach ($login as $l) {
                    if (isset($this->storage->players[$l]) || isset($this->storage->spectators[$l])) {
                        $connected[] = strval($l);
                    }
                }
                if (count($connected) == 0) {
                    return;
                }
                $login = $connected;
            } else {
                if (!isset($this->storage->players[$login]) && !isset($this->storage->spectators[$login])) {
                    return;
                }
                $login = strval($login);
            }
            try {
                $this->connection->sendDisplayManialinkPage($login, $xml, $timeout, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                if (is_array($login)) {
                    Helper::log('Cannot send manialink: "' . $this->name . '" to players, retrying each login individually, server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));

                    foreach ($login as $l) {
                        try {
                            $this->connection->sendDisplayManialinkPage($l, $xml, $timeout, false, false);
                        } catch (\Exception $e2) {
                            Helper::log('Cannot send manialink: "' . $this->name . '" to player: "' . $l . '" , server said: ' . $e2->getMessage(), array("Gui", "ManiaLink"));
                        }
                    }
                } else {
                    Helper::log('Cannot send manialink: "' . $this->name . '" to player: "' . $login . '" , server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));
                }
            }
        } else {
            $this->connection->sendDisplayManialinkPage(null, $xml, $timeout, false, true);
        }

        if ($persistant && $login == null) {
            Gui::$persistentWidgets[$this->getWidgetHashName()] = $xml;
        }
    }

    public function erase($login = null)
    {
        if ($login !== null) {
            if (is_array($login)) {
                // check if login exists in $this->storage->players and $this->storage->spectators
                $connected = array();
                foreach ($login as $l) {
                    if (isset($this->storage->players[$l]) || isset($this->storage->spectators[$l])) {
                        $connected[] = strval($l);
                    }
                }
                if (count($connected) == 0) {
                    return;
                }
                $login = $connected;
            } else {
                if (!isset($this->storage->players[$login]) && !isset($this->storage->spectators[$login])) {
                    return;
                }
                $login = strval($login);
            }
            try {
                $this->connection->sendDisplayManialinkPage($login, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                if (is_array($login)) {
                    Helper::log('Cannot erase player manialink: "' . $this->name . '" to players, retrying each login individually, server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));

                    foreach ($login as $l) {
                        try {
                            $this->connection->sendDisplayManialinkPage($l, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, false);
                        } catch (\Exception $e2) {
                            Helper::log('Cannot erase player manialink: "' . $this->name . '" to player: "' . $l . '" , server said: ' . $e2->getMessage(), array("Gui", "ManiaLink"));
                        }
                    }
                } else {
                    Helper::log('Cannot erase player manialink: "' . $this->name . '" to player: "' . $login . '" , server said: ' . $e->getMessage(), array("Gui", "ManiaLink"));
                }
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
