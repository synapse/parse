<?php

/**
 * @package     Synapse
 * @subpackage  Helpers/DB
 */

defined('_INIT') or die;


class DBHelper
{
    /**
     * @param String $table
     * @param Array $cols Something like array("col_1" => "=", "col_2" => "LIKE", "col_3" => "IN")
     * @param Array $values Something like array("AND" => "value", "value", "OR" => "value")
     * @param bool $first
     * @param string $select
     * @return array|mixed|void
     * @throws Error
     */
    public static function get($table, $cols = array(), $values = array(), $first = false, $select = "*")
    {
        $db = App::getDBO();
        if(!$db) return;

        $query = $db->getQuery(true);

        $query->select($select);

        $query->from($table);

        $i = 0;
        if(is_array($cols) && count($cols))
        {
            foreach($cols as $col => $match)
            {
                $value = array_slice($values, $i, 1);
                $glue = array_keys($value)[0];
                $value = $value[$glue];

                if(is_numeric($glue)) $glue = 'AND';

                if (is_array($value)) {
                    $elements = array_map(array($db, 'quote'), $value);
                    $query->where($col . ' ' . $match . ' (' . implode(",", $elements) . ')', $glue);
                } else {
                    $query->where($col . ' ' . $match . ' ' . $db->quote($value), $glue);
                }

                $i++;
            }
        }

        $db->setQuery($query);

        if($first) return $db->loadObject();

        return $db->loadObjectList();
    }

    /**
     * Deletes a specific record from the database
     * @param String $table
     * @param String $col
     * @param String $value
     * @param bool $like
     * @param string $select
     * @return mixed
     * @throws Error
     */
    public static function delete($table, $col, $value, $like = false)
    {
        $db = App::getDBO();
        if(!$db) return;

        $equal = " = ";
        if($like) $equal = " LIKE ";

        $query = $db->getQuery(true);
        $query->delete($table)->where($col.$equal.$db->quote($value));
        $db->setQuery($query);

        return $db->exec();
    }
}
