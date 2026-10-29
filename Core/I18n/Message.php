<?php

namespace ManiaLivePlugins\eXpansion\Core\I18n;

use ManiaLivePlugins\eXpansion\Helpers\Helper;

/**
 * Description of Message
 *
 * @author oliverde8
 */
class Message
{

    public static $defaultLanguage = "en";

    /**
     * The orginal message as used in ManiaLive
     */
    private $originalMessage;

    /**
     * List of all the messages in different languages
     *
     * @var String[String]
     */
    private $lmessages = array();

    /**
     * List of all arguments passed for parsing
     *
     * @var String[String]
     */
    private $args = array();

    public function __construct($orginalMessage)
    {
        $this->originalMessage = $orginalMessage;
    }

    public function addLanguageMessage($lang, $message)
    {
        $this->lmessages[$lang] = $message;
    }

    public function setArgs(array $args)
    {
        $this->args = $args;
    }

    public function getOriginalMessage()
    {
        return $this->originalMessage;
    }

    /**
     * getMultiLangArray()
     * Returns a multilanguage message array to be used with Connection->ChatSendServerMessageToLanguage();
     * if message contains arguments, please use $msg->setArgs before calling this function
     *
     * @return Strign[String] $out Array[] = array("Lang" => string, "Text" => string)
     */
    public function getMultiLangArray()
    {
        $temp = $this->lmessages;
        if (!isset($temp["en"])) {
            $temp["en"] = $this->originalMessage;
        }
        $out = array();

        foreach ($temp as $lang => $msg) {
            $text = self::format($msg, $this->args);
            $out[$lang] = array(
                "Lang" => lcfirst($lang),
                "Text" => \ManiaLivePlugins\eXpansion\Core\ColorParser::getInstance()->parseColors($text),
            );
        }

        // Put en message last for unknown languages to see english.
        $en = $out['en'];
        unset($out['en']);
        $out['en'] = $en;

        return array_values($out);
    }

    /**
     * getParsedMessage ($lang)
     *
     * @param string $lang
     *
     * @return string
     */
    public function getParsedMessage($lang = null)
    {
        $text = self::format($this->getMessage($lang), $this->args);

        return \ManiaLivePlugins\eXpansion\Core\ColorParser::getInstance()->parseColors($text);
    }

    /**
     * format()
     * sprintf() that doesn't crash on an invalid format (a stray % in the message),
     * the error is logged and the message is returned unformatted.
     *
     * @param string $message
     * @param array $args
     *
     * @return string
     */
    public static function format($message, array $args)
    {
        array_unshift($args, $message);
        $error = 'sprintf() failed';
        try {
            $text = call_user_func_array('sprintf', $args);
        } catch (\Exception $e) {
            $text = false;
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $text = false;
            $error = $e->getMessage();
        }

        if ($text === false) {
            Helper::logError('[I18n]Error with translations strings : ' . $message);
            Helper::logError('[I18n]' . $error);

            return $message;
        }

        return $text;
    }

    /**
     *  getMessage
     *
     * @param string $lang language code
     *
     * @return string
     */
    public function getMessage($lang = null)
    {
        if ($lang == null) {
            return isset($this->lmessages[self::$defaultLanguage]) ? $this->lmessages[self::$defaultLanguage] : $this->originalMessage;
        } else {
            if (isset($this->lmessages[$lang])) {
                return $this->lmessages[$lang];
            } else {
                return isset($this->lmessages[self::$defaultLanguage]) ? $this->lmessages[self::$defaultLanguage] : $this->originalMessage;
            }
        }
    }

    public function __toString()
    {
        return $this->originalMessage;
    }
}
