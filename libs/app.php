<?php

/**
 * @package     Synapse
 * @subpackage  App
 */

defined('_INIT') or die;


class App {

    public static $instance	 = null;
    public static $dbo 		 = null;
	public static $config 	 = null;
	public static $session 	 = null;
	public static $language  = null;
	public static $menu      = null;
    public static $page      = null;
    public static $uri       = null;
    public static $router    = null;
    public static $request   = null;
    protected $messages      = array();

    public static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new App();
        }
        return self::$instance;
    }
	
	public static function getDBO()
	{
        $config = self::getConfig();
        if(empty($config->db_host)) return null;
        
        if(!self::$dbo){
            $options = array(
                'host'      => self::getConfig()->db_host,
                'user'      => self::getConfig()->db_user,
                'password'  => self::getConfig()->db_pass,
                'database'  => self::getConfig()->db_name,
                'port'      => self::getConfig()->db_port,
            );

            self::$dbo = new DB($options);
        }
        return self::$dbo;
	}
	
	public static function getConfig()
	{
        if(!self::$config){
            self::$config = new Config();
        }
        return self::$config;
	}
	
	public static function getSession()
	{
        if(!self::$session){
            self::$session	= Session::getInstance();
        }
        return self::$session;
	}
	
	public static function getLanguage()
	{
        $session 	        = App::getSession();
        $sessionLanguage 	= $session->get('language');
		$request	        = App::getRequest();

        // if there is not language object create one and assign the browser language
        if(!self::$language){

            // if there is a session language saved set it
            if(isset($sessionLanguage)){
                $lng = $sessionLanguage;

            // else set the language from the browser language
            } else {
                $lng = $request->getLanguage();
            }

            self::$language = Language::getInstance($lng);
        }


        return self::$language;
	}

    public static function getMenu()
	{
        if(!self::$menu){
            self::$menu = Menu::getInstance();
        }
        return self::$menu;
	}


    public static function getURI()
	{
        if(!self::$uri){
            self::$uri = new URI();
        }
        return self::$uri;
	}

    public static function getRequest()
	{
        if(!self::$request){
            self::$request = new Request();
        }
        return self::$request;
	}
	
	public static function getRouter()
	{
        if(!self::$router){
            self::$router = new Router();
        }
        return self::$router;
	}

    /**
    * Triggers and event and launches all plugins that are registered to this specific event
    */
    public function trigger($event, $params = array())
    {
        foreach(glob(PLUGINS."/*.php") as $pluginFile){
            include($pluginFile);
            $pluginFile = File::getName($pluginFile);
            $plugin     = ucfirst(File::stripExt($pluginFile)).'Plugin';
            //TODO: check if the plugin class exists before calling the dispatch on it
            //TODO: check if method dispatch exists before calling it
            $plugin::dispatch($event, $params);
        }
    }

    /**
     * Adds a new message to the stack of messages
     * @param String $message
     * @param String $type
     * @return App
     */
    public function enqueueMessage($message, $type = "alert alert-info")
    {
        if(!isset($this->messages[$type])){
            $this->messages[$type] = array();
        }

        if(is_array($message)){
            foreach($message as $i=>$msg){
                $this->messages[$type][] = $msg;
            }
        } else {
            $this->messages[$type][] = $message;
        }

        App::getSession()->set('messages', $this->messages, true);

        return $this;
    }

    /**
     * Returns the stack of saved messages
     * @return array
     */
    public function getMessageQueue()
    {
        $messages = $this->messages;
        $this->messages = array();

        $unreadMessages = App::getSession()->get('messages');
        if(is_array($unreadMessages)){
            return array_merge($messages, $unreadMessages);
        }

        return $messages;
    }

    public function run()
    {
        ob_start();
		
        App::getRouter()->match();

        $contents = ob_get_contents();
		ob_end_clean();

		echo $contents;
    }
}

?>