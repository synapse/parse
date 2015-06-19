<?php defined('_INIT') or die;

Class FilesModel extends Model {

    public $languages = array("af","ar","at","az","be","bg","bn","br","bs","ca","ch","cy","cz","da","de","dk","el","en","eo","es","et","fa","fi","fr","gd","he","hi","hk","hr","hu","hy","id","is","it","ja","ka","km","ko","ku","lo","lt","lv","mk","mn","nl","no","pl","ps","pt","ro","ru","sk","sl","sr","sv","sw","sy","ta","th","tr","tw","uk","ur","us","uz","vi","zh");

	public function getFiles()
	{
        $files = array();

        $JSONFiles = glob(LANGUAGES."/*.json");

        foreach($JSONFiles as $JSONFile){
            $file = new stdClass();
            $file->path = $JSONFile;
            $file->filename = File::getName($JSONFile);
            $file->name = File::stripExt($file->filename);
            $file->translations = json_decode(file_get_contents($JSONFile));
            $files[] = $file;
        }

		return $files;
	}

    public function newTranslation($info)
    {

        if($info->file == 1){
            $info->file = StringNormalise::toUnderscoreSeparated(File::makeSafe($info->filename));

            if(!File::exists(LANGUAGES.'/'. $info->file.'.json')){
                file_put_contents(LANGUAGES.'/'. $info->file.'.json', '');
            }
        }

        $file = LANGUAGES.'/'.$info->file.'.json';

        if(empty($info->original) || $info->original == ' '){
            $this->setError('The original text is required');
            return false;
        }

        if(!File::exists($file)){
            $this->setError('Translation file not found!');
            return false;
        }

        $info->data = file_get_contents($file);

        if($info->file != 1){
            /*
            if(empty($info->data)){
                $this->setError('JSON file is empty!');
                return false;
            }
            */

            $info->data = json_decode($info->data);

            /*
            if(json_last_error() != JSON_ERROR_NONE){
                $this->setError('Translation file is not a valid JSON string!');
                return false;
            }
            */
        }

        $id = md5(strtolower($info->original));

        if(property_exists($info->data, $id)){
            $this->setError('JSON file already contains this translation!');
            return false;
        }

        $info->data->$id = new stdClass();
        $info->data->$id->original = $info->original;
        $info->data->$id->translations = new stdClass();

        if(file_put_contents($file, json_encode($info->data, JSON_PRETTY_PRINT)) === false){
            $this->setError('Unable to update the JSON file!');
            return false;
        }

        return true;
    }

    public function save($files)
    {
        foreach($files as $file=>$datas){

            $file = LANGUAGES.'/'.$file.'.json';

            if(!File::exists($file)){
                $this->setError('JSON file not found! '.$file.'.json');
                return false;
            }

            $json = new stdClass();

            foreach($datas as $id=>$data){

                $id = md5(strtolower($data['original']));
                $json->$id->original = $data['original'];

                $translations = new stdClass();

                foreach($data['translations'] as $lang=>$text) {
                    $translations->$lang = $text;
                }

                $json->$id->translations = $translations;
            }


            if(file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT)) === false){
                $this->setError('Unable to update the JSON file!');
                return false;
            }
        }

        return true;
    }

    public function delete($file, $id)
    {
        $file = LANGUAGES.'/'.$file.'.json';

        if(!File::exists($file)){
            $this->setError('JSON file not found! '.$file.'.json');
            return false;
        }

        $data = json_decode(file_get_contents($file));

        if(!property_exists($data, $id)){
            $this->setError('Translation not found!');
            return false;
        }

        unset($data->$id);

        if(file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) === false){
            $this->setError('Unable to update the JSON file!');
            return false;
        }

        return true;
    }
}