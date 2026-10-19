<?php

namespace ManiaLivePlugins\eXpansion\ScriptTester;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\ScriptTester\Gui\TesterScript;

/**
 * Description of Netstat
 *
 * @author Petri
 */
class ScriptTester extends ExpPlugin
{

    private $cmd_edit;

    /** @var StaticWidget */
    private $editorWidget;

    /** @var Widget */
    private $testWidget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->registerManialinkCallback('apply', true);
        $this->registerManialinkCallback('closeEditor');
        $this->registerManialinkCallback('closeTest');

        $this->editorWidget = new StaticWidget('ScriptTester\Gui\Widgets\ScriptEditor.xml');
        $this->editorWidget->setName("Script Editor");
        $this->editorWidget->setLayer("normal");
        $this->editorWidget->setPosition(-150, -10, 10);

        $this->testWidget = new Widget('ScriptTester\Gui\Widgets\TestWidget.xml');
        $this->testWidget->setName("Test widget");
        $this->testWidget->setLayer("normal");
        $this->testWidget->setSize(100, 40);
        $this->testWidget->setPosition(-50, 60, 10);

        $this->cmd_edit = AdminGroups::addAdminCommand("editor", $this, 'showEditor', Permission::SERVER_ADMIN);
    }

    public function showEditor($login)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $this->editorWidget->setParam("manialink", $this->editorWidget->handleSpecialChars($config->tester_manialink));
        $this->editorWidget->setParam("maniascript", $this->editorWidget->handleSpecialChars($config->tester_maniascript));
        $this->editorWidget->show($login);
    }

    public function closeEditor($login)
    {
        $this->editorWidget->erase($login);
    }

    public function closeTest($login)
    {
        $this->testWidget->erase($login);
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();

        if ($this->editorWidget instanceof StaticWidget) {
            $this->editorWidget->erase();
        }
        $this->editorWidget = null;

        if ($this->testWidget instanceof Widget) {
            $this->testWidget->erase();
        }
        $this->testWidget = null;

        $admingroup = AdminGroups::getInstance();
        $admingroup->removeAdminCommand($this->cmd_edit);
        $this->cmd_edit = null;
    }

    public function apply($login, $data = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::SERVER_ADMIN)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        $manialink = isset($data['manialink']) ? $data['manialink'] : "";
        $script    = isset($data['script']) ? $data['script'] : "";

        $var = MetaData::getInstance()->getVariable('tester_manialink');
        $var->setRawValue($manialink);

        $var = MetaData::getInstance()->getVariable('tester_maniascript');
        $var->setRawValue($script);

        \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

        try {
            $this->testWidget->registerOrOverrideScript(new TesterScript($script));
            $this->testWidget->setParam("userXml", $manialink);
            $this->testWidget->show($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage("Error: " . $e->getMessage(), $login);
        }
    }
}
