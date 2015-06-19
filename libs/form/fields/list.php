<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/List
 */

defined('_INIT') or die;

class ListFieldType extends FieldType
{

    public function render()
    {
        $html = array();

        $html[] = '<div class="form-group">';

        if($this->getField()->hasOption('label'))
        {
            $html[] = '<label';

            if($this->getField()->hasAttribute('id'))
            {
                $html[] = ' for="'.$this->getField()->getAttribute('id').'"';
            }

            $html[] = '>';
            $html[] = __($this->getField()->getOption('label'));
            $html[] = '</label>';
        }
        $html[] = '<select class="form-control"';

        if(count($this->getField()->getAttributes()))
        {
            foreach ($this->getField()->getAttributes() as $attrName => $attrValue) {

                if(in_array($attrName, array('placeholder')))
                {
                    $attrValue = __($attrValue);
                }

                $html[] = ' '.$attrName.'="'.$attrValue.'"';
            }
        }

        $html[] = '>';

        if($this->getField()->hasOption('items') && count($this->getField()->getOption('items')))
        {
            foreach ($this->getField()->getOption('items') as $item) {
                $html[] = '<option value="'.$item->text.'">'.$item->text.'</option>';
            }
        }

        /*
        if($this->getField()->getValue())
        {
            $html[] = ' value="'.$this->getField()->getValue().'"';
        }
        else if($this->getField()->getDefault())
        {
            $html[] = ' value="'.$this->getField()->getDefault().'"';
        }
        */

        $html[] = '</select>';

        $html[] = '</div>';

        return implode("", $html);
    }

    /*
    public function render()
    {
        $html = array();

        $html[] = '<div>';
        $labelClass = $this->field->getOption('labelclass');
        $labelClass = !empty($labelClass) ? 'class="'.$labelClass.'"' : '';
        $html[] = '<label class="'.$labelClass.'">'.$this->field->getLabel().'</label>';


        if ($this->field->hasOption('layout') && $this->field->getOption('layout') == 'checkboxes') {
            $html[] = $this->getCheckboxes();
        } else if ($this->field->hasOption('layout') && $this->field->getOption('layout') == 'radios'){
            $html[] = $this->getRadios();
        } else {
            $html[] = $this->getDropdown();
        }

        $html[] = '</div>';

        return implode("", $html);
    }

    protected function getDropdown()
    {
        $html = array();


        $html[] = '<select name="'.$this->field->getName().'" '.$this->field->getAttributes(true).'>';

        foreach($this->field->getOption('items') as $item){

            $selected = '';
            $found = false;
            if(($item->value == $this->field->getValue()) || ($this->field->hasAttribute('multiple') && in_array($item->value, $this->field->getValue()))){
                $selected = 'selected';

                if(!$this->field->hasAttribute('multiple')) {
                    $found = true;
                }

            } else if ($item->value == $this->field->getDefault() && !$found){
                $selected = 'selected';
            } else {
                $selected = '';
            }


            $html[] = '<option value="'.$item->value.'" '.$selected.'>'.$item->text.'</option>';
        }

        $html[] = '</select>';
        return implode("", $html);
    }

    protected function getCheckboxes()
    {
        $html = array();

        foreach($this->field->getOption('items') as $item){
            $checked = in_array($item->value, $this->field->getValue()) ? 'checked' : '';
            $html[] = '<div>';
            $html[] = '    <label>';
            $html[] = '     <input type="checkbox" name="'.$this->field->getName().'" value="'.$item->value.'" '.$checked.' />'.$item->text;
            $html[] ='    </label>';
            $html[] ='</div>';
        }

        return implode("", $html);
    }

    protected function getRadios()
    {
        $html = array();

        foreach($this->field->getOption('items') as $item){
            $checked = ($item->value == $this->field->getValue()) ? 'checked' : '';
            $html[] = '<div>';
            $html[] = '    <label>';
            $html[] = '     <input type="radio" name="'.$this->field->getName().'" value="'.$item->value.'" '.$checked.' />'.$item->text;
            $html[] ='    </label>';
            $html[] ='</div>';
        }

        return implode("", $html);
    }

    public function validate()
    {
        $valid = false;

        if(isset($this->field->options) && count($this->field->options->items)) {
            foreach($this->field->options->items as $item){
                if($item->value == $this->field->getValue()){
                    $valid = true;
                }
            }
        }

        return $valid;
    }
    */
}
