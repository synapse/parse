<?php

/**
 * @package     Synapse
 * @subpackage  Snippets/Messages
 */

defined('_INIT') or die;


class SnippetMessages {

    public function render(&$tag, &$dom, $data)
    {
        $messages = App::getInstance()->getMessageQueue();
        if(count($messages)){
            $messagesString = '';

            foreach($messages as $type=>$message){
                $messagesString .= '<div class="'.$type.'"><p>'. implode('</p><p>', $message) .'</p></div>';
            }

            $msgDocument = new DOMDocument();
            libxml_use_internal_errors(true);
            $msgDocument->resolveExternals = true;
            $msgDocument->substituteEntities = false;
            $msgDocument->loadHTML($messagesString);
            libxml_clear_errors();
            $msgNodes = $msgDocument->getElementsByTagName('body')->item(0)->childNodes;

            foreach($msgNodes as $msgNode) {
                $msg = $dom->importNode($msgNode, true);
                $tag->parentNode->insertBefore($msg, $tag);
            }
        }

    }

}