<?php

/**
 * @package     Synapse
 * @subpackage  Controller/Rest
 */

defined('_INIT') or die;

class ControllerRest extends Controller
{
    /**
     * Generates a JSON string based on the return object of a callable method inside the given model object
     * @param Object $model
     * @param String $method
     * @param Array $params
     */
    protected function render($model, $method, $params, $jsonp = false, $callback = null)
    {
        $data   = new stdClass();
        $object = call_user_func_array(array($model, $method), $params);

        if($object === false || $object === null){
            $data->error = $model->getError();
            $data->success = false;
        } else {
            $data->success = true;
            $data->data = $object;
        }

        if(method_exists($model, 'getInfo')){
            if($info = $model->getInfo()) {
                $data->info = $info;
            }
        }

        if(method_exists($model, 'getCode')){
            if($code = $model->getCode()) {
                http_response_code($code);
            }
        }

        header('Content-Type: text/javascript; charset=UTF-8');

        if(!empty($jsonp)){
            echo $jsonp.'('.json_encode($data).')';
        } else {
            echo json_encode($data);
        }

        ob_start();
        if($callback){
            $callback($data);
        }
        ob_end_clean();

        die;
    }

}
