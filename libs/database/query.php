<?php
/**
 * @package     Synapse
 * @subpackage  Database/Query
 */

defined('_INIT') or die;


class Query {

    protected $type         = '';
    protected $sql          = null;
	protected $select       = null;
	protected $update       = null;
	protected $values       = null;
	protected $from         = null;
	protected $where        = null;
	protected $leftJoin     = null;
    protected $limit        = null;
    protected $offset       = null;
    protected $order        = null;
    protected $columns      = null;
    protected $join         = null;
    protected $group        = null;
    protected $having       = null;
    protected $call         = null;
    protected $delete       = null;
    protected $exec         = null;
    protected $union        = null;
    protected $set          = null;
    protected $prefix       = null;
    protected $autoIncrementField = null;

	public function __construct()
	{
		$this->prefix = App::getConfig()->db_prefix;
	}

    /**
	 * Add a single column, or array of columns to the SELECT clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 * The select method can, however, be called multiple times in the same query.
	 *
	 * Usage:
	 * $query->select('a.*')->select('b.id');
	 * $query->select(array('a.*', 'b.id'));
	 *
	 * @param   mixed  $columns  A string or an array of field names.
	 * @param   Boolean  $count  If true will count all rows when using a limit.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function select($columns, $count = false)
    {
        $this->type = 'select';

		if (is_null($this->select)){
			if($count){
				$this->select = new QueryElement('SELECT SQL_CALC_FOUND_ROWS', $columns);
			} else {
				$this->select = new QueryElement('SELECT', $columns);
			}
		} else {
			$this->select->append($columns);
		}

        return $this;
    }

    /**
	 * Add a single condition string, or an array of strings to the SET clause of the query.
	 *
	 * Usage:
	 * $query->set('a = 1')->set('b = 2');
	 * $query->set(array('a = 1', 'b = 2');
	 *
	 * @param   mixed   $conditions  A string or array of string conditions.
	 * @param   string  $glue        The glue by which to join the condition strings. Defaults to ,.
	 *                               Note that the glue is set on first use and cannot be changed.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function set($conditions, $glue = ',')
	{
		if (is_null($this->set)){
			$glue = strtoupper($glue);
			$this->set = new QueryElement('SET', $conditions, PHP_EOL . "\t$glue ");
		} else {
			$this->set->append($conditions);
		}

		return $this;
	}

    /**
	 * Allows a direct query to be provided to the database
	 * driver's setQuery() method, but still allow queries
	 * to have bounded variables.
	 *
	 * Usage:
	 * $query->setQuery('select * from #__users');
	 *
	 * @param   mixed  $sql  An SQL Query
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function setQuery($sql)
	{
		$this->sql = $sql;

		return $this;
	}

    /**
	 * Adds a tuple, or array of tuples that would be used as values for an INSERT INTO statement.
	 *
	 * Usage:
	 * $query->values('1,2,3')->values('4,5,6');
	 * $query->values(array('1,2,3', '4,5,6'));
	 *
	 * @param   string  $values  A single tuple, or array of tuples.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function values($values)
	{
		if (is_null($this->values)){
			$this->values = new QueryElement('()', $values, '),(');
		} else {
			$this->values->append($values);
		}

		return $this;
	}

    /**
	 * Add a table name to the UPDATE clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 *
	 * Usage:
	 * $query->update('#__foo')->set(...);
	 *
	 * @param   string  $table  A table to update.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function update($table)
	{
		$this->type = 'update';
		$this->update = new QueryElement('UPDATE', $table);

		return $this;
	}

    /**
	 * Add a table to the FROM clause of the query.
	 *
	 * Note that while an array of tables can be provided, it is recommended you use explicit joins.
	 *
	 * Usage:
	 * $query->select('*')->from('#__a');
	 *
	 * @param   mixed   $tables         A string or array of table names.
	 *                                  This can be a JDatabaseQuery object (or a child of it) when used
	 *                                  as a subquery in FROM clause along with a value for $subQueryAlias.
	 * @param   string  $subQueryAlias  Alias used when $tables is a Query.
	 *
	 * @return  DatabaseQuery  Returns this object to allow chaining.
	 * @throws  Error
	 */
    public function from($tables, $subQueryAlias = null)
	{
		if (is_null($this->from)){
			if ($tables instanceof $this){

				if (is_null($subQueryAlias)){
					throw new Error('Sub query alias is null???');
				}

				$tables = '( ' . (string) $tables . ' ) AS ' . $this->quoteName($subQueryAlias);
			}

			$this->from = new QueryElement('FROM', $tables);
		} else {
			$this->from->append($tables);
		}

		return $this;
	}

    /**
	 * Add a single condition, or an array of conditions to the WHERE clause of the query.
	 *
	 * Usage:
	 * $query->where('a = 1')->where('b = 2');
	 * $query->where(array('a = 1', 'b = 2'));
	 *
	 * @param   mixed   $conditions  A string or array of where conditions.
	 * @param   string  $glue        The glue by which to join the conditions. Defaults to AND.
	 *                               Note that the glue is set on first use and cannot be changed.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function where($conditions, $glue = 'AND')
	{
		if (is_null($this->where)){
			$glue = strtoupper($glue);
			$this->where = new QueryElement('WHERE', $conditions, " $glue ");
		} else {
			$this->where->append($conditions);
		}

		return $this;
	}

    /**
	 * Add a JOIN clause to the query.
	 *
	 * Usage:
	 * $query->join('INNER', 'b ON b.id = a.id);
	 *
	 * @param   string  $type        The type of join. This string is prepended to the JOIN keyword.
	 * @param   string  $conditions  A string or array of conditions.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function join($type, $conditions)
	{
		if (is_null($this->join)){
			$this->join = array();
		}

		$this->join[] = new QueryElement(strtoupper($type) . ' JOIN', $conditions);

		return $this;
	}

    /**
	 * Add a LEFT JOIN clause to the query.
	 *
	 * Usage:
	 * $query->leftJoin('b ON b.id = a.id')->leftJoin('c ON c.id = b.id');
	 *
	 * @param   string  $condition  The join condition.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function leftJoin($condition)
	{
		$this->join('LEFT', $condition);

		return $this;
	}

    /**
	 * Add an INNER JOIN clause to the query.
	 *
	 * Usage:
	 * $query->innerJoin('b ON b.id = a.id')->innerJoin('c ON c.id = b.id');
	 *
	 * @param   string  $condition  The join condition.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function innerJoin($condition)
	{
		$this->join('INNER', $condition);

		return $this;
	}

    /**
	 * Add an OUTER JOIN clause to the query.
	 *
	 * Usage:
	 * $query->outerJoin('b ON b.id = a.id')->outerJoin('c ON c.id = b.id');
	 *
	 * @param   string  $condition  The join condition.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function outerJoin($condition)
	{
		$this->join('OUTER', $condition);

		return $this;
	}

    /**
	 * Add a RIGHT JOIN clause to the query.
	 *
	 * Usage:
	 * $query->rightJoin('b ON b.id = a.id')->rightJoin('c ON c.id = b.id');
	 *
	 * @param   string  $condition  The join condition.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function rightJoin($condition)
	{
		$this->join('RIGHT', $condition);

		return $this;
	}

    /**
	 * Add a table name to the INSERT clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 *
	 * Usage:
	 * $query->insert('#__a')->set('id = 1');
	 * $query->insert('#__a')->columns('id, title')->values('1,2')->values('3,4');
	 * $query->insert('#__a')->columns('id, title')->values(array('1,2', '3,4'));
	 *
	 * @param   mixed    $table           The name of the table to insert data into.
	 * @param   boolean  $incrementField  The name of the field to auto increment.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function insert($table, $incrementField=false)
	{
		$this->type = 'insert';
		$this->insert = new QueryElement('INSERT INTO', $table);
		$this->autoIncrementField = $incrementField;

		return $this;
	}

    public function limit($count)
    {
        $this->limit = new QueryElement('LIMIT', $count);
        return $this;
    }

    public function offset($count)
    {
        $this->offset = new QueryElement('OFFSET', $count);
        return $this;
    }

    /**
	 * Add a ordering column to the ORDER clause of the query.
	 *
	 * Usage:
	 * $query->order('foo')->order('bar');
	 * $query->order(array('foo','bar'));
	 *
	 * @param   mixed  $columns  A string or array of ordering columns.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function order($columns)
	{
		if (is_null($this->order)){
			$this->order = new QueryElement('ORDER BY', $columns);
		} else {
			$this->order->append($columns);
		}

		return $this;
	}

    /**
	 * Adds a column, or array of column names that would be used for an INSERT INTO statement.
	 *
	 * @param   mixed  $columns  A column name, or array of column names.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
    public function columns($columns)
	{
		if (is_null($this->columns)){
			$this->columns = new QueryElement('()', $columns);
		} else {
			$this->columns->append($columns);
		}

		return $this;
	}

    /**
	 * Add a table name to the DELETE clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 *
	 * Usage:
	 * $query->delete('#__a')->where('id = 1');
	 *
	 * @param   string  $table  The name of the table to delete from.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function delete($table = null)
	{
		$this->type = 'delete';
		$this->delete = new QueryElement('DELETE', null);

		if (!empty($table)){
			$this->from($table);
		}

		return $this;
	}

    /**
	 * Add a single column, or array of columns to the EXEC clause of the query.
	 *
	 * Note that you must not mix insert, update, delete and select method calls when building a query.
	 * The exec method can, however, be called multiple times in the same query.
	 *
	 * Usage:
	 * $query->exec('a.*')->exec('b.id');
	 * $query->exec(array('a.*', 'b.id'));
	 *
	 * @param   mixed  $columns  A string or an array of field names.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function exec($columns)
	{
		$this->type = 'exec';

		if (is_null($this->exec)){
			$this->exec = new QueryElement('EXEC', $columns);
		} else {
			$this->exec->append($columns);
		}

		return $this;
	}

    /**
	 * Add a query to UNION with the current query.
	 * Multiple unions each require separate statements and create an array of unions.
	 *
	 * Usage:
	 * $query->union('SELECT name FROM  #__foo')
	 * $query->union('SELECT name FROM  #__foo','distinct')
	 * $query->union(array('SELECT name FROM  #__foo', 'SELECT name FROM  #__bar'))
	 *
	 * @param   mixed    $query     The Query object or string to union.
	 * @param   boolean  $distinct  True to only return distinct rows from the union.
	 * @param   string   $glue      The glue by which to join the conditions.
	 *
	 * @return  mixed    The Query object on success or boolean false on failure.
	 *
	 * @since   1.0
	 */
	public function union($query, $distinct = false, $glue = '')
	{
		// Clear any ORDER BY clause in UNION query
		// See http://dev.mysql.com/doc/refman/5.0/en/union.html
		if (!is_null($this->order)){
			$this->clear('order');
		}

		// Set up the DISTINCT flag, the name with parentheses, and the glue.
		if ($distinct){
			$name = 'UNION DISTINCT ()';
			$glue = ')' . PHP_EOL . 'UNION DISTINCT (';
		} else {
			$glue = ')' . PHP_EOL . 'UNION (';
			$name = 'UNION ()';
		}

		// Get the Query\QueryElement if it does not exist
		if (is_null($this->union)){
			$this->union = new QueryElement($name, $query, "$glue");
		} else { // Otherwise append the second UNION.
			$this->union->append($query);
		}

		return $this;
	}

    /**
	 * Add a query to UNION DISTINCT with the current query. Simply a proxy to Union with the Distinct clause.
	 *
	 * Usage:
	 * $query->unionDistinct('SELECT name FROM  #__foo')
	 *
	 * @param   mixed   $query  The Query object or string to union.
	 * @param   string  $glue   The glue by which to join the conditions.
	 *
	 * @return  mixed   The Query object on success or boolean false on failure.
	 */
	public function unionDistinct($query, $glue = '')
	{
		$distinct = true;

		// Apply the distinct flag to the union.
		return $this->union($query, $distinct, $glue);
	}

    /**
	 * Add a grouping column to the GROUP clause of the query.
	 *
	 * Usage:
	 * $query->group('id');
	 *
	 * @param   mixed  $columns  A string or array of ordering columns.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function group($columns)
	{
		if (is_null($this->group)){
			$this->group = new QueryElement('GROUP BY', $columns);
		} else {
			$this->group->append($columns);
		}

		return $this;
	}

    /**
	 * A conditions to the HAVING clause of the query.
	 *
	 * Usage:
	 * $query->group('id')->having('COUNT(id) > 5');
	 *
	 * @param   mixed   $conditions  A string or array of columns.
	 * @param   string  $glue        The glue by which to join the conditions. Defaults to AND.
	 *
	 * @return  Query  Returns this object to allow chaining.
	 */
	public function having($conditions, $glue = 'AND')
	{
		if (is_null($this->having)){
			$glue = strtoupper($glue);
			$this->having = new QueryElement('HAVING', $conditions, " $glue ");
		} else {
			$this->having->append($conditions);
		}

		return $this;
	}

