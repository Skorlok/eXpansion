<?php

namespace ManiaLivePlugins\eXpansion\Discord\Classes;

use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Connection
{
    public $token;
    public $channelId;

    /** @var DataAccess */
    private $dataAccess;

    /** @var array|null */
    private $currentMessage = null;
    private $messageQueue = array();
    private $isSending = false;
    private $sendingSince = 0;
    private $requestId = 0;
    private $nextSendTime = 0;
    private $retryCount = 0;
    private $isProcessingQueue = false;

    /** @var string|null only the latest topic is kept, discord allows ~2 topic changes per 10 minutes */
    private $pendingTopic = null;
    /** @var string|null */
    private $currentTopic = null;
    private $isUpdatingTopic = false;
    private $topicUpdatingSince = 0;
    private $topicRequestId = 0;
    private $nextTopicUpdateTime = 0;

    public function __construct($token, $channelId)
    {
        $this->token = $token;
        $this->channelId = $channelId;

        $this->dataAccess = DataAccess::getInstance();
    }

    public function sendDiscordMessage($content = "", $embeds = array())
    {
        if ($this->token == "" || $this->channelId == "") {
            return;
        }

        $postData = array("content" => $content);
        if (sizeof($embeds) > 0) {
            $postData["embeds"] = array($embeds);
        }

        $this->messageQueue[] = $postData;

        $this->processMessageQueue();
    }

    public function processMessageQueue()
    {
        if ($this->isProcessingQueue) {
            return;
        }
        $this->isProcessingQueue = true;

        if ($this->isSending && time() - $this->sendingSince > 60) {
            $this->console("request got no answer, sending the message again");
            $this->isSending = false;
        }

        if (!$this->isSending && microtime(true) >= $this->nextSendTime) {
            if ($this->currentMessage === null && !empty($this->messageQueue)) {
                $this->currentMessage = array_shift($this->messageQueue);
                $this->retryCount = 0;
            }
            if ($this->currentMessage !== null) {
                $this->postMessage($this->currentMessage);
            }
        }

        $this->isProcessingQueue = false;
    }

    public function postMessage($postData)
    {
        $this->isSending = true;
        $this->sendingSince = time();
        $this->requestId++;

        $url = 'https://discord.com/api/v10/channels/' . $this->channelId . '/messages';
        $headers = array("Authorization: Bot " . $this->token, "Content-Type: application/json; charset=utf-8");

        $options = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($postData), CURLOPT_HTTPHEADER => $headers);
        $this->dataAccess->httpCurl($url, array($this, "requestCallback"), array("requestId" => $this->requestId), $options);
    }

    public function requestCallback($job, $jobData)
    {
        $additionalData = $job->__additionalData;
        if (!isset($additionalData['requestId']) || $additionalData['requestId'] !== $this->requestId) {
            return;
        }
        $this->isSending = false;

        $info = $job->getCurlInfo();
        $code = is_null($info) ? 0 : (int)$info['http_code'];
        $data = $job->getResponse();
        $json = $data ? json_decode($data, true) : null;

        if ($code >= 200 && $code < 300) {
            $this->currentMessage = null;
        } elseif ($code == 429) {
            $retryAfter = (is_array($json) && isset($json['retry_after'])) ? (float)$json['retry_after'] : 5;
            $this->nextSendTime = microtime(true) + $retryAfter;
        } elseif ($code == 0 || $code >= 500) {
            $this->retryCount++;
            $this->nextSendTime = microtime(true) + 2 * $this->retryCount;
        } else {
            $this->console("Error while sending discord message : " . $code);
            if ($json) {
                $this->console(print_r($json, true));
            }
            $this->currentMessage = null;
        }

        $this->processMessageQueue();
    }

    public function setChannelTopic($topic)
    {
        if ($this->token == "" || $this->channelId == "") {
            return;
        }

        $this->pendingTopic = $topic;
        $this->processChannelTopic();
    }

    public function processChannelTopic()
    {
        if ($this->isUpdatingTopic && time() - $this->topicUpdatingSince > 60) {
            $this->isUpdatingTopic = false;
        }

        if ($this->pendingTopic === $this->currentTopic) {
            $this->pendingTopic = null;
        }

        if ($this->isUpdatingTopic || $this->pendingTopic === null || microtime(true) < $this->nextTopicUpdateTime) {
            return;
        }

        $this->isUpdatingTopic = true;
        $this->topicUpdatingSince = time();
        $this->topicRequestId++;

        $url = 'https://discord.com/api/v10/channels/' . $this->channelId;
        $headers = array("Authorization: Bot " . $this->token, "Content-Type: application/json; charset=utf-8");

        $options = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => "PATCH", CURLOPT_POSTFIELDS => json_encode(array("topic" => $this->pendingTopic)), CURLOPT_HTTPHEADER => $headers);
        $this->dataAccess->httpCurl($url, array($this, "topicCallback"), array("requestId" => $this->topicRequestId, "topic" => $this->pendingTopic), $options);
    }

    public function topicCallback($job, $jobData)
    {
        $additionalData = $job->__additionalData;
        if (!isset($additionalData['requestId']) || $additionalData['requestId'] !== $this->topicRequestId) {
            return;
        }
        $this->isUpdatingTopic = false;

        $info = $job->getCurlInfo();
        $code = is_null($info) ? 0 : (int)$info['http_code'];
        $data = $job->getResponse();
        $json = $data ? json_decode($data, true) : null;

        if ($code >= 200 && $code < 300) {
            $this->currentTopic = $additionalData['topic'];
        } elseif ($code == 429) {
            $retryAfter = (is_array($json) && isset($json['retry_after'])) ? (float)$json['retry_after'] : 60;
            $this->nextTopicUpdateTime = microtime(true) + $retryAfter;
        } elseif ($code == 0 || $code >= 500) {
            $this->nextTopicUpdateTime = microtime(true) + 30;
        } else {
            $this->console("Error while updating discord channel topic : " . $code);
            if ($json) {
                $this->console(print_r($json, true));
            }
            $this->pendingTopic = null;
        }
    }

    public function console($message)
    {
        Helper::log($message, array('Discord/Connection'));
    }
}
