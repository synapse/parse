<?php

/**
 * @package     Synapse
 * @subpackage  Language
 */

defined('_INIT') or die;


class Language {
	
	public static $instance		= null;

	protected $language 		= null;
	protected $id				= null;
	protected $name				= null;
	protected $strings 			= null;
	protected $default			= null;
	
	public function __construct($lang = null)
	{
        $this->strings = new stdClass();

        // load framework translations
        $json = json_decode(file_get_contents(LIBRARY.'/language/framework.json'));
        foreach (get_object_vars($json) as $key => $value) {
            $this->strings->$key = $value;
        }

        // load application translations
        foreach(glob(LANGUAGES."/*.json") as $json){
            $json = json_decode(file_get_contents($json));

            foreach (get_object_vars($json) as $key => $value) {
                $this->strings->$key = $value;
            }
        }

		$this->setLanguage($lang);
	}

    /**
     * Returns the instance of the Language object
     * @param String $lang
     * @return Language|null
     */
    public static function getInstance($lang)
    {
        if(!self::$instance){
            self::$instance = new Language($lang);
        } else {
            self::$instance->setLanguage($lang);
        }
        return self::$instance;
    }

    /**
     * Sets the current language used for the translations
     * @param String $lang
     * @return $this
     */
	public function setLanguage($lang)
	{
        App::getSession()->set('language', $lang);

        $this->language = $lang;

        return $this;
	}

    /**
     * Loads an extra JSON language file from the provided path
     * @param String $path
     * @throws Error
     */
	public function load($path)
	{
        $json = json_decode(file_get_contents($path));

        if(json_last_error() != JSON_ERROR_NONE){
            throw new Error( __('Language file does not contain a valid JSON string: {1}', $path), null );
        }

        foreach (get_object_vars($json) as $key => $value) {
            $this->strings->$key = $value;
        }
	}

    /**
     * Returns all the translation strings
     * @return String
     */
	public function getStrings()
	{
		return $this->strings;
	}

    /**
     * Returns the current language
     * @return String
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Translates the given string in the currently set language
     * @return mixed|string
     */
    public function translate()
    {
        ini_set('memory_limit', '1024M');
        $args = func_get_args();

        // loading language strings
        $language = $this->getStrings();

        // current language
        $currentLanguage = $this->getLanguage();


        // if no string options found
        if(count($args) == 1){
            $string = $args[0];
            $stringHash = md5(strtolower($string));
            // no translation found -> return the original string
            if(!isset($language->$stringHash) || !isset($language->$stringHash->translations->$currentLanguage)){
                return $string;
            }

            $translation = $language->$stringHash->translations->$currentLanguage;

            if(DEBUG_LANGUAGE){
                $translation = '**'.$translation.'**';
            }

            return $translation;
        }

        $string = array_shift($args);
        $stringHash = md5(strtolower($string));

        $params = array();
        foreach($args as $index=>$value){
            $params['{'.($index + 1).'}'] = $value;
        }

        // no translation found -> replace the params and return the original string
        if(!isset($language->$string) && !isset($language->$stringHash->translations->$currentLanguage)){
            return str_replace(array_keys($params),array_values($params), $string);
        }

        $translation = str_replace(array_keys($params),array_values($params), $language->$stringHash->translations->$currentLanguage);

        if(DEBUG_LANGUAGE){
            $translation = '**'.$translation.'**';
        }

        return $translation;
    }
}

?>