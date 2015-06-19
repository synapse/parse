<?php
/**
 * @package     Synapse
 * @subpackage  Cli
 */

defined('_INIT') or die;

class Cli {

	public static $instance	 = null;

	public static function getInstance()
	{
		if(!static::$instance){
			static::$instance = new static;
		}
		return static::$instance;
	}

	protected function getDBO()
	{
		return App::getDBO();
	}

    /**
     * Returns the model based on the name provided
     * @param String $modelName
     * @param String $folder
     */
    protected function getModel($modelName)
    {
        $modelFileName = strtolower($modelName).'.php';
        $modelPath = MODELS.'/'.$modelFileName;


        if(!file_exists($modelPath)){
            throw new Error( __('Controller model not found: {1}', $modelFileName), null );
        }

        require_once($modelPath);

        $model = explode("/", $modelName);
        $modelClass  = ucfirst(array_pop($model)).'Model';

        return new $modelClass();
    }

    /**
     * Returns the sessione object
     * @return null|Session
     */
    protected function getSession()
    {
        return App::getSession();
    }

    /**
     * Returns the configuration parameters
     * @return Config|null
     */
    protected function getConfig()
    {
        return App::getConfig();
    }

    /**
     * Returns the App instance
     * @return App|null
     */
    protected function getApp()
    {
        return App::getInstance();
    }

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  JApplicationCli  Instance of $this to allow chaining.
	 *
	 * @codeCoverageIgnore
	 * @since   11.1
	 */
	public function out($text = '', $nl = true)
	{
		fwrite(STDOUT, $text . ($nl ? "\n" : null));

		return $this;
	}

	/**
	 * Get a value from standard input.
	 *
	 * @return  string  The input string from standard input.
	 *
	 * @codeCoverageIgnore
	 */
	public function in()
	{
		return rtrim(fread(STDIN, 8192), "\n");
	}

} 