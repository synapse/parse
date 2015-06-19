<?php

/**
 * @package     Synapse
 * @subpackage  Loader
 */

defined('_INIT') or die;

class Loader
{
    public static function register()
    {
        if (function_exists('__autoload')) {
            //    Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }

        return spl_autoload_register(array('Loader', 'load'));
    }

    public static function load($class_name)
    {
        $filename = strtolower($class_name) . '.php';
        $directories = array();
        $files = scandir(LIBRARY);

        // search in the library folder
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;

            if (is_dir(LIBRARY . '/' . $file)) {
                $directories[] = LIBRARY . '/' . $file;
            } else {
                if ($file == $filename) {
                    require_once(LIBRARY . '/' . $file);
                    return;
                }
            }
        }

        // search in sub folders
        if (!count($directories)) return;
        foreach ($directories as $directory) {
            foreach (scandir($directory) as $file) {
                if ($file == '.' || $file == '..') continue;
                if ($file == $filename) {
                    require_once($directory . '/' . $file);
                    return;
                }
            }
        }

        // find in the vendor folder
        /*
        $folders = scandir(VENDOR);

        foreach($folders as $folder){
            if($folder == '.' || $folder == '..') continue;
            if(!is_dir(VENDOR.'/'.$folder)) continue;

            $files = scandir(VENDOR.'/'.$folder);
            foreach($files as $file){
                if($file == '.' || $file == '..') continue;

                $file = VENDOR.'/'.$folder.'/'.$file;
                if(file_has_php_classes($file, $class_name)){
                    require_once($file);
                    return;
                }
            }
        }
        */

        if(DEBUG) error_log('========> Requested class name ' . $class_name . ' was not found.');
    }
}