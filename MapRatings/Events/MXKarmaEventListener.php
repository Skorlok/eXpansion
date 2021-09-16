<?php

namespace ManiaLivePlugins\eXpansion\MapRatings\Events;

/**
 * Description of PlayerEventListener
 *
 * @author reaby
 */
interface MXKarmaEventListener
{

    /**
     * Event after connection is successfull
     */
    public function MXKarma_onConnected();

    /**
     *
     */
    public function MXKarma_onDisconnected();

    /**
     *
     * @param \ManiaLivePlugins\eXpansion\MapRatings\Structures\MXRating $votes
     */
    public function MXKarma_onVotesRecieved(\ManiaLivePlugins\eXpansion\MapRatings\Structures\MXRating $votes);

    /**
     *
     * @param bool $isSuccess
     */
    public function MXKarma_onVotesSave($isSuccess);

    /**
     *
     * @param string $state
     * @param int $number
     * @param string $reason
     */
    public function MXKarma_onError($state, $number, $reason);
}
