<?php

/**
 * @package     Synapse
 * @subpackage  Model/List
 */

defined('_INIT') or die;

class ModelList extends Model {

    protected $limit = 20;

    /**
     * Returns the query used to generate the list of items
     * @return Query
     */
    protected function getQuery()
    {
        $db = $this->getDBO();
		$query = $db->getQuery(true);

		return $query;
    }

    /**
     * Returns an array of items
     * @return array
     */
    public function getItems()
    {
        $db = $this->getDBO();
        $query = $this->getQuery();

        $db->setQuery($query);

        $items = $db->loadObjectList();

        return $items;
    }

    public function getPagination()
    {

    }

}