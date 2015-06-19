<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/Text
 */

defined('_INIT') or die;

class TextFieldType extends FieldType {

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
        $html[] = '<input type="text" class="form-control"';

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

        if($this->getField()->getValue())
        {
            $html[] = ' value="'.$this->getField()->getValue().'"';
        }
        else if($this->getField()->getDefault())
        {
            $html[] = ' value="'.$this->getField()->getDefault().'"';
        }

        $html[] = '>';
        $html[] = '</div>';

        return implode("", $html);
    }

}
