<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/Date
 */

defined('_INIT') or die;

class FieldTypeDate extends FieldType {

    public $template = array(
                            "<div>",
                            "   <label class='{{labelclass}}'>{{label}}</label>",
                            "   <input type='date' id='{{id}}' name='{{name}}' value='{{value}}' {{attributes}} {{required}} />",
                            "</div>"
                        );

    public function render()
    {
        $this->replace('name', $this->field->name)
            ->replace('id', 'id="'.$this->field->id.'"')
            ->replace('labelclass', 'class="'.$this->field->labelclass.'"')
            ->replace('label', $this->field->label)
            ->replace('required', $this->field->required ? 'required=""' : '')
            ->setAttributes('attributes', $this->field->getAttributes())
            ->setValue('value', $this->field->getValue(), $this->field->getDefault());

        return $this->getTemplate();
    }
}