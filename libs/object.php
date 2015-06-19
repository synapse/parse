<?php

/**
 * @package     Synapse
 * @subpackage  Object
 */

defined('_INIT') or die;


class Object {

    protected $error = null;

    public function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}