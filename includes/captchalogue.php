<?php

// Code provides several convenience methods for captchalogue
// code operations.
class Code {
	public $digits = array(0, 0, 0, 0, 0, 0, 0, 0);

	// initializes a code with the given digits
	function __construct($digits) {
		$this->digits = $digits;
	}
	
	// parses a code string
	public static function from_string($str) {
		$digits = array(0, 0, 0, 0, 0, 0, 0, 0);
		foreach(str_split($str) as $i => $c) {
			$digits[$i] = Code::char_to_digit($c);
		}
		return new Code($digits);
	}

	// returns the string representation of the code
	public function to_string() {
		$str = "00000000";
		foreach($this->digits as $i => $d) {
			$str[$i] = Code::digit_to_char($d);
		}
		return $str;
	}
	
	// bitwise or operation
	public function or($code_b) {
		$result = array(0, 0, 0, 0, 0, 0, 0, 0);
		foreach($this->digits as $i => $d) {
			$result[$i] = $d | $code_b->digits[$i];
		}
		return new Code($result);
	}
	
	// bitwise and operation
	public function and($code_b) {
		$result = array(0, 0, 0, 0, 0, 0, 0, 0);
		foreach($this->digits as $i => $d) {
			$result[$i] = $d & $code_b->digits[$i];
		}
		return new Code($result);
	}
	
	// bitwise xor operation
	public function xor($code_b) {
		$result = array(0, 0, 0, 0, 0, 0, 0, 0);
		foreach($this->digits as $i => $d) {
			$result[$i] = $d ^ $code_b->digits[$i];
		}
		return new Code($result);
	}
	
	// helper functions
	public function char_to_digit($c) {
		$c = ord($c);
		if ($c >= ord('0') && $c <= ord('9')) {
			return $c - ord('0');
		} elseif ($c >= ord('A') && $c <= ord('Z')) {
			return $c - ord('A') + 10;
		} elseif ($c >= ord('a') && $c <= ord('z')) {
			return $c - ord('a') + 36;
		} elseif ($c == ord('?')) {
			return 62;
		} elseif ($c == ord('!')) {
			return 63;
		}
		return 0;
	}
	
	public function digit_to_char($d) {
		if ($d >= 0 && $d <= 9) {
			return chr($d + ord('0'));
		} elseif ($d >= 10 && $d <= 35) {
			return chr($d - 10 + ord('A'));
		} elseif ($d >= 36 && $d <= 61) {
			return chr($d - 36 + ord('a'));
		} elseif ($d == 62) {
			return '?';
		} elseif ($d == 63) {
			return '!';
		}
		return '_';
	}
}

?>
