<?php

namespace ManiaLivePlugins\eXpansion\MapRatings\Gui\Windows;

/**
 * Description of MapManager
 *
 * @author Reaby
 */
class MapRatingsManager extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $pager;

    public static $removeId;

    protected $btn_remove;

    public function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->addComponent($this->pager);

        $this->btn_remove = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_remove->setContent('<frame posn="100 -84 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Remove", $login), null, null, "d00", null, null, self::$removeId, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->btn_remove);
    }

    /**
     * set ratings for window
     *
     * @param \ManiaLivePlugins\eXpansion\MapRatings\Structures\MapRating[] $ratings ;
     */
    public function setRatings($ratings)
    {
        $this->pager->clearItems();
        $index = 0;
        foreach ($ratings as $rating) {
            $this->pager->addItem(new \ManiaLivePlugins\eXpansion\MapRatings\Gui\Controls\RatingsItem($index, $rating));
            $index++;
        }
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 22);
    }

    public function destroy()
    {
        $this->pager->destroy();
        parent::destroy();
    }
}
