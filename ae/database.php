<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2013 Anton Muraviev <chromice@gmail.com>
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# 

ae::invoke(array('aeDatabase', 'connection'));

class aeDatabase
/*
	A MySQL database abstraction layer.
	
	`database.[connection name]` options:
		`class`     - connection class: 'aeDatabase' by default;
		`host`      - connection host;
		`port`      - connection port;
		`socket`    - connection socket;
		`user`      - user name;
		`password`  - password;
		`database`  - database name.
*/
{
	// ==============
	// = Connection =
	// ==============
	
	protected static $connections = array();
	
	public static function connection($database = null)
	{
		if (is_null($database))
		{
			$database = 'default';
		}
		
		if (isset(self::$connections[$database]))
		{
			return self::$connections[$database];
		}
		
		$params = ae::options('database.' . $database, false);
		
		if ($params === false)
		{
			trigger_error('Unknown database connection: ' . $database, E_USER_ERROR);
		}
		
		$class = $params->get('class', get_called_class());
		
		self::$connections[$database] = new $class(
			$params->get('host'),
			$params->get('user'),
			$params->get('password'),
			$params->get('database'),
			$params->get('port'),
			$params->get('socket')
		);
		
		return self::$connections[$database];
	}
	
	protected $db;
	
	public function __construct($host, $user, $password, $database, $port, $socket)
	{
		if (!is_null($port))
		{
			$port = (int) $port;
		}

		$this->db = new MySQLi($host, $user, $password, $database, $port, $socket);
		
		if ($this->db->connect_error)
		{
			throw new aeDatabaseException($this->db->connect_error);
		}
		
		if ($this->db->set_charset('utf8') === false)
		{
			throw new aeDatabaseException('Could not switch character set to utf8: ' 
				. $this->db->error);
		}
	}
	
	public function __destruct()
	{
		$this->db->close();
	}
	
	// ===============
	// = Transaction =
	// ===============

	public function transaction()
	/*
		Returns a transaction object, which opens up a new transaction, and 
		rolls it back, unless it has been explicitly committed.
	*/
	{
		return new aeDatabaseTransaction($this->db);
	}
	
	// =========
	// = Query =
	// =========
	
	protected $query;
	
	protected $sql_join;
	protected $sql_where;
	protected $sql_group_by;
	protected $sql_having;
	protected $sql_order_by;
	protected $sql_limit;
	
	protected $names = array();
	protected $variables = array();
	protected $values = array();
	
	public function query($query)
	/*
		Sets the query to run.
		
		The query may contain {placeholders} that are replaced by other methods.
		See pretty much every method below.
	*/
	{
		$this->query = $query;
		
		return $this;
	}
	
	public function join($join, $type = '')
	/*
		Sets the `{sql:join}` placeholder.
		
		Example:
		
			// LEFT JOIN b ON b.id = a.b_id
 			join('b ON b.id = a.b_id', 'left'); 
		
	*/
	{
		$type = strtoupper($type);
		
		if (empty($this->sql_join))
		{
			$this->sql_join = '';
		}
		else
		{
			$this->sql_join.= "\n";
		}
		
		$this->sql_join.= trim($type . ' JOIN ' . $join);
		
		return $this;
	}
	
	public function where($column, $value = null)
	/*
		Sets the `{sql:where}` placeholder.
		
		Examples:
			
			// `id` = 2
			where('id', 2);
			
			// `a` = 1 AND `b` = 2
			where(array(
				'a' => 1,
				'b' => 2
			));
			
			// `table`.`created_on` > NOW()
			where('{table}.`created_on` > NOW()');
	*/
	{
		if (empty($this->sql_where))
		{
			$this->sql_where = 'WHERE ';
		}
		else
		{
			$this->sql_where.= "\n\tAND ";
		}
		
		$this->sql_where.= '(' . $this->_where($column, $value) . ')';
		
		return $this;
	}
	
	public function group_by($clause)
	/*
		Sets the `{sql:group_by}` placeholder. 
		
		Example:
		
			// GROUP BY `id`
			group_by('id');
	*/
	{
		if (empty($this->sql_group_by))
		{
			$this->sql_group_by = 'GROUP BY ' . $clause;
		}
		else
		{
			$this->sql_group_by.= ', ' . $clause;
		}
		
		return $this;
	}
	
	public function having($column, $value = null)
	/*
		Sets the `{sql:having}` placeholder. 
		
		Works similarly to `where()` method.
	*/
	{
		if (empty($this->sql_having))
		{
			$this->sql_having = 'HAVING ';
		}
		else
		{
			$this->sql_having.= "\n\tAND ";
		}
		
		$this->sql_having.= '(' . $this->_where($column, $value) . ')';
		
		return $this;
	}
	
	public function order_by($clause)
	/*
		Sets the `{sql:order_by}` placeholder. 
		
		Example:
		
			// ORDER BY `time` DESC
			order_by('`time` DESC');
	*/
	{
		if (empty($this->sql_order_by))
		{
			$this->sql_order_by = 'ORDER BY ' . $clause;
		}
		else
		{
			$this->sql_order_by.= ', ' . $clause;
		}
		
		return $this;
	}
	
	public function limit($limit, $offset = null)
	/*
		Sets the `{sql:limit}` placeholder. 
		
		Examples:
		
			// LIMIT 10
			limit(10);
			
			// LIMIT 10 OFFSET 20
			limit(10, 20);
	*/
	{
		$this->sql_limit = 'LIMIT ' . (int) $limit;
		
		if (!empty($offset))
		{
			$this->sql_limit.= ' OFFSET ' . (int) $offset;
		}
		
		return $this;
	}
	
	protected function _where($where, $value = null)
	{
		if (is_scalar($value) && is_scalar($where))
		{
			$where = array($where => $value);
		}
		
		if (is_array($where))
		{
			$_where = array();

			foreach ($where as $_key => $_value)
			{
				$_where[] = $this->identifier($_key) . ' = ' . $this->value($_value);
			}

			return implode(' AND ', $_where);
		}
		else
		{
			return $where;
		}
	}
	
	protected function _query()
	/*
		Returns a query ready to be executed and resets the state.
	*/
	{
		if (empty($this->query))
		{
			return trigger_error('Cannot execute an empty SQL query!', E_USER_ERROR);
		}
		
		$query = str_replace(array(
			'{sql:join}',
			'{sql:where}',
			'{sql:group_by}',
			'{sql:having}',
			'{sql:order_by}',
			'{sql:limit}'
		), array(
			$this->sql_join,
			$this->sql_where,
			$this->sql_group_by,
			$this->sql_having,
			$this->sql_order_by,
			$this->sql_limit
		), $this->query);
		
		$placeholders = array();
		
		if (!empty($this->names))
		{
			$placeholders = array_map(array($this, 'identifier'), $this->names);
		}
		
		if (!empty($this->variables))
		{
			$placeholders = array_merge($placeholders, array_map(array($this, 'value'), $this->variables));
		}
		
		if (!empty($this->values))
		{
			$keys = array();
			$values = array();
			$keys_values = array();
			
			foreach ($this->values as $key => $value)
			{
				$keys[] = $key = $this->identifier($key);
				$values[] = $value = $this->value($value);
				$keys_values[] = $key . ' = ' . $value;
			}
			
			$placeholders['keys'] = implode(', ', $keys);
			$placeholders['values'] = implode(', ', $values);
			$placeholders['keys=values'] = implode(', ', $keys_values);
		}

		$tokens = preg_replace('/.+/', '{$0}', array_keys($placeholders));

		$query = str_replace(
			$tokens,
			$placeholders,
			$query
		);
		
		$this->query = null;
		$this->parts = null;
		$this->names = array();
		$this->variables = array();
		$this->values = array();
		
		$this->sql_join = null;
		$this->sql_where = null;
		$this->sql_group_by = null;
		$this->sql_having = null;
		$this->sql_order_by = null;
		$this->sql_limit = null;
		
		return $query;
	}
	
	public function identifier($name)
	/*
		Protects an alias, table or column name with backticks.
	*/
	{
		return '`' . str_replace('`', '``', $name) . '`';
	}
	
	public function value($value)
	/*
		Returns an escaped value.
		
		- Arrays are serialized into strings. 
		- Objects are cast to string. 
		- Booleans are cast to integer.
	*/
	{
		switch (gettype($value))
		{
			case 'array':
				$value = serialize($value); // ...and then...
			case 'object':
				$value = (string) $value; // ...implicitly cast to string...
			case 'string': 
				$value = '"' . $this->db->real_escape_string($value) . '"';
				break;
			case 'boolean':
				$value = $value ? '1' : '0';
				break;
			case 'NULL':
				$value = 'NULL';
		}
		
		return $value;
	}
	
	public function names($names)
	{
		$this->names = array_merge($this->names, $names);
		
		return $this;
	}
	
	public function variables($variables)
	{
		$this->variables = array_merge($this->variables, $variables);
		
		return $this;
	}
	
	public function values($values)
	{
		$this->values = array_merge($this->values, $values);
		
		return $this;
	}
	
	public function prepared()
	/*
		Returns a raw MySQLi prepared statement and resets the query.
	*/
	{
		$query = $this->_query();
		$query = str_replace($this->value('?'), '?', $query);
		
		return $this->db->prepare($query);
	}
	
	public function make()
	/*
		Runs current query and returns the number of affected rows 
		or FALSE if query had an error.
	*/
	{
		$this->_result(null);
		
		return $this->db->affected_rows;
	}
	
	public function insert_id()
	/*
		Returns the auto generated id used in the last query. Or NULL.
	*/
	{
		return $this->db->insert_id !== 0 ? $this->db->insert_id : null;
	}
	
	public function result($result = 'aeDatabaseResult')
	/*
		Runs current query and returns the result set.
	*/
	{
		return $this->_result($result);
	}
	
	protected function _result($result = 'aeDatabaseResult', $class = null, $related = null)
	{
		$query = $this->_query();
		
		$return = $this->db->query($query, MYSQLI_STORE_RESULT);
		
		if ($return === false)
		{
			throw new aeDatabaseException($this->db->error . ': "' . $query . '"');
		}
		
		if (class_exists($result))
		{
			return new $result($return, $class, $related);
		}
	}
	
	// =================
	// = Class binding =
	// =================
	
	protected $using = array();
	
	public function one($class, $result = 'aeDatabaseResult')
	/*
		Executes the query and returns an instance of table class.
	*/
	{
		$result = $this->many($class, $result);
		
		if ($result->count() > 0)
		{
			return $result->fetch();
		}
		
		return null;
	}
	
	public function many($class, $result = 'aeDatabaseResult')
	/*
		Executes the query and returns a instance of result class.
	*/
	{
		$return = $this->_result($result, $class, $this->using);
		
		$this->using = array();
		
		return $return;
	}
	
	public function using($class, $property = null)
	/*
		Specifies secondary/related table classes to use and what property 
		of the primary instance to assign the instance to.
	*/
	{
		$this->using[$class] = $property;
		
		return $this;
	}
	
	// ==================
	// = Helper queries =
	// ==================
	
	public function columns($table)
	/*
		Returns an associative array of column names as keys and whether 
		they are primary keys (TRUE or FALSE) as values.
	*/
	{
		$result = $this->query("SHOW COLUMNS FROM {table}")
			->names(array(
				'table' => $table
			))
			->result();
		
		$columns = array();
		
		while ($column = $result->fetch())
		{
			$columns[$column['Field']] = ($column['Key'] === 'PRI');
		}
		
		return $columns;
	}
	
	public function count($table, $where = null, $where_value = null)
	/*
		Returns the number of total or matching rows in the table.
	*/
	{
		if (!is_null($where))
		{
			$this->where($where, $where_value);
		}
		
		$result = $this->select($table, 'COUNT(*) AS `found`')
			->result()
			->fetch();
		
		return $result['found'];
	}
	
	public function find($table, $where, $where_value = null)
	/*
		Finds a particular row in the table.
	*/
	{
		$result = $this->select($table)
			->where($where, $where_value)
			->limit(1)
			->result();
			
		$found = $result->fetch();
		
		return $found;
	}
	
	public function select($table, $columns = null)
	/*
		Declares a standard SELECT query with clause placeholders that
		you can fill/modify via join(), where(), group_by(), having(),
		order_by() and limit() methods.
	*/
	{
		if (is_null($columns))
		{
			$columns = '*';
		}
		else if (is_array($columns))
		{
			$columns = array_map(array($this, 'identifier'), $columns);
			$columns = implode(', ', $columns);
		}
		
		return $this->query("SELECT $columns FROM {table} {sql:join} {sql:where} {sql:group_by} {sql:having} {sql:order_by} {sql:limit}")
			->names(array(
				'table' => $table
			));
	}
	
	public function insert($table, $values)
	/*
		Insert a new row and returns its autoincremented primary key or NULL.
	*/
	{
		return $this->query("INSERT INTO {table} ({keys}) VALUES ({values})")
			->names(array(
				'table' => $table
			))
			->values($values)
			->make() > 0 ? $this->insert_id() : null;
	}
	
	public function insert_or_update($table, $values, $where)
	/*
		Updates the existing row or inserts a new one, if it does not exist.
		
		NB! Unlike other methods, $where argument must be an associative array.
	*/
	{
		$insert_keys = array();
		$insert_values = array();
		
		foreach ($where as $key => $value)
		{
			$insert_keys[] = $this->identifier($key);
			$insert_values[] = $this->value($value);
		}
		
		$insert_keys = implode(', ', $insert_keys);
		$insert_values = implode(', ', $insert_values);
		
		return $this->query("INSERT INTO {table} ({keys}, $insert_keys) 
				VALUES ({values}, $insert_values) 
				ON DUPLICATE KEY UPDATE {keys=values}")
			->names(array(
				'table' => $table
			))
			->values($values)
			->make();
	}
	
	public function update($table, $values, $where, $where_value = null)
	/*
		Updates existing row(s) and returns the number of affected rows.
	*/
	{
		return $this->query("UPDATE {table} SET {keys=values} {sql:where}")
			->names(array(
				'table' => $table
			))
			->values($values)
			->where($where, $where_value)
			->make();
	}
	
	public function delete($table, $where, $where_value = null)
	/*
		Deletes existing row(s) and returns the number of affected rows.
	*/
	{
		return $this->query("DELETE FROM {table} {sql:where}")
			->names(array(
				'table' => $table
			))
			->where($where, $where_value)
			->make();
	}
}

class aeDatabaseTransaction
{
	protected $db;
	
	public function __construct($db)
	{
		$this->db = $db;
		$this->db->autocommit(false);
	}
	
	public function __destruct()
	{
		$this->db->rollback();
		$this->db->autocommit(true);
	}
	
	public function commit()
	{
		$this->db->commit();
	}
}

class aeDatabaseResult
{
	protected $result;
	
	protected $class;
	protected $columns;
	protected $related;
	
	public function __construct($result, $class = null, $related = null)
	{
		$this->result = $result;
		
		if (is_null($class))
		{
			return;
		}
		
		$this->class = $class;
		$this->columns = array();
		$this->related = array();
		
		if (is_array($related)) foreach ($related as $_class => $_alias)
		{
			$this->related[$_class::name()] = array(
				'class' => $_class,
				'alias' => empty($_alias) ? $_class::name() : $_alias
			);
		}
		
		foreach ($this->result->fetch_fields() as $offset => $field)
		{
			if (isset($this->related[$field->table]))
			{
				$this->related[$field->table]['columns'][$offset] = $field->name;
			}
			else
			{
				$this->columns[$offset] = $field->name;
			}
		}
	}
	
	public function __destruct()
	{
		$this->result->free();
	}
	
	public function fetch()
	/*
		Fetches the next result as an associate array or instance of 
		the table class.
	*/
	{
		if (is_null($this->class))
		{
			return $this->result->fetch_assoc();
		}

		$row = $this->result->fetch_array(MYSQLI_NUM);
		
		if (is_null($row))
		{
			return;
		}
		
		$class = $this->class;
		
		$values = array_combine($this->columns, array_intersect_key($row, $this->columns));
		
		$object = new $class($values, true);
		
		foreach ($this->related as $name => $table)
		{
			$class = $table['class'];
			
			$values = array_combine($table['columns'], array_intersect_key($row, $table['columns']));
			
			$related = new $class($values, true);
			
			$object->_attach($table['alias'], $related);
		}
		
		return $object;
	}
	
	public function count()
	/*
		Returns the number of results.
	*/
	{
		return $this->result->num_rows;
	}
	
	public function seek($offset)
	/*
		Sets the internal pointer to a particular result's offset.
	*/
	{
		return $this->result->data_seek($offset);
	}

	public function all()
	/*
		Returns an array of all results.
	*/
	{
		$all = array();
		
		if ($this->count() > 0)
		{
			while ($row = $this->fetch())
			{
				$all[] = $row;
			}
		
			$this->seek(0);
		}
		
		return $all;
	}
}

abstract class aeDatabaseTable
{
	// ========================
	// = Entity configuration =
	// ========================
	
	private static $tables = array();

	protected static function database()
	/*
		Returns an instance of database connection.
	*/
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['database']))
		{
			self::$tables[$class]['database'] = ae::database('default');
		}
		
		return self::$tables[$class]['database'];
	}
	
	public static function name()
	/*
		Returns the actual name of the table.
		
		The default implementation derives the from the class name, e.g.:
		Customers -> customers, CustomerOrders -> customer_orders, etc.
	*/
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['name']))
		{
			self::$tables[$class]['name'] = strtolower(
				preg_replace('~(?<=.)_*([A-Z])~', '_$1', $class)
			);
		}
		
		return self::$tables[$class]['name'];
	}

	public static function accessor()
	/*
		Returns an array of primary key names.
	*/
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['accessor']))
		{
			static::_load_columns();
		}

		return self::$tables[$class]['accessor'];
	}
	
	public static function columns()
	/*
		Returns an array of data column names.
	*/
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['columns']))
		{
			static::_load_columns();
		}
		
		return self::$tables[$class]['columns'];
	}
	
	private static function _load_columns()
	{
		$class = get_called_class();
		
		self::$tables[$class]['accessor'] = array();
		self::$tables[$class]['columns'] = array();
		
		$db = static::database();
		$columns = $db->columns(static::name());
		
		foreach ($columns as $column => $primary)
		{
			if ($primary)
			{
				self::$tables[$class]['accessor'][] = $column;
			}
			else
			{
				self::$tables[$class]['columns'][] = $column;
			}
		}
	}
	
	// ======================
	// = Data serialization =
	// ======================
	
	protected static function serialize($values)
	/*
		Processes values before they are sent to database.
	*/
	{
		return $values;
	}
	
	protected static function unserialize($record)
	/*
		Processes values retrieved from database.
	*/
	{
		return $record;
	}
	
	// ===================
	// = Entity creation =
	// ===================
	
	private $ids = array();
	private $values = array();
	private $transient = array();
	private $related = array();
	
	private $is_dirty = false;
	
	public function __construct($values = null, $_raw = false)
	/*
		NB! Must not be used directly. 
		
		Use aeDatabaseTable::create() method instead.
	*/
	{
		$table = static::name();
		$accessor = static::accessor();
		
		if (empty($accessor))
		{
			trigger_error('Table "' . $table . '" has no accessor.', E_USER_ERROR);
		}
		
		if (is_array($values))
		{
			$this->values($values, $_raw);
		}
		
		$this->is_dirty = !$_raw;
	}
	
	public function _attach($related, $object)
	/*
		Attaches an instance of (usually) related entity.
	*/
	{
		if (!is_a($object, 'aeDatabaseTable'))
		{
			trigger_error('Cannot attach an instance of "' . get_class($object) . '" class.', E_USER_ERROR);
		}
		
		$this->related[$related] = $object;
		
		return $this;
	}
	
	// =======================
	// = Setters and getters =
	// =======================
	
	public function values($values = null, $raw = false)
	/*
		Sets supplied $values or returns current values.
		
		If second argument is TRUE, $values are unserialized before being set.
	*/
	{
		if (is_array($values))
		{
			if ($raw === true)
			{
				$values = static::unserialize($values);
			}
			
			foreach ($values as $key => $value)
			{
				$this->$key = $value;
			}
		}
		else if (is_null($values))
		{
			return array_merge($this->transient, $this->values, $this->ids);
		}
	}
	
	public function __set($name, $value)
	{
		$ref =& $this->_value_reference($name);
		
		if (!isset($ref[$name]) || $ref[$name] !== $value)
		{
			$this->is_dirty = true;
		}
		
		$ref[$name] = $value;
	}
	
	public function __get($name)
	{
		if (isset($this->related[$name]))
		{
			return $this->related[$name];
		}
		
		$ref =& $this->_value_reference($name);
		
		if (isset($ref[$name]))
		{
			return $ref[$name];
		}
	}
	
	public function __isset($name)
	{
		$ref =& $this->_value_reference($name);
		
		return isset($ref[$name]);
	}
	
	public function __unset($name)
	{
		$ref =& $this->_value_reference($name);
		
		unset($ref[$name]);
	}
	
	protected function &_value_reference($name)
	{
		if (in_array($name, static::accessor()))
		{
			return $this->ids;
		}
		else if (in_array($name, static::columns()))
		{
			return $this->values;
		}
		else
		{
			return $this->transient;
		}
	}
	
	// ======================
	// = Basic CRUD methods =
	// ======================
	
	public static function create($values = null)
	/*
		Creates a new instance.
		
		Using this factory method is preferable to `new` operator.
	*/
	{
		$class = get_called_class();
		
		$entity = new $class($values);
		
		return $entity;
	}
	
	public static function find($ids)
	/*
		Creates an instance pointing to a record.
	*/
	{
		$accessor = static::accessor();
		
		if (is_scalar($ids) && count($accessor) === 1)
		{
			$ids = array(
				array_pop($accessor) => $ids
			);
		}
		else if (count($accessor) !== count($ids))
		{
			throw new aeDatabaseException(get_called_class() 
				. '::find() failed, because accessor value'
				. (count($accessor) > 1 ? 's are' : ' is') 
				. ' not defined.');
		}
		
		return static::create($ids);
	}
	
	public function ids()
	/*
		Returns an associative array of accessor values.
	*/
	{
		$accessor = static::accessor();
		
		if (count($accessor) !== count($this->ids))
		{
			throw new aeDatabaseException(get_class($this) 
				. '::id() failed, because accessor value' 
				. (count($accessor) > 1 ? 's are' : ' is') 
				. ' not defined.');
		}
		
		return $this->ids;
	}
	
	public function load($columns = '*')
	/*
		(Re)load the record values from database.
	*/
	{
		$found = static::database()
			->select(static::name(), $columns)
			->where($this->ids())
			->limit(1)
			->result();
		
		$values = $found->fetch();
		
		if (!is_array($values))
		{
			throw new aeDatabaseException(get_class($this) 
				. '::load() failed, because accessor points ' 
				. 'to nothing.');
		}
		
		// Reset all current values
		$this->values = array();
		$this->transient = array();
		
		$this->values($values, true);
		$this->is_dirty = false;
		
		return $this;
	}
	
	public function save()
	/*
		Saves or updates the record data in database.
	*/
	{
		if (!$this->is_dirty || empty($this->values))
		{
			return $this;
		}
		
		$db = static::database();
		$accessor = static::accessor();
		$columns = static::columns();
		
		if (empty($this->ids) && count($accessor) == 1)
		{
			$this->ids[array_pop($accessor)] = $db->insert(
				static::name(),
				static::serialize($this->values)
			);
		}
		else
		{
			$db->insert_or_update(
				static::name(), 
				static::serialize($this->values), 
				$this->ids()
			);
		}
		
		$this->is_dirty = false;

		return $this;
	}
	
	public function delete()
	/*
		Deletes the record from database.
	*/
	{
		static::database()->delete(static::name(), $this->ids());
		
		$this->ids = array();
		$this->values = array();
		$this->transient = array();
		
		return $this;
	}
}

class aeDatabaseException extends Exception {}