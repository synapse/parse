<?php defined('_INIT') or die;

Class RequestController extends ControllerRest {

	public function index()
	{
		$request = $this->getRequest();
		$params = $this->getParams();

		$data = $this->dispatch($params, $request);

		header('Content-Type: application/json');
		echo json_encode($data);

		die();
	}

    private function dispatch($params, $request)
    {


		switch ($request->getType()) {
			case 'GET':
				$model = $this->getModel('get');
				return $model->get($params, $this->getQuery());
				break;
			case 'POST':
				//$model->set($params, $request->getJSON());
				break;
			default:
				die('Unhandled request type');
				break;
		}
    }
}
