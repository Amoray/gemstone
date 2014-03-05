<?php

namespace gemstone;

class PDO extends \PDO {}

/**
* Gemstones
*/
class G
{
	
	static		$db = null;

	static function init($dsn, $username, $password)
	{
		self::$db = new PDO($dsn, $username, $password, array(
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		));
	}
	/**
	 * Mine will generate a stone that will be attached to a specific table
	 * @param  string $table
	 * @return gemstone\stone
	 */
	static function mine($table)
	{
		$stone = new stone(self::$db, $table);
		$stone->mine();
		return $stone;
	}

	/**
	 * Withdraw will generate a stone and populate it with information from a specific table
	 * @param  string $table
	 * @param  int $id
	 * @param  string $column
	 * @return gemstone\stone
	 */
	static function withdraw($table, $id, $column = 'id')
	{
		$stone = new stone(self::$db, $table);
		$stone->withdraw($id, $column);
		return $stone;
	}

	static function find($table, $statement, array $param = array())
	{
		// $stone = new stone(self::$db, $table);
		// $stone->find($statement, $param);
	}

	static function deposit(stone $stone)
	{
		return $stone->deposit();
	}
}

class_alias('gemstone\G', 'G');

/**
* stone
*/
class stone
{
	private		$db		= null;
	private 	$table	= null;
	private		$desc	= null;
	private		$pri	= null;
	private		$auto	= null;
	private 	$attach = array();

	public function __call($name, $attributes)
	{
		if (preg_match('/set_?(.*)/', $name, $match)) 
		{
			return $this->__set($match[1], $attributes);
		}
		else
		{
			return $this->__set($name, $attributes);
		}
	}

	public function __set($name, $attr)
	{
		// All __set $attr values are arrays, reduce single arrays to values
		// Makes ->var(1,2,3) functionally equivalent to ->var(array(1,2,3))
		if (is_array($attr) && count($attr) == 1) 
		{
			$attr = reset($attr);
		}
		$this->attach[$name] = $attr;
		return $this;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->attach)) {
			return $this->attach[$name];
		}
		return false;
	}

	public function __construct(PDO $db, $table)
	{
		$this->table = $table;
		$this->db = $db;
		$statement = $this->db->query("DESC `{$this->table}`");
		$this->desc = $statement->fetchAll();

		$this->pri = array_filter($this->desc, function ($value)
		{
			if (array_key_exists('Key', $value) && $value['Key'] == 'PRI') 
			{
				return true;
			}
			return false;
		});
		$this->pri = reset($this->pri);

		$this->auto = array_filter($this->desc, function ($value)
		{
			if (array_key_exists('Extra', $value) && $value['Extra'] == "auto_increment") 
			{
				return true;
			}
			return false;
		});
		$this->auto = reset($this->auto);

		echo "<pre>"; var_dump($this->pri, $this->auto); echo "</pre>";
	}

	public function mine()
	{
		
	}

	public function withdraw($id, $column = 'id')
	{
	}

	public function deposit()
	{
		$attach = $this->attach;
		$pri = $this->pri;
		$auto = $this->auto;
		echo "<pre>"; var_dump($this->attach); echo "</pre>";
		
		$map = array_map(function ($value) use ($attach)
		{
			// Skip as no value is attached
			if (array_key_exists($value['Field'], $attach)) 
			{
				if (preg_match('/set\((.*)\)/', $value['Field'], $match) && is_array($attach[ $value['Field'] ])) 
				{
					// Look for sets and format value to correct type
					$attach[ $value['Field'] ] == implode(',', $match[1]);
				}
				elseif (is_array($attach[ $value['Field'] ])) 
				{
					// Encode arrays for storage
					$attach[ $value['Field'] ] == json_encode($attach[ $value['Field'] ]);
				}
				elseif (is_object($attach[ $value['Field'] ])) 
				{
					// Encode objects for storage
					$attach[ $value['Field'] ] == serialize($attach[ $value['Field'] ]);
				}
				return array($value['Field'], $attach[ $value['Field'] ]);
			}
		}, $this->desc);

		$map = array_filter($map, function ($value) use ($pri, $auto)
		{
			// if $value is empty
			// if $value's first is empty and field is primary or auto_increment
			if (empty($value) || 
				(
					empty($value[0]) && 
					(
						$pri['Field'] == $value[0] || $auto['Field'] == $value[0]
					)
				)
			)
			{
				return false;
			}
			return true;
		});
		echo "<pre>"; var_dump($map); echo "</pre>";
	}	
}

?>