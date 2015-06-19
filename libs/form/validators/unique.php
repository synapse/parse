<?php

/**
 * @package     Synapse
 * @subpackage  Form/Validate/Unique
 */

defined('_INIT') or die;

class UniqueFieldValidator {

    public static function test($value, $field = null, $validator = null)
    {
        $db = App::getDBO();
        if(!$db) return true;

        $query = $db->getQuery(true);

        if(!isset($validator->column)){
            throw new Error( __('Missing "column" validator property') );
        }

        if(!isset($validator->table)){
            throw new Error('Missing "table" validator property');
        }

        $query->select('*')
            ->from($validator->table)
            ->where($validator->column. ' = '.$db->quote($value));

        if(isset($validator->self)) {
            $self = $field->getForm()->getFieldValue($validator->self);

            $query->where($validator->self.' <> '.$db->quote($self));
        }

        $db->setQuery($query);
        $users = $db->loadObjectList();

        return count($users) == 0;
    }

}
