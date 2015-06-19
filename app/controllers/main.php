<?php defined('_INIT') or die;

Class MainController extends Controller {

	public function index()
	{
		$model = $this->getModel('main');
		$helloString = $model->sayHello();
		
		$this->getView()
			->setTemplate('main')
			->setData($helloString, 'hello')
			->render();
	}
}