<?php

namespace ManiaLivePlugins\eXpansion\Faq;

use DirectoryIterator;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Faq\Gui\Windows\FaqWidget;
use ManiaLivePlugins\eXpansion\Gui\Elements\ScrollableArea;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Maniascript;
use ManiaLivePlugins\eXpansion\Menu\Menu;

class Faq extends ExpPlugin
{
    private $msg_admin_redirect;
    private $msg_admin_info;
    public static $availableLanguages = array();

    /** @var Window */
    private $faqWindow;

    /** @var Script */
    private $scrollScript;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->msg_admin_redirect = eXpGetMessage('Notice: a help page is displayed by an admin!');
        $this->msg_admin_info = eXpGetMessage('Notice: Displaying a help page "%1$s" to "%2$s"');
        $this->setPublicMethod("showFaq");

        /** @var ActionHandler $aH */
        $aH = ActionHandler::getInstance();
        Menu::addMenuItem("Faq",
            array("Help" => array(null, $aH->createAction(array($this, "showFaq"))))
        );

        $langs = new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . "Topics");
        foreach ($langs as $lang) {
            if ($lang->isDot()) {
                continue;
            }
            if ($lang->isDir()) {
                if (is_file(
                    $lang->getPath() . DIRECTORY_SEPARATOR . $lang->getFilename() . DIRECTORY_SEPARATOR . "toc.txt"
                )) {
                    self::$availableLanguages[] = $lang->getFilename();
                }
            }
        }
    }

    public function eXpOnReady()
    {
        $this->registerChatCommand("faq", "showFaq", 0, true);
        $this->registerChatCommand("faq", "showFaq", 1, true);
        $this->registerChatCommand("faq", "showFaq", 2, true);

        FaqWidget::$mainPlugin = $this;

        $this->faqWindow = new Window("Faq\\Gui\\Windows\\FaqWindow.xml");
        $this->faqWindow->setName("Faq Help");
        $this->faqWindow->setSize(160, 90);

        $this->scrollScript = ScrollableArea::getScriptML();
        $this->faqWindow->registerScript($this->scrollScript);
    }

    public function showFaq($login, $topic = "toc", $recipient = null)
    {
        $topic = str_replace(".md", "", $topic);
        $showTo = $login;
        if (AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            if (!empty($recipient)) {
                if (array_key_exists($recipient, $this->storage->players)) {
                    $showTo = $recipient;
                    $this->eXpChatSendServerMessage($this->msg_admin_redirect, $showTo);
                    $this->eXpChatSendServerMessage($this->msg_admin_info, $login, array($showTo, $topic));
                }
            }
        }

        $player = $this->storage->getPlayerObject($login);
        $language = "en";
        if ($player && in_array($player->language, self::$availableLanguages)) {
            $language = $player->language;
        }

        $topicFile = $topic . ".md";
        if (strpos($topicFile, '../') !== false || strpos($topicFile, "..\\") !== false ||
            strpos($topicFile, '/..') !== false || strpos($topicFile, '\\..') !== false
        ) {
            $topicFile = "toc.md";
        }

        $filePath = __DIR__ . "/Topics/" . $language . "/" . $topicFile;
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . "/Topics/en/" . $topicFile;
        }

        $file = file_get_contents($filePath);
        list($contentXml, $totalHeight, $title) = $this->parseFaq($file);

        $this->scrollScript->setParam("contentSizeY", Maniascript::getReal($totalHeight));
        $this->faqWindow->setTitle("Help " . $title);
        $this->faqWindow->setParam("contentXml", $contentXml);
        $this->faqWindow->show($showTo);
    }

    public function eXpOnUnload()
    {
        if ($this->faqWindow instanceof Window) {
            $this->faqWindow->erase();
        }
        $this->faqWindow = null;
    }

    private function parseFaq($file)
    {
        $contentXml  = '';
        $totalHeight = 0.0;
        $y           = 0.0;
        $title       = '';
        $firstLine   = true;
        $isCodeBlock = false;
        $emptyLines  = 0;
        $lastIsTitle = false;

        $y -= 3;
        $totalHeight += 3;
        list($lxml, $lh) = $this->buildLine("[Back to index](#toc.md)", $y);
        $contentXml .= $lxml;
        $y -= $lh;
        $totalHeight += $lh;

        foreach (explode("\n", $file) as $rawLine) {
            $line = trim($rawLine);

            if ($firstLine) {
                $title     = $line;
                $firstLine = false;
                continue;
            }

            $hm = array();
            if (preg_match('/^(?P<level>#{1,6})(?P<rest>.*)/', $line, $hm)) {
                if ($emptyLines == 0) {
                    list($lxml, $lh) = $this->buildLine($rawLine, $y);
                    $contentXml .= $lxml;
                    $y -= $lh;
                    $totalHeight += $lh;
                }
                $lastIsTitle = true;
                list($hxml, $hh) = $this->buildHeader(trim($hm['rest']), strlen($hm['level']), $y);
                $contentXml .= $hxml;
                $y -= $hh;
                $totalHeight += $hh;
                $emptyLines = 0;
                continue;
            }

            if ($line === '') {
                $emptyLines++;
                if ($emptyLines > 1 || $lastIsTitle) {
                    continue;
                }
                $lastIsTitle = false;
                $y -= 3;
                $totalHeight += 3;
                continue;
            }

            $emptyLines  = 0;
            $lastIsTitle = false;

            if ($line === '```') {
                $isCodeBlock = !$isCodeBlock;
                continue;
            }

            if ($isCodeBlock) {
                list($cxml, $ch) = $this->buildCodeLine($rawLine, $y);
                $contentXml .= $cxml;
                $y -= $ch;
                $totalHeight += $ch;
                continue;
            }

            $wrapped = wordwrap(trim($rawLine), 90, "\n", false);
            $parts   = explode("\n", $wrapped);
            if (count($parts) > 1) {
                $prevBlock = null;
                foreach ($parts as $part) {
                    list($lxml, $lh, $prevBlock) = $this->buildLine($part, $y, $prevBlock);
                    $contentXml .= $lxml;
                    $y -= $lh;
                    $totalHeight += $lh;
                }
            } else {
                list($lxml, $lh) = $this->buildLine($rawLine, $y);
                $contentXml .= $lxml;
                $y -= $lh;
                $totalHeight += $lh;
            }
        }

        return array($contentXml, $totalHeight, $title);
    }

    private function buildLine($text, $y, $forceBlock = null)
    {
        $block     = 0.0;
        $hasBullet = false;
        $action    = null;
        $textColor = 'fff';
        $style     = 'TextRaceChat';
        $textSize  = 2;
        $height    = 5;

        $lm = array();
        if (preg_match("/^(\t|    )*(\*|\d+\.) (.*)/", $text, $lm)) {
            $indent = substr_count($lm[1], '    ') + substr_count($lm[1], "\t");
            if ($lm[2] == "*") {
                $hasBullet = true;
                $text      = preg_replace("/^(\t|    )*\*/", "", $text);
            }
            $block = $indent + 0.2;
        } elseif ($indent = substr_count($text, "\t")) {
            $block = (float)$indent;
        }

        if ($forceBlock !== null) {
            $block = $forceBlock;
        }

        $posX      = $block * 6;
        $labelPosX = $hasBullet ? $posX + 2 : $posX;
        $width     = 240 - $posX;

        $um = array();
        preg_match("/(?P<textb>.*)(\[(?P<text>.*?)\]\((?P<url>.*?)\))(?P<texta>.*)/", $text, $um);
        if (!empty($um['url']) && !empty($um['text'])) {
            if (substr($um['url'], 0, 4) == 'http') {
                $text = $um['textb'] . '$3af$l[' . $um['url'] . ']' . $um['text'] . '$l$z' . $um['texta'];
            } elseif (substr($um['url'], 0, 4) == '##') {
                $text = $um['textb'] . '$3af$l[' . str_replace('##', '', $um['url']) . ']' . $um['text'] . '$l$z' . $um['texta'];
            } else {
                $linkFile  = str_replace("#", "", $um['url']);
                $aH        = ActionHandler::getInstance();
                $action    = $aH->createAction(array($this, "showFaq"), $linkFile, null);
                $textColor = '3af';
                $style     = 'TextCardMedium';
                $text      = $um['textb'] . $um['text'] . $um['texta'];
            }
        }

        $text = str_replace("**", '$o', $text);
        $text = str_replace("__", '$o', $text);
        $text = preg_replace("/\*(.*?)\*/", '\$i$1\$i', $text);
        $text = preg_replace("/_(.*?)_/", '\$i$1\$i', $text);
        $text = preg_replace("/`(.*?)`/", '\$i\$ff0$1\$z', $text);
        $im   = array();
        if (preg_match_all("/```(?P<inline>.*?)```(?P<rest>.*)/", $text, $im) && !empty($im['inline'])) {
            $text = '$ff0$i' . $im['inline'][0] . '$i$fff ' . $im['rest'][0];
        }

        if (trim($text) === '') {
            $height = 3;
        }

        $safeText   = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $actionAttr = $action !== null ? ' action="' . $action . '"' : '';

        $xml = '';
        if ($hasBullet) {
            $xml .= '<quad posn="' . $posX . ' ' . $y . ' 0" sizen="2 2" halign="left" valign="center" image="file://Media/Manialinks/Common/Disc.dds" colorize="fff"/>';
        }
        $xml .= '<label posn="' . $labelPosX . ' ' . $y . ' 0" sizen="' . $width . ' ' . $height . '" halign="left" valign="center" style="' . $style . '" textcolor="' . $textColor . '" textsize="' . $textSize . '"' . $actionAttr . ' text="' . $safeText . '"/>';

        return array($xml, $height, $block);
    }

    private function buildHeader($text, $level, $y)
    {
        $height   = 8 - $level;
        $textSize = 6 - $level;
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $xml = '<label posn="0 ' . $y . ' 0" sizen="240 5" halign="left" valign="center" style="TextRaceMessageBig" textcolor="fff" textsize="' . $textSize . '" text="' . $safeText . '"/>';

        return array($xml, $height);
    }

    private function buildCodeLine($text, $y)
    {
        $text     = str_replace('```', '', $text);
        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $xml  = '<quad posn="0 ' . $y . ' -0.1" sizen="240 8" halign="left" valign="center" bgcolor="3af8"/>';
        $xml .= '<label posn="0 ' . $y . ' 0" sizen="240 8" halign="left" valign="center" style="StyleTextScriptEditor" textcolor="ff0" textsize="1.5" text="' . $safeText . '"/>';

        return array($xml, 8);
    }
}
