<?php

/**
 * @package     Synapse
 * @subpackage  Session
 */

defined('_INIT') or die;

class Session {

    public static $instance	= null;
	
	public function __construct()
	{
		$this->reload();
	}

    public static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new Session();
        }
        return self::$instance;
    }
	
	public function set($name, $value, $selfDestruct = false)
	{
		$_SESSION[$name] = $value;

        if($selfDestruct){
            if(!is_array($_SESSION['selfdestruct'])){
                $_SESSION['selfdestruct'] = array();
            }

            if(!in_array($name, $_SESSION['selfdestruct'])){
                $_SESSION['selfdestruct'][] = $name;
            }
        }

        $this->reload();
        return $this;
	}
	
	public function get($name)
	{
		if(isset($_SESSION[$name])){
            $value = $_SESSION[$name];

            if(isset($_SESSION['selfdestruct']) && in_array($name, $_SESSION['selfdestruct'])){
                foreach($_SESSION['selfdestruct'] as $i=>$val){
                    if($val == $name){
                        unset($_SESSION['selfdestruct'][$i]);
                    }
                }
                $this->remove($name);
            }

			return $value;
		}
		return null;
	}
	
	public function getAll()
	{
		return $_SESSION;
	}
	
	public function remove($name)
	{
		if(isset($_SESSION[$name])){
			unset($_SESSION[$name]);
		}
        return $this;
	}
	
	public function removeAll()
	{
		session_unset();
        return $this;
	}

    public function destroy()
    {
        session_destroy();
        return $this;
    }

    public function setID($id = null)
    {
        session_destroy();
        session_id($id);
        session_start();

        $this->reload();

        return $id;
    }

    public function getID()
    {
        return session_id();
    }

    public function reload()
    {
        $this->clean();

        if(count($_SESSION)){
			foreach($_SESSION as $name=>$value){
				$this->$name = $value;
			}
		}
    }

    protected function clean()
    {
        foreach(get_object_vars($this) as $key=>$val)
        {
            unset($this->$key);
        }
    }
}