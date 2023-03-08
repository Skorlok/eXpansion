<?php

namespace ManiaLivePlugins\eXpansion\Gui\Windows;

class ResetHud extends \ManiaLive\Gui\Window
{
    private $xml;

    protected function onConstruct()
    {
        $this->xml = new \ManiaLive\Gui\Elements\Xml();
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
    }

    public function onDraw()
    {
        $this->removeComponent($this->xml);
        $this->xml->setContent('    
        <script><!--
            main () {     
                declare persistent Vec3[Text] EXP_windowLastPos;
                declare persistent Vec3[Text] EXP_windowLastPosRel;

                declare persistent Vec3[Text][Text] EXP_widgetLastPos;
                declare persistent Vec3[Text][Text] EXP_widgetLastPosRel;
                declare persistent Boolean[Text][Text] EXP_widgetVisible;
                declare persistent Text[Text][Text] EXP_widgetLayers;

                declare Boolean exp_needToCheckPersistentVars for UI = False;

                declare persistent Boolean exp_chatVisible = True;
                exp_chatVisible = True;

                EXP_windowLastPos.clear();
                EXP_windowLastPosRel.clear();
                EXP_widgetLastPos.clear();
                EXP_widgetLastPosRel.clear();
                EXP_widgetVisible.clear();
                EXP_widgetLayers.clear();

                exp_needToCheckPersistentVars = True;


                declare persistent Vec3[Text][Text] exp_windowLastPos;
                declare persistent Vec3[Text][Text] exp_windowLastPosRel;

                declare persistent Vec3[Text][Text][Text] eXp_widgetLastPos;
                declare persistent Vec3[Text][Text][Text] eXp_widgetLastPosRel;
                declare persistent Boolean[Text][Text][Text] eXp_widgetVisible;
                declare persistent Text[Text][Text][Text] eXp_widgetLayers;

                eXp_widgetVisible = ["deleted"=>["deleted"=>["deleted"=>True]]];
                eXp_widgetLastPos = ["deleted"=>["deleted"=>["deleted"=>< 1.00, 1.00, 1.0>]]];
                eXp_widgetLastPosRel = ["deleted"=>["deleted"=>["deleted"=>< 1.00, 1.00, 1.0>]]];
                eXp_widgetLayers = ["deleted"=>["deleted"=>["deleted"=>"detete"]]];

                exp_windowLastPos = ["deleted"=>["deleted"=>< 1.00, 1.00, 1.0>]];
                exp_windowLastPosRel = ["deleted"=>["deleted"=>< 1.00, 1.00, 1.0>]];
            }
        --></script>');
        $this->addComponent($this->xml);
        parent::onDraw();
    }

    public function destroy()
    {
        parent::destroy();
    }
}
