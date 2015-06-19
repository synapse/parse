<?php

/**
 * @package     Synapse
 * @subpackage  Snippets/Snippet
 */

defined('_INIT') or die;


class SnippetSnippet {

    public function render(&$tag, &$dom, $data)
    {
        foreach($data as $_data){
            if(is_object($_data)){
                $_data = get_object_vars($_data);
            }
            extract($_data);
        }

        $attr = $tag->attributes;

        $name = $attr->getNamedItem('name')->nodeValue;
        if(!file_exists(SNIPPETS .'/'. $name .'.php')) {
            return;
        }

        ob_start();
        require(SNIPPETS .'/'. $name .'.php');
        $snippetHTML = ob_get_clean();

        $snippetDocument = new DOMDocument();
        $snippetDocument->resolveExternals = true;
        $snippetDocument->substituteEntities = false;
        $snippetDocument->loadHTML($snippetHTML);
        $snippetsNodes = $snippetDocument->getElementsByTagName('body')->item(0)->childNodes;

        foreach($snippetsNodes as $snippetNode) {
            $snippet = $dom->importNode($snippetNode, true);
            $tag->parentNode->insertBefore($snippet, $tag);
        }
    }

}