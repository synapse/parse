<?php

/**
 * @package     Synapse
 * @subpackage  URI
 */

defined('_INIT') or die;

/**
 * Class URI
 * version 1.0
 */
class URI {

    protected $slugs = array();


    public function __construct()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = urldecode($url);

        $base = trim($this->base(true), '/');
        $url = str_replace($base, "", $url);
        $url = str_replace("//", "/", $url);

        $_slugsRaw = trim(parse_url($url, PHP_URL_PATH), '/');
        $this->slugs = explode('/', $_slugsRaw);

		if(count($this->slugs) == 1){
			if(!strlen($this->getSlugAtIndex())){
				$this->slugs = array();
			}
		}

        // remove the slug from the $_GET variable
        unset($_GET['slug']);
    }

    public function getSlugs()
    {
        return $this->slugs;
    }

    public function getSlugAtIndex($index = 0)
    {
        if(!array_key_exists($index, $this->slugs)){
            throw new Exception('Undefined slug offset!');
        }

        return $this->slugs[$index];
    }

    public function getSlugCount()
    {
        return count($this->slugs);
    }

	public function __toString()
	{
        $path = implode('/', $this->slugs);
		return strlen($path)?$path:'/';
	}

    public function root()
    {
        if(isset($_SERVER['HTTPS'])){
            $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
        }
        else{
            $protocol = 'http';
        }
        return $protocol . "://" . $_SERVER['HTTP_HOST'];
    }

    public function base($removeServer = false, $removeTail = false)
    {
        $filePath = $_SERVER['SCRIPT_FILENAME'];
        $file = basename($filePath);

        if($removeServer){
            $url = $_SERVER['PHP_SELF'];
        } else {
            $url = $this->root() . $_SERVER['PHP_SELF'];
        }

        $url = str_replace($file, "", $url);

        if($removeTail){
            if(substr($url, -1) == '/'){
                $url = substr_replace($url, "", -1);
            }
        }

        return $url;
    }

    public function current($removeServer = false)
    {
        if($removeServer){
            return $_SERVER['REQUEST_URI'];
        }

        return $this->root() . $_SERVER['REQUEST_URI'];
    }


}


?>