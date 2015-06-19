<?php

/**
 * @package     Synapse
 * @subpackage  Form/Field
 */

defined('_INIT') or die;

class Field extends FormElement {

    protected $name         = null;
    protected $label        = null;
    protected $type         = null;
    protected $filter       = null;
    protected $value        = null;
    protected $form         = null;
    protected $default      = null;
    protected $message      = null;
    protected $validations  = array();
    protected $options      = array();
    protected $fieldsPaths  = array();

    public function __construct($options = array())
    {
        $this->attributes   = new stdClass();
        $this->options      = new stdClass();
        $this->fieldsPaths  = array(APP.'/forms/', LIBRARY.'/form/');

        if(array_key_exists('attributes', $options) && (is_array($options['attributes']) || is_object($options['attributes']))){
            $attributes = (object)$options['attributes'];
            $this->setAttributes($attributes);
        }

        if(array_key_exists('options', $options) && (is_array($options['options']) || is_object($options['options']))){
            $options['options'] = (object)$options['options'];
            $this->setOptions($options['options']);
        }

        if(array_key_exists('name', $options) && is_string($options['name'])){
            $this->setName($options['name']);
        }

        if(array_key_exists('label', $options) && is_string($options['label'])){
            $this->setLabel($options['label']);
        }

        if(array_key_exists('type', $options) && is_string($options['type'])){
            $this->setType($options['type']);
        }

        if(array_key_exists('filter', $options) && is_string($options['filter'])){
            $this->setFilter($options['filter']);
        }

        if(array_key_exists('message', $options) && is_string($options['message'])){
            $this->setMessage($options['message']);
        }

        if(array_key_exists('validate', $options) && is_array($options['validate']) && count($options['validate'])){
            $this->setValidations($options['validate']);
        }

        if(array_key_exists('default', $options)){
            $this->setDefault($options['default']);
        }

        if(array_key_exists('value', $options)){
            $this->setValue($options['value']);
        }

        if(array_key_exists('form', $options) && get_class($options['form']) == 'Form'){
            $this->setForm($options['form']);
        }

        return $this;
    }


    public function setName($name)
    {
        if(!is_string($name)){
            throw new Error('setName expects a string, '.gettype($name).' given');
        }

        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setLabel($label)
    {
        if(!is_string($label)){
            throw new Error('setLabel expects a string, '.gettype($label).' given');
        }

        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setDefault($default)
    {
        $this->default = $default;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function setType($type)
    {
        if(!is_string($type)){
            throw new Error('setType expects a string, '.gettype($type).' given');
        }

        //$this->type = $type;

        foreach($this->getIncludePaths() as $path){
            $path = Path::clean($path.'/fields/'.$type.'.php');

            if(File::exists($path)){
                require_once($path);

                $typeClass  = ucfirst($type).'FieldType';

                if(!class_exists($typeClass)){
                    throw new Error( __('Field type class not found!').' '.$typeClass );
                }

                $fieldType = new $typeClass($this);

                if(!method_exists($typeClass, 'render')){
                    throw new Error( __('Field type class method "render()" not found in class "{1}"', $typeClass), null );
                }

                $this->type = $fieldType;

                break;
            }
        }

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setFilter($filter)
    {
        if(!is_string($filter)){
            throw new Error('setFilter expects a string, '.gettype($filter).' given');
        }

        $this->filter = $filter;

        return $this;
    }

    public function setValidations($validations = array())
    {
        if(!is_array($validations)){
            throw new Error('setValidations expects an array, '.gettype($validations).' given');
        }

        foreach($validations as $validation){
            $this->setValidation($validation);
        }

        return $this;
    }

    public function setValidation($validation)
    {
        if(!is_array($validation) && !is_object($validation)){
            throw new Error('setValidations expects an array or object, '.gettype($validation).' given');
        }

        $validation = (object)$validation;

        if(!property_exists($validation, 'type')){
            throw new Error('Validation must have a type');
        }

        $this->validations[] = $validation;

        return $this;
    }

    public function getValidations()
    {
        return $this->validations;
    }

    public function setMessage($message)
    {
        if(!is_string($message)){
            throw new Error('setMessage expects a string, '.gettype($message).' given');
        }

        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setForm($form)
    {
        if(get_class($form) != 'Form'){
            throw new Error('setForm require an object of type Form, '.gettype($form).' received instead.');
        }

        $this->form = $form;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function setOptions($options)
    {
        if(!is_array($options) && !is_object($options)){
            throw new Error('setOptions expects an object or an array, '.gettype($options).' given');
        }

        $this->options = (object)$options;
        return $this;
    }


    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($name, $value)
    {
        $this->options->$name = $value;
        return $this;
    }

    public function getOption($name)
    {
        return $this->options->$name;
    }

    public function hasOption($name)
    {
        return property_exists($this->options, $name);
    }

    public function addIncludePath($path)
    {
        if(!Folder::exists($path)){
            throw new Error('Include path not found');
        }

        array_unshift($this->fieldsPaths, $path);

        return $this;
    }

    public function getIncludePaths()
    {
        return $this->fieldsPaths;
    }

    public function validate()
    {
        // check required value
        $value = $this->getValue();
        $message = $this->getMessage();

        if($this->hasAttribute('required') && $this->getAttribute('required') === true && empty($value)){
            if($message && !empty($message)) {
                $this->setError( __($message) );
            } else {
                $this->setError( __("Field {1}: value required", $this->getName()) );
            }
        }


        // check field type validation
        if($this->getType()){
            if(!$this->getType()->validate()){
                if($message && !empty($message)) {
                    $this->setError( __($message) );
                } else {
                    $this->setError( __("Invalid field value") );
                }
            }
        }


        // if there are not extra validations or if the value is empty
        // return OK
        if(!count($this->getValidations())){
            return true;
        }


        foreach($this->getValidations() as $validate){

            foreach($this->getIncludePaths() as $path) {
                $path = Path::clean($path . '/validations/' . $validate->type . '.php');
                if(File::exists($path)){
                    require_once($path);

                    $validateClass = ucfirst($validate->type).'FieldValidate';

                    if(!class_exists($validateClass)){
                        throw new Error( __('Field validation class not found!').' '.$validateClass );
                    }

                    if(!method_exists($validateClass, 'test')){
                        throw new Error( __('Field validation class method "text()" not found in class "{1}"', $validateClass), null );
                    }

                    if(!$validateClass::test($this->getValue(), $this, $validate)){
                        if(isset($validate->message) && !empty($validate->message)){
                            $this->setError( __($validate->message) );
                        } else {
                            $this->setError( __("Invalid field value") );
                        }
                    }

                }
            }

        }

        if(count($this->getErrors())) return false;

        return true;
    }

    public function render()
    {
        if(!$this->getType()){
            throw new Error('Missing field type');
        }

        if(!$this->getName()){
            throw new Error('Missing field name');
        }

        return $this->getType()->render();
    }
}















