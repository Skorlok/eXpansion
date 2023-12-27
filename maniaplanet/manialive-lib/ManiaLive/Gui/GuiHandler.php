<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Gui;

use ManiaLib\Gui\Elements\Bgs1;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Data\Event as PlayerEvent;
use ManiaLive\Data\Listener as PlayerListener;
use ManiaLive\Data\Storage;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Gui\Windows\Info;
use ManiaLive\Gui\Windows\Thumbnail;
use ManiaLive\Utilities\Console;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Structures\Status;
use Maniaplanet\DedicatedServer\Xmlrpc\GbxRemote;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Description of GuiHandler
 */
final class GuiHandler extends \ManiaLib\Utils\Singleton implements AppListener, PlayerListener, ServerListener
{
    const MAX_THUMBNAILS = 5;
    const NEXT_IS_MODAL = 0xFA15EADD;

    /**
     * @var Connection
     */
    private $connection;
    private $hidingGui = array();
    private $modals = array();
    private $modalsRecipients = array();
    private $modalShown = array();
    private $managedWindow = array();
    private $thumbnails = array();
    private $currentWindows = array();
    private $nextWindows = array();
    private $modalBg;
    /** @var  Group */
    private $groupAll;
    /** @var  Group */
    private $groupPlayers;
    /** @var  Group */
    private $groupSpectators;

    private $nextLoop;
    // Profiling
    private $sendingTimes = array();
    private $averageSendingTimes;

