<?php

/**
 * @package     Synapse
 * @subpackage  Model
 */

defined('_INIT') or die;

class Model extends Object {

    public function __construct()
    {

    }

    protected function getDBO()
    {
        return App::getDBO();
    }

    protected function getConfig()
    {
        return App::getConfig();
    }

    /**
     * Returns the model based on the name provided
     * @param String $modelName
     */
    protected function getSibling($modelName)
    {
        $modelFileName = strtolower($modelName).'.php';
        $modelPath = MODELS.'/'.$modelFileName;


        if(!file_exists($modelPath)){
            throw new Error( Text::_('CLASS_ROUTER_MODEL_FILE_NOT_FOUND', $modelFileName), null );
        }

        require_once($modelPath);

        $model = explode("/", $modelName);
        $modelClass  = ucfirst(array_pop($model)).'Model';

        return new $modelClass();
    }

}