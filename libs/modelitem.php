<?php

/**
 * @package     Synapse
 * @subpackage  Model/Item
 */

defined('_INIT') or die;

class ModelItem extends Model {

    protected $table    = null;
    protected $key      = 'id';
    protected $item     = null;
    protected $form     = null;

    /**
     * Query to execute when loading the item
     * @param $id
     * @return Query
     * @throws Error
     */
    protected function getQuery($id)
    {
        $db = $this->getDBO();
		$query = $db->getQuery(true);

        $query->select('*')
            ->from($this->table)
            ->where($this->key.' = '.$db->quote($id));

		return $query;
    }

    /**
     * Returns the current item or loads it from the database
     * @param null $id
     * @return mixed|null
     */
    public function getItem($id = null)
    {
        if($this->item && !$id) return $this->item;
        $key = $this->key;
        if($this->item && $id && $id == $this->item->$key) return $this->item;

        $db = $this->getDBO();

        if($id) {
            $db->setQuery($this->getQuery($id));
            $this->item = $db->loadObject();
        }

        return $this->item;
    }

    /**
     * Sets the current item from outside
     * @param $item
     * @return $this
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Inserts a new item in the db
     * @param null $item
     * @param bool $set If true then the current item will be updated to the item passed if the insert is successfull
     * @return bool
     */
    public function insertItem(&$item = null, $set = true)
    {
        $db = $this->getDBO();

        if($item) {
            if(!$db->insertObject($this->table, $item, $this->key)){
                $this->setError($db->getErrorMsg());
                return false;
            }

            if($set) {
                $this->setItem($item);
            }

            return true;
        }

        if(!$db->insertObject($this->table, $this->item, $this->key)){
            $this->setError($db->getErrorMsg());
            return false;
        }

        return true;
    }

    /**
     * Updates the item in the db
     * @param null $item
     * @param bool $set If true then the current item will be updated to the item passed if the update is successfull
     * @return bool
     */
    public function updateItem(&$item = null, $set = true)
    {
        $db = $this->getDBO();

        if($item) {
            if(!$db->updateObject($this->table, $item, $this->key)){
                $this->setError($db->getErrorMsg());
                return false;
            }

            if($set) {
                $this->setItem($item);
            }

            return true;
        }

        if(!$db->updateObject($this->table, $this->item, $this->key)){
            $this->setError($db->getErrorMsg());
            return false;
        }

        return true;
    }

    /**
     * If a form is present the item can be validated against the form filters and requirements
     * @param null $item
     * @return bool
     */
    public function validate($item = null)
    {
        if(!$this->form) return;

        $form = new Form(FORMS.'/'.$this->form);

        if($item) {
            $form->setData($item);
        } else {
            $form->setData($this->item);
        }

        if(!$form->validate()){
            $errors = array();
            foreach($form->getErrors() as $fieldset){
                foreach($fieldset as $error){
                    $errors[] = __($error->message);
                }
            }

            $this->setError($errors);
            return false;
        }

        return true;
    }
}