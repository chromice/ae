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

ae::invoke(array('aeDatabase', 'connection'), ae::factory);

class aeDatabase
{
	// ==============
	// = Connection =
	// ==============
	
	protected $db;
	
	public static function connection($database = null)
	{
		if (is_null($database))
		{
			$database = 'default';
		}
		
		$params = ae::options('database.' . $database);
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
			$port = (int)$port;
		}

		$this->db = new MySQLi($host, $user, $password, $database, $port, $socket);
		
		if ($this->db->connect_error)
		{
			throw new ErrorException($this->db->connect_error, $this->db->connect_errno, E_USER_ERROR);
		}
	}
	
	public function __destruct()
	{
		$this->db->close();
	}
	
	
	// =========
	// = Query =
	// =========
	
	protected $query;
	
	protected $names = array();
	protected $variables = array();
	protected $values = array();
	
	// FIXME: Writing queries takes ages... We need some shorthands, e.g. select(), insert(), etc. 
	public function query($query)
	{
		$this->query = $query;
		
		return $this;
	}
	
	protected function _query()
	/*
		Returns a ready to execute query and resets the state.
	*/
	{
		if (empty($this->query))
		{
			trigger_error('Cannot execute an empty SQL query!', E_USER_ERROR);
		}
		
		$query = $this->query;
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
		$this->names = array();
		$this->variables = array();
		$this->values = array();
		
		return $query;
	}
	
	public function protect($value)
	/*
		Protects an alias, table or column name.
	*/
	{
		return '`' . str_replace('`', '``', $value) . '`';
	}
	
	public function escape($value)
	/*
		Returns correctly escaped value.
		
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
	
	public function result($result = 'aeDatabaseResult')
	/*
		Runs current query and returns the result set.
	*/
	{
		return $this->_result(null, $result, null);
	}
	
	public function run()
	/*
		Runs current query and returns number of affected rows 
		or FALSE if query returned an error.
	*/
	{
		$this->result();
		
		return $this->db->affected_rows;
	}
	
	protected function _result($class = null, $result = 'aeDatabaseResult', $related = null)
	{
		$query = $this->_query();
		
		$return = $this->db->query($query, MYSQLI_STORE_RESULT);
		
		if ($return === false)
		{
			throw new ErrorException($this->db->error, 0, E_USER_ERROR);
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
		
		if ($result->length() > 0)
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
	
	public function exists($table, $where)
	{
		$where = $this->_where($where);
		
		$result = $this->query("SELECT COUNT(*) AS `found` FROM {table} WHERE $where")
			->names(array(
				'table' => $table
			))
			->result();
		
		$found = $result->fetch();
		
		return $found['found'] > 0;
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
			->run() > 0 ? $this->db->insert_id : null;
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
			->run();
	}
	
	public function update($table, $values, $where)
	{
		$where = $this->_where($where);
		
		return $this->query("UPDATE {table} SET {keys_values} WHERE $where")
			->names(array(
				'table' => $table
			))
			->values($values)
			->run();
	}
	
	public function delete($table, $where)
	{
		$where = $this->_where($where);
		
		return $this->query("DELETE FROM {table} WHERE $where")
			->names(array(
				'table' => $table
			))
			->run();
	}
	
	protected function _where($where)
	{
		if (is_array($where))
		{
			$_where = array();

			foreach ($where as $key => $value)
			{
				$_where[] = $this->protect($key) . ' = ' . $this->escape($value);
			}

			return implode(' AND ', $_where);
		}
		else
		{
			return $where;
		}
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
		
		$object = $class::create($values);
		
		foreach ($this->related as $name => $table)
		{
			$class = $table['class'];
			
			$values = $class::unserialize(array_combine(
				$table['columns'], 
				array_intersect_key($row, $table['columns'])
			));
			
			$related = $class::create($values);
			
			$object->attach($table['alias'], $related);
		}
		
		return $object;
	}
	
	public function length()
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
		
		if ($this->length() > 0)
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
	// ==================
	// = Static methods =
	// ==================
	
	private static $tables = array();

	public static function create($values = null)
	/*
		Creates a new instance.
		
		Using this factory method is preferable to new operator.
	*/
	{
		$class = get_called_class();
		
		return new $class($values);
	}
	
	public static function find($ids)
	{
		$accessor = static::accessor();
		
		if (count($accessor) !== count($ids))
		{
			trigger_error(get_called_class() . '::find() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
			
		}
		
		$object = static::create();
		
		$object->set($ids, true, false);
		
		return $object;
	}
	
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
		static $database;
		
		if (empty($database))
		{
			$database = ae::database('default');
		}
		
		return $database;
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
		Install/uninstall
	*/

	public static function install()
	{
		// CREATE TABLE IF NOT EXISTS {table} ( ... )
	}

	public static function uninstall()
	{
		// DROP TABLE IF EXISTS {table};
	}

	// ====================
	// = Instance methods =
	// ====================
	
	private $ids = array();
	private $values = array();
	private $transient = array();
	private $related = array();
	
	// FIXME: Records are always dirty
	private $is_dirty = false;
	
	public function __construct($values = null)
	{
		$table = static::name();
		$accessor = static::accessor();
		$columns = static::columns();
		
		if (empty($accessor))
		{
			trigger_error('Table "' . $table . '" has no accessor.', E_USER_ERROR);
		}
		
		if (empty($columns))
		{
			trigger_error('Table "' .$table . '" has no columns.', E_USER_ERROR);
		}
		
		if (is_array($values))
		{
			$this->set($values);
		}
	}
	
	public function set($values, $accessor = true, $columns = true)
	{
		if ($accessor && $columns)
		{
			// FIXME: All transient properties are lost
			$values = array_intersect_key($values, 
				array_merge(
					array_flip(static::accessor()), array_flip(static::columns())
				)
			);
		}
		else if ($accessor)
		{
			$values = array_intersect_key($values,
				array_flip(static::accessor())
			);
		}
		else if ($columns)
		{
			$values = array_intersect_key($values, 
				array_flip(static::columns())
			);
		}
		
		foreach ($values as $key => $value)
		{
			$this->$key = $value;
		}
		
		return $this;
	}
	
	public function attach($related, $object)
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
	public function load()
	{
		$db = static::database();
		$accessor = static::accessor();
		
		if (count($accessor) !== count($this->ids))
		{
			trigger_error(get_class($this) . '::load() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
		
		$values = $db->find(static::name(), $this->ids);
		
		if (is_null($values))
		{
			trigger_error(get_class($this) . '::load() failed, because accessor points ' .
				'to nothing.', E_USER_ERROR);
		}
		
		$this->set($values, false, true);
		
		return $this;
	}
	
	public function save()
	/*
		Intelegently saves or updates records in the database.
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