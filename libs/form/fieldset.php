<?php

/**
 * @package     Synapse
 * @subpackage  Form/Fieldset
 */

defined('_INIT') or die;

class Fieldset extends FormElement {

    protected $label        = null;
    protected $fields       = array();
    protected $form         = null;
    protected $template     = "<fieldset {{attributes}}><legend>{{label}}</legend>{{fields}}</fieldset>";

    /**
     * Initializes the fieldset with a name, label and fields list
     * @param String $name
     * @param String $label
     * @param Array $fields
     */
    public function __construct($options = array())
    {
        $this->attributes = new stdClass();

        if(array_key_exists('attributes', $options) && (is_array($options['attributes']) || is_object($options['attributes']))){
            $attributes = (object)$options['attributes'];
            $this->setAttributes($attributes);
        }

        if(array_key_exists('label', $options) && is_string($options['label'])){
            $this->setLabel($options['label']);
        }

        if(array_key_exists('fields', $options) && is_array($options['fields']) && count($options['fields'])){
            $this->loadFields($options['fields']);
        }
    }

    /**
     * Parse the array of fields
     */
    public function loadFields($fields = array())
    {
        foreach($fields as $field){
            $newField = new Field((array)$field);

            $this->addField($newField);
        }
        return $this;
    }

    /**
     * Add a new field to the current Fieldset
     * @param Field $field
     * @return $this
     * @throws Error
     */
    public function addField($field)
    {
        if(get_class($field) != 'Field'){
            throw new Error('addFieldset require an object of type Field, '.gettype($field).' received instead.');
        }

        if($this->getForm()) {
            $field->setForm($this->getForm());
        }

        $this->fields[$field->getName()] = $field;
        return $this;
    }


    /**
     * Return an array of Field type objects
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Sets the fieldset label
     * @param String $text
     * @return $this
     */
    public function setLabel($text)
    {
        $this->label = $text;

        return $this;
    }


    /**
     * Return the fieldset label
     * @return String
     */
    public function getLabel()
    {
        return $this->label;
    }

    public function getField($name)
    {
        if(!isset($this->fields[$name])) return $this->fields[$name];

        return null;
    }

    /**
     * Sets the value of a given Field
     * @param String $name
     * @param Mixed $value
     * @return $this
     */
    public function setFieldValue($name, $value)
    {
        if(!$this->hasField($name)) {
            throw new Error( __('Field {1} not found.', $name) );
        }

        $this->getField($name)->setValue($value);
        return $this;
    }

    /**
     * Returns the fields value
     * @param String $name
     * @return Mixed
     */
    public function getFieldValue($name)
    {
        if(!isset($this->fields[$name])) return null;

        return $this->fields[$name]->getValue();
    }

    /**
     * Check if the fieldset contains a field
     * @param String $name
     * @return bool
     */
    public function hasField($name)
    {
        if(isset($this->fields[$name])) return true;

        return false;
    }

    /**
     * Validates the fieldset fields values
     */
    public function validate()
    {
        $errors = array();

        foreach($this->getFields() as $field){
            if(!$field->validate()){

                $error = new stdClass();
                $error->field = $field;
                $error->messages = $field->getErrors();

                $errors[] = $error;
            }
        }

        if(count($errors)){
            $this->errors = $errors;
            return false;
        }

        return true;
    }

    /**
     * Returns the list of errors from the validation method
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Sets the current reference of the form
     * @param Form $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Returns the current form
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Renders and echoes out the fieldset in html
     */
    public function render()
    {
        $template = $this->getTemplate();

        $template = str_replace("{{attributes}}", $this->getAttributes(true), $template);

        if($this->label) {
            $template = str_replace("{{label}}", $this->label, $template);
        } else {
            $template = str_replace("{{label}}", '', $template);
        }

        if(count($this->getFields())){
            $fields = array();

            foreach($this->getFields() as $field){
                $fields[] = $field->render();
            }

            $template = str_replace("{{fields}}", implode("", $fields), $template);
        } else {
            $template = str_replace("{{fields}}", '', $template);
        }

        return $template;
    }
}