<?php

/**
 * @package     Synapse
 * @subpackage  Form
 */

defined('_INIT') or die;

class Form extends FormElement {

    protected $path         = null;
    protected $fields       = array();
    protected $table        = null;
    protected $fieldsets    = array();

    /**
     * Initialize the Form object with a JSON file
     * @param String $path
     */
    public function __construct($path = null)
    {
        parent::__construct();

        // generate the form from a JSON file
        if($path) {
            $this->setPath($path);
            $this->load();
        }
    }

    public function setPath($path)
    {
        if(!is_string($path)){
            throw new Error('setPath expects a string as the value, '.gettype($value).' given');
        }

        $this->path = $path;
        return $this;
    }

    /**
     * Loads the content of the JSON file at the given path
     */
    public function load()
    {
        if(!$this->path)
        {
            throw new Error('Form path not set');
        }

        if (!File::exists($this->path))
        {
            throw new Error('Form not found at path: ' . $path);
        }

        $json = file_get_contents($this->path);
        $form = json_decode($json);

        if(json_last_error()){
            throw new Error('The JSON file provided is not valid');
        }

        if(isset($form->attributes)){
            $this->setAttributes($form->attributes);
        }

        if(isset($form->fields) && is_array($form->fields) && count($form->fields)){
            $this->loadFields($form->fields);
        }

        if(isset($form->fieldsets) && is_array($form->fieldsets) && count($form->fieldsets)){
            $this->loadFieldsets($form->fieldsets);
        }
    }

    protected function loadFields($fields)
    {
        foreach ($fields as $fieldOptions) {
            $field = new Field($fieldOptions->type);

            if(isset($fieldOptions->attributes))
            {
                $field->setAttributes($fieldOptions->attributes);
            }

            if(isset($fieldOptions->options))
            {
                $field->setOptions($fieldOptions->options);
            }

            if(isset($fieldOptions->value))
            {
                $field->setValue($fieldOptions->value);
            }

            if(isset($fieldOptions->default))
            {
                $field->setDefault($fieldOptions->default);
            }

            if(isset($fieldOptions->message))
            {
                $field->setMessage($fieldOptions->message);
            }

            if(isset($fieldOptions->filter))
            {
                $field->setFilter($fieldOptions->filter);
            }

            $field->setForm($this);

            $this->addField($field);
        }
    }

    public function addFields($fields = array())
    {

    }

    public function addField($field)
    {
        if(get_class($field) !== 'Field')
        {
            throw new Error(__('addField expects a Field object, {1} given', get_class($field)));
        }

        $this->fields[] = $field;
    }

    /**
     * Returns all the forms fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns a single field based on its attribute value
     */
    public function getField($name, $attribute = 'name')
    {
        foreach ($this->getFields() as $field) {
            if($field->getAttribute($attribute) == $name)
            {
                return $field;
            }
        }

        return null;
    }

    /**
     * Generate Fieldset objects based on the passed array
     * @param Array $fieldsets
     */
    protected function loadFieldsets($fieldsets)
    {
        foreach($fieldsets as $fieldset){
            //$newFieldset = new Fieldset((array)$fieldset);
            //$newFieldset->setForm($this);

            //$this->addFieldset($newFieldset);
        }
    }

    /**
     * Add a fieldset to the form collection
     * @param Fieldset $fieldset
     * @return $this
     * @throws Error
     */
     /*
    public function addFieldset($fieldset)
    {
        if(get_class($fieldset) != 'Fieldset'){
            throw new Error('addFieldset require an object of type Fieldset, '.get_class($fieldset).' received instead.');
        }

        $this->fieldsets[$fieldset->getAttribute('name')] = $fieldset;

        return $this;
    }
    */

    /**
     * Sets the name attribute of the form
     * @param String $name
     * @return $this
     */
    public function setName($name)
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    /**
     * Returns the name attribute of the form
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }


    /**
     * Sets the action attribute of the form
     * @param String $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->setAttribute('action', $action);
        return $this;
    }

    /**
     * Return the action attribute of the form
     */
    public function getAction()
    {
        return $this->getAttribute('action');
    }


    /**
     * Sets the request method attribute of the form
     * @param String $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->setAttribute('method', $method);
        return $this;
    }

    /**
     * Return the request method attribute of the form
     */
    public function getMethod()
    {
        return $this->getAttribute('method');
    }

    /**
     * Sets the encoding type of the form
     * @param String $encoding
     * @return $this
     */
    public function setEnctype($encoding)
    {
        $this->setAttribute('enctype', $encoding);
        return $this;
    }


    /**
     * Return the enctype attribute of the form
     */
    public function getEnctype()
    {
        return $this->getAttribute('enctype');
    }

    /**
     * Validates the form fields values
     */
    public function validate()
    {
        $errors = array();

        foreach($this->getFields() as $field){
            if(!$field->validate()){
                $errors[] = $field->getErrors();
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
            throw new Error(__('setData expects an object or an array, {1} given', gettype($data)));
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
        $data = array();

        foreach ($this->getFields() as $field) {
            if($field->hasAttribute('name'))
            {
                $data[$field->getAttribute('name')] = $field->getValue();
            }
        }

        return $data;
    }

    /**
     * Sets the value of a given form field by a given attribute, by default is name
     * @param String $name
     * @param Mixed $value
     * @param String $attribute
     * @return $this
     */
    public function setFieldValue($name, $value, $attribute = 'name')
    {
        $field = $this->getField($name, $attribute);

        if($field)
        {
            $field->setValue($value);
        }

        return $this;
    }

    /**
     * Returns the fields value
     * @param String $name
     * @param String $attribute
     * @return Mixed
     */
    public function getFieldValue($name, $attribute = 'name')
    {
        $field = $this->getField($name, $attribute);

        if($field)
        {
            return $field->getValue();
        }

        return null;
    }

    public function filter()
    {
        foreach ($this->getFields() as $field) {
            $field->filter();
        }

        return $this;
    }

    public function setTable($table)
    {
        if(!is_string($table)){
            throw new Error('setTable expects a string as the value, '.gettype($table).' given');
        }

        $this->table = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function save()
    {
        $db = $this->getDBO();

    }

    /**
     * Renders and returns the form in html
     */
    public function render()
    {

        /*
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
        */

        $html = array();
        $html[] = '<form';

        if($this->getAttributes())
        {
            foreach ($this->getAttributes() as $attrName => $attrValue) {
                $html[] = ' '.$attrName.'="'.$attrValue.'"';
            }
        }

        $html[] = '>';

        ob_start();

        foreach ($this->getFields() as $field) {
            $html[] = $field->render();
        }

        $html[] = '</form>';

        echo implode("", $html);

        return ob_get_clean();
    }

    public function __toString()
    {
        return $this->render();
    }
}
