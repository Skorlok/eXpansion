<?php

namespace ManiaLivePlugins\eXpansion\Notifications\Gui\Controls;

class ItemPlugin extends \ManiaLivePlugins\eXpansion\Gui\Control
{

    /** @var \ManiaLive\Gui\Elements\Xml */
    protected $checkbox;

    /** @var string */
    public $cbName;

    /** @var string */
    protected $cbText;

    public $pluginId;

    public function __construct($pluginId, \ManiaLivePlugins\eXpansion\Core\types\config\MetaData $meta)
    {
        $this->sizeX = 100;
        $this->sizeY = 6;
        $this->setAlign("left", "top");

        $this->pluginId = $pluginId;
        $this->cbName = 'cb_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $pluginId);
        $this->cbText = $this->handleSpecialChars($meta->getName());

        $this->checkbox = new \ManiaLive\Gui\Elements\Xml();
        $this->setStatus(false);
        $this->addComponent($this->checkbox);
    }

    public function setStatus($boolean)
    {
        $this->checkbox->setContent(
            '<frame posn="0 0 1">' .
            \ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted::getXML($this->cbName, $boolean, 60, true, $this->cbText) .
            '</frame>'
        );
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    public function destroy()
    {
        parent::destroy();
    }
}
