<?php defined('_INIT') or die;

Class GetModel extends Model {


	/*

	empty	Equal to
	!  		Not equal to
	<   	Less than
	>   	Greater than

	<=  	Less than or equal to
	>=  	Greater than or equal to
	*		Contains one of the items (should be separated by |
	%  		Contains the exact word or phrase (using slower SQL LIKE) [v2.1]
	^  		Contains the exact word or phrase at the beginning of the field [v2.1]
	$  		Contains the exact word or phrase at the end of the field [v2.1]

	*/
	private $comparators	= array( '!', '<', '>', '%', '^', '$');
	private $keys			= array('_include', '_require', '_limit', '_offset', '_order');


	public function get($params, $data)
    {
//		echo 'DATA: <pre>';
//		print_r($data);
//		echo '</pre>';

//		echo 'PARAMS: <pre>';
//		print_r($params);
//		echo '</pre>';


		$items = array();

		// limit to one record by ID
		if(isset($params->id) && !empty($params->id))
		{
			$items = $this->getOne();
		}
		else
		{
			$items = $this->getAll($params, $data);
		}

//		echo "<pre>";
//		print_r($items);
//		echo "</pre>";

		return $items;
    }

	private function getAll($params, $data)
	{
		$includes = array();
		$requires = array();

		$db = $this->getDBO();
		$query = $db->getQuery(true);

		// select the collection
		$query->select('*')
			->from($db->quoteName('#__'.$params->collection));

		// parse every GET parameter
		foreach($data as $key => $value)
		{
			// if the key is set to manipulate the query
			if(in_array($key, $this->keys)){
				switch($key)
				{
					case '_limit':
						$query->limit((int)$value);
						break;
					case '_offset':
						$query->offset((int)$value);
						break;
					case '_order':
						$dir = 'ASC';
						if(substr($value, 0, 1) === '-') {
							$value = substr($value, 1);
							$dir = 'DESC';
						}
						$query->order($value.' '.$dir);
						break;
					case '_include':
						$includes[] = $value;
						break;
					case '_require':
						$requires[] = $value;
						break;
					default:
						break;
				}
			}

			// if the key is a field name
			else
			{
				$comp = '=';

				// fix the values based on the comparator
				$this->fixValue($value, $key, $comp);

				// if there is an OR required
				if(count(explode("|", $value)))
				{
					$where = '(';
					$values = explode("|", $value);
					foreach($values as $i => $val)
					{
						$where .= $db->quoteName($key).' '.$comp.' '.$db->quote($val) . (($i < count($values) - 1) ? ' OR ' : '' );
					}

					$where .= ')';
					$query->where($where);
				}

				// else just use a simple AND
				else
				{
					$query->where($db->quoteName($key).' '.$comp.' '.$db->quote($value));
				}

			}
		}

//		echo $query->dump();
		$db->setQuery($query);
		$records = $db->loadObjectList();

		// if there is at least on _include request
		if(count($includes))
		{
			$reqIncludes = array();
			foreach($includes as $i => $include)
			{
				foreach($records as $record)
				{
					// check if the requested column is in the includes list
					if(!in_array($record->$include, $reqIncludes[$include]))
						// add the id
						$reqIncludes[$include][] = $record->$include;
				}
			}

			// load the one-to-one data
			$this->getIncludes($reqIncludes, $records, $params->collection);
		}

		// if there is at least one _require request
		if(count($requires))
		{
			$this->getRequires($requires, $records, $params->collection);
		}

		return $records;
	}

	private function getOne()
	{
		//$query->where('id = '.$db->quote($params->id));
	}

	private function getRequires($requires, &$records, $collection)
	{
		$config = $this->getConfig();
		$db = $this->getDBO();
		$tables = $db->getTableList();

		foreach($requires as $i => $require)
		{
			// if the required table was not found continue
			if(!in_array($config->db_prefix.$require, $tables)) continue;

			// get all the columns for the required table
			$column = null;
			$columns = $db->getTableColumns('#__'.$require, false);
			foreach($columns as $col)
			{
				// find the one that has a comment equal to the table name that requires the data
				if($col->Comment === $collection) {
					$column = $col->Comment;
					break;
				}
				continue;
			}

			// if there is no such column then return
			if(!$column) continue;

			foreach($records as &$record)
			{
				$query = $db->getQuery(true);
				$query->select('*')
					->from('#__'.$require)
					->where($column.' = '.$db->quote($record->id));
				$db->setQuery($query);
				$record->$require = $db->loadObjectList();
			}
		}

	}

	private function getIncludes(&$includes, &$records, $collection)
	{
		$db = $this->getDBO();

		$columns = $db->getTableColumns('#__'.$collection, false);

		// load the includes
		foreach($includes as $col => $ids)
		{
			// get the table from the column comment
			$table = $columns[$col]->Comment;

			// if there is no comment then skip
			if(!strlen($table)) continue;

			// get all the records from the table mentioned in the comment
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->quoteName('#__'.$table))
				->where('id IN ('. implode(",",$ids) .')');
			$db->setQuery($query);

			$includes[$col] = $db->loadObjectList();
		}

		// set the includes
		foreach($includes as $col => $values)
		{
			foreach($records as &$record)
			{
				if(isset($record->$col))
				{
					foreach($values as $value)
					{
						if($value->id == $record->$col)
							$record->$col = $value;
					}
				}
			}
		}
	}

	private function fixValue(&$value, &$key, &$comp)
	{
		$multi = count(explode("|", $value)) ? true : false;


		if(in_array(substr($key, -1), $this->comparators))
		{
			switch(substr($key, -1))
			{
				case '!':
					$comp = '!=';
					break;
				case '>':
					$comp = '>';
					break;
				case '<':
					$comp = '<';
					break;
				case '*':
					$comp = 'IN';
					$value = '('.implode(",", explode("|", $value)).')';
					break;
				case '%':
					$comp = 'LIKE';

					if($multi)
					{
						$values = explode("|", $value);
						foreach($values as $i => &$val)
						{
							$val = '%'.$val.'%';
						}
						$value = implode("|", $values);
					}
					else
					{
						$value = '%'.$value.'%';
					}
					break;

				case '^':
					$comp = 'LIKE';

					if($multi)
					{
						$values = explode("|", $value);
						foreach($values as $i => &$val)
						{
							$val = '%'.$val;
						}
						$value = implode("|", $values);
					}
					else
					{
						$value = '%'.$value;
					}
				case '$':
					$comp = 'LIKE';

					if($multi)
					{
						$values = explode("|", $value);
						foreach($values as $i => &$val)
						{
							$val = $val.'%';
						}
						$value = implode("|", $values);
					}
					else
					{
						$value = $value.'%';
					}
				default:
					break;
			}

			$key = substr($key, 0, -1);
		}

	}
}
