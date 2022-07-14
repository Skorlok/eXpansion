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
                declare persistent Vec3[Text][Text] exp_windowLastPos;
                declare persistent Vec3[Text][Text] exp_windowLastPosRel;
			
                declare persistent Vec3[Text][Text][Text] eXp_widgetLastPos;
                declare persistent Vec3[Text][Text][Text] eXp_widgetLastPosRel;
                declare persistent Boolean[Text][Text][Text] eXp_widgetVisible;
			    declare persistent Text[Text][Text][Text] eXp_widgetLayers;
			    declare Boolean exp_needToCheckPersistentVars for UI = False;
			    declare persistent Boolean exp_chatVisible = True;
			    exp_chatVisible = True;
                declare Text version = "' . \ManiaLivePlugins\eXpansion\Core\Core::EXP_VERSION . '";
			
			    exp_windowLastPos.clear();
			    exp_windowLastPosRel.clear();
			    eXp_widgetLastPos.clear();
			    eXp_widgetLastPosRel.clear();
			    eXp_widgetVisible.clear();
			    eXp_widgetLayers.clear();

			    exp_needToCheckPersistentVars = True;
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
