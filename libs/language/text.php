<?php

/**
 * @package     Synapse
 * @subpackage  Language/Text
 */

defined('_INIT') or die;

Class Text {
	
	public static function _()
	{
		$args = func_get_args();
        $strings = App::getLanguage()->getStrings();
		
		if(count($args) == 1){			
			return $strings[$args[0]];
		}
		
		$text = array_shift($args);		

		
		$params = array();
		foreach($args as $index=>$value){
			$params['{'.($index + 1).'}'] = $value;
		}
		
		$txt = str_replace(array_keys($params),array_values($params), $strings[$text]);
		
		return $txt;
	}
	
}


?>