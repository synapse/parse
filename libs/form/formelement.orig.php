<?php

/**
 * @package     Synapse
 * @subpackage  Form Element
 */

defined('_INIT') or die;


class FormElement {

    protected $attributes   = null;
    protected $errors       = array();
    protected $template     = "";

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    /**
     * Sets the attributes for the form
     * @param Array|Object $attributes
     * @return $this
     * @throws Error
     */
    public function setAttributes($attributes)
    {
        if(!is_array($attributes) && !is_object($attributes)){
            throw new Error('setAttributes expects an object or an array, '.gettype($attributes).' given');
        }

        // TODO check the types of every item passed
        $this->attributes = (object)$attributes;
        return $this;
    }

    /**
     * Returns the form attributes
     * @return Object
     */
    public function getAttributes($toString = false)
    {
        if($toString){
            if($this->attributes){
                $attributes = array();
                foreach($this->attributes as $attribute=>$value){

                    if($value) {
                        $attributes[] = $attribute . '="' . (string)$value . '"';
                    } else {
                        $attributes[] = $attribute;
                    }
                }

                return implode(" ", $attributes);
            }

            return '';
        }

        return $this->attributes;
    }

    /**
     * Sets a specific form attribute
     * @param String $name
     * @param String $value
     */
    public function setAttribute($name, $value)
    {
        if(is_string($value) && $value === 'true'){
            $value = true;
        }

        if(is_string($value) && $value === 'false'){
            $value = false;
        }

        $this->attributes->$name = $value;
        return $this;
    }

    /**
     * Returns a specific form attribute
     * @param String $name
     * @return mixed
     */
    public function getAttribute($name, $toString = false)
    {
        if(!property_exists($this, $name)) return null;

        if($toString){
            return $name.'="'.(string)$this->attributes->$name.'"';
        }

        return $this->attributes->$name;
    }

    /**
     * Checks if the given attribute exists
     * @param String $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        return property_exists($this->attributes, $name);
    }

    /**
     * Sets the html template splitted in an array
     * @param Array $template
     */
    public function setTemplate($template)
    {
        if(!is_string($template)){
            throw new Error('setTemplate expects a string, '.gettype($template).' given');
        }

        $this->template = $template;
        return $this;
    }

    /**
     * Loads a form template from a file
     * @param String $path
     */
    public function loadTemplate($path)
    {
        if(!File::exists($path)){
            throw new Error('Form template file not found at the given path: '.$path);
        }

        $template = file_get_contents($path);
        $this->setTemplate($template);
    }

    /**
     * Return the html template
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function setError($error)
    {
        if(!is_string($error)){
            throw new Error('setError expects a string, '.gettype($error).' given');
        }

        $this->errors[] = $error;
    }

    /**
     * Returns the list of errors from the validation method
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
