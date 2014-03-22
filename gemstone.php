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
	private		$sync	= array();

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

	}

	public function mine()
	{
		
	}

	public function withdraw($id, $column = 'id')
	{
	}

	public function sync(stone $stone)
	{
		$this->sync[] = $stone;
	}

	public function deposit()
	{
		try 
		{
			$this->db->beginTransaction();
			
			list($prepare, $values) = $this->insertinto();
			$statement = $this->db->prepare($prepare);
			$statement->execute($values);

			foreach ($this->sync as $substone) 
			{
				list($prepare, $values) = $substone->insertinto();
				$statement = $this->db->prepare($prepare);
				$statement->execute($values);
			}

			$this->db->commit();
		} 
		catch (\PDOException $e) 
		{
			echo "<pre>"; var_dump($test); echo "</pre>";
			$this->db->rollBack();
			echo $e->getMessage();
		}
	}

	public function insertinto()
	{
		$attach = $this->attach;
		$pri = $this->pri;
		$auto = $this->auto;

		// Need to reverse this so that we can throw an error whenever we try to write a column that does not exist.
		$desc = array_reduce($this->desc, function ($output, $input)
		{
			$output[] = $input['Field'];
			return $output;
		}, array());

		// Master Exception
		$exception = new GemstoneException('Parent');


		// Overloading Database Detection
		$overloading = array_diff(array_keys($attach), $desc);
		if (count($overloading)) 
		{
			foreach ($overloading as $value) 
			{
				$exception->add(new GemstoneException("Unknown column {$value}"));
			}
		}

		if ($exception->toThrow()) 
		{
			throw $exception;
		}

		$map = array_map(function ($value) use ($attach)
		{
			// Skip as no value is attached
			if (array_key_exists($value['Field'], $attach)) 
			{
				if (preg_match('/set\((.*)\)/', $value['Type'], $match) && is_array($attach[ $value['Field'] ])) 
				{
					// Look for sets and format value to correct type
					$attach[ $value['Field'] ] = implode(',', $attach[ $value['Field'] ]);
				}
				elseif (is_array($attach[ $value['Field'] ])) 
				{
					// Encode arrays for storage
					$attach[ $value['Field'] ] = json_encode($attach[ $value['Field'] ]);
				}
				elseif (is_object($attach[ $value['Field'] ])) 
				{
					// Encode objects for storage
					$attach[ $value['Field'] ] = serialize($attach[ $value['Field'] ]);
				}
				return array($value['Field'], $attach[ $value['Field'] ]);
				// return $attach[ $value['Field'] ];
			}
			else
			{
				// throw new GemstoneException("Unknown column `{$value['Field']}` in `{$this->table}`");
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

		// Get Fields for insert
		$fields = array_reduce($map, function ($output, $input)
		{
			$output[] = $input[0];
			return $output;
		}, array());

		// Get Values for insert
		$values = array_reduce($map, function ($output, $input)
		{
			$output[] = $input[1];
			return $output;
		}, array());


		$prepare = 
				"INSERT INTO `{$this->table}` (`". 
					implode('`, `', $fields) 
				."`) VALUES (". 
					implode(', ', array_fill(0, count($fields), '?')) 
				.")";
		return array($prepare, $values);
	}
}

/**
* Database Exceptions
*/
class GemstoneException extends \PDOException
{
	private $children = array();

	public function __construct($message, $code = 0, \Exception $previous = null)
	{
		parent::__construct("Gemstone Error: ". $message, $code, $previous);
	}

	public function add(GemstoneException $exception)
	{
		$this->children[] = $exception;
	}

	public function toThrow()
	{
		return !empty($this->children);
	}
}

?>