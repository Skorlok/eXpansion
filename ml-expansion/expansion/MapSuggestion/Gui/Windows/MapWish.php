<?php

/*
 * Copyright (C) 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\MapSuggestion\Gui\Windows;

use ManiaLive\Data\Storage;
use ManiaLive\Utilities\Validation;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use ManiaLivePlugins\eXpansion\MapSuggestion\MapSuggestion;

/**
 * Description of MapWish
 *
 * @author Petri
 */
class MapWish extends Window
{
    /** @var string $mxid */
    protected $mxid = "";

    /**
     * @var MapSuggestion
     */
    protected $plugin;

    protected function onConstruct()
    {
        parent::onConstruct();

        $login = $this->getRecipient();
        $player = Storage::getInstance()->getPlayerObject($login);
        $fromText = $player->nickName . '$z$s$fff (' . $login . ')';

        $this->setName("MapSuggestion window");
        $this->setTitle(__("Wish a map", $login));
        $this->setSize(90, 60);

        $content = '<frame posn="2 -6 0">';
        $content .= \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("from", 60, false, __('From', $login), $fromText, null, null);
        $content .= '<frame posn="0 -12 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("mxid", 60, true, __('Mania-Exchange ID-number for map wish', $login), $this->mxid, null, null) . '</frame>';
        $content .= '<frame posn="0 -24 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("description", 60, true, mb_convert_encoding(__('Why you would like this map to be added ?', $login), "UTF-8", 'ISO-8859-1'), null, null, null) . '</frame>';
        $content .= '<frame posn="0 -36 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0d0", null, null, $this->createAction(array($this, "apply")), null, null, null, null, null, null) . '</frame>';
        $content .= '</frame>';

        $xml = new \ManiaLive\Gui\Elements\Xml();
        $xml->setContent($content);
        $this->mainFrame->addComponent($xml);
    }

    /**
     * @param MapSuggestion $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    public function apply($login, $entries)
    {
        $mxid = $entries['mxid'];
        $this->plugin->addMapToWish($login, $mxid, $entries['description']);
    }

    public function setMXid($mxid)
    {
        if (Validation::int($mxid, 1)) {
            $this->mxid = "" . $mxid;
        }
    }
}