    public function call($columns)
	{
		$this->type = 'call';

		if (is_null($this->call)){
			$this->call = new QueryElement('CALL', $columns);
		} else {
			$this->call->append($columns);
		}

		return $this;
	}

    /**
	 * Concatenates an array of column names or values.
	 *
	 * Usage:
	 * $query->select($query->concatenate(array('a', 'b')));
	 *
	 * @param   array   $values     An array of values to concatenate.
	 * @param   string  $separator  As separator to place between each value.
	 *
	 * @return  string  The concatenated values.
	 */
	public function concatenate($values, $separator = null)
	{
		if ($separator){
			return 'CONCATENATE(' . implode(' || ' . $this->quote($separator) . ' || ', $values) . ')';
		} else {
			return 'CONCATENATE(' . implode(' || ', $values) . ')';
		}
	}

    /**
	 * Gets the current date and time.
	 *
	 * Usage:
	 * $query->where('published_up < '.$query->currentTimestamp());
	 *
	 * @return  string
	 */
	public function currentTimestamp()
	{
		return 'CURRENT_TIMESTAMP()';
	}

    /**
	 * Used to get a string to extract year from date column.
	 *
	 * Usage:
	 * $query->select($query->year($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing year to be extracted.
	 *
	 * @return  string  Returns string to extract year from a date.
	 */
	public function year($date)
	{
		return 'YEAR(' . $date . ')';
	}

    /**
	 * Used to get a string to extract month from date column.
	 *
	 * Usage:
	 * $query->select($query->month($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing month to be extracted.
	 *
	 * @return  string  Returns string to extract month from a date.
	 */
	public function month($date)
	{
		return 'MONTH(' . $date . ')';
	}

	/**
	 * Used to get a string to extract day from date column.
	 *
	 * Usage:
	 * $query->select($query->day($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing day to be extracted.
	 *
	 * @return  string  Returns string to extract day from a date.
	 */
	public function day($date)
	{
		return 'DAY(' . $date . ')';
	}

	/**
	 * Used to get a string to extract hour from date column.
	 *
	 * Usage:
	 * $query->select($query->hour($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing hour to be extracted.
	 *
	 * @return  string  Returns string to extract hour from a date.
	 */
	public function hour($date)
	{
		return 'HOUR(' . $date . ')';
	}

	/**
	 * Used to get a string to extract minute from date column.
	 *
	 * Usage:
	 * $query->select($query->minute($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing minute to be extracted.
	 *
	 * @return  string  Returns string to extract minute from a date.
	 */
	public function minute($date)
	{
		return 'MINUTE(' . $date . ')';
	}

	/**
	 * Used to get a string to extract seconds from date column.
	 *
	 * Usage:
	 * $query->select($query->second($query->quoteName('dateColumn')));
	 *
	 * @param   string  $date  Date column containing second to be extracted.
	 *
	 * @return  string  Returns string to extract second from a date.
	 */
	public function second($date)
	{
		return 'SECOND(' . $date . ')';
	}

    /**
	 * Get the length of a string in bytes.
	 *
	 * Note, use 'charLength' to find the number of characters in a string.
	 *
	 * Usage:
	 * query->where($query->length('a').' > 3');
	 *
	 * @param   string  $value  The string to measure.
	 *
	 * @return  integer
	 */
	public function length($value)
	{
		return 'LENGTH(' . $value . ')';
	}

    public function castAsChar($value)
	{
		return $value;
	}

    public function charLength($field, $operator = null, $condition = null)
	{
		return 'CHAR_LENGTH(' . $field . ')' . (isset($operator) && isset($condition) ? ' ' . $operator . ' ' . $condition : '');
	}

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function dump()
    {
        return '<pre>'.print_r( $this->__toString(), true ).'</pre>';
    }

