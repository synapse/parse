<?php

/**
 * @package     Synapse
 * @subpackage  Form/Validate/Or
 */

defined('_INIT') or die;

class OrFieldValidator {

    public function test($value, $field = null, $validator = null)
    {
        if($value){
            return true;
        }

        if(!isset($validator->fields) && !count($validator->fields)){
            return false;
        }

        $orValue = false;
        foreach($validator->fields as $index=>$f){
            $fValue = $field->getForm()->getFieldValue($f);

            if($fValue){
                $orValue = true;
            }
        }

        return $orValue;
    }

}
