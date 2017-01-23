<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/captchalogue.php');

// Item defines a captchalogueable entity.
class Item {
	public $id;
	public $code = null;
	public $name = "", $description = "";
	
	// Item cache
	private static $cache = [];
	
	// Loads a certain item from the database
	public static function load($conn, $id, $useCache = true) {
		if ($useCache && isset(Item::$cache[$id])) {
			return Item::$cache[$id];
		}
		
		// escape the id, just to be sure
		$id = mysqli_real_escape_string($conn, $id);
		$result = mysqli_query($conn, "SELECT * FROM `Items` WHERE `ID` = '$id' LIMIT 1;");
		// TODO: error handling
		$row = mysqli_fetch_array($result);
		$item = Item::from_row($row);
		Item::$cache[$id] = $item;
		echo 'fetched '.$id;
		return $item;
	}
	
	public static function from_row($row) {
		$item = new Item;
		$item->id = $row['ID'];
		$item->name = $row['name'];
		$item->description = $row['description'];
		if (!is_null($row['code'])) {
			$item->code = Code::from_string($row['code']);
		}
		return $item;
	}
	
	public function insert($conn) {
		// TODO: check for errors
		if (is_null($this->code)) {
			$code_str = 'NULL';
		} else {
			$code_str = "'".$this->code->to_string()."'";
		}
		$name = mysqli_real_escape_string($conn, $this->name);
		$description = mysqli_real_escape_string($conn, $this->description);
		mysqli_query($conn, "INSERT INTO `Items` (`code`, `name`, `description`) VALUES ($code_str, '$name', '$description')");
		$this->id = mysqli_insert_id($conn);
	}
}

?>
