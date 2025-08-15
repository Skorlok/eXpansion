<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\ServerStatistics\Gui\Controls\InfoLine;
use ManiaLivePlugins\eXpansion\Core\Config as CoreConfig;

class InfoWindow extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    public static $statsAction = -1;

    protected $frame;
    protected $stats;
    protected $button_addFav;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var \ManiaLive\Data\Storage */
    protected $storage;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setScale(0.8);
        $this->frame->setPosY(2);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Column(120, 30));
        $this->mainFrame->addComponent($this->frame);
        $version = $this->connection->getVersion();

        $line = new Infoline(5, "Server Login", $this->storage->serverLogin, 0);
        $this->frame->addComponent($line);

        $line = new Infoline(5, "Server version", $version->version, 0);
        $this->frame->addComponent($line);
        $line = new Infoline(5, "Server Build", $version->build, 0);
        $this->frame->addComponent($line);
        $line = new Infoline(5, "Server ApiVersio", $version->apiVersion, 0);
        $this->frame->addComponent($line);

        $line = new Infoline(5, "Server Titlepack", $version->titleId, 0);
        $this->frame->addComponent($line);

        $line = new Infoline(5, "Manialive version", \ManiaLive\Application\VERSION, 0);
        $this->frame->addComponent($line);

        $line = new Infoline(5, "eXpansion version", \ManiaLivePlugins\eXpansion\Core\Core::EXP_VERSION . " - " . (date("Y-m-d h:i:s A", Helper::getBuildDate())), 0);
        $this->frame->addComponent($line);

        $line = new Infoline(5, "Php Version", phpversion(), 0);
        $this->frame->addComponent($line);

        $admContact = CoreConfig::getInstance()->contact;
        if (strpos($admContact, '@') != false) {
            $admContact = '$lmailto://' . $admContact;
        } else {
            $admContact = '$l' . $admContact;
        }
        $line = new Infoline(5, "Admin contact", $admContact, 0);
        $this->frame->addComponent($line);

        $this->frame->addComponent(new \ManiaLib\Gui\Elements\Label(10, 7));

        $line = new Inputbox("join", 77);
        $line->setScale(1.2);
        $line->setLabel("Join link:");
        $line->setText("maniaplanet://#join=" . $this->storage->serverLogin . "@" . $version->titleId);
        $this->frame->addComponent($line);

        $line = new Inputbox("fav", 77);
        $line->setScale(1.2);
        $line->setLabel("Favourite link:");
        $line->setText("maniaplanet://#addfavourite=" . $this->storage->serverLogin . "@" . $version->titleId);
        $this->frame->addComponent($line);

        if (self::$statsAction > 0) {
            $this->stats = new \ManiaLive\Gui\Elements\Xml();
            $this->stats->setContent('<frame posn="78 -69 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(30, 6, __("Server Stats", $login), null, null, null, null, null, self::$statsAction, null, null, null, null, null, null) . '</frame>');
            $this->mainFrame->addComponent($this->stats);
        }

        $this->button_addFav = new \ManiaLive\Gui\Elements\Xml();
        $this->button_addFav->setContent('<frame posn="80 -60.5 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(20, 6, __("Add to Fav's", $login), null, null, null, null, null, null, ('http://reaby.kapsi.fi/ml/addfavourite.php?login=' . rawurldecode($this->storage->serverLogin)), null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->button_addFav);
    }

    public function destroy()
    {
        $this->connection = null;
        $this->storage = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
