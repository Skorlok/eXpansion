<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PersonalBest\Gui\Widgets;

class PBPanel extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    public function setRecord($record, $rank, $rankTotal)
    {
        $this->setName("Personal Best Widget");
        if ($record == null) {
            $pbTime = '--';
            $avgTime = $pbTime;
            $nbFinish = 0;
        } else {
            $pbTime = \ManiaLive\Utilities\Time::fromTM($record->time);
            if (substr($pbTime, 0, 2) === "0:") {
                $pbTime = substr($pbTime, 2);
            }
            $avgTime = \ManiaLive\Utilities\Time::fromTM($record->avgScore);
            if (substr($avgTime, 0, 2) === "0:") {
                $avgTime = substr($avgTime, 2);
            }
            $nbFinish = $record->nbFinish;
        }

        $login = $this->getRecipient();

        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<frame posn="20 0 2.0E-5">
        <label sizen="32 7" scale="0.7" halign="right" valign="top" style="TextStaticSmall" text="$ddd' . __('Personal Best', $login) . '"/>
        <label posn="1 0 1.0E-5" sizen="16 4" scale="0.7" halign="left" valign="top" style="TextStaticSmall" text="$ddd' . $pbTime . '"/>
        <label posn="0 -3 2.0E-5" sizen="32 7" scale="0.7" halign="right" valign="top" style="TextStaticSmall" text="$ddd' . __('Average', $login) . '"/>
        <label posn="1 -3 3.0E-5" sizen="16 4" scale="0.7" halign="left" valign="top" style="TextStaticSmall" text="$ddd' . $avgTime . '"/>
        <label posn="0 -6 4.0E-5" sizen="32 7" scale="0.7" halign="right" valign="top" style="TextStaticSmall" text="$ddd' . __('Finishes', $login) . '"/>
        <label posn="1 -6 5.0E-5" sizen="16 4" scale="0.7" halign="left" valign="top" style="TextStaticSmall" text="$ddd' . $nbFinish . '"/>
        <label posn="0 -9 6.0E-5" sizen="32 7" scale="0.7" halign="right" valign="top" style="TextStaticSmall" text="$ddd' . __('Server Rank', $login) . '"/>
        <label posn="1 -9 7.0E-5" sizen="16 4" scale="0.7" halign="left" valign="top" style="TextStaticSmall" text="$ddd' . $rank . '$n $m/$n $m' . $rankTotal . '"/></frame>');
        $this->addComponent($widget);
    }

    public function destroy()
    {
        $this->destroyComponents();
        parent::destroy();
    }
}
