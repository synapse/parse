<?php defined('_INIT') or die;


class TranslateRoutes extends ModuleRouter
{
    public function getRoutes()
    {
        $this->get('/', 'main');
        $this->post('/new', 'main.newTranslation');
        $this->post('/save', 'main.save');
        $this->get('/delete/:file/:id', 'main.delete');
    }
}