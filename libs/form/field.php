<?php

/**
 * @package     Synapse
 * @subpackage  Form/Field
 */

defined('_INIT') or die;

class Field extends FormElement {

    protected $type         = null;
    protected $value        = null;
    protected $form         = null;
    protected $default      = null;
    protected $options      = null;
    protected $message      = null;
    protected $filter       = null;
    protected $validators   = array();


    public function __construct($type = null)
    {
        if($type === null)
        {
            throw new Error('Field type not specified');
        }

        if(!is_string($type))
        {
            throw new Error('Field type expects a string, '.gettype($type).' given');
        }

        $fieldsPaths  = array(APP.'/form/fields/', LIBRARY.'/form/fields/');

        $hasType = false;
        foreach($fieldsPaths as $path){

            $path = Path::clean($path.$type.'.php');

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
                $hasType = true;

                break;
            }
        }

        if(!$hasType)
        {
            throw new Error( __('Field type "{1}" not found. Please make sure that the type specified has a corresponding class', $type), null );
        }

        $this->options = new stdClass();
    }

    public function setName($name)
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
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

    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Passed the fields value trough a filtering process
     * param Bool $return
     */
    public function filter($return = false)
    {
        if(!$this->getValue() && !$this->getFilter()) return $this;

        $value = $this->getValue();

        switch ($this->getFilter()) {
            case 'integer':
                $this->setValue( StringFilter::clean($value, 'integer') );
                break;
            case 'unsigned':
                $this->setValue( StringFilter::clean($value, 'uint') );
                break;
            case 'double':
            case 'float':
                $this->setValue( StringFilter::clean($value, 'float') );
                break;
            case 'bool':
            case 'boolean':
                $this->setValue( StringFilter::clean($value, 'boolean') );
                break;
            case 'word':
                $this->setValue( StringFilter::clean($value, 'word') );
                break;

            /* MORE TO BE ADDED */

            default:
                $this->setValue( StringFilter::clean($value, 'raw') );
                break;
        }

        if($return)
        {
            return $this->getValue();
        }

        return $this;
    }

    public function setValidators($validators = array())
    {
        if(!is_array($validators)){
            throw new Error('setValidators expects an array, '.gettype($validators).' given');
        }

        foreach($validators as $validator){
            $this->setValidator($validator);
        }

        return $this;
    }

    public function setValidator($validator)
    {
        if(!is_array($validator) && !is_object($validator)){
            throw new Error('setValidator expects an array or object, '.gettype($validator).' given');
        }

        $validator = (object)$validator;

        if(!property_exists($validator, 'type')){
            throw new Error('Validator must have a type');
        }

        $this->validators[] = $validator;

        return $this;
    }

    public function getValidators()
    {
        return $this->validators;
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
            throw new Error(__('setForm require an object of type Form, {1} received instead.', gettype($form)));
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

    public function validate()
    {
        // check required value
        $value = $this->getValue();
        $message = $this->getMessage();

        if(
            $this->hasAttribute('required') &&
            (
                $this->getAttribute('required') === true ||
                $this->getAttribute('required') === 'true' ||
                $this->getAttribute('required') === 1 ||
                $this->getAttribute('required') === '1'
            ) &&
            empty($value)
        ){
            if($message && !empty($message)) {
                $this->setError( __($message) );
            } else {
                $this->setError( __("Field {1}: value required", $this->getName()) );
            }
        }


        // check field type validation

        if(!$this->getType()->validate()){
            if($message && !empty($message)) {
                $this->setError( __($message) );
            } else {
                $this->setError( __("Invalid field value") );
            }
        }

        if(count($this->getValidators()))
        {
            $foundValidators = false;

            foreach($this->getValidators() as $validator)
            {
                $validationPaths  = array(APP.'/form/validators/', LIBRARY.'/form/validators/');

                foreach($validationPaths as $path)
                {
                    $path = Path::clean($path . $validator->type . '.php');

                    if(File::exists($path))
                    {
                        require_once($path);

                        $validatorClass = ucfirst($validator->type).'FieldValidator';

                        if(!class_exists($validatorClass))
                        {
                            throw new Error( __('Field validator class not found!').' '.$validatorClass );
                        }

                        if(!method_exists($validatorClass, 'test'))
                        {
                            throw new Error( __('Field validation class method "test()" not found in class "{1}"', $validatorClass), null );
                        }

                        if(!$validatorClass::test($this->getValue(), $this, $validator))
                        {
                            if(isset($validator->message) && !empty($validator->message))
                            {
                                $this->setError( __($validator->message) );
                            }
                            else
                            {
                                $this->setError( __("Invalid field value") );
                            }
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
        return $this->getType()->render();
    }
}