    public function __toString()
	{
		$query = '';

		if ($this->sql){
			return $this->sql;
		}

		switch ($this->type){
			case 'element':
				$query .= (string) $this->element;
				break;

			case 'select':
				$query .= (string) $this->select;
				$query .= (string) $this->from;

				if ($this->join){
					// Special case for joins
					foreach ($this->join as $join)
					{
						$query .= (string) $join;
					}
				}

				if ($this->where)
				{
					$query .= (string) $this->where;
				}

				if ($this->group)
				{
					$query .= (string) $this->group;
				}

				if ($this->having)
				{
					$query .= (string) $this->having;
				}

				if ($this->order)
				{
					$query .= (string) $this->order;
				}

                if ($this->limit)
                {
                    $query .= (string) $this->limit;
                }

                if ($this->offset)
                {
                    $query .= (string) $this->offset;
                }


				break;

			case 'union':
				$query .= (string) $this->union;
				break;

			case 'delete':
				$query .= (string) $this->delete;
				$query .= (string) $this->from;

				if ($this->join)
				{
					// Special case for joins
					foreach ($this->join as $join)
					{
						$query .= (string) $join;
					}
				}

				if ($this->where)
				{
					$query .= (string) $this->where;
				}

                if ($this->limit)
                {
                    $query .= (string) $this->limit;
                }

				break;

			case 'update':
				$query .= (string) $this->update;

				if ($this->join)
				{
					// Special case for joins
					foreach ($this->join as $join)
					{
						$query .= (string) $join;
					}
				}

				$query .= (string) $this->set;

				if ($this->where)
				{
					$query .= (string) $this->where;
				}

                if ($this->limit)
                {
                    $query .= (string) $this->limit;
                }

				break;

			case 'insert':
				$query .= (string) $this->insert;

				// Set method
				if ($this->set)
				{
					$query .= (string) $this->set;
				}
				elseif ($this->values)
				// Columns-Values method
				{
					if ($this->columns){
						$query .= (string) $this->columns;
					}

					$elements = $this->values->getElements();

					if (!($elements[0] instanceof $this))
					{
						$query .= ' VALUES ';
					}

					$query .= (string) $this->values;
				}

				break;

			case 'call':
				$query .= (string) $this->call;
				break;

			case 'exec':
				$query .= (string) $this->exec;
				break;
		}

		return str_replace('#__', $this->prefix, $query);
	}

    public function clear($clause = null)
	{
		$this->sql = null;

		switch ($clause)
		{
			case 'select':
				$this->select = null;
				$this->type = null;
				break;

			case 'delete':
				$this->delete = null;
				$this->type = null;
				break;

			case 'update':
				$this->update = null;
				$this->type = null;
				break;

			case 'insert':
				$this->insert = null;
				$this->type = null;
				$this->autoIncrementField = null;
				break;

			case 'from':
				$this->from = null;
				break;

			case 'join':
				$this->join = null;
				break;

			case 'set':
				$this->set = null;
				break;

			case 'where':
				$this->where = null;
				break;

			case 'group':
				$this->group = null;
				break;

			case 'having':
				$this->having = null;
				break;

			case 'order':
				$this->order = null;
				break;

			case 'columns':
				$this->columns = null;
				break;

			case 'values':
				$this->values = null;
				break;

			case 'exec':
				$this->exec = null;
				$this->type = null;
				break;

			case 'call':
				$this->call = null;
				$this->type = null;
				break;

			case 'limit':
				$this->offset = 0;
				$this->limit = 0;
				break;

			case 'union':
				$this->union = null;
				break;

            case 'limit':
                $this->limit = null;
				break;

            case 'offset':
                $this->offset = null;
				break;

			default:
				$this->type = null;
				$this->select = null;
				$this->delete = null;
				$this->update = null;
				$this->insert = null;
				$this->from = null;
				$this->join = null;
				$this->set = null;
				$this->where = null;
				$this->group = null;
				$this->having = null;
				$this->order = null;
				$this->columns = null;
				$this->values = null;
				$this->autoIncrementField = null;
				$this->exec = null;
				$this->call = null;
				$this->union = null;
				$this->offset = null;
				$this->limit = null;
				break;
		}

		return $this;
	}
}

?>