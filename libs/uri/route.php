<?php

/**
 * @package     Synapse
 * @subpackage  URI/Router/Route
 */

defined('_INIT') or die;

class Route {
    
    private $url            = null;
    private $methods        = array('GET','POST','PUT','DELETE', 'MODULE');
    private $controller     = null;
    private $controllerPath = null;
    private $middlewares    = array();
    //private $mAction        = null;
    private $action         = null;
    private $params         = null;
    private $tokens         = array();

    public function __construct()
    {
        $this->params = new stdClass();
    }

    /**
     * Returns the route URL
     * @return String
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Sets the route URL
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $url = (string) $url;
        $uri = App::getInstance()->getURI();

        // make sure that the URL is suffixed with a forward slash
        if(substr($url,-1) !== '/') $url .= '/';
        
        $this->url = $url;
        
        $segments = $this->getSegments();
        foreach($segments as $i=>$segment){
            if(substr($segments[$i], 0, 1) == ':'){
                $this->tokens[] = substr($segments[$i], 1, strlen($segments[$i]));
            }
        }
        
        return $this;
    }

    /**
     *
     * @return array
     */
    public function getMethods() {
        return $this->methods;
    }

    /**
     * Sets the request methods accepted
     * @param array $methods
     * @return $this
     */
    public function setMethods($methods = array())
    {
        $this->methods = $methods;
        
        return $this;
    }

    /**
     * Sets the controller class method of this route
     * @param $action
     * @return $this
     */
    public function setAction($action)
    {
        $action = explode('.', $action);
        
        $this->controller = $action[0];
        
        if(count($action) != 2){
            $this->action = 'index';
        } else {
            $this->action = $action[1];
        }
        
        return $this;
    }

    /**
     * Sets the middleware class for this route
     * @param Array $middlewares
     * @return $this
     */
    public function setMiddlewares($middlewares = array())
    {
        if(!is_array($middlewares)){
            throw new Error('setMiddlewares expects an array, '.gettype($middlewares).' given');
        }

        foreach($middlewares as $i=>$middleware) {
            $middleware = explode('.', $middleware);

            if(!is_array($middlewares) || (is_array($middlewares) && empty($middlewares[0]))){
                throw new Error( __('Middlewares is baddly formated') );
            }

            $middlewareObject = new stdClass();

            $middlewareName = explode('/', $middleware[0]);


            if(count($middlewareName) >= 2) {
                $middlewareObject->name = array_pop($middlewareName);
                $middlewareObject->path = implode("/", $middlewareName);
            } else {
                $middlewareObject->name = $middleware[0];
                $middlewareObject->path = null;
            }

            if (count($middleware) != 2) {
                $middlewareObject->action = 'index';
            } else {
                $middlewareObject->action = $middleware[1];
            }

            $this->addMiddleware($middlewareObject);
        }

        return $this;
    }

    public function addMiddleware($middleware)
    {
        if(!is_object($middleware)){
            throw new Error('addMiddleware expects an object, '.gettype($middleware).' given');
        }

        if(!property_exists($middleware, 'name')){
            throw new Error("Middleware object does not contain a 'name' property");
        }

        if(!property_exists($middleware, 'action')){
            throw new Error("Middleware object does not contain a 'action' property");
        }

        $this->middlewares[] = $middleware;
    }

    /**
     * Sets the controller path
     * @param String $path
     * @return $this
     */
    public function setControllerPath($path)
    {
        $this->controllerPath = $path;
        return $this;
    }

    /**
     * Returns the controller class of this route
     * @return String
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Returns the controller class method of this route
     * @return String
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Returns the middleware class of this route
     * @return Array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Returns the controllers path
     * @return String
     */
    public function getControllerPath()
    {
        return $this->controllerPath;
    }

//    /**
//     * Returns the middleware class method of this route
//     * @return String
//     */
//    public function getMiddlewareAction()
//    {
//        return $this->mAction;
//    }

    /**
     * Returns the segments of this route
     * @return array
     */
    public function getSegments()
    {
        $tmpSegments = explode('/', $this->url);
        $segments = array();

        foreach($tmpSegments as $segment){
            if(strlen($segment)){
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /**
     * Returns the count of the segments
     * @return int
     */
    public function getSegmentsCount()
    {
        return count($this->getSegments());
    }

    /**
     * Checks if the segments passed are equal to the one contained in this route
     * @param $segments
     * @return bool
     */
    public function checkSegments($segments)
    {
        $routeSegments = $this->getSegments();

        if(count($segments) != count($routeSegments)){
            return false;
        }

        foreach($segments as $i=>$segment){
            if(substr($routeSegments[$i], 0, 1) == ':'){

                $property = substr($routeSegments[$i], 1, strlen($routeSegments[$i]));
                $this->params->$property = $segment;
                continue;
            }

            if($segment != $routeSegments[$i]){
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the params of this route
     * @return null|stdClass
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns the token used in the route segments
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns the token count of this route
     * @return int
     */
    public function getTokensCount()
    {
        return count($this->getTokens());
    }
}

?>