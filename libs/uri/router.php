<?php

/**
 * @package     Synapse
 * @subpackage  URI/Router
 */

defined('_INIT') or die;

/**
 * Class Router
 * version 1.0
 */
class Router {

    private $routes  = array();
    private $current = null;

    public function __construct()
    {

    }

    /**
     * Add a GET route
     * @param String $segments
     * @param String $action
     */
    public function get($segments, $action, $middleware = null)
    {
        return $this->map(array('GET'), $segments, $action, $middleware);
    }

    /**
     * Add a POST route
     * @param String $segments
     * @param String $action
     */
    public function post($segments, $action, $middleware = null)
    {
        return $this->map(array('POST'), $segments, $action, $middleware);
    }

    /**
     * Add a PUT route
     * @param String $segments
     * @param String $action
     */
    public function put($segments, $action, $middleware = null)
    {
        return $this->map(array('PUT'), $segments, $action, $middleware);
    }

    /**
     * Add a DELETE route
     * @param String $segments
     * @param String $action
     */
    public function delete($segments, $action, $middleware = null)
    {
        return $this->map(array('DELETE'), $segments, $action, $middleware);
    }

    /**
     * Add a MODULE route
     * @param $segments
     * @param $action
     * @param null $middleware
     * @return Router
     */
    public function module($segments, $module, $middleware = null)
    {
        $routesPath = MODULES.'/'.$module.'/routes.php';

        if(!file_exists($routesPath)){
            throw new Error( __('Module routes file not found!').' '.$routesPath );
        }

        require_once($routesPath);

        $routesClass  = ucfirst($module).'Routes';

        if(!class_exists($routesClass)){
            throw new Error( __('Module routes class not found!').' '.$routesClass );
        }

        // load module languages
        $language = App::getLanguage();
        foreach(glob(MODULES.'/'.$module.'/languages/*.json') as $json){
            $language->load($json);
        }

        $moduleRoutes = new $routesClass($module, $segments, $this, $middleware);
        $moduleRoutes->getRoutes();
    }

    /**
     * Map a route
     * @param Array $type
     * @param String $segments
     * @param String $action
     */
    public function map($type, $segments, $action, $middleware = null)
    {
        $route = new Route();

        $route->setUrl($segments)
              ->setMethods($type)
              ->setAction($action)
              ->setControllerPath(CONTROLLERS);

        if($middleware){
            if(is_string($middleware)){
                $middleware = array($middleware);
            }

            $route->setMiddlewares($middleware);
        }

        $this->addRoute($route);
        $this->reorderRoutes();
        return $this;
    }

    /**
     * Reorder the routes bases on the segment count
     */
    public function reorderRoutes()
    {
        $tmp = array();

        foreach($this->routes as $route){
            $tmp[$route->getSegmentsCount()][] = $route;
        }

        ksort($tmp);

        foreach($tmp as $k=>&$routes){
            usort($routes, function($a, $b){
                return $a->getTokensCount() > $b->getTokensCount();
            });
        }

        $this->routes = array();
        foreach($tmp as $k=>&$routes){
            foreach($routes as $route){
                $this->routes[] = $route;
            }
        }
    }

    public function addRoute($route)
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * Returns all the available routes
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Returns the current route
     * @return null
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Checks the current route and executes the controller and middleware attached to it
     * @throws Error
     */
    public function match()
    {
        $app           = App::getInstance();
        $request       = $app->getRequest();
        $uri           = $app->getURI();

        $segments      = $uri->getSlugs();
        $requestType   = $request->getType();
        $this->current = null;

        foreach($this->routes as $route){
            if(
                in_array($requestType, $route->getMethods()) &&
                $route->checkSegments($segments)
            ){
                $this->current = $route;
                break;
            }
        }

        if(!$this->current){
            echo '<pre>';
            print_r($segments);
            echo '</pre>';

            die('404 Route not found');
        }

        if(count($this->current->getMiddlewares())){
            $this->_execMiddlewares($request);
        }

        $this->_execController($request);
    }

    protected function _execController($request)
    {
        $controllerFileName = strtolower($this->current->getController()).'.php';
        $controllerPath = $this->current->getControllerPath().'/'.$controllerFileName;
        if(!file_exists($controllerPath)){
            throw new Error( __('Controller file not found: "{1} at path {2}"', $controllerFileName, $controllerPath), null );
        }

        require_once($controllerPath);

        $controller = explode("/", $this->current->getController());

        $controllerClass  = ucfirst(array_pop($controller)).'Controller';
        $controllerMethod = $this->current->getAction();

        if(!class_exists($controllerClass)){
            throw new Error( __('Controller class not found!').' '.$controllerClass );
        }

        $controller = new $controllerClass($this, $this->current->getParams(), $request->getParams());

        if(!method_exists($controller, $controllerMethod)){
            throw new Error( __('Controller class method "{1}" not found in class "{2}"', $controllerMethod, $controllerClass), null );
        }

        $controller->$controllerMethod();
    }

    protected function _execMiddlewares($request)
    {
        $middlewares = $this->current->getMiddlewares();

        foreach($middlewares as $i=>$middleware) {
            $middlewareFileName = strtolower($middleware->name) . '.php';
            $middlearePath = MIDDLEWARES . '/' . $middleware->path . $middlewareFileName;
            if (!file_exists($middlearePath)) {
                throw new Error(__('Middleware file not found: "{1}"', $middlewareFileName), null);
            }

            require_once($middlearePath);

            $middlewareClass = ucfirst($middleware->name) . 'Middleware';
            $middlewareMethod = $middleware->action;

            if (!class_exists($middlewareClass)) {
                throw new Error(__('Middleware class not found!') . ' ' . $middlewareClass);
            }

            $middleware = new $middlewareClass($this, $this->current->getParams(), $request->getParams());

            if (!method_exists($middleware, $middlewareMethod)) {
                throw new Error(__('Middleware class method "{1}" not found in class "{2}"', $middlewareMethod, $middlewareClass), null);
            }

            $middleware->$middlewareMethod();
        }
    }


    /**
     * Redirect to another url
     * @param $url
     */
    public function redirect($url)
    {
        header('location: '.$url);
        die;
    }
}

?>
