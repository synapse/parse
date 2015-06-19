<?php

/**
 * @package     Synapse
 * @subpackage  Helpers/User
 * @ver 1.1.1
 */

defined('_INIT') or die;


class UserHelper
{
    /**
     * Checks if the users with the specified username and password exists and sets the session
     * @param String $username
     * @param String $password
     * @return bool|mixed
     * @throws Error
     */
    public static function login($username, $password)
    {
        $db = App::getDBO();
        if(!$db) return;

        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__users')
            ->where('username = '.$db->quote($username))
            ->where('password = '. $db->quote($password));

        $user = $db->setQuery($query)->loadObject();

        if($user->id){
            App::getSession()->set('user', $user)->set('isLoggedin', true);
            return $user;
        }

        return false;
    }

    /**
     * Logout the user by destroying the session data
     */
    public static function logout()
    {
        App::getSession()->remove('user')->remove('isLoggedin');
    }

    /**
     * Return one or more users based on the selected field
     * @param String $field
     * @param String $value
     * @return bool|mixed
     */
    public static function getByField($field, $value, $first = false, $fields = "*")
    {
        $db = App::getDBO();
        if(!$db) return;

        $query = $db->getQuery(true);

        $query->select($fields)
            ->from('#__users')
            ->where($db->quoteName($field) .' = '.$db->quote($value));
        $db->setQuery($query);

        if($first){
            return $db->loadObject();
        }

        return $db->loadObjectList();
    }

    /**
     * @param array $fields
     * @param bool $first
     * @param array $options
     * > glue => AND / OR
     * > like => true / false
     * @return array|mixed
     * @throws Error
     */
    public function getByFields($fields = array(), $first = false, $options = array())
    {
        if(!count($fields)) return null;

        $db = App::getDBO();
        if(!$db) return;

        $query = $db->getQuery(true);

        $query->select('*')
            ->from('#__users');

        $glue = array_key_exists('glue', $options) ? $options['glue'] : 'AND';
        $like = (array_key_exists('like', $options) && $options['like']) ? 'LIKE' : '=';

        foreach($fields as $field => $value){
            $query->where($field .' '.$like.' '.$db->quote($value), $glue);
        }

        $db->setQuery($query);

        if($first){
            return $db->loadObject();
        }

        return $db->loadObjectList();
    }

    public function update($user)
    {
        $db = App::getDBO();
        if(!$db) return;

        if(!$db->updateObject('#__users', $user, 'id', true)){
            return false;
        }

        return $user;
    }
}
