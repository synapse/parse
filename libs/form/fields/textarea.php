<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/Textarea
 */

defined('_INIT') or die;

class TextareaFieldType extends FieldType {

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
        $html[] = '<textarea class="form-control"';

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

        if($this->getField()->getValue())
        {
            $html[] = $this->getField()->getValue();
        }
        else if($this->getField()->getDefault())
        {
            $html[] = $this->getField()->getDefault();
        }


        $html[] = '</textarea>';
        $html[] = '</div>';

        return implode("", $html);
    }
}
