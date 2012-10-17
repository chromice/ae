<?php if (!class_exists('ae')) exit;

#
# Copyright 2012 Anton Muraviev <chromice@gmail.com>
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

// TODO: Documentation is missing for a lot of methods.
// FIXME: insert_or_update() method's $where clause is different from the other functions.

ae::invoke(array('aeDatabase', 'connection'), ae::factory);

class aeDatabase
/*
	A simple MySQL database abstraction layer.
	
	`database.[connection name]` options:
		`class`		-	connection class: 'aeDatabase' by default;
		`host`		-	connection host;
		`port`		-	connection port;
		`socket`	-	connection socket;
		`user`		-	user name;
		`password`	-	password;
		`database`	-	database name.
*/
{
	/*
		Connection
	*/
	
	protected $db;
	
	public static function connection($database = null)
	{
		if (is_null($database))
		{
			$database = 'default';
		}
		
		$params = ae::options('database.' . $database, false);
		
		if ($params === false)
		{
			throw new aeDatabaseException('Unknown database connection: ' . $database);
		}
		
		$class = $params->get('class', get_called_class());
		
		return new $class(
			$params->get('host'),
			$params->get('user'),
			$params->get('password'),
			$params->get('database'),
			$params->get('port'),
			$params->get('socket')
		);
	}
	
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
	}
	
	public function __destruct()
	{
		$this->db->close();
	}
	
	/*
		Query
	*/
	
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
	{
		$this->query = $query;
		
		return $this;
	}
	
	public function join($join, $type = '')
	/*
		Sets the `{sql:join}` part.
		
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
		Sets the `{sql:where}` part.
		
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
		Sets the `{sql:group_by}` part. 
		
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
		Sets the `{sql:having}` part. 
		
		See where() for examples.
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
		Sets the `{sql:order_by}` part. 
		
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
		Sets the `{sql:limit}` part. 
		
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
				$_where[] = $this->protect($_key) . ' = ' . $this->escape($_value);
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
		Returns a query ready to execute and resets the state.
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
			$placeholders = array_map(array($this, 'protect'), $this->names);
		}
		
		if (!empty($this->variables))
		{
			$placeholders = array_merge($placeholders, array_map(array($this, 'escape'), $this->variables));
		}
		
		if (!empty($this->values))
		{
			$keys = array();
			$values = array();
			$keys_values = array();
			
			foreach ($this->values as $key => $value)
			{
				$keys[] = $key = $this->protect($key);
				$values[] = $value = $this->escape($value);
				$keys_values[] = $key . ' = ' . $value;
			}
			
			$placeholders['keys'] = implode(', ', $keys);
			$placeholders['values'] = implode(', ', $values);
			$placeholders['keys_values'] = implode(', ', $keys_values);
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
	
	public function protect($name)
	/*
		Protects an alias, table or column name with backticks.
	*/
	{
		return '`' . str_replace('`', '``', $name) . '`';
	}
	
	public function escape($value)
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
	
	/*
		Placeholders
	*/
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
	
	/*
		Result
	*/
	
	public function make()
	/*
		Runs current query and returns number of affected rows 
		or FALSE if query returned an error.
	*/
	{
		$this->result();
		
		return $this->db->affected_rows;
	}
	
	public function insert_id()
	/*
		Returns the auto generated id used in the last query or zero.
	*/
	{
		return $this->db->insert_id;
	}
	
	public function result($result = 'aeDatabaseResult')
	/*
		Runs current query and returns the result set.
	*/
	{
		return $this->_result(null, $result, null);
	}
	
	protected function _result($class = null, $result = 'aeDatabaseResult', $related = null)
	{
		$query = $this->_query();
		
		$return = $this->db->query($query, MYSQLI_STORE_RESULT);
		
		if ($return === false)
		{
			throw new aeDatabaseException($this->db->error);
		}
		
		return new $result($return, $class, $related);
	}
	
	/*
		Class binding
	*/
	
	protected $using = array();
	
	public function one($class)
	{
		$result = $this->many($class);
		
		if ($result->count() > 0)
		{
			return $result->fetch();
		}
		
		return null;
	}
	
	public function many($class, $result = 'aeDatabaseResult')
	{
		$return = $this->_result($class, $result, $this->using);
		
		$this->using = array();
		
		return $return;
	}
	
	public function using($class, $alias = null)
	{
		$this->using[$class] = $alias;
		
		return $this;
	}
	
	/*
		Helper queries
	*/
	
	public function columns($table)
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
	
	public function count($table, $where = null)
	{
		$result = $this->query("SELECT COUNT(*) AS `found` FROM {table}" . 
				(!is_null($where) ? ' WHERE ' . $this->_where($where) : '')
			)
			->names(array(
				'table' => $table
			))
			->result();
		
		$result = $result->fetch();
		
		return $result['found'];
	}
	
	public function find($table, $where)
	{
		$where = $this->_where($where);
		
		$result = $this->query("SELECT * FROM {table} WHERE $where")
			->names(array(
				'table' => $table
			))
			->result();
			
		$found = $result->fetch();
		
		return $found;
	}
	
	public function insert($table, $values)
	{
		return $this->query("INSERT INTO {table} ({keys}) VALUES ({values})")
			->names(array(
				'table' => $table
			))
			->values($values)
			->make() > 0 ? $this->db->insert_id : null;
	}
	
	public function insert_or_update($table, $values, $where)
	{
		$insert_keys = array();
		$insert_values = array();
		
		foreach ($where as $key => $value)
		{
			$insert_keys[] = $this->protect($key);
			$insert_values[] = $this->escape($value);
		}
		
		$insert_keys = implode(', ', $insert_keys);
		$insert_values = implode(', ', $insert_values);
		
		return $this->query("INSERT INTO {table} ({keys}, $insert_keys) 
				VALUES ({values}, $insert_values) 
				ON DUPLICATE KEY UPDATE {keys_values}")
			->names(array(
				'table' => $table
			))
			->values($values)
			->make();
	}
	
	public function update($table, $values, $where)
	{
		$where = $this->_where($where);
		
		return $this->query("UPDATE {table} SET {keys_values} WHERE $where")
			->names(array(
				'table' => $table
			))
			->values($values)
			->make();
	}
	
	public function delete($table, $where)
	{
		$where = $this->_where($where);
		
		return $this->query("DELETE FROM {table} WHERE $where")
			->names(array(
				'table' => $table
			))
			->make();
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
		if (is_object($this->result))
		{
			$this->result->free();
		}
	}
	
	public function fetch()
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
		
		$values = $class::unserialize(array_combine(
			$this->columns, 
			array_intersect_key($row, $this->columns)
		));
		
		$object = $class::create($values, false);
		
		foreach ($this->related as $name => $table)
		{
			$class = $table['class'];
			
			$values = $class::unserialize(array_combine(
				$table['columns'], 
				array_intersect_key($row, $table['columns'])
			));
			
			$related = $class::create($values, false);
			
			$object->_attach($table['alias'], $related);
		}
		
		return $object;
	}
	
	public function count()
	{
		if (is_object($this->result))
		{
			return $this->result->num_rows;
		}
	}
	
	public function seek($offset)
	{
		if (is_object($this->result))
		{
			return $this->result->data_seek($offset);
		}
	}

	public function all()
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
	/*
		Entity configuration
	*/
	
	private static $tables = array();
	
	public static function name()
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
	
	protected static function database()
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['database']))
		{
			self::$tables[$class]['database'] = ae::database('default');
		}
		
		return self::$tables[$class]['database'];
	}
	
	protected static function accessor()
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['accessor']))
		{
			static::_load_columns();
		}

		return self::$tables[$class]['accessor'];
	}
	
	protected static function columns()
	{
		$class = get_called_class();
		
		if (!isset(self::$tables[$class]['columns']))
		{
			static::_load_columns();
		}
		
		return self::$tables[$class]['columns'];
	}
	
	protected static function _load_columns()
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
	
	/*
		Data serialization
	*/
	
	public static function serialize($values)
	{
		return $values;
	}
	
	public static function unserialize($record)
	{
		return $record;
	}
	
	/*
		Entity creation
	*/
	
	private $ids = array();
	private $values = array();
	private $transient = array();
	private $related = array();
	
	private $is_dirty = false;
	
	public function __construct($values = null)
	{
		$table = static::name();
		$accessor = static::accessor();
		
		if (empty($accessor))
		{
			trigger_error('Table "' . $table . '" has no accessor.', E_USER_ERROR);
		}
		
		if (is_array($values)) foreach ($values as $key => $value)
		{
			$this->$key = $value;
		}
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
	
	/*
		Setters and getters
	*/
	
	public function __set($name, $value)
	{
		$ref =& $this->_values_or_ids($name);
		
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
		
		$ref =& $this->_values_or_ids($name);
		
		if (isset($ref[$name]))
		{
			return $ref[$name];
		}
	}
	
	public function __isset($name)
	{
		$ref =& $this->_values_or_ids($name);
		
		return isset($ref[$name]);
	}
	
	public function __unset($name)
	{
		$ref =& $this->_values_or_ids($name);
		
		unset($ref[$name]);
	}
	
	protected function &_values_or_ids($name)
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
	
	/*
		Basic CRUD methods.
	*/
	
	public static function create($values = null, $_dirty = true)
	/*
		Creates a new instance.
		
		Using this factory method is preferable to `new` operator.
	*/
	{
		$class = get_called_class();
		
		$entity = new $class($values);
		
		$entity->is_dirty = (bool) $_dirty;
		
		return $entity;
	}
	
	public static function find($ids)
	/*
		Creates an instance pointing to a record. 
		
		Useful to save() or delete() records without loading the data. 
		You should use load() to load the data.
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
			trigger_error(get_called_class() . '::find() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
			
		}
		
		return static::create($ids);
	}
	
	public function load()
	/*
		(Re)load the current record values from the database.
	*/
	{
		$db = static::database();
		$accessor = static::accessor();
		
		if (count($accessor) !== count($this->ids))
		{
			trigger_error(get_class($this) . '::load() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
		
		$values = $db->find(static::name(), $this->ids);
		
		if (!is_array($values))
		{
			trigger_error(get_class($this) . '::load() failed, because accessor points ' .
				'to nothing.', E_USER_ERROR);
		}
		
		foreach ($values as $key => $value)
		{
			$this->$key = $value;
		}
		
		$this->is_dirty = false;
		
		return $this;
	}
	
	public function save()
	/*
		Intelegently saves or updates the record in the database.
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
		else if (count($accessor) === count($this->ids))
		{
			$db->insert_or_update(
				static::name(), 
				static::serialize($this->values), 
				$this->ids
			);
		}
		else
		{
			trigger_error(get_class($this) . '::save() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
		
		return $this;
	}
	
	public function delete()
	/*
		Deletes the current record from the database.
	*/
	{
		$db = static::database();
		$accessor = static::accessor();
		
		if (count($accessor) === count($this->ids))
		{
			$db->delete(static::name(), $this->ids);
			
			$this->ids = array();
			$this->values = array();
			$this->transient = array();
		}
		else
		{
			trigger_error(get_class($this) . '::delete() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
		
		return $this;
	}
}

class aeDatabaseException extends Exception {}