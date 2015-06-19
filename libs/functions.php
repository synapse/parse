<?php

/**
 * @package     Synapse
 * @subpackage  Functions
 * @version 1.0.1
 */

defined('_INIT') or die;

function __()
{
    $language = App::getLanguage();
    $args = func_get_args();

    return call_user_func_array(array($language, 'translate'), $args);
}

function _trigger($event, $params){ App::trigger($event, $params); }
function _hook($event, $params){ App::trigger($event, $params); }


function _log($message){ Log::_($message); }
function _info($message){ Log::info($message); }
function _warning($message){ Log::warn($message); }
function _error($message){ Log::error($message); }