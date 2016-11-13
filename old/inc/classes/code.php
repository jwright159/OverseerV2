<?php
/**
 * Overseer v2 PHP Class: Code
 *
 * File containing the Code class.
 *
 * PHP Version 7
 *
 * @category Overseer
 * @package  Overseer
 * @author   Difarem <difarem@gmail.com>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */

namespace Overseer;
use \PDO;

/**
 * Code handling class
 *
 * Class that simplifies operations with captchalogue codes.
 *
 * @category Overseer
 * @package  Overseer\Code
 * @author   Difarem <difarem@gmail.com>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class Code {
    public $_bin;

    function __construct($initcode="") {
        if ($initcode == "") $this->_bin = 0;
		else $this->set($initcode);
    }

    public function set($code) {
		$this->_bin = 0;

		for ($i = 0; $i < 8; $i++) {
			$this->_bin <<= 6;
			$this->_bin |= $this->_binary($code[$i]);
		}
    }

	public function string() {
		$str = "________";
		$bin = $this->_bin;

		for ($i = 0; $i < 8; $i++) {
			$str[$i] = $this->_character($bin & 63);
				$bin >>= 6;
		}

		return strrev($str);
	}

    public function combineOr($code2) {
		$result = new Code();
		$result->_bin = $this->_bin | $code2->_bin;
		return $result;
    }

    public function combineAnd($code2) {
		$result = new Code();
		$result->_bin = $this->_bin & $code2->_bin;
		return $result;
    }

	public function combineXor($code2) {
		$result = new Code();
		$result->_bin = $this->_bin ^ $code2->_bin;
		return $result;
	}

    public function _binary($char) {
        $c = ord($char);

        if ($c >= ord("0") && $c <= ord("9")) return $c - ord("0");
        if ($c >= ord("A") && $c <= ord("Z")) return $c - ord("A") + 10;
        if ($c >= ord("a") && $c <= ord("z")) return $c - ord("a") + 36;
        if ($char == "?") return 62;
        if ($char == "!") return 63;

		return 0;
    }

    public function _character($bin) {
        if ($bin >= 0 && $bin <= 9) return chr(ord("0") + $bin);
        if ($bin >= 10 && $bin <= 35) return chr(ord("A") + $bin - 10);
		if ($bin >= 36 && $bin <= 61) return chr(ord("a") + $bin - 36);
		if ($bin == 62) return "?";
		if ($bin == 63) return "!";

		return 0;
    }
}

?>
