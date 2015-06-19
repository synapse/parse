<?php

/**
 * @package     Synapse
 * @subpackage  Log
 */

defined('_INIT') or die;

/*

CREATE TABLE `#__logs` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `message` text NOT NULL,
  `object` longtext,
  `type` enum('INFO','WARNING','ERROR','LOG') NOT NULL DEFAULT 'LOG',
  `backtrace` longtext,
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

 */


class Log
{
    public static function addLog($message, $type)
    {
        if(!defined('LOG') ):
            return;
        else:
            if(!LOG) return;
        endif;

        $db = App::getDBO();

        $log = new stdClass;
        $log->timestamp = date('Y-m-d H:i:s');

        if (is_string($message)):
            $log->message = $message;
        else:
            $log->message = '->';
            $log->object = addslashes(print_r($message, true));
        endif;

        $log->type = $type;
        $log->backtrace = addslashes(print_r(debug_backtrace(false), true));
        $log->url = Log::curPageURL();

        if($db) {
            $db->insertObject('#__logs', $log, 'id');
            return $log->id;
        }

        if(!Folder::exists(LOGS)){
            Folder::create(LOGS);
        }

        $logFile = LOGS.'/logs.php';

        if(!File::exists($logFile)){
            File::create($logFile);
        }

        File::append($logFile, '['.$log->timestamp.']['.$log->type.'] '.$log->message.' -> '.$log->url."\r\n");
    }

    public function _($message)
    {
        Log::addLog($message, 'LOG');
    }

    public static function info($message)
    {
        Log::addLog($message, 'INFO');
    }

    public static function warn($message)
    {
        Log::addLog($message, 'WARNING');
    }

    public static function error($message)
    {
        Log::addLog($message, 'ERROR');
    }

    public static function curPageURL() {
		$pageURL = 'http';
		$pageURL .= "://";

		if ($_SERVER["SERVER_PORT"] != "80"):
		    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else:
		    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		endif;

		return $pageURL;
	}
}