<?php

/**
 * @package     Synapse
 * @subpackage  Controller
 */

defined('_INIT') or die;

class Controller extends Object {

    public $router  = null; // the router / if any
    public $params  = null; // params from the dynamic segments / if any
    public $query   = null; // query items from get requests using ? / if any
    protected $view = null;

    /**
     * Controller constructor
     * @param Object $params
     * @param Object $query
     */
    public function __construct($router = null, $params = null, $query = null)
    {
        if($router){
            $this->router = $router;
        }

        if($params){
            $this->params = $params;
        }

        if($query){
            $this->query = $query;
        }

        $this->view = new View();
    }

    /**
     * Returns all available params
     * @return null|Object
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * Returns a specific param value or null if not found
     * @param $param
     * @return null|Object
     */
    protected function getParam($param)
    {
        if(isset($this->params->$param)){
            return $this->params->$param;
        }

        return null;
    }

    /**
     * Sets the params value
     * @param $param
     * @param $value
     * @return bool
     */
    protected function setParam($param, $value)
    {
        if(isset($this->params->$param)){
            $this->params->$param = $value;
            return true;
        }

        return false;
    }

    /**
     * returns an array of found params keys
     * @return array
     */
    protected function listParams()
    {
        return array_keys((array)$this->params);
    }

    /**
     * Returns an object containing the query values
     * @return null|Object
     */
    protected function getQuery()
    {
        return $this->query;
    }

    public function index()
    {

    }

    /**
     * Returns the associated Router
     * @return null
     */
    protected function getRouter()
    {
        return $this->router;
    }

    /**
     * Redirects to the specified uri
     * @param String $url
     * @param Mixed $data
     */
    protected function redirect($url, $data = null)
    {
        if($data){
            $data_id = md5(time().microtime().rand(0,100));
            $session = $this->getSession();
            $session->set('REQUESTDATA_'.$data_id, $data, true);
            $session->set('REDIRECT', $data_id, true);
        }

        $this->router->redirect($url);
        die;
    }

    /**
     * Loads the view
     * @return null|View
     */
    protected function getView()
    {
        return $this->view;
    }

    /**
     * Run the view object that should render the HTML view
     * @param $view
     */
    protected function render()
    {
        $this->getView()->render();
    }

    /**
     * Returns the model based on the name provided
     * @param String $modelName
     * @param String $folder
     */
    protected function getModel($modelName)
    {
        $modelFileName = strtolower($modelName).'.php';
        $modelPath = MODELS.'/'.$modelFileName;


        if(!file_exists($modelPath)){
            throw new Error( __('Controller model not found: {1}', $modelFileName), null );
        }

        require_once($modelPath);

        $model = explode("/", $modelName);
        $modelClass  = ucfirst(array_pop($model)).'Model';

        return new $modelClass();
    }

    /**
     * Returns the current URI object
     * @return null|URI
     */
    protected function getURI()
    {
        return App::getURI();
    }

    /**
     * Returns the current request
     * @return null|Request
     */
    protected function getRequest()
    {
        return App::getRequest();
    }

    /**
     * Returns the sessione object
     * @return null|Session
     */
    protected function getSession()
    {
        return App::getSession();
    }

    /**
     * Returns the configuration parameters
     * @return Config|null
     */
    protected function getConfig()
    {
        return App::getConfig();
    }

    /**
     * Returns the App instance
     * @return App|null
     */
    protected function getApp()
    {
        return App::getInstance();
    }
}