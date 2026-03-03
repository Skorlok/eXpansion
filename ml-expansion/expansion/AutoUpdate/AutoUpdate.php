<?php

namespace ManiaLivePlugins\eXpansion\AutoUpdate;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use oliverde8\AsynchronousJobs\Job\CallbackComposerHandler;

/**
 * Auto update will check for updates and will update eXpansion if asked
 *
 * @author Petri & oliverde8
 */
class AutoUpdate extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /**
     * Configuration of eXpansion
     *
     * @var Config
     */
    private $config;

    /**
     * Currently on going git updates or checks
     *
     * @var boolean[]
     */
    private $onGoing = false;

    /**
     * The login of the player that started the currently running steps
     *
     * @var String
     */
    private $currentLogin;

    public function eXpOnReady()
    {
        $adm = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();

        $adm->addAdminCommand("update", $this, "autoUpdate", "server_update");
        $adm->addAdminCommand("check", $this, "checkUpdate", "server_update");

        $this->config = Config::getInstance();
        $this->enableDedicatedEvents();
    }

    /**
     * Will check if updates are necessary.
     */
    public function checkUpdate()
    {
        $AdminGroups = AdminGroups::getInstance();

        //If on going updates cancel !!
        if ($this->onGoing) {
            $msg = "#admin_error#An update or check for update is already under way!";
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, $msg);
            return;
        }

        $this->onGoing = true;

        $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_action#[#variable#AutoUpdate#admin_action#] Checking updates for #variable#eXpansion & Components');

        $composerJob = new CallbackComposerHandler();
        $composerJob->setCallback(array($this, 'checkExecuted'));
        $composerJob->setDirectory(APP_ROOT);
        if ($this->config->useGit) {
            $composerJob->setCommand(array(PHP_BINARY, 'composer.phar', 'update', '--prefer-source', '--no-interaction', '--no-progress', '--no-ansi', '--dry-run'));
        } else {
            $composerJob->setCommand(array(PHP_BINARY, 'composer.phar', 'update', '--prefer-dist', '--no-interaction', '--no-progress', '--no-ansi', '--dry-run'));
        }

        $composerJob->start();
    }

    /**
     * Handles the results of one of the update steps. and starts next step.
     */
    public function checkExecuted($job, $jobData)
    {
        $AdminGroups = AdminGroups::getInstance();
        $result = $job->getResult();

        if ($result['returnVar'] == -2) {
            $this->console('Error while checking for updates eXpansion !!');
            $this->console('proc_open failed to execute the command.');
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError('proc_open failed to execute the command.', AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while checking for updates of #variable#eXpansion & Components !!');
        } else if ($result['returnVar'] == -1) {
            $this->console('Error while checking for updates eXpansion !!');
            $this->console('proc_open is not available on this system.');
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError('proc_open is not available on this system.', AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while checking for updates of #variable#eXpansion & Components !!');
        } else if ($result['returnVar'] == 0) {
            if ($this->arrayContainsText('Nothing to install, update or remove', $result['error'])) {
                $this->console('eXpansion & Components are up to date');
                $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#vote_success#eXpansion & Components are up to date!');
            } else {
                $this->console('eXpansion needs updating!!');
                $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#eXpansion needs updating!');
            }
        } else {
            $this->console('Error while checking for updates eXpansion !!');
            $this->console($result['output']);
            $this->console($result['error']);
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError($result['error'], AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while checking for updates of #variable#eXpansion & Components !!');
        }

        $this->onGoing = false;
    }

    /**
     * Will start the auto update process using git or http
     *
     * @param $login
     */
    public function autoUpdate($login)
    {
        $AdminGroups = AdminGroups::getInstance();

        //If on going updates cancel !!
        if ($this->onGoing) {
            $msg = "#admin_error#An update or check for update is already under way!";
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, $msg);
            return;
        }

        $this->onGoing = true;

        $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_action#[#variable#AutoUpdate#admin_action#] Updating #variable#eXpansion & Components');

        $composerJob = new CallbackComposerHandler();
        $composerJob->setCallback(array($this, 'updateExecuted'));
        $composerJob->setDirectory(APP_ROOT);
        if ($this->config->useGit) {
            $composerJob->setCommand(array(PHP_BINARY, 'composer.phar', 'update', '--prefer-source', '--no-interaction', '--no-progress', '--no-ansi'));
        } else {
            $composerJob->setCommand(array(PHP_BINARY, 'composer.phar', 'update', '--prefer-dist', '--no-interaction', '--no-progress', '--no-ansi'));
        }

        $composerJob->start();
    }

    /**
     * Handles the results of one of the update steps. and starts next step.
     */
    public function updateExecuted($job, $jobData)
    {
        $AdminGroups = AdminGroups::getInstance();
        $result = $job->getResult();

        if ($result['returnVar'] == -2) {
            $this->console('Error while updating eXpansion !!');
            $this->console('proc_open failed to execute the command.');
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError('proc_open failed to execute the command.', AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while updating #variable#eXpansion & Components !!');
        } else if ($result['returnVar'] == -1) {
            $this->console('Error while updating eXpansion !!');
            $this->console('proc_open is not available on this system.');
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError('proc_open is not available on this system.', AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while updating #variable#eXpansion & Components !!');
        } else if ($result['returnVar'] == 0) {
            if ($this->arrayContainsText('Nothing to install, update or remove', $result['error'])) {
                $this->console('eXpansion & Components are already up to date');
                $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#vote_success#eXpansion & Components are already up to date!');
            } else {
                $this->console('eXpansion Updated!!');
                $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#vote_success#Update of #variable#eXpansion & Components #vote_success#Done');
            }
        } else {
            $this->console('Error while updating eXpansion !!');
            $this->console($result['output']);
            $this->console($result['error']);
            \ManiaLivePlugins\eXpansion\Gui\Gui::showError($result['error'], AdminGroups::getAdminsByPermission(Permission::SERVER_UPDATE));
            $AdminGroups->announceToPermission(Permission::SERVER_UPDATE, '#admin_error#Error while updating #variable#eXpansion & Components !!');
        }

        $this->onGoing = false;
    }

    /**
     * Checks if one of the strings in the array contains another text
     *
     * @param string $needle text to search for in the array
     * @param string[] $array The array of text in which we need to search for the text
     *
     * @return bool was the needle found in the array
     */
    protected function arrayContainsText($needle, $array)
    {
        foreach ($array as $val) {
            if (strpos($val, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
