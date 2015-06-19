<?php

/**
 * @package     Synapse
 * @subpackage  Middleware
 */

defined('_INIT') or die;

class Middleware extends Controller {

    /**
     * Returns the current DB object
     * @return DB|null
     */
    protected function getDBO()
    {
        return App::getDBO();
    }

    /**
     * Returns the middleware based on the name provided
     * @param String $middlewareName
     */
    protected function getSibling($middlewareName)
    {
        $middlewareFileName = strtolower($middlewareName).'.php';
        $middlewarePath = MIDDLEWARES.'/'.$middlewareFileName;


        if(!file_exists($middlewarePath)){
            throw new Error( __('Middleware class not found: {1}', $middlewareFileName), null );
        }

        require_once($middlewarePath);

        $middleware = explode("/", $middlewareName);
        $middlewareClass  = ucfirst(array_pop($middleware)).'Middleware';

        return new $middlewareClass();
    }

}