<?php

/**
 * @package     Synapse
 * @subpackage  Snippet
 * @version 1.0
 */

defined('_INIT') or die;

class Snippet extends Object {

    public static function _($snippet, $data)
    {
        $snippet = SNIPPETS .'/'. $snippet .'.php';

        if(is_array($data)) {
            $items = [];

            if(Snippet::isAssoc($data)){
                foreach ($data as $k => $v) {
                    $items[] = array($k => $v);
                }

                foreach ($items as $i => $_data) {
                    if (is_object($_data)) {
                        $_data = get_object_vars($_data);
                    }
                    extract($_data);
                }
            }
        } else {
            extract($data);
        }

        ob_start();
		require($snippet);
		return ob_get_clean();
    }

    public static function render($type, &$tag, &$dom, $data)
    {
        $snippetClass = 'Snippet'.ucfirst($type);
        $snippetClassPath = LIBRARY.'/snippets/snippet'. strtolower($type).'.php';
        if(!File::exists($snippetClassPath)){
            return;
        }

        include($snippetClassPath);

        $snippet = new $snippetClass();
        $snippet->render($tag, $dom, $data);
    }

    protected function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
