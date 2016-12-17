<?php

function orcombine($code1,$code2) { //Performs a bitwise OR operation on the two captchalogue codes given and returns the new code.
  $binary1 = breakdown($code1);
  $binary2 = breakdown($code2);
  $i = 0;
  $binaryresult = "";
  while ($i < 48) {
    if (substr($binary1,$i,1) == "1" || substr($binary2,$i,1) == "1") { //Perform bitwise OR
      $binaryresult = $binaryresult . "1";
    } else {
      $binaryresult = $binaryresult . "0";
    }
    $i++;
  }
  $coderesult = reassemble($binaryresult);
  return $coderesult;
}
function andcombine($code1,$code2) { //Performs a bitwise AND operation on the two captchalogue codes given and returns the new code.
  $binary1 = breakdown($code1);
  $binary2 = breakdown($code2);
  $i = 0;
  $binaryresult = "";
  while ($i < 48) {
    if (substr($binary1,$i,1) == "1" && substr($binary2,$i,1) == "1") { //Perform bitwise AND
      $binaryresult = $binaryresult . "1";
    } else {
      $binaryresult = $binaryresult . "0";
    }
    $i++;
  }
  $coderesult = reassemble($binaryresult);
  return $coderesult;
}
function breakdown($code) { //Breaks the given code down into the appropriate binary string and returns it. Assumes input of length 8 string.
  $binarystr = "";
  $i = 0;
  while($i < 8) {
    $binarystr = $binarystr . binary(substr($code,$i,1)); //Add the ith character to the binary.
    $i++; //Increment i
  }
  return $binarystr;
}
function reassemble($binarystr) { //Reassembles the given binary string into a captchalogue code. Assumes input of length 48 binary string.
  $code = "";
  $i = 0;
  while($i < 8) {
    $code = $code . character(substr($binarystr,($i * 6),6)); //Add the ith collection of 6 bits to the code.
    $i++; //Increment i
  }
  return $code;
}
function binary($char) { //Maps a character to the relevant binary string. Only works if given a string of length 1 containing the correct character.
  if ($char == "0") return "000000";
  if ($char == "1") return "000001";
  if ($char == "2") return "000010";
  if ($char == "3") return "000011";
  if ($char == "4") return "000100";
  if ($char == "5") return "000101";
  if ($char == "6") return "000110";
  if ($char == "7") return "000111";
  if ($char == "8") return "001000";
  if ($char == "9") return "001001";
  if ($char == "A") return "001010";
  if ($char == "B") return "001011";
  if ($char == "C") return "001100";
  if ($char == "D") return "001101";
  if ($char == "E") return "001110";
  if ($char == "F") return "001111";
  if ($char == "G") return "010000";
  if ($char == "H") return "010001";
  if ($char == "I") return "010010";
  if ($char == "J") return "010011";
  if ($char == "K") return "010100";
  if ($char == "L") return "010101";
  if ($char == "M") return "010110";
  if ($char == "N") return "010111";
  if ($char == "O") return "011000";
  if ($char == "P") return "011001";
  if ($char == "Q") return "011010";
  if ($char == "R") return "011011";
  if ($char == "S") return "011100";
  if ($char == "T") return "011101";
  if ($char == "U") return "011110";
  if ($char == "V") return "011111";
  if ($char == "W") return "100000";
  if ($char == "X") return "100001";
  if ($char == "Y") return "100010";
  if ($char == "Z") return "100011";
  if ($char == "a") return "100100";
  if ($char == "b") return "100101";
  if ($char == "c") return "100110";
  if ($char == "d") return "100111";
  if ($char == "e") return "101000";
  if ($char == "f") return "101001";
  if ($char == "g") return "101010";
  if ($char == "h") return "101011";
  if ($char == "i") return "101100";
  if ($char == "j") return "101101";
  if ($char == "k") return "101110";
  if ($char == "l") return "101111";
  if ($char == "m") return "110000";
  if ($char == "n") return "110001";
  if ($char == "o") return "110010";
  if ($char == "p") return "110011";
  if ($char == "q") return "110100";
  if ($char == "r") return "110101";
  if ($char == "s") return "110110";
  if ($char == "t") return "110111";
  if ($char == "u") return "111000";
  if ($char == "v") return "111001";
  if ($char == "w") return "111010";
  if ($char == "x") return "111011";
  if ($char == "y") return "111100";
  if ($char == "z") return "111101";
  if ($char == "?") return "111110";
  if ($char == "!") return "111111";
  return "000000"; //If character doesn't match, treat it like a zero.
}
function character($str) { //Turns a string of six binary digits into the relevant character.
  if ($str == "000000") return "0";
  if ($str == "000001") return "1";
  if ($str == "000010") return "2";
  if ($str == "000011") return "3";
  if ($str == "000100") return "4";
  if ($str == "000101") return "5";
  if ($str == "000110") return "6";
  if ($str == "000111") return "7";
  if ($str == "001000") return "8";
  if ($str == "001001") return "9";
  if ($str == "001010") return "A";
  if ($str == "001011") return "B";
  if ($str == "001100") return "C";
  if ($str == "001101") return "D";
  if ($str == "001110") return "E";
  if ($str == "001111") return "F";
  if ($str == "010000") return "G";
  if ($str == "010001") return "H";
  if ($str == "010010") return "I";
  if ($str == "010011") return "J";
  if ($str == "010100") return "K";
  if ($str == "010101") return "L";
  if ($str == "010110") return "M";
  if ($str == "010111") return "N";
  if ($str == "011000") return "O";
  if ($str == "011001") return "P";
  if ($str == "011010") return "Q";
  if ($str == "011011") return "R";
  if ($str == "011100") return "S";
  if ($str == "011101") return "T";
  if ($str == "011110") return "U";
  if ($str == "011111") return "V";
  if ($str == "100000") return "W";
  if ($str == "100001") return "X";
  if ($str == "100010") return "Y";
  if ($str == "100011") return "Z";
  if ($str == "100100") return "a";
  if ($str == "100101") return "b";
  if ($str == "100110") return "c";
  if ($str == "100111") return "d";
  if ($str == "101000") return "e";
  if ($str == "101001") return "f";
  if ($str == "101010") return "g";
  if ($str == "101011") return "h";
  if ($str == "101100") return "i";
  if ($str == "101101") return "j";
  if ($str == "101110") return "k";
  if ($str == "101111") return "l";
  if ($str == "110000") return "m";
  if ($str == "110001") return "n";
  if ($str == "110010") return "o";
  if ($str == "110011") return "p";
  if ($str == "110100") return "q";
  if ($str == "110101") return "r";
  if ($str == "110110") return "s";
  if ($str == "110111") return "t";
  if ($str == "111000") return "u";
  if ($str == "111001") return "v";
  if ($str == "111010") return "w";
  if ($str == "111011") return "x";
  if ($str == "111100") return "y";
  if ($str == "111101") return "z";
  if ($str == "111110") return "?";
  if ($str == "111111") return "!";
  return "0"; //Treat malformed strings as zeroes.
}
?>