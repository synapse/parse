<?php

/**
 * @package     Synapse
 * @subpackage  Form
 */

defined('_INIT') or die;

class Form extends FormElement {

    protected $path         = null;
    protected $fieldsets    = array();
    protected $data         = array();
    protected $template     = "<form {{attributes}}>{{fieldsets}}</form>";


    /**
     * Initialize the Form object with a JSON file
     * @param String $path
     */
    public function __construct($path = null)
    {
        $this->attributes = new stdClass();

        // generate the form from a JSON file
        if($path) {
            $this->path = $path;

            if (!File::exists($path)) {
                throw new Error('Form not found at path: ' . $path);
            }
            $this->load();
        }
    }

    /**
     * Loads the content of the JSON file at the given path
     */
    protected function load()
    {
        $json = file_get_contents($this->path);
        $obj = json_decode($json);

        if(json_last_error()){
            throw new Error('The JSON file provided is not valid');
        }

        if(isset($obj->attributes) && is_object($obj->attributes)){
             $this->setAttributes($obj->attributes);
        }

        if(isset($obj->template)){
            $this->setTemplate($obj->template);
        }

        if(isset($obj->fieldsets) && is_array($obj->fieldsets) && count($obj->fieldsets)){
            $this->loadFieldsets($obj->fieldsets);
        }
    }

    /**
     * Generate Fieldset objects based on the passed array
     * @param Array $fieldsets
     */
    protected function loadFieldsets($fieldsets)
    {
        foreach($fieldsets as $fieldset){
            $newFieldset = new Fieldset((array)$fieldset);
            $newFieldset->setForm($this);

            $this->addFieldset($newFieldset);
        }
    }


    /**
     * Add a fieldset to the form collection
     * @param Fieldset $fieldset
     * @return $this
     * @throws Error
     */
    public function addFieldset($fieldset)
    {
        if(get_class($fieldset) != 'Fieldset'){
            throw new Error('addFieldset require an object of type Fieldset, '.get_class($fieldset).' received instead.');
        }

        $this->fieldsets[$fieldset->getAttribute('name')] = $fieldset;

        return $this;
    }


    /**
     * Sets the name attribute of the form
     * @param String $name
     * @return $this
     */
    public function setName($name)
    {
        $this->attributes->name = $name;
        return $this;
    }

    /**
     * Returns the name attribute of the form
     */
    public function getName()
    {
        return $this->attributes->name;
    }


    /**
     * Sets the action attribute of the form
     * @param String $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->attributes->action = $action;
        return $this;
    }

    /**
     * Return the action attribute of the form
     */
    public function getAction()
    {
        return $this->attributes->action;
    }


    /**
     * Sets the request method attribute of the form
     * @param String $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->attributes->method = $method;
        return $this;
    }

    /**
     * Return the request method attribute of the form
     */
    public function getMethod()
    {
        return $this->attributes->method;
    }

    /**
     * Sets the encoding type of the form
     * @param String $encoding
     * @return $this
     */
    public function setEnctype($encoding)
    {
        $this->attributes->enctype = $encoding;
        return $this;
    }


    /**
     * Return the enctype attribute of the form
     */
    public function getEnctype()
    {
        return $this->attributes->enctype;
    }

    /**
     * Validates the form fields values
     */
    public function validate()
    {
        $errors = array();

        foreach($this->getFieldsets() as $fieldset){
            if(!$fieldset->validate()){
                $errors[] = $fieldset->getErrors();
            }
        }

        if(count($errors)){
            $this->errors = $errors;
            return false;
        }

        return true;
    }


    /**
     * Sets the form fields values
     * @param Array $data
     * @return $this
     * @throws Error
     */
    public function setData($data = array())
    {
        if(!is_array($data) && !is_object($data)){
            throw new Error('setData expects an object or an array, '.gettype($data).' given');
        }

        $data = (array)$data;

        foreach($data as $name=>$value){
            $this->setFieldValue($name, $value);
        }

        $this->data = $data;

        return $this;
    }


    /**
     * Returns the form fields values
     * @return Array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * Sets the value of a given form field by name
     * @param String $name
     * @param Mixed $value
     * @return $this
     */
    public function setFieldValue($name, $value)
    {
        foreach($this->fieldsets as $fieldset){
            $fieldset->setFieldValue($name, $value);
        }
        return $this;
    }


    /**
     * Returns the fields value
     * @param String $name
     * @return Mixed
     */
    public function getFieldValue($name)
    {
        foreach($this->getFieldsets() as $fieldset){
            if($fieldset->hasField($name)){
                return $fieldset->getFieldValue($name);
            }
        }

        return null;
    }


    /**
     * Returns the list of fieldsets
     * @return array
     */
    public function getFieldsets()
    {
        return $this->fieldsets;
    }


    /**
     * Return the list of Fieldsets
     * @param String $name
     * @return Array
     */
    public function getFieldset($name)
    {
        return $this->fieldsets[$name];
    }


    /**
     * Renders and returns the form in html
     */
    public function render()
    {
        $template = $this->getTemplate();

        $template = str_replace("{{attributes}}", $this->getAttributes(true), $template);

        if(count($this->getFieldsets())){
            $fieldsets = array();

            foreach($this->getFieldsets() as $fieldset){
                $fieldsets[] = $fieldset->render();
            }

            $template = str_replace("{{fieldsets}}", implode("", $fieldsets), $template);
        } else {
            $template = str_replace("{{fieldsets}}", '', $template);
        }

        return $template;
    }

    public function __toString()
    {
        return $this->render();
    }
}