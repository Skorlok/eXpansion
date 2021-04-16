<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Widgets;

use ManiaLivePlugins\eXpansion\ManiaExchange\Config;

class MxWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{

    /**
     * @var \Maniaplanet\DedicatedServer\Connection
     */
    protected $connection;

    /** @var \ManiaLive\Data\Storage */
    protected $storage;
    protected $_windowFrame;
    protected $_mainWindow;
    protected $_minButton;
    protected $servername;
    protected $btnVisit;
    protected $btnAward;
    protected $actionVisit;
    protected $actionAward;

    protected function eXpOnBeginConstruct()
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();
        if ($storage->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }
        $this->setName("ManiaExchange Panel");

        $login = $this->getRecipient();

        $dedicatedConfig = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \Maniaplanet\DedicatedServer\Connection::factory(
            $dedicatedConfig->host,
            $dedicatedConfig->port
        );

        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->actionVisit = $this->createAction(array($this, 'Visit'));
        $this->actionAward = $this->createAction(array($this, 'Award'));

        $this->_windowFrame = new \ManiaLive\Gui\Controls\Frame();
        $this->_windowFrame->setAlign("left", "top");
        $this->_windowFrame->setId("Frame");
        $this->_windowFrame->setScriptEvents(true);

        $this->_mainWindow = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround(37, 6);
        $this->_mainWindow->setId("MainWindow");
        $this->_windowFrame->addComponent($this->_mainWindow);

        $frame = new \ManiaLive\Gui\Controls\Frame(5, -3);
        $frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->btnVisit = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(6, 6);
        $this->btnVisit->setIcon("Icons64x64_1", "TrackInfo");
        $this->btnVisit->setDescription("Visit the maps Mania-exchange page", 80);
        $this->btnVisit->setAction($this->actionVisit);
        $frame->addComponent($this->btnVisit);

        $this->btnAward = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(6, 6);
        $this->btnAward->setIcon("Icons64x64_1", "OfficialRace");
        $this->btnAward->setDescription("Grant a Mania-exchange award to this map", 80);
        $this->btnAward->setAction($this->actionAward);
        $frame->addComponent($this->btnAward);

        $this->_windowFrame->addComponent($frame);

        $this->_minButton = new \ManiaLib\Gui\Elements\Quad(5, 5);
        $this->_minButton->setScriptEvents(true);
        $this->_minButton->setId("minimizeButton");
        $this->_minButton->setAlign("left", "top");
        $this->_minButton->setPosition(35 - 4, 0);
        $this->_windowFrame->addComponent($this->_minButton);
        $this->addComponent($this->_windowFrame);

        $this->setScriptEvents(true);
    }

    protected function eXpOnEndConstruct()
    {
        $this->setSize(35, 7);
    }

    protected function eXpOnSettingsLoaded()
    {
        $config = Config::getInstance();
        $this->_minButton->setImage($config->iconMx, true);
        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui\Scripts\TrayWidget");
        $script->setParam('isMinimized', 'True');
        $script->setParam('posXMin', -27);
        $script->setParam('posX', -27);
        $script->setParam('posXMax', -4);
        $script->setParam('autoCloseTimeout', $this->getParameter('autoCloseTimeout'));
        $this->registerScript($script);
    }

    public function Visit($login)
    {
        $mxId = $this->getMXid($login);
        if ($mxId === false) {
            return;
        }

        $link = "http://tm.mania-exchange.com/tracks/view/" . $mxId;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function Award($login)
    {
        $mxId = $this->getMXid($login);
        if ($mxId === false) {
            return;
        }
        $link = "http://tm.mania-exchange.com/awards/add/" . $mxId;
        $this->connection->sendOpenLink($login, $link, 0);
    }

    public function getMXid($login)
    {
        $query = "http://api.mania-exchange.com/tm/tracks/" . $this->storage->currentMap->uId;

        $ch = curl_init($query);
        curl_setopt($ch, CURLOPT_USERAGENT, "Manialive/eXpansion MXapi [getter] ver 0.1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        if ($data === false) {
            $this->connection->chatSendServerMessage('Error receving data from ManiaExchange!');

            return false;
        }

        if ($status["http_code"] !== 200) {
            if ($status["http_code"] == 301) {
                $this->connection->chatSendServerMessage('Map not found from ManiaExchange', $login);

                return false;
            }

            $this->connection->chatSendServerMessage(
                sprintf('MX returned http error code: %s', $status["http_code"]),
                $login
            );

            return false;
        }

        $json = \json_decode($data);
        if ($json === false || sizeof($json) == 0) {
            $this->connection->chatSendServerMessage('Map not found from ManiaExchange', $login);

            return false;
        }

        return $json[0]->TrackID;
    }
}
