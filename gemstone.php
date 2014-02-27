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

	static function find()
	{
		
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
		if (is_array($attr)) 
		{
			if (count($attr) == 1) 
			{
				$attr = reset($attr);
			}
			else
			{
				$attr = json_encode($attr);
			}
		}
		elseif (is_object($attr)) 
		{
			$attr = serialize($attr);
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
		echo "<pre>"; var_dump($this->attach); echo "</pre>";
		list($field, $values) = array_map(function ($value) use ($attach)
		{
			return array($value['Field'], $attach[$value['Field']]);
		}, $this->desc);
		echo "<pre>"; var_dump($field, $value); echo "</pre>";
	}	
}

?>