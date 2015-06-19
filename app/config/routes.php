<?php defined('_INIT') or die;

$router = $app->getRouter();

$router->get('/:collection', 'Request');
$router->post('/:collection', 'Request');
$router->get('/:collection/:id', 'Request');
$router->post('/:collection/:id', 'Request');


//$router->get('/', 'Main');
//$router->module('/translate', 'translate');
