<?php

/**
 * @package     Synapse
 * @subpackage  URI/Request
 * @ver         1.2
 */

defined('_INIT') or die;

class Request {

    protected $_params      = '';
    protected $_type        = null;
    protected $_slugs       = null;
    protected $_ajax        = true;
    protected $_files       = array();
    protected $_contentType = null;
    protected $_origin      = null;
    protected $_userAgent   = null;
    protected $_headers     = array();

    public function __construct()
    {
        $this->_params = new stdClass();

        $request     = null;
        $this->_type = $this->getType();

        switch($this->_type){
            case 'GET':
                $request = $_GET;
                break;
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $request);
                break;
            case 'POST':
                $request = $_POST;

                if(count($_FILES))
                {
                    $this->_files = $_FILES;
                }
                break;
            case 'PUT':
                parse_str(file_get_contents('php://input'), $request);
                if(count($_FILES))
                {
                    $this->_files = $_FILES;
                }
                break;
        }

        $this->_contentType  = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null;
        $this->_origin       = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
        $this->_userAgent    = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        // read the request headers
        $this->readHeaders();

        if(count($request)){
            foreach($request as $k=>$v){
                if($k === 'slug'){
                    $this->_slugs = $v;
                    continue;
                }
                $this->_params->$k = $v;
                $this->$k = $v;
            }
        }

        if(in_array($this->_type, array('POST','PUT')) && count($this->getFiles()))
        {
            $this->_params->files = $this->getFiles();
        }

        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        {
            $this->_ajax = true;
        }
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getSlugs()
    {
        return $this->_slugs;
    }

    public function getValue($key)
    {
        return isset($this->_params->$key) ? $this->_params->$key : null;
    }

    public function setValue($key, $value)
    {
        $this->$key = $value;
    }

    public function getJSON()
    {
        if(strpos($this->_contentType, 'application/json') === false){
            return null;
        }

        $rawJSON = file_get_contents('php://input');
        return json_decode($rawJSON);
    }

    /**
     * Returns the request type. Example GET POST
     * @return mixed
     */
    public function getType()
    {
        if($this->_type) return $this->_type;

        if(isset($_POST['_METHOD']) && !empty($_POST['_METHOD'])){
            $method = $_POST['_METHOD'];
            unset($_POST['_METHOD']);
            if(strtolower($method) == 'put'){
                return 'PUT';
            } else if (strtolower($method) == 'delete'){
                return 'DELETE';
            }
        }

        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Check if the request is AJAX based
     * @return bool
     */
    public function isAjax()
    {
        return $this->_ajax;
    }

    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * Returns the request IP address
     * @return mixed
     */
    public function getIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Returns the request language
     * @param $long | bool
     * @return String | null
     */
    public function getLanguage($long = false)
    {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])){
            $lng = $this->parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            return $long ? $lng : substr($lng, 0, 2);
        } else {
            $lng = $this->parseDefaultLanguage(NULL);
            return $long ? $lng : substr($lng, 0, 2);
        }
    }

    /**
     * Parses the request language and returns the one with the highest Q value
     * @param $http_accept
     * @param string $deflang
     * @return int|string
     */
    protected function parseDefaultLanguage($http_accept, $deflang = "en-US") {
        if(isset($http_accept) && strlen($http_accept) > 1)  {
            # Split possible languages into array
            $x = explode(",",$http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if(preg_match("/(.*);q=([0-1]{0,1}.d{0,4})/i",$val,$matches))
                    $lang[$matches[1]] = (float)$matches[2];
                else
                    $lang[$val] = 1.0;
            }

            #return default language (highest q-value)
            $qval = 0.0;
            foreach ($lang as $key => $value) {
                if ($value > $qval) {
                    $qval = (float)$value;
                    $deflang = $key;
                }
            }
        }

        if(strlen($deflang) == 2){
            return $deflang.'-'.strtoupper($deflang);
        }

        return $deflang;
    }

    /**
     * Return the request content type
     * @return String | null
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * Returns the request user agent
     * @return String | null
     */
    public function getUserAgent()
    {
        return $this->_userAgent;
    }

    /**
     * Returns the request origin
     * @return String | null
     */
    public function getOrigin()
    {
        return $this->_origin;
    }

    protected function readHeaders()
    {
        // if not Apache
        if (!function_exists('getallheaders'))
        {
            $headers = '';
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            $this->_headers = $headers;
        } else {
            $this->_headers = getallheaders();
        }

        return $this;
    }

    /**
     * Returns a list of headers
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * @param String $key
     * @return String | mixed
     * @throws Error
     */
    public function getHeader($key = null)
    {
        if(!$key)
        {
            throw new Error( __('Header name missing!'), null );
        }

        $headers = $this->getHeaders();

        if(!array_key_exists($key, $headers)) return null;

        return $headers[$key];
    }

    /**
     * Sets a header key with the specified value
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        $this->_headers[$key] = $value;
    }
}

?>
