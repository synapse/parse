<?php

/**
 * @package     Synapse
 * @subpackage  Module
 */

defined('_INIT') or die;

class ModuleController extends Controller {

    private $baseURL    = null;
    private $name       = null;
    private $config     = null;

    /**
     * Module constructor
     * @param Object $router
     * @param Object $params
     * @param Object $query
     */
    public function __construct($router = null, $params = null, $query = null)
    {
        $this->name     = $router->getCurrent()->moduleName;
        $this->baseURL  = $router->getCurrent()->baseURL;

        parent::__construct($router, $params, $query);

        $this->getView()->setTemplatePath(MODULES.'/'.$this->name.'/views');
    }

    /**
     * Return the module config
     * @return Object $config
     * @throws Error
     */
    public function getModuleConfig()
    {
        if($this->config) return $this->config;

        $configPath = MODULES.'/'.$this->name.'/config.php';

        if(!file_exists($configPath)){
            throw new Error( __('Module config file not found!').' '.$configPath );
        }

        require_once($configPath);

        $configClass  = ucfirst($this->name).'Config';

        if(!class_exists($configClass)){
            throw new Error( __('Module config class not found!').' '.$configClass );
        }

        $this->config = new $configClass();

        return $this->config;
    }

    /**
     * Returns the model based on the name provided
     * @param String $modelName
     * @param String $folder
     */
    protected function getModel($modelName)
    {
        $modelFileName = strtolower($modelName).'.php';
        $modelPath = MODULES.'/'.$this->name.'/models/'.$modelFileName;

        if(!file_exists($modelPath)){
            throw new Error( __('Module model not found: {1}', $modelFileName), null );
        }

        require_once($modelPath);

        $model = explode("/", $modelName);
        $modelClass  = ucfirst(array_pop($model)).'Model';

        return new $modelClass();
    }

    /**
     * Returns the model name
     * @return String $name
     */
    protected function getName()
    {
        return $this->name;
    }

    /**
     * Return the base URL of the module
     */
    public function getBaseURL()
    {
        return $this->baseURL;
    }
}