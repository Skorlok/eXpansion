<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook\Classes;

use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Connection
{
    /** @var string */
    public $url;

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

    public function __construct($url)
    {
        $this->url = $url;

        $this->dataAccess = DataAccess::getInstance();
    }

    public function sendWebhookMessage($content = "", $author = null, $embeds = array())
    {
        if ($this->url == "") {
            return;
        }

        /** @var Storage $storage */
        $storage = Storage::getInstance();

        $postData = array("content" => $content, "allowed_mentions" => array("parse" => array()));
        if (is_array($author) && isset($author["name"])) {
            $postData["username"] = $author["name"];
            if (isset($author["flag"])) {
                $postData["avatar_url"] = "https://cdn.skorlok.com/eXpansion/Flags/" . $author["flag"] . ".jpg";
            }
        } else {
            $postData["username"] = Formatting::stripStyles($storage->server->name);
        }
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

        $options = array(CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($postData), CURLOPT_HTTPHEADER => array("Content-Type: application/json; charset=utf-8"));
        $this->dataAccess->httpCurl($this->url, array($this, "requestCallback"), array("requestId" => $this->requestId), $options);
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
            $this->console("Error while sending webhook message : " . $code);
            if ($json) {
                $this->console(print_r($json, true));
            }
            $this->currentMessage = null;
        }

        $this->processMessageQueue();
    }

    public function console($message)
    {
        Helper::log($message, array('ChatWebhook/Connection'));
    }
}