    protected function __construct()
    {
        unlink("./WINDOWS.xml");
        $this->modalBg = new Bgs1(340, 200);
        $this->modalBg->setSubStyle(Bgs1::BgDialogBlur);
        $this->modalBg->setAlign('center', 'center');
        $this->modalBg->setPosZ(Window::Z_MODAL);
        $this->modalBg->setScriptEvents();
        $this->nextLoop = microtime(true);
        Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
        Dispatcher::register(PlayerEvent::getClass(), $this, PlayerEvent::ON_PLAYER_CHANGE_SIDE);
        Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ALL & ~AppEvent::ON_POST_LOOP);

        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = Connection::factory($config->host, $config->port, $config->timeout, $config->user, $config->password);
    }

    function getAverageSendingTimes()
    {
        return $this->averageSendingTimes;
    }

    function addToShow(Window $window, $recipients)
    {
        $windowId = $window->getId();

        if ($window instanceof ManagedWindow) {
            if ($this->managedWindow[$recipients[0]] && $this->managedWindow[$recipients[0]] !== $window && !$this->sendToTaskbar($recipients[0])) {
                return;
            }
            $this->managedWindow[$recipients[0]] = $window;
            if (($thumbnail = $this->getThumbnail($window))) {
                $thumbnail->hide();
            }
        }

        foreach ($recipients as $login) {
            if (isset($this->nextWindows[$windowId])) {
                $this->nextWindows[$windowId][$login] = $window;
            } else {
                $this->nextWindows[$windowId] = array($login => $window);
            }
        }
    }

    function addToHide(Window $window, $recipients)
    {
        $windowId = $window->getId();

        if ($window instanceof ManagedWindow && $this->managedWindow[$recipients[0]] === $window) {
            $this->managedWindow[$recipients[0]] = null;
        }

        if (isset($this->currentWindows[$windowId])) {
            foreach ($recipients as $login) {
                if (isset($this->currentWindows[$windowId][$login])) {
                    if (isset($this->nextWindows[$windowId])) {
                        $this->nextWindows[$windowId][$login] = false;
                    } else {
                        $this->nextWindows[$windowId] = array($login => false);
                    }
                } else {
                    if (isset($this->nextWindows[$windowId])) {
                        unset($this->nextWindows[$windowId][$login]);
                        if (!$this->nextWindows[$windowId]) {
                            unset($this->nextWindows[$windowId]);
                        }
                    }
                }
            }
        } else {
            if (isset($this->modalsRecipients[$windowId])) {
                foreach ($recipients as $login) {
                    if (isset($this->modalShown[$login]) && $this->modalShown[$login] === $window) {
                        if (isset($this->nextWindows[$windowId])) {
                            $this->nextWindows[$windowId][$login] = false;
                        } else {
                            $this->nextWindows[$windowId] = array($login => false);
                        }
                    }
                }
            } else {
                unset($this->nextWindows[$windowId]);
            }
        }


    }

    function addToRedraw(Window $window, $recipients)
    {
        $windowId = $window->getId();

        if ($window instanceof ManagedWindow && ($thumbnail = $this->getThumbnail($window))) {
            $thumbnail->enableHighlight();
        } else {
            if (isset($this->currentWindows[$windowId])) {
                foreach ($recipients as $login) {
                    if (isset($this->currentWindows[$windowId][$login])) {
                        if (isset($this->nextWindows[$windowId])) {
                            if (!isset($this->nextWindows[$windowId][$login])) {
                                $this->nextWindows[$windowId][$login] = $window;
                            }
                        } else {
                            $this->nextWindows[$windowId] = array($login => $window);
                        }
                    }
                }
            }
        }
    }

    private function parse($array)
    {
        if (is_array($array)) {
            return implode(",", $array);
        }
        return $array;
    }

    function sendToTaskbar($login)
    {
        $window = $this->managedWindow[$login];
        // seeking an empty place in the player taskbar
        $taskbarIndex = 0;
        $freePlaceFound = false;
        foreach ($this->thumbnails[$login] as $taskbarIndex => $placedThumbnail) {
            if (!$placedThumbnail) {
                $freePlaceFound = true;
                break;
            }
        }
        if (!$freePlaceFound) {
            if ($taskbarIndex == self::MAX_THUMBNAILS - 1) {
                $info = Info::Create($login, false);
                $info->setSize(40, 25);
                $info->setTitle('Too many Windows!');
                $info->setText("You are in the process of minimizing another window ...\n" .
                    "Due to restricted resources you have reached the limit of allowed concurrent displayable minimized windows.\n" .
                    "Please close some old windows in order to be able to open and minimize new ones.");
                $this->addModal($info);
                return false;
            } else {
                $taskbarIndex = count($this->thumbnails[$login]);
            }
        }

        // create the thumbnail
        $thumbnail = Thumbnail::Create($login, false, $window);
        $this->thumbnails[$login][$taskbarIndex] = $thumbnail;
        $thumbnail->setSize(30, 26);
        $thumbnail->setPosition(80 - 31 * $taskbarIndex, 85);
        $thumbnail->addCloseCallback(array($this, 'onThumbnailClosed'));
        $thumbnail->show();
        $window->hide();
        $this->managedWindow[$login] = null;

        return true;
    }

    function onThumbnailClosed($login, Thumbnail $thumbnail)
    {
        $taskbarIndex = array_search($thumbnail, $this->thumbnails[$login], true);
        if ($taskbarIndex !== false) {
            $this->thumbnails[$login][$taskbarIndex] = false;
        }
        $thumbnail->destroy();
    }

    private function getNextModal($login)
    {
        if ($this->modalShown[$login]) {
            return null;
        }
        return array_shift($this->modals[$login]);
    }

    function addModal(Window $modal, $recipients)
    {
        foreach ($recipients as $login) {
            if (isset($this->modals[$login]) && !isset($this->modalsRecipients[$modal->getId()][$login])) {
                $this->modals[$login][] = $modal;
                $this->modalsRecipients[$modal->getId()][$login] = true;
            }
        }

        $modal->addCloseCallback(array($this, 'onModalClosed'));
    }

    function onModalClosed($login, Window $window)
    {
        $windowId = $window->getId();
        unset($this->modalsRecipients[$windowId][$login]);
        if (empty($this->modalsRecipients[$windowId])) {
            $window->destroy();
            unset($this->modalsRecipients[$windowId]);
        }
        $this->modalShown[$login] = null;
    }

    function getThumbnail(ManagedWindow $window)
    {
        $login = $window->getRecipient();
        if (isset($this->thumbnails[$login])) {
            foreach ($this->thumbnails[$login] as $thumbnail) {
                if ($thumbnail && $thumbnail->getWindow() === $window) {
                    return $thumbnail;
                }
            }
        }
        return null;
    }

    // Application Listener

    function onInit()
    {
        if (Storage::getInstance()->serverStatus->code > Status::LAUNCHING) {
            $this->connection->sendHideManialinkPage();
        }

        $this->groupAll = Group::Create('all');
        $this->groupPlayers = Group::Create('players');
        $this->groupSpectators = Group::Create('spectators');

        foreach (Storage::getInstance()->players as $login => $player) {
            $this->onPlayerConnect($player->login, false);
        }

        foreach (Storage::getInstance()->spectators as $login => $spectator) {
            $this->onPlayerConnect($spectator->login, true);
        }
    }

    function onRun()
    {

        foreach (Storage::getInstance()->players as $login => $player) {
            $this->onPlayerConnect($player->login, false);
        }

        foreach (Storage::getInstance()->spectators as $login => $spectator) {
            $this->onPlayerConnect($spectator->login, true);
        }

    }

    function onPreLoop()
    {

        // If server is stopped, we don't need to send manialinks

        /* if (Storage::getInstance()->serverStatus->code <= Status::LAUNCHING) {
             echo "not processing since server stopped state...\n";
             return;
         } */

        // Before loops (stopping if too soon)

        $startTime = microtime(true);
        if ($startTime < $this->nextLoop) {
            return;
        }

        $stackByPlayer = array();
        $playersOnServer = array_merge(Storage::keys(Storage::getInstance()->players) , Storage::keys(Storage::getInstance()->spectators));
        $playersHidingGui = Storage::keys(array_filter($this->hidingGui));
        $playersShowingGui = array_intersect(array_diff(Storage::keys($this->hidingGui), $playersHidingGui), $playersOnServer);

        // First loop to prepare player stacks
        foreach ($this->nextWindows as $windowId => $visibilityByLogin) {
            $showing = array_intersect(array_diff(Storage::keys(array_filter($visibilityByLogin)), $playersHidingGui), $playersOnServer);
            $hiding = array_intersect(array_diff(Storage::keys($visibilityByLogin), $showing, $playersHidingGui), $playersOnServer);
            if (count($showing)) {
                // sort($showing); // disabled sorting, unknown effects with mixed values
                $stackByPlayer[implode(',', $showing)][] = $visibilityByLogin[reset($showing)];
            }
            if (count($hiding)) {
                // sort($hiding); // disabled sorting, unknown effects with mixed values
                $stackByPlayer[implode(',', $hiding)][] = $windowId;
            }
        }

        // Second loop to add modals and regroup identical custom UIs
        $loginsByDiff = array();
        $customUIsByDiff = array();
        foreach ($playersShowingGui as $login) {
            $modal = $this->getNextModal($login);
            if ($modal) {
                $stackByPlayer[$login][] = self::NEXT_IS_MODAL;
                $stackByPlayer[$login][] = $modal;
                $this->modalShown[$login] = $modal;
            }

            $customUI = CustomUI::Create($login);
            $diff = $customUI->getDiff();
            if ($diff) {
                $loginsByDiff[$diff][] = $login;
                $customUIsByDiff[$diff][] = $customUI;
            }
        }

        // Third loop to add custom UIs
        $outlogins = "";
        foreach ($loginsByDiff as $diff => $logins) {
            $stackByPlayer[implode(',', $logins)][] = $customUIsByDiff[$diff];
        }


        try {
            $this->doWindowSend($stackByPlayer);
        } catch (\Exception $ex) {
            echo "[GuiHandler] error while sending windows: " . $ex->getMessage();
        }

        // Merging windows and deleting hidden ones to keep clean the current state
        foreach ($this->nextWindows as $windowId => $visibilityByLogin) {

            // this unfortunately needs to be done manually...
            if (isset($this->currentWindows[$windowId])) {
                // array_merge, replaced with + operator -> to preserve numeric array keys
                $newCurrent = $this->currentWindows[$windowId] + $visibilityByLogin;

                // array_merge would override the visibility by login value with the later value
                foreach ($visibilityByLogin as $key => $value) {
                    if (isset($newCurrent[$key])) {
                        $newCurrent[$key] = $value;
                    }
                }
                $newCurrent = array_filter($newCurrent);
            } else {
                $newCurrent = array_filter($visibilityByLogin);
            }

            if ($newCurrent) {
                $this->currentWindows[$windowId] = $newCurrent;
            } else {
                unset($this->currentWindows[$windowId]);
            }
        }

        $this->nextWindows = array();

        // After loops
        $endTime = microtime(true);
        do {
            $this->nextLoop += 0.2;
        } while ($this->nextLoop < $endTime);


        // Profiling
        $this->sendingTimes[] = $endTime - $startTime;
        if (count($this->sendingTimes) >= 10) {
            $this->averageSendingTimes = array_sum($this->sendingTimes) / count($this->sendingTimes);
            $this->sendingTimes = array();
        }

    }

    private function prepareWindows($stackByPlayer)
    {
        $limit = (GbxRemote::MAX_REQUEST_SIZE / 6) - 16384;

        $groups = array();

        foreach ($stackByPlayer as $login => $data) {
            $nextIsModal = false;
            foreach ($data as $toDraw) {
                Manialinks::load();

                if ($nextIsModal) // this element can't be anything else than a window
                {
                    $this->drawModal($toDraw);
                    $nextIsModal = false;
                } else {
                    if ($toDraw === self::NEXT_IS_MODAL) // special delimiter for modals
                    {
                        $nextIsModal = true;
                    } else {
                        if (is_string($toDraw)) // a window's id alone means it has to be hidden
                        {
                            $this->drawHidden($toDraw);
                        } else {
                            if (is_array($toDraw)) // custom ui's special case
                            {
                                array_shift($toDraw)->save();
                                foreach ($toDraw as $customUI) {
                                    $customUI->hasBeenSaved();
                                }
                            } else // else it can only be a window to show
                            {
                                $this->drawWindow($toDraw);
                            }
                        }
                    }
                }

                $xml = Manialinks::getXml();
                $size = strlen($xml);

                if ($size > $limit) {
                    if ($toDraw instanceof Window) {
                        $title = $toDraw->getName();
                    } else {
                        $title = "-unknown-";
                    }
                    Console::println("To big windows($size) wasn't sent limit is $limit TITLE : $title");
                    \ManiaLive\Utilities\Logger::info("To big windows($size) wasn't sent limit is  $limit  TITLE : $title");
                } else {
                    $groups[$login][] = $this->fixXml($xml);
                }
            }
        }

        $optimizedGrouping = array();

        $totalSize = 0;
        $groupNum = 0;
        foreach ($groups as $login => $data) {
            $login = strval($login);
            $currentXml = '';
            foreach ($data as $xml) {
                $size = strlen($xml);

                if ($totalSize + $size > $limit) {
                    // If new size is to big then create a new group with current data and start queuing old data for next group.
                    $optimizedGrouping[$groupNum][$login] = $currentXml;
                    $groupNum++;
                    $currentXml = $xml;
                    $totalSize = $size;
                } else {
                    $currentXml .= "\n" . $xml;
                    $totalSize += $size;
                }
            }

            // Remaining data goes to current group.
            $optimizedGrouping[$groupNum][$login] = $currentXml;
        }

        return $optimizedGrouping;
    }

    /**
     * Not very clean solution.
     *
     * @param $xml
     * @return mixed
     */
    private function fixXml($xml)
    {
        return str_replace(array('<manialinks>', '</manialinks>'), '', $xml);
    }

    private function doWindowSend($stackByPlayer)
    {

        $grouped = $this->prepareWindows($stackByPlayer);

        foreach ($grouped as $groupNum => $groupData) {

            foreach ($groupData as $login => $toDraw) {
                file_put_contents("./WINDOWS.xml", \str_replace('><', ">\n<", $toDraw), FILE_APPEND);
                $this->connection->sendDisplayManialinkPage(strval($login), '<manialinks>' . $toDraw . '</manialinks>', 0, false, true);
            }

            try {
                $this->connection->executeMulticall();
            } catch (UnknownPlayerException $ex) {
                $this->log("[ManiaLive]Attempt to send Manialink to a login failed. Login unknown. Retrying each login individually !");

                // Send again this time without multiexec.
                foreach ($groupData as $login => $toDraw) {
                    $login = strval($login);
                    $logins = explode(",", $login);
                    foreach ($logins as $login) {
                        try {
                            $this->connection->sendDisplayManialinkPage($login, '<manialinks>' . $toDraw . '</manialinks>', 0, false, false);
                        } catch (UnknownPlayerException $ex) {
                            $this->log("[ManiaLive]Attempt to send Manialink to $login failed. Login unknown");
                        }
                    }
                }
            }
        }
    }

    private function drawWindow(Window $window)
    {
        if ($window instanceof ManagedWindow && $window->isMaximized()) {
            $window->setPosZ(Window::Z_MAXIMIZED);
        } else {
            $window->setPosZ($window->getMinZ());
        }

        Manialinks::beginManialink($window->getId(), 2, $window->getLayer(), false, $window->getName());
        $window->save();
        Manialinks::endManialink();
    }

    private function log($string)
    {
        \ManiaLive\Utilities\Console::println($string);
        \ManiaLive\Utilities\Logger::info($string);
    }

    private function drawModal(Window $window)
    {
        $window->setPosZ(Window::Z_MODAL + Window::Z_OFFSET);

        Manialinks::beginManialink($window->getId());
        $this->modalBg->save();
        $window->save();
        Manialinks::endManialink();
    }

    private function drawHidden($windowId)
    {
        Manialinks::beginManialink($windowId);
        Manialinks::endManialink();
    }

    function onPostLoop()
    {

    }

    function onTerminate()
    {
        if (Storage::getInstance()->serverStatus->code > Status::LAUNCHING) {
            $this->connection->sendHideManialinkPage();
        }
    }

    // Storage Listener

    function onPlayerNewBestTime($player, $oldBest, $newBest)
    {

    }

    function onPlayerNewRank($player, $oldRank, $newRank)
    {

    }

    function onPlayerNewBestScore($player, $oldScore, $newScore)
    {

    }

    function onPlayerChangeSide($player, $oldSide)
    {
        if ($player->spectator) {
            $this->groupPlayers->remove($player->login);
            $this->groupSpectators->add($player->login, true);
        } else {
            $this->groupSpectators->remove($player->login);
            $this->groupPlayers->add($player->login, true);
        }
    }

    function onPlayerFinishLap($player, $timeOrScore, $checkpoints, $nbLap)
    {

    }

    function onPlayerChangeTeam($login, $formerTeamId, $newTeamId)
    {

    }

    function onPlayerJoinGame($login)
    {

    }

    // Dedicated Listener

    function onPlayerConnect($login, $isSpectator)
    {
        $this->hidingGui[$login] = false;
        $this->modals[$login] = array();
        $this->modalShown[$login] = null;
        $this->managedWindow[$login] = null;
        $this->thumbnails[$login] = array();

        $this->groupAll->add(strval($login), true);
        if ($isSpectator) {
            $this->groupSpectators->add(strval($login), true);
        } else {
            $this->groupPlayers->add(strval($login), true);
        }
    }

    function onPlayerDisconnect($login, $disconnectionReason)
    {

        $this->groupAll->remove(strval($login));
        $this->groupPlayers->remove(strval($login));
        $this->groupSpectators->remove(strval($login));

        Window::Erase(strval($login));
        CustomUI::Erase(strval($login));

        if (array_key_exists($login, $this->modals)) {
            foreach ($this->modals[$login] as $dialog) {
                $this->onModalClosed($login, $dialog);
            }
            if ($this->modalShown[$login]) {
                $this->onModalClosed($login, $this->modalShown[$login]);
            }
        }

        unset($this->hidingGui[$login]);
        unset($this->modals[$login]);
        unset($this->modalShown[$login]);
        unset($this->managedWindow[$login]);
        unset($this->thumbnails[$login]);
    }

    function onBeginMap($map, $warmUp, $matchContinuation)
    {

    }

    function onBeginMatch()
    {

    }

    function onBeginRound()
    {

    }

    function onBillUpdated($billId, $state, $stateName, $transactionId)
    {

    }

    function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
    {

    }

    function onEcho($internal, $public)
    {

    }

    function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {

    }

    function onEndMatch($rankings, $winnerTeamOrMap)
    {

    }

    function onEndRound()
    {

    }

    function onManualFlowControlTransition($transition)
    {

    }

    function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {

    }

    function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {

    }

    function onPlayerFinish($playerUid, $login, $timeOrScore)
    {

    }

    function onPlayerIncoherence($playerUid, $login)
    {

    }

    function onPlayerInfoChanged($playerInfo)
    {

    }

    function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
    {

    }

    function onServerStart()
    {

    }

    function onServerStop()
    {

    }

    function onStatusChanged($statusCode, $statusName)
    {

    }

    function onTunnelDataReceived($playerUid, $login, $data)
    {

    }

    function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {

    }

    function onModeScriptCallback($param1, $param2)
    {

    }

    function onPlayerAlliesChanged($login)
    {

    }
}

?>
