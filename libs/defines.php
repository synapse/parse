<?php

    define('_INIT', true);							        // used to stop loading php file via url
    define('VENDOR', LIBRARY.'/vendor');			        // vendor library path
    define('RESOURCES', ROOT_PATH.'/resources');	        // resources path (images, scripts, styles)
    define('APP', ROOT_PATH.'/app');				        // application folder contains the controllers, models and views
    define('LOGS', ROOT_PATH.'/logs');
    define('CLI', ROOT_PATH.'/cli');

    /* Application Path */
    define('LANGUAGES', APP.'/languages');			        // language folder
    define('CONTROLLERS', APP.'/controllers');				// app controllers
	define('MODELS', APP.'/models');						// app models
	define('MIDDLEWARES', APP.'/middlewares');				// app models
	define('MODULES', APP.'/modules');      				// app modules
	define('VIEWS', APP.'/views');							// app views
	define('SNIPPETS', APP.'/snippets');					// app snippets
	define('FORMS', APP.'/forms');							// app forms, templates, fields and validations
    define('CACHE', APP.'/cache');							// cache folder used for garbage
    define('PLUGINS', APP.'/plugins');                      // plugins folder used to launch events handlers
    define('CONFIG', APP.'/config/config.php');		        // configuration file
    define('ROUTES', APP.'/config/routes.php');		        // routes file

    /* Options */
	define('DEBUG', false);									// if true all errors and warnings will be printed and saved to the log file (LOGS)
	define('DEBUG_LANGUAGE', false);    					// if true all translated string will be wrapped in **string_here**
	define('LOG', false);									// if true all internal Log messages will be saved to DB via the Log Class
	define('VERSION', '1.0.2');								// version number

?>
