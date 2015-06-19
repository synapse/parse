<?php

/**
 * @package     Synapse
 * @subpackage  Form/FieldType/SQL
 */

defined('_INIT') or die;

class FieldTypeSql extends FieldType
{
    protected $options = null;

    public $template = array(
        "<div>",
        "   <label {{labelclass}}>{{label}}</label>",
        "   <select {{id}} {{name}} {{attributes}} {{required}}>",
        "{{repeat}}",
        "   <option value='{{value}}' {{selected}}>{{text}}</option>",
		"{{/repeat}}",
        "</select>",
        "</div>"
    );


    public function render()
    {
        $this->replace('name', isset($this->field->name) ? 'name="'.$this->field->name.'"' : '')
            ->replace('id', isset($this->field->id) ? 'id="'.$this->field->id.'"' : '')
            ->replace('labelclass', 'class="'.$this->field->labelclass.'"')
            ->replace('label', $this->field->label)
            ->replace('required', $this->field->required ? 'required=""' : '')
            ->setAttributes('attributes', $this->field->getAttributes());


        if(isset($this->field->options) && count($this->field->options->query)){
            $items = $this->getOptions($this->field->options->query);

            foreach($items as &$item){
                $item->selected = ($item->value == $this->field->getValue()) ? 'selected=""' : '';
            }
            $this->repeat('repeat', $items);
        }

        return $this->getTemplate();
    }

    protected function getOptions($query)
    {
        if($this->options) return $this->options;

        $query = str_replace('#__', App::getConfig()->db_prefix, $query);

        $db = App::getDBO();
        $db->setQuery($query);

        $this->options = $db->loadObjectList();

        if(isset($this->field->options) && count($this->field->options->items)) {
            $this->options = array_merge($this->field->options->items, $this->options);
        }

        return $this->options;
    }

    public function validate()
    {
        $valid = false;

        if(isset($this->field->options) && count($this->field->options->query)) {
            $options = $this->getOptions($this->field->options->query);
        }

        if($options && count($options)) {
            foreach($options as $item){
                if($item->value == $this->field->getValue()){
                    $valid = true;
                }
            }
        }

        return $valid;
    }

}
