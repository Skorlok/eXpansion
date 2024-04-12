<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

/**
 * Description of Dico
 *
 * @author Petri
 */
class Dico
{

    protected $xml = "";
    protected $messages = array();

    public function __construct($dicoText = array())
    {
        $this->messages = $dicoText;
    }

    public function setMessages($msg)
    {
        $this->messages = $msg;
    }

    public function getXml()
    {
        $xml = "";
        /*
         * Message Array("Lang" = "en", "Text" = Text);
         */
        $messages = array();
        foreach ($this->messages as $id => $msg) {
            foreach ($msg as $message) {
                $messages[$message['Lang']][$id][] = $message['Text'];
            }
        }

        $xml = '<dico>' . "\n";
        foreach ($messages as $key => $value) {
            $xml .= '<language id="' . $key . '">' . "\n";
            foreach ($value as $id => $msg) {
                foreach ($msg as $text) {
                    $xml .= '<' . $id . '>' . $text . '</' . $id . '>' . "\n";
                }
            }
            $xml .= '</language>' . "\n";
        }
        $xml .= '</dico>' . "\n";

        return $xml;
    }
}
