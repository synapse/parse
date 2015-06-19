<?php

/**
 * @package     Synapse
 * @subpackage  View
 */

defined('_INIT') or die;


class View extends Object {

	private $template       = null;
    private $_data          = array();
    private $templatePath   = null;

	public function __construct($path = null)
	{
        if(!$path) $this->templatePath = VIEWS;

        return $this;
	}

    /**
     * Set the html template for the view
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $this->templatePath .'/'. $template .'.php';

        if(!file_exists($this->template)){
            throw new Error( __('Template file not found!').' '.$this->template );
        }

        return $this;
    }

    /**
     * Add data to the view
     * @param Object|Array $data
     * @param String $name
     * @return $this
     */
    public function setData($_data, $name = null)
    {
        if($name)
        {
            $this->_data[] = array($name => $_data);
        }
        else
        {
            $this->_data[] = $_data;
        }

        return $this;
    }

    /**
     * Sets the path were the templates file are located
     * @param String $path
     */
    public function setTemplatePath($path)
    {
        $this->templatePath = $path;

        return $this;
    }

    protected function addIncludes(&$view, $data)
    {
        if(empty($view)) return;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->resolveExternals = true;
        $dom->substituteEntities = false;
        $dom->loadHTML($view, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $includes = $dom->getElementsByTagName('include');

        foreach($includes as $include){
            $attr = $include->attributes;
            $type = $attr->getNamedItem('type')->nodeValue;

            Snippet::render($type, $include, $dom, $data);
        }

        for($i = $includes->length - 1; $i >= 0; $i--){
            $includes->item($i)->parentNode->removeChild($includes->item($i));
        }

        $view = $dom->saveHTML();
    }

    /**
     * Echo out the rendered HTML template
     */
    public function render()
	{
        foreach($this->_data as $_data){
            if(is_object($_data)){
                $_data = get_object_vars($_data);
            }
            extract($_data);
        }

        if(!$this->template){
            throw new Error( __('Template is missing!') );
        }

		ob_start();

		require($this->template);
		$view = ob_get_clean();

        $this->addIncludes($view, $this->_data);

        echo $view;
	}

}
