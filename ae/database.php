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
		
		if (!empty($this->db->connect_error))
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
	
	public function query($sql)
	{
		# code...
	}
	
	public function result()
	/*
		Runs current query and returns the result set.
	*/
	{
		# code...
	}
	
	public function run()
	/*
		Runs current query.
		
		Returns number of affected rows.
	*/
	{
		return $this->db->affected_rows;
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
				$value = serialize($value); // and then escape as string
			case 'object':
				$value = (string) $value; // implicitly cast to string
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
	public function aliases($vars)
	{
		# code...
	}
	
	public function variables($vars)
	{
		# code...
	}
	
	public function values($vars)
	{
		# code...
	}
	
	/*
		Entity binding
	*/
	public function one($class)
	{
		# code...
	}
	
	public function many($class)
	{
		# code...
	}
	
	public function using($class, $alias = null)
	{
		# code...
	}
	
	/*
		Helper queries
	*/
	public function insert($table, $values)
	{
		return $this->query("INSERT INTO {table} ({keys}) VALUES ({values})")
			->aliases(array(
				'table' => static::table()
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
			$insert_keys = $this->protect($key);
			$insert_values = $this->escape($value);
		}
		
		$insert_keys = implode(', ', $insert_keys);
		$insert_values = implode(', ', $insert_values);
		
		return $this->query("INSERT INTO {table} ({keys}, $insert_keys) 
				VALUES ({values}, $insert_values) 
				ON DUPLICATE KEY UPDATE {keys_values}")
			->aliases(array(
				'table' => static::table()
			))
			->values(static::serialize($this->values))
			->run();
	}
	
	public function update($table, $values, $where)
	{
		$where = $this->_where($where);
		
		return $this->query("UPDATE {table} SET {keys_values} WHERE $where")
			->aliases(array(
				'table' => static::table()
			))
			->values($values)
			->run();
	}
	
	public function delete($table, $where)
	{
		$where = $this->_where($where);
		
		return $db->query("DELETE FROM {table} WHERE $where")
			->aliases(array(
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
				$_where[] = $db->protect($key) . ' = ' . $db->escape($value);
			}

			return implode(' AND ', $_where);
		}
		else
		{
			return $where;
		}
	}
}

abstract class aeDatabaseEntity
{
	// ==================
	// = Static methods =
	// ==================
	
	protected static $table;
	protected static $columns;
	protected static $accessor; // array(`id`) or array(`foo_id`, `bar_id`) or NULL
	
	protected static $database = 'default';
	protected static $connection;
	
	public static function create($values = null)
	/*
		Creates a new instance.
		
		Using this factory method is preferable, as it allows
		the entity implementation class to override this behaviour.
	*/
	{
		$class = get_called_class();
		
		return new $class($values);
	}
	
	protected static function database()
	{
		if (empty(static::$connection))
		{
			static::$connection = ae::database(static::$database);
		}
		
		return static::$connection;
	}
	
	protected static function table()
	{
		if (empty(static::$table))
		{
			trigger_error(get_called_class() . '::$table is empty!', E_USER_ERROR);
		}
		
		return static::$table;
	}
	
	protected static function accessor()
	{
		if (empty(static::$accessor))
		{
			// TODO: Implement accessor detection using database connection
		}
		
		if (!is_array(static::$accessor))
		{
			static::$accessor = array(static::$accessor);
		}
		
		return static::$accessor;
	}
	
	protected static function columns()
	{
		if (empty(static::$columns))
		{
			// TODO: Implement column detection using database connection
		}
		
		return static::$columns;
	}
	
	public static function serialize($object)
	{
		return $object;
	}
	
	public static function unserialize($record)
	{
		return $record;
	}
	
	// ====================
	// = Instance methods =
	// ====================
	
	private $ids = array();
	private $values = array();
	private $is_dirty = false;
	
	public function __construct($values = null)
	{
		$accessor = static::accessor();
		
		if (empty($accessor))
		{
			trigger_error(get_class($this) . ' has no accessor.', E_USER_WARNING);
		}
		
		if (is_array($values))
		{
			$this->set($values);
		}
	}
	
	public function set($values)
	{
		$values = array_intersect_key($values, static::accessor(), static::columns());
		
		foreach ($values as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
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
			trigger_error('Uknown property ' . get_class($this) . '::' . $name . '.', E_USER_ERROR);
		}
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
				static::table(),
				static::serialize($this->values)
			);
		}
		else if (count($accessor) === count($this->ids))
		{
			$db->insert_or_update(
				static::table(), 
				static::serialize($this->values), 
				$this->ids
			);
		}
		else
		{
			trigger_error(get_class($this) . '::save() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
	}
	
	public function delete()
	{
		$db = static::database();
		$accessor = static::accessor();
		
		if (count($accessor) === count($this->ids))
		{
			$db->delete(static::table(), $this->ids);
		}
		else
		{
			trigger_error(get_class($this) . '::delete() failed, because accessor value' .
				(count($accessor) > 1 ? 's are' : ' is') . ' not defined.', E_USER_ERROR);
		}
	}
	
}