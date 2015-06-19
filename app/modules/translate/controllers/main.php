<?php defined('_INIT') or die;

Class MainController extends ModuleController {

	public function index()
	{
        $model = $this->getModel('files');
        $files = $model->getFiles();
        $languages = $model->languages;

		$this->getView()
			->setTemplate('translate')
            ->setData($files, 'files')
            ->setData($languages, 'languages')
			->render();
	}

	public function newTranslation()
	{
		$query = $this->getQuery();
		$model = $this->getModel('files');
        $uri = $this->getURI();

		if(!$model->newTranslation($query)){
			App::enqueueMessage($model->getError(), 'alert alert-danger');
			die;
		} else {
            App::enqueueMessage('Translation created successfully!');
        }

        $this->redirect($uri->base().$this->getBaseURL());
	}

    public function save()
    {
        $query = $this->getQuery();
		$model = $this->getModel('files');
        $uri = $this->getURI();

		if(!$model->save($query)){
			App::enqueueMessage($model->getError(), 'alert alert-danger');
			die;
		} else {
            App::enqueueMessage('Translations saved successfully!');
        }

		$this->redirect($uri->base().$this->getBaseURL());
    }

    public function delete()
    {
        $params = $this->getParams();
        $model = $this->getModel('files');
        $uri = $this->getURI();

        if(!$model->delete($params->file, $params->id)){
            App::enqueueMessage($model->getError(), 'alert alert-danger');
		} else {
            App::enqueueMessage('Translations deleted successfully!');
        }

        $this->redirect($uri->base().$this->getBaseURL());
    }
}