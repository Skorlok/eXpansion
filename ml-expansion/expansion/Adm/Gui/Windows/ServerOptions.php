<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

use Exception;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Config;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted as Checkbox;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Structures\ServerOptions as Dedicated_ServerOptions;

class ServerOptions extends Window
{
    protected $cbPublicServer;
    protected $cbLadderServer;
    protected $cbAllowMapDl;
    protected $cbAllowp2pDown;
    protected $cbAllowp2pUp;
    protected $cbReferee;

    protected $frameCb;

    protected $buttonOK;

    /** @var Connection */
    protected $connection;

    protected $actionOK;

    protected $e = array();

    public function onConstruct()
    {
        parent::onConstruct();
        $config = Config::getInstance();
        $this->connection = Connection::factory($config->host, $config->port);
        $this->actionOK = $this->createAction(array($this, "serverOptionsOk"));

        $this->setTitle(__('Server Options', $this->getRecipient()));

        $this->inputboxes();
        $this->checkboxes();

        $this->registerScript(new Script("Adm/Gui/Scripts"));
        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());

        $this->addComponent($this->frameCb);
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    // Generate all inputboxes
    private function inputboxes()
    {
        $login = $this->getRecipient();

        /** @var Dedicated_ServerOptions @server */
        $server = $this->connection->getServerOptions();

        $content = '<frame posn="0 -6 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("serverName", 152, AdminGroups::hasPermission($login, Permission::SERVER_NAME), __("Server Name", $this->getRecipient()), $this->handleSpecialChars($this->connection->getServerName()), null, null) . '</frame>';
        $content .= '<textedit id="commentFrom" posn="0 -3 2.0E-5" sizen="96 32" scale="0.75" scriptevents="1" default="' . $this->handleSpecialChars($this->connection->getServerComment()) . '" textformat="default" name="serverCommentE" showlinenumbers="0" autonewline="0"/>';
        $content .= '<frame posn="900 900 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("serverComment", 60, AdminGroups::hasPermission($login, Permission::SERVER_COMMENT), null, null, null, null) . '</frame>';
        $content .= '<frame posn="0 -36 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("maxPlayers", 12, AdminGroups::hasPermission($login, Permission::SERVER_MAXPLAYER), __("Players", $this->getRecipient()), $server->nextMaxPlayers, null, null) . '</frame>';
        $content .= '<frame posn="15 -36 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("maxSpec", 12, AdminGroups::hasPermission($login, Permission::SERVER_MAXSPEC), __("Spectators", $this->getRecipient()), $server->nextMaxSpectators, null, null) . '</frame>';
        $content .= '<frame posn="0 -48 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("ladderMin", 35, false, __("Ladderpoints minimum", $this->getRecipient()), $server->ladderServerLimitMin, null, null) . '</frame>';
        $content .= '<frame posn="38 -48 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("ladderMax", 35, false, __("Ladderpoints Maximum", $this->getRecipient()), $server->ladderServerLimitMax, null, null) . '</frame>';
        $content .= '<frame posn="0 -60 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\InputboxMasked::getXML($this, "serverPass", 76, AdminGroups::hasPermission($login, Permission::SERVER_PASSWORD), __("Password for server", $this->getRecipient()), $this->handleSpecialChars($this->connection->getServerPassword()), true, null, null) . '</frame>';
        $content .= '<frame posn="0 -73 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\InputboxMasked::getXML($this, "serverSpecPass", 76, AdminGroups::hasPermission($login, Permission::SERVER_SPECPWD), __("Password for spectators", $this->getRecipient()), $this->handleSpecialChars($this->connection->getServerPasswordForSpectator()), true, null, null) . '</frame>';
        $content .= '<frame posn="0 -86 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\InputboxMasked::getXML($this, "refereePass", 76, AdminGroups::hasPermission($login, Permission::SERVER_REFPWD), __("Referee password", $this->getRecipient()), $this->handleSpecialChars($this->connection->getRefereePassword()), true, null, null) . '</frame>';
        $content .= '</frame>';

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent($content);
        $this->addComponent($input);
    }

    // Generate all checkboxes
    private function checkboxes()
    {
        /** @var ServerOptions2 */
        $server = $this->connection->getServerOptions();
        $login = $this->getRecipient();

        $this->frameCb = new Frame();
        $this->frameCb->setAlign("left", "top");
        $this->frameCb->setLayout(new Column());

        // checkbox for public server
        $publicServer = true;
        if ($server->hideServer > 0) {
            $publicServer = false;  // 0 = visible, 1 = hidden 2 = hidden from nations
        }
        $this->cbPublicServer = new Checkbox(4, 4, 50);
        $this->cbPublicServer->setStatus($publicServer);
        $this->cbPublicServer->setText(__("Show Server in public server list", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbPublicServer);

        // checkbox for ladder server
        $this->cbLadderServer = new Checkbox();
        $this->cbLadderServer->setStatus($server->currentLadderMode);
        $this->cbLadderServer->setText(__("Ladder server", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbLadderServer);

        // checkbox for allow map download
        $this->cbAllowMapDl = new Checkbox(4, 4, 50);
        $this->cbAllowMapDl->setStatus($server->allowMapDownload);
        $this->cbAllowMapDl->setText(__("Allow map download using ingame menu", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbAllowMapDl);

        // checkbox for p2p download
        $this->cbAllowp2pDown = new Checkbox(4, 4, 50);
        $this->cbAllowp2pDown->setStatus($server->isP2PDownload);
        $this->cbAllowp2pDown->setText(__("Allow Peer-2-Peer download", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbAllowp2pDown);

        // checkbox for p2p upload
        $this->cbAllowp2pUp = new Checkbox(4, 4, 50);
        $this->cbAllowp2pUp->setStatus($server->isP2PUpload);
        $this->cbAllowp2pUp->setText(__("Allow Peer-2-Peer upload", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbAllowp2pUp);

        // checkbox for Enable referee mode
        $this->cbReferee = new Checkbox(4, 4, 50);
        $this->cbReferee->setStatus($server->refereeMode);
        $this->cbReferee->setText(__("Enable Referee-mode", $this->getRecipient()));
        $this->frameCb->addComponent($this->cbReferee);

        $this->e['DisableHorns'] = new Checkbox(4, 4, 50);
        $this->e['DisableHorns']->setStatus($server->disableHorns);
        $this->e['DisableHorns']->setText(__("Disable Horns", $login));
        $this->frameCb->addComponent($this->e['DisableHorns']);

        $this->e['DisableAnnounces'] = new Checkbox(4, 4, 50);
        $this->e['DisableAnnounces']->setStatus($server->disableServiceAnnounces);
        $this->e['DisableAnnounces']->setText(__("Disable Announces", $login));
        $this->frameCb->addComponent($this->e['DisableAnnounces']);

        $this->e['AutosaveReplays'] = new Checkbox(4, 4, 50);
        $this->e['AutosaveReplays']->setStatus($server->autoSaveReplays);
        $this->e['AutosaveReplays']->setText(__("Autosave All Replays", $login));
        $this->frameCb->addComponent($this->e['AutosaveReplays']);

        $this->e['AutosaveValidation'] = new Checkbox(4, 4, 50);
        $this->e['AutosaveValidation']->setStatus($server->autoSaveValidationReplays);
        $this->e['AutosaveValidation']->setText(__("Autosave Validation Replays", $login));
        $this->frameCb->addComponent($this->e['AutosaveValidation']);

        $this->e['KeepPlayerSlots'] = new Checkbox(4, 4, 50);
        $this->e['KeepPlayerSlots']->setStatus($server->keepPlayerSlots);
        $this->e['KeepPlayerSlots']->setText(__("Keep Player Slots", $login));
        $this->frameCb->addComponent($this->e['KeepPlayerSlots']);

        $this->buttonOK = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonOK->setContent('<frame posn="126 -100 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $this->getRecipient()), null, null, null, null, null, $this->actionOK, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->buttonOK);
    }

    protected function onDraw()
    {
        $login = $this->getRecipient();

        $this->cbPublicServer->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));
        $this->cbLadderServer->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));
        $this->cbAllowMapDl->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));
        $this->cbAllowp2pDown->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));
        $this->cbAllowp2pUp->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));
        $this->cbReferee->SetIsWorking(AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS));

        parent::onDraw();
    }

    public function destroy()
    {
        $this->connection = null;
        parent::destroy();
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frameCb->setPosition($this->sizeX / 2 + 20, -25);
    }

    public function serverOptionsOk($login, $args)
    {

        $server = Storage::getInstance()->server;

        foreach ($this->frameCb->getComponents() as $component) {
            if ($component instanceof Checkbox) {
                $component->setArgs($args);
            }
        }

        $serverOptions = array(
            "Name" => !AdminGroups::hasPermission($login, Permission::SERVER_NAME)
                ? $server->name : $args['serverName'],
            "Comment" => !AdminGroups::hasPermission($login, Permission::SERVER_COMMENT)
                ? $server->comment : $args['serverComment'],
            "Password" => !AdminGroups::hasPermission($login, Permission::SERVER_PASSWORD)
                ? $server->password : $args['serverPass'],
            "PasswordForSpectator" => !AdminGroups::hasPermission($login, Permission::SERVER_SPECPWD)
                ? $server->passwordForSpectator : $args['serverSpecPass'],
            "NextCallVoteTimeOut" => !AdminGroups::hasPermission($login, Permission::SERVER_VOTES)
                ? $server->nextCallVoteTimeOut : intval($server->nextCallVoteTimeOut),
            "CallVoteRatio" => !AdminGroups::hasPermission($login, Permission::SERVER_VOTES)
                ? $server->callVoteRatio : floatval($server->callVoteRatio),
            "RefereePassword" => !AdminGroups::hasPermission($login, Permission::SERVER_REFPWD)
                ? $server->refereePassword : $args['refereePass'],
            "IsP2PUpload" => !AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS)
                ? $server->isP2PUpload : $this->cbAllowp2pUp->getStatus(),
            "IsP2PDownload" => !AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS)
                ? $server->isP2PDownload : $this->cbAllowp2pDown->getStatus(),
            "AllowMapDownload" => !AdminGroups::hasPermission($login, Permission::SERVER_GENERIC_OPTIONS)
                ? $server->allowMapDownload : $this->cbAllowMapDl->getStatus(),
            "NextMaxPlayers" => !AdminGroups::hasPermission($login, Permission::SERVER_MAXPLAYER)
                ? $server->nextMaxPlayers : intval($args['maxPlayers']),
            "NextMaxSpectators" => !AdminGroups::hasPermission($login, Permission::SERVER_MAXSPEC)
                ? $server->nextMaxSpectators : intval($args['maxSpec']),
            "RefereeMode" => !AdminGroups::hasPermission($login, 'server_refmode')
                ? $server->refereeMode : $this->cbReferee->getStatus(),
            "AutoSaveReplays" => $this->e['AutosaveReplays']->getStatus(),
            "AutoSaveValidationReplays" => $this->e['AutosaveValidation']->getStatus(),
            "DisableHorns" => $this->e['DisableHorns']->getStatus(),
            "DisableServiceAnnounces" => $this->e['DisableAnnounces']->getStatus(),
            "KeepPlayerSlots" => $this->e['KeepPlayerSlots']->getStatus(),
        );

        try {
            $this->connection->setServerOptions(Dedicated_ServerOptions::fromArray($serverOptions));
            $this->connection->keepPlayerSlots($this->e['KeepPlayerSlots']->getStatus());

            if (AdminGroups::hasPermission($login, Permission::SERVER_MAXPLAYER)) {
                $this->connection->setMaxPlayers(intval($args['maxPlayers']));
            }

            if (AdminGroups::hasPermission($login, Permission::SERVER_MAXSPEC)) {
                $this->connection->setMaxSpectators(intval($args['maxSpec']));
            }
        } catch (Exception $e) {
            $this->connection->chatSendServerMessage("Error: " . $e->getMessage());
            $this->connection->chatSendServerMessage(__("Settings not changed.", $login));
        }

        $this->Erase($this->getRecipient());
    }
}
