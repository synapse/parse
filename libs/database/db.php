<?php
/**
 * @package     Synapse
 * @subpackage  Database
 */

defined('_INIT') or die;

class DB extends mysqli {

	private $query      = null;

    /**
	 * The character(s) used to quote SQL statement names such as table names or field names,
	 * etc.
	 *
	 * The child classes should define this as necessary.  If a single character string the
	 * same character is used for both sides of the quoted name, else the first character will be
	 * used for the opening quote and the second for the closing quote.
     *
	 * @var    string
	 */
    protected $nameQuote = '`';

    public function __construct($options = array())
    {
		$this->prefix = App::getConfig()->db_prefix;
        $this->connect($options['host'], $options['user'], $options['password'], $options['database'], $options['port']);
		$this->set_charset("utf8");
    }

    public function __destruct()
	{
		$this->close();
	}

	public function setQuery($query)
	{
		$this->query = $query;
        return $this;
	}

	public function execute()
	{
		return $this->query($this->query);
	}

    /**
	 * Method to fetch a row from the result set cursor as an array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   1.0
	 */
	protected function fetchArray($cursor = null)
	{
		return $cursor->fetch_row();
	}

	public function loadAssocList()
	{
		$results = $this->execute();

        if(!$results){
            throw new Error( $this->getErrorMsg(), $this->getErrorCode() );
        }

		$return = array();

		while ($cols = $results->fetch_assoc()) {
			$return[] = $cols;
		}

		return $return;
	}

	public function loadObjectList()
	{
		$items = $this->loadAssocList();
		$return = array();

		foreach($items as $item){
			$row = new stdClass();
			foreach ($item as $key => $value){
		    	$row->$key = $value;
			}
			$return[] = $row;
		}

		return $return;
	}

	public function loadObject()
	{
        $objects = $this->loadObjectList();
		return array_shift($objects);
	}

    public function getQuery($new = false)
    {
        if($new){
            $this->query = new Query();
        }

        return $this->query;
    }

    public function updateObject($table, &$object, $key, $nulls = false)
    {
        $fields = array();
		$where = array();

		if (is_string($key)){
			$key = array($key);
		}

		if (is_object($key)){
			$key = (array) $key;
		}

		$statement = 'UPDATE ' . $this->quoteName($table) . ' SET %s WHERE %s';

		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process scalars that are not internal fields.
			if (is_array($v) or is_object($v) or $k[0] == '_'){
				continue;
			}

			// Set the primary key to the WHERE clause instead of a field to update.
			if (in_array($k, $key)){
				$where[] = $this->quoteName($k) . '=' . $this->quote($v);
				continue;
			}

			// Prepare and sanitize the fields and values for the database query.
			if ($v === null) {
				// If the value is null and we want to update nulls then set it.
				if ($nulls){
					$val = 'NULL';
				} else { // If the value is null and we do not want to update nulls then ignore this field.
					continue;
				}
			} else { // The field is not null so we prep it for update.
				$val = $this->quote($v);
			}

			// Add the field to be updated.
			$fields[] = $this->quoteName($k) . '=' . $val;
		}

		// We don't have any fields to update.
		if (empty($fields)) {
			return true;
		}

		// Set the query and execute the update.
        $query = sprintf($statement, implode(",", $fields), implode(' AND ', $where));
        $query = str_replace('#__', App::getConfig()->db_prefix, $query);

		$this->setQuery($query);

