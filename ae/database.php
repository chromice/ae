<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2014 Anton Muraviev <chromice@gmail.com>
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

ae::options('ae.database', array(
	'log' => false
));

ae::invoke(array('aeDatabase', 'connection'));

class aeDatabase
/*
	A MySQL database abstraction layer.
	
	Use `ae.database.[connection name]` options to configure connection:
		`class`     - connection class: 'aeDatabase' by default;
		`host`      - connection host;
		`port`      - connection port;
		`socket`    - connection socket;
		`user`      - user name;
		`password`  - password;
		`database`  - database name.
		
	This library must be invoked with the connection name, 
	otherise 'default' connection is used.
	
		ae::options('ae.database.default')
			->set('host', 'localhost')
			->set('user', 'root');
		
		$db = ae::database('default');
		
		$db->query('SELECT 1')->make();
*/
{
	// Value types
	const value = 1;
	const statement = 2;
	
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
		
		$params = ae::options('ae.database.' . $database, array(
			'class' => get_called_class(),
			'host' => '127.0.0.1',
			'user' => null,
			'password' => null,
			'database' => null,
			'port' => null,
			'socket' => null
		));
		
		$class = $params->get('class');
		
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
			throw new aeDatabaseException('Could not switch character set to utf8: ' . $this->db->error);
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
		
		See `aeDatabaseTransaction` for more details.
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
	
	protected $aliases = array();
	protected $variables = array();
	protected $values = array();
	
	public function query($query)
	/*
		Sets the query to run.
		
		The query may contain {placeholders} that are replaced by 
		other methods.
		
		See pretty much every method below.
	*/
	{
		$this->query = $query;
		
		return $this;
	}
	
	public function join($join, $type = '')
	/*
		Sets the {sql:join} placeholder.
		
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
		Sets the {sql:where} placeholder.
		
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
		Sets the {sql:group_by} placeholder.
		
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
		Sets the {sql:having} placeholder. 
		
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
		Sets the {sql:order_by} placeholder.
		
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
		Sets the {sql:limit} placeholder. 
		
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
				$_where[] = $this->backtick($_key) . ' = ' . $this->escape($_value);
			}
			
			$where = implode(' AND ', $_where);
		}
		
		if (is_array($value))
		{
			$tokens = preg_replace('/.+/', '{$0}', array_keys($value));
			$values = array_map(array($this, 'escape'), array_values($value));
			$where = str_replace($tokens, $values, $where);
		}
		
		return $where;
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
		
		if (!empty($this->aliases))
		{
			$placeholders = array_map(array($this, 'backtick'), $this->aliases);
		}
		
		if (!empty($this->variables))
		{
			$placeholders = array_merge($placeholders, $this->variables);
		}
		
		if (!empty($this->values))
		{
			$keys = array();
			$values = array();
			$keys_values = array();
			
			foreach ($this->values as $key => $value)
			{
				$keys[] = $key = $this->backtick($key);
				$values[] = $value;
				$keys_values[] = $key . ' = ' . $value;
			}
			
			$placeholders['data:names'] = implode(', ', $keys);
			$placeholders['data:values'] = implode(', ', $values);
			$placeholders['data:set'] = implode(', ', $keys_values);
		}

		$tokens = preg_replace('/.+/', '{$0}', array_keys($placeholders));

		$query = str_replace($tokens, $placeholders, $query);
		
		$this->query = null;
		$this->parts = null;
		$this->aliases = array();
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
	
	public function backtick($name)
	/*
		Protects an alias, table or column name with backticks.
	*/
	{
		return '`' . str_replace('`', '``', $name) . '`';
	}
	
	public function escape($value)
	/*
		Returns an escaped value:
		
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
	
	public function aliases($aliases)
	/*
		Accepts an associative array of placeholder/indetifier pairs
		used in the current query.
	*/
	{
		$this->aliases = array_merge($this->aliases, $aliases);
		
		return $this;
	}
	
	public function variables($variables, $type = aeDatabase::value)
	/*
		Accepts an associative array of placeholder/variable pairs
		used in the current query.
	*/
	{
		if ($type !== aeDatabase::statement)
		{
			$variables = array_map(array($this, 'escape'), $variables);
		}
		
		$this->variables = array_merge($this->variables, $variables);
		
		return $this;
	}
	
	public function data($values, $type = aeDatabase::value)
	/*
		Accepts an associative array of key/value pairs
		used to replace the following placeholders:
		
		- {data:names} with `key_1`, `key_2`, ...
		- {data:values} with "value 1", "value 2", ...
		- {data:set} with `key_1` = "value 1", `key_2` = "value 2", ...
		
		Useful for writing INSERT and UPDATE queries.
	*/
	{
		if ($type !== aeDatabase::statement)
		{
			$values = array_map(array($this, 'escape'), $values);
		}

		$this->values = array_merge($this->values, $values);
		
		return $this;
	}
	
	public function prepared()
	/*
		Returns a raw MySQLi prepared statement and resets the query.
	*/
	{
		$query = $this->_query();
		$query = str_replace($this->escape('?'), '?', $query);
		
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
		
		See `aeDatabaseResult` for more details.
	*/
	{
		return $this->_result($result);
	}
	
	protected function _result($result = 'aeDatabaseResult', $class = null, $related = null)
	{
		static $query_counter = 0;
		
		$query = $this->_query();
		
		if (ae::options('ae.database')->get('log') === true)
		{
			ae::register('utilities/inspector');
			
			$probe = ae::probe('Query #' . ++$query_counter)->mark();
		}
		
		$return = $this->db->query($query, MYSQLI_STORE_RESULT);
		
		if (!empty($probe))
		{
			$probe->mark($query);
		}
		
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
		
		See `aeDatabaseResult` for more details.
	*/
	{
		$return = $this->_result($result, $class, $this->using);
		
		$this->using = array();
		
		return $return;
	}
	
	public function using($class, $property = null)
	/*
		Specifies secondary/related table class to use and what property 
		of the primary object to assign the instances to.
	*/
	{
		$this->using[$class] = $property;
		
		return $this;
	}
	
	public function joining($class, $singular, $related = null)
	/*
		Specifies secondary/related table join and use.
		
		See `aeDatabaseResult::using()`.
	*/
	{
		$table = $this->backtick($class::name());
		$foreign_key = $this->backtick($singular . '_id');
		
		if (is_null($related))
		{
			$related = '{table}';
		}
		else
		{
			$related = $this->backtick($related::name());
		}
		
		return $this
			->join("$table ON $table.`id` = $related.$foreign_key")
			->using($class, $singular);
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
			->aliases(array(
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
			$columns = array_map(array($this, 'backtick'), $columns);
			$columns = implode(', ', $columns);
		}
		
		return $this->query("SELECT $columns FROM {table} {sql:join} {sql:where} {sql:group_by} {sql:having} {sql:order_by} {sql:limit}")
			->aliases(array(
				'table' => $table
			));
	}
	
	public function insert($table, $values)
	/*
		Insert a new row and returns its autoincremented primary key or NULL.
	*/
	{
		return $this->query("INSERT INTO {table} ({data:names}) VALUES ({data:values})")
			->aliases(array(
				'table' => $table
			))
			->data($values)
			->make() > 0 ? $this->insert_id() : null;
	}
	
	public function insert_or_update($table, $values, $where)
	/*
		Updates the existing row or inserts a new one, if it does not exist.
		
		NB! Unlike other methods, `$where` argument must be an associative array.
	*/
	{
		$insert_keys = array();
		$insert_values = array();
		
		foreach ($where as $key => $value)
		{
			$insert_keys[] = $this->backtick($key);
			$insert_values[] = $this->escape($value);
		}
		
		$insert_keys = implode(', ', $insert_keys);
		$insert_values = implode(', ', $insert_values);
		
		return $this->query("INSERT INTO {table} ({data:names}, $insert_keys) 
				VALUES ({data:values}, $insert_values) 
				ON DUPLICATE KEY UPDATE {data:set}")
			->aliases(array(
				'table' => $table
			))
			->data($values)
			->make();
	}
	
	public function update($table, $values, $where, $where_value = null)
	/*
		Updates existing row(s) and returns the number of affected rows.
	*/
	{
		return $this->query("UPDATE {table} SET {data:set} {sql:where}")
			->aliases(array(
				'table' => $table
			))
			->data($values)
			->where($where, $where_value)
			->make();
	}
	
	public function delete($table, $where, $where_value = null)
	/*
		Deletes existing row(s) and returns the number of affected rows.
	*/
	{
		return $this->query("DELETE FROM {table} {sql:where}")
			->aliases(array(
				'table' => $table
			))
			->where($where, $where_value)
			->make();
	}
}

class aeDatabaseTransaction
/*
	Used by `aeDatabase::transaction()`.
*/
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
/*
	Used by `aeDatabase::many()` and `aeDatabase::result()`.
*/
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
		Fetches the next row as an associate array or instance of 
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
			
			$object->attach($related, $table['alias']);
		}
		
		return $object;
	}
	
	public function count()
	/*
		Returns the number of rows in the result set.
	*/
	{
		return $this->result->num_rows;
	}
	
	public function seek($offset)
	/*
		Sets the internal pointer to a particular offset.
	*/
	{
		return $this->result->data_seek($offset);
	}

	public function all()
	/*
		Returns an array of all rows.
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
/*
	A database table abstraction class that provides active record style
	access to data in a specifc table.
	
	Table specific class must extend this class:
	
		class MyTable extends aeDatabaseTable {}
	
	Now you can easily perform CRUD actions on "my_table" table:
	
		$row = MyTable::create(array('column' => 'value'))->save();
		
		$row_ids = $row->ids();
		
		$row_copy = MyTable::find($row_ids)->load();
		
		echo $row_copy->column; // echoes "value"
		
	NB! For this class to work, table must have a scalar or composite 
	primary key.
*/
{
	// ========================
	// = Entity configuration =
	// ========================
	
	private static $tables = array();

	protected static function database()
	/*
		Returns an instance of default database connection.
		
		Override this method, if you want your class to use a 
		different connection.
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
		
		The default implementation derives it from the class name, e.g.:
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
	private $data = array();
	private $values = array();
	private $transient = array();
	private $related = array();
	
	public function __construct($values = null, $_raw_data = false)
	/*
		NB! Must not be used directly. 
		
		Use `aeDatabaseTable::create()` method instead.
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
			$this->values($values, $_raw_data);
		}
	}
	
	public function __clone()
	/*
		Clones related entity objects as well.
	*/
	{
		foreach ($this->related as &$related)
		{
			$related = clone $related;
		}
	}
	
	public function attach($object, $property)
	/*
		Attaches an instance of (usually) related entity.
	*/
	{
		if (!is_a($object, 'aeDatabaseTable'))
		{
			trigger_error('Cannot attach an instance of "' . get_class($object) . '" class.', E_USER_ERROR);
		}
		
		$this->related[$property] = $object;
		
		return $this;
	}
	
	// =======================
	// = Setters and getters =
	// =======================
	
	public function values($values = null, $_raw_data = false)
	/*
		Sets supplied $values or returns current values.
		
		If second argument is TRUE, $values are unserialized and set as data.
	*/
	{
		if (is_array($values))
		{
			if ($_raw_data === true)
			{
				$this->data = static::unserialize($values);
			}
			else foreach ($values as $key => $value)
			{
				$this->$key = $value;
			}
		}
		else if (is_null($values))
		{
			return array_merge($this->transient, $this->data, $this->values, $this->ids);
		}
	}
	
	public function __set($name, $value)
	{
		$ref =& $this->_value_reference($name, true);
		
		$ref[$name] = $value;
	}
	
	public function __get($name)
	{
		if (isset($this->related[$name]))
		{
			return $this->related[$name];
		}
		
		$ref =& $this->_value_reference($name, false);
		
		if (isset($ref[$name]))
		{
			return $ref[$name];
		}
	}
	
	public function __isset($name)
	{
		$ref =& $this->_value_reference($name, false);
		
		return isset($ref[$name]);
	}
	
	public function __unset($name)
	{
		$ref =& $this->_value_reference($name, true);
		
		unset($ref[$name]);
	}
	
	protected function &_value_reference($name, $mutate)
	{
		if (in_array($name, static::accessor()))
		{
			return $this->ids;
		}
		else if (in_array($name, static::columns()))
		{
			if ($mutate === true || isset($this->values[$name]))
			{
				return $this->values;
			}
			else
			{
				return $this->data;
			}
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
		
		return new $class($values);
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
		Returns an associative array of primary key(s)/value(s).
	*/
	{
		$accessor = static::accessor();
		
		if (count($accessor) !== count($this->ids))
		{
			throw new aeDatabaseException(get_class($this) 
				. '::ids() failed, because accessor value' 
				. (count($accessor) > 1 ? 's are' : ' is') 
				. ' not defined.');
		}
		
		return $this->ids;
	}
	
	public function load($columns = '*')
	/*
		(Re)loads the record values from database.
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
		
		$this->reset();
		$this->values($values, true);
		
		return $this;
	}
	
	public function save()
	/*
		Saves or updates the record in database.
	*/
	{
		if (empty($this->values))
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
		
		$this->data = array_merge($this->data, $this->values);
		$this->values = array();

		return $this;
	}
	
	public function reset($accessor = false)
	/*
		Resets the record data and (optionally) the accessor.
	*/
	{
		if ($accessor === true)
		{
			$this->ids = array();
		}
		
		$this->values = array();
		$this->transient = array();
		$this->related = array();
		
		return $this;
	}
	
	public function delete()
	/*
		Deletes the record from database.
	*/
	{
		static::database()->delete(static::name(), $this->ids());
		
		return $this->reset(true);
	}
}

class aeDatabaseException extends Exception {}
