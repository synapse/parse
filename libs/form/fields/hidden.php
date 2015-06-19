<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/Hidden
 */

defined('_INIT') or die;

class HiddenFieldType extends FieldType {

    public function render()
    {
        $html = array();

        $value = $this->field->getValue() ? $this->field->getValue() : $this->field->getDefault();
        $html[] = '<input name="'.$this->field->getName().'" type="hidden" value="'.$value.'" '.$this->field->getAttributes(true).' />';


        return implode("", $html);
    }
}