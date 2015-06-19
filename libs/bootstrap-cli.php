<?php

/**
 * @package     Synapse
 * @subpackage  Cli Bootstrap
 */

define('ROOT_PATH', dirname(dirname(__FILE__)));		// root path of the website
define('LIBRARY', ROOT_PATH.'/libs');			        // framework path

include('defines.php');

defined('_INIT') or die;

if(DEBUG){
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	ini_set('error_log', LOGS . '/log.txt');
	error_reporting(E_ALL & ~E_STRICT);
}

session_start();
// include the config file
require_once(CONFIG);
// include the custom functions file
include(LIBRARY.'/functions.php');
// include the autoloader class
include(LIBRARY.'/loader.php');
// register the class autoloader
Loader::register();
