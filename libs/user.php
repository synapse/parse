<?php

/**
 * @package     Synapse
 * @subpackage  User
 */

defined('_INIT') or die;

class User {

    public static $instance	= null;


	public function __construct($id)
	{
        $user = $this->loadUser($id);
        foreach($user as $k=>$v){
            $this->$k = $v;
        }
	}

    public static function getInstance($id = null)
    {
        if(!self::$instance || $id){
            self::$instance = new User($id);
        }
        return self::$instance;
    }

    protected function loadUser($id)
    {
        $db = App::getInstance()->getDBO();
        $query = $db->getQuery(true);
        $query->select('*')->from('#__users')->where('id = '.$db->quote($id));
        $db->setQuery($query);

        return $db->loadObject();
    }
}




