		return $this->execute();
    }

    /**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  mixed  The value of the auto-increment field from the last inserted row.
	 */
	public function insertid()
    {
        return $this->insert_id;
    }

    /**
	 * Inserts a row into a table based on an object's properties.
	 *
	 * @param   string  $table    The name of the database table to insert into.
	 * @param   object  &$object  A reference to an object whose public properties match the table fields.
	 * @param   string  $key      The name of the primary key. If provided the object property is updated.
	 *
	 * @return  boolean    True on success.
	 */
	public function insertObject($table, &$object, $key = null)
	{
		$fields = array();
		$values = array();

		// Iterate over the object variables to build the query fields and values.
		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process non-null scalars.
			if (is_array($v) or is_object($v) or $v === null) continue;

			// Ignore any internal fields.
			if ($k[0] == '_') continue;

			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->quoteName($k);
			$values[] = $this->quote($v);
		}

		// Create the base insert statement.
		$query = $this->getQuery(true);
		$query->insert($this->quoteName($table))
				->columns($fields)
				->values(implode(',', $values));

		// Set the query and execute the insert.
		$this->setQuery($query);

		if (!$this->execute()) return false;

		// Update the primary key if it exists.
		$id = $this->insertid();

		if ($key && $id && is_string($key)) $object->$key = $id;

		return true;
	}

	/**
	 * Inserts multiple rows into a table based on an array of object's properties.
	 *
	 * @param   string  $table    The name of the database table to insert into.
	 * @param   object  $objects  An array of objects whose public properties match the table fields.
	 *
	 * @return  boolean    True on success.
	 */
	public function insertObjects($table, $objects)
	{
		if(!is_array($objects)) return false;
		if(!count($objects)) return false;

		$fields = array();

		// Iterate over the object variables to build the query fields and values.
		foreach (get_object_vars($objects[0]) as $k => $v)
		{
			// Only process non-null scalars.
			if (is_array($v) or is_object($v) or $v === null) continue;

			// Ignore any internal fields.
			if ($k[0] == '_') continue;

			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->quoteName($k);
		}

		// Create the base insert statement.
		$query = $this->getQuery(true);
		$query->insert($this->quoteName($table))
				->columns($fields);

		foreach ($objects as &$object) {

			$values = array();

			foreach (get_object_vars($object) as $k => $v){
				// Only process non-null scalars.
				if (is_array($v) or is_object($v) or $v === null) continue;
				// Prepare and sanitize the fields and values for the database query.
				$values[] = $this->quote($v);
			}

			$query->values(implode(',', $values));
		}

		// Set the query and execute the insert.
		$this->setQuery($query);

		if (!$this->execute()) return false;

		return true;
	}

	public function escape($text, $extra = false)
	{
        $result = $this->escape_string($text);
		if ($extra){
			$result = addcslashes($result, '%_');
		}

		return $result;
	}

	public function quote($text, $escape = true)
	{
		return '\'' . ($escape ? $this->escape($text) : $text) . '\'';
	}

    public function quoteName($name, $as = null)
	{
		if (is_string($name)){
			$quotedName = $this->quoteNameStr(explode('.', $name));

			$quotedAs = '';

			if (!is_null($as)){
				settype($as, 'array');
				$quotedAs .= ' AS ' . $this->quoteNameStr($as);
			}

			return $quotedName . $quotedAs;
		} else {
			$fin = array();

			if (is_null($as)){
				foreach ($name as $str){
					$fin[] = $this->quoteName($str);
				}
			} elseif (is_array($name) && (count($name) == count($as))) {
				$count = count($name);

				for ($i = 0; $i < $count; $i++){
					$fin[] = $this->quoteName($name[$i], $as[$i]);
				}
			}

			return $fin;
		}
	}

    /**
	 * Quote strings coming from quoteName call.
	 * @param  Array  $strArr  Array of strings coming from quoteName dot-explosion.
	 * @return  String  Dot-imploded string of quoted parts.
	 */
    protected function quoteNameStr($strArr)
	{
		$parts = array();
		$q = $this->nameQuote;

		foreach ($strArr as $part)
		{
			if (is_null($part)){
				continue;
			}

			if (strlen($q) == 1){
				$parts[] = $q . $part . $q;
			} else {
				$parts[] = $q{0} . $part . $q{1};
			}
		}

		return implode('.', $parts);
	}

    public function getErrorMsg()
    {
        return $this->error;
    }

    public function getErrorCode()
    {
        return $this->errno;
    }

    /**
	 * Drops a table from the database.
	 *
	 * @param   string   $tableName  The name of the database table to drop.
	 * @param   boolean  $ifExists   Optionally specify that the table must exist before it is dropped.
	 *
	 * @return  DB  Returns this object to support chaining.
	 */
	public function dropTable($tableName, $ifExists = true)
	{
		$query = $this->getQuery(true);

		$this->setQuery('DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $query->quoteName($tableName));

		$this->execute();

		return $this;
	}

	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return  integer  The number of affected rows.
	 *
	 * @since   1.0
	 */
	public function getAffectedRows()
	{
		return $this->affected_rows;
	}

    /**
	 * Method to get the database collation in use by sampling a text field of a table in the database.
	 *
	 * @return  mixed  The collation in use by the database (string) or boolean false if not supported.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getCollation()
	{
		$tables = $this->getTableList();

		$this->setQuery('SHOW FULL COLUMNS FROM ' . $tables[0]);
		$array = $this->loadAssocList();

		foreach ($array as $field)
		{
			if (!is_null($field['Collation']))
			{
				return $field['Collation'];
			}
		}

		return null;
	}

    /**
	 * Retrieves field information about a given table.
	 *
	 * @param   string   $table     The name of the database table.
	 * @param   boolean  $typeOnly  True to only return field types.
	 *
	 * @return  array  An array of fields for the database table.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getTableColumns($table, $typeOnly = true)
	{
		$result = array();

		// Set the query to get the table fields statement.
		$this->setQuery('SHOW FULL COLUMNS FROM ' . $this->quoteName($this->escape( str_replace('#__', $this->prefix, $table))));
		$fields = $this->loadObjectList();

		// If we only want the type as the value add just that to the list.
		if ($typeOnly)
		{
			foreach ($fields as $field)
			{
				$result[$field->Field] = preg_replace("/[(0-9)]/", '', $field->Type);
			}
		}
		else
		// If we want the whole field data object add that to the list.
		{
			foreach ($fields as $field)
			{
				$result[$field->Field] = $field;
			}
		}

		return $result;
	}

    /**
	 * Get the details list of keys for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return  array  An array of the column specification for the table.
	 */
	public function getTableKeys($table)
	{
		// Get the details columns information.
		$this->setQuery('SHOW KEYS FROM ' . $this->quoteName($table));
		$keys = $this->loadObjectList();

		return $keys;
	}

    /**
	 * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
	 * the database query.
	 *
	 * @param   integer  $offset  The row offset to use to build the result array.
	 *
	 * @return  mixed    The return value or null if the query failed.
	 */
	public function loadColumn($offset = 0)
	{
		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set as arrays.
		while ($row = $this->fetchArray($cursor))
		{
			$array[] = $row[$offset];
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

    /**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 */
	public function getTableList()
	{
		// Set the query to get the tables statement.
		$this->setQuery('SHOW TABLES');
		$tables = $this->loadColumn();

		return $tables;
	}

    /**
	 * Locks a table in the database.
	 *
	 * @param   string  $table  The name of the table to unlock.
	 *
	 * @return  DB  Returns this object to support chaining.
	 */
	public function lockTable($table)
	{
		$this->setQuery('LOCK TABLES ' . $this->quoteName($table) . ' WRITE')->execute();

		return $this;
	}

    /**
	 * Unlocks tables in the database.
	 *
	 * @return  MysqliDriver  Returns this object to support chaining.
	 */
	public function unlockTables()
	{
		$this->setQuery('UNLOCK TABLES')->execute();

		return $this;
	}

    /**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function freeResult($cursor = null)
	{
		$cursor->free();
	}

    /**
	 * Renames a table in the database.
	 *
	 * @param   string  $oldTable  The name of the table to be renamed
	 * @param   string  $newTable  The new name for the table.
	 * @param   string  $backup    Not used by MySQL.
	 * @param   string  $prefix    Not used by MySQL.
	 *
	 * @return  MysqliDriver  Returns this object to support chaining.
	 */
	public function renameTable($oldTable, $newTable, $backup = null, $prefix = null)
	{
		$this->setQuery('RENAME TABLE ' . $oldTable . ' TO ' . $newTable)->execute();

		return $this;
	}

    /**
     * Checks if a given table name exists in the database
     *
     * @param $table
     * @return bool
     */
    public function tableExists($table)
    {
        $query = $this->getQuery(true);
        $query->select('table_name as `name`')
            ->from('information_schema.tables')
            ->where('table_schema = DATABASE()')
            ->where('table_name = '.$this->quote($table));

        $this->setQuery($query);
        $tables = $this->loadObjectList();

        return count($tables)?true:false;
    }

    /**
     * Inserts a column in a specific table
     *
     * @param $table
     * @param $column
     * @param null $after
     * @param bool $first
     * @return  MysqliDriver  Returns this object to support chaining.
     */
    public function insertColumn($table, $column, $after = null, $first = false)
    {
        $column = (object)$column;

        if(isset($column->decimal) && strlen($column->decimal))
        {
            $column->length = $column->length.','.$column->decimal;
        }

        $query = 'ALTER TABLE '.$this->quoteName($table).' ADD '.$this->quoteName($column->name).' '.$column->type.'('.$column->length.')';

        if(isset($column->required))
        {
            if($column->required)
            {
                $query .= ' NOT NULL';
            }
            else
            {
                $query .= ' NULL';
            }

        }

        if(isset($column->default) && strlen($column->default))
        {
            $query .= ' DEFAULT '.$this->quote($column->default);
        }

        if($after)
        {
            $query .= ' AFTER '.$after;
        }
        else if($first)
        {
            $query .= ' FIRST';
        }

        $query .= ';';

        return $this->setQuery($query)->execute();
    }

    /**
     * Removes a specific column from a secific table
     *
     * @param $table
     * @param $column
     * @return  MysqliDriver  Returns this object to support chaining.
     */
    public function removeColumn($table, $column)
    {
        return $this->setQuery('ALTER TABLE '.$this->quoteName($table).' DROP '.$this->quoteName($column))->execute();
    }

	/**
	 * Counts all the rows when using a limit
	 * @return Integer
	 */
	public function getTotalCount()
	{
		$this->setQuery('SELECT FOUND_ROWS() AS count');
		$count = $this->loadObject();
		return $count->count;
	}

	/**
	* Truncates the selected table
	* @param $table
	* @return $this
	*/
	public function truncate($table)
	{
		$this->setQuery('TRUNCATE TABLE '.$this->quoteName($table).';')->execute();

		return $this;
	}

	/**
	* Duplicates a table
	* @param $table
	* @param $content
	* @return $this
	*/
	public function duplicate($table, $content = true)
	{
		$date = date('Ymd_His');
		$this->setQuery('CREATE TABLE '.$this->quoteName($table.'_'.$date).' LIKE '.$this->quoteName($table).';')->execute();

		if($content){
			$this->copy($table, $table.'_'.$date);
		}

		return $this;
	}

	/**
	* Copy a table intro another
	* @param $tableFrom
	* @param $tableTo
	* @return $this
	*/
	public function copy($tableFrom, $tableTo)
	{
		$this->setQuery('INSERT '.$this->quoteName($tableTo).' SELECT * FROM '.$this->quoteName($tableFrom).';')->execute();

		return $this;
	}
}
