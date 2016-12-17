<?php
$invslots = 50; //Increase this if inventory size goes up.
$strifeslots = 16;
function addItem($item,$userrow,$incode = "00000000") { //Adds an item to a user's inventory. Returns true if successful, or false if the user's inventory is full.
  $invslots = 50; //Placed here so function can use it.
  $i=1;
  while ($i <= $invslots) {
    $invstr = "inv" . strval($i);
    //echo $invstr;
    if ($userrow[$invstr] == "") { //First empty inventory card
      $compuname = str_replace("'", "\\\\''", $item); //Add escape characters so we can find item correctly in database. Also those backslashes are retarded.
      $compuname = str_replace("\\\\\\", "\\\\", $compuname); //really hope this works
      $compuresult = mysql_query("SELECT `captchalogue_code`,`name`, `size`, `effects` FROM Captchalogue WHERE `Captchalogue`.`name` = '" . $compuname . "' LIMIT 1;");
      $compurow = mysql_fetch_array($compuresult);
      $item = str_replace("\\", "", $item); //Remove escape backslashes since inventory doesn't have 'em.
      if (itemSize($compurow['size']) <= itemSize($userrow['moduspower'])) {
      	if ($item == "Captchalogue Card") $item = "Captchalogue Card (CODE:$incode)";
      	if ($item == "Cruxite Dowel") $item = "Cruxite Dowel (CODE:$incode)";
      	if ($item == "Punch Card Shunt") {
      		if ($incode != "00000000") $item = "Punch Card Shunt (CODE:$incode)"; //shunts containing unpunched cards? the card will disappear when retrieved because lazy also who would do this and expect something to happen
      	}
      	mysql_query("UPDATE `Players` SET `" . $invstr . "` = '" . mysql_real_escape_string($item) . "' WHERE `Players`.`username` = '" . $userrow['username'] . "' LIMIT 1 ;");
      	$userrow[$invstr] = $item;
      	$athenresult = mysql_query("SELECT `atheneum` FROM Sessions WHERE `Sessions`.`name` = '" . $userrow['session_name'] . "' LIMIT 1;");
      	$athenrow = mysql_fetch_array($athenresult);
      	if (!strrpos($athenrow['atheneum'], $compurow['captchalogue_code']) && strpos($compurow['effects'], "OBSCURED|") === false) {
      		$newatheneum = $athenrow['atheneum'] . $compurow['captchalogue_code'] . "|";
      		mysql_query("UPDATE `Sessions` SET `atheneum` = '" . $newatheneum . "' WHERE `Sessions`.`name` = '" . $userrow['session_name'] . "' LIMIT 1 ;");
      	}
      	compuRefresh($userrow);
      	return $invstr;
      } else {
      	$j = 0;
      	while ($j < $invslots) {
      		$jnvstr = "inv" . strval($j);
      		$compuname = str_replace("'", "\\\\''", $userrow[$jnvstr]); //Add escape characters so we can find item correctly in database. Also those backslashes are retarded.
      		$compuname = str_replace("\\\\\\", "\\\\", $compuname); //really hope this works
      		$compuresult = mysql_query("SELECT `name`, `effects` FROM Captchalogue WHERE `Captchalogue`.`name` = '" . $compuname . "' LIMIT 1;");
      		while ($gostrow = mysql_fetch_array($compuresult)) {
      			$ghosters = specialArray($gostrow['effects'], "GHOSTER");
      			if ($ghosters[0] == "GHOSTER") {
      				echo "<br />This item is too big for you to captchalogue! Instead, you use your " . $gostrow['name'] . " to create a ghost image of it.<br />";
      				mysql_query("UPDATE `Players` SET `" . $invstr . "` = '" . mysql_real_escape_string($item . " (ghost image)") . "' WHERE `Players`.`username` = '" . $userrow['username'] . "' LIMIT 1 ;");
      				$athenresult = mysql_query("SELECT `atheneum` FROM Sessions WHERE `Sessions`.`name` = '" . $userrow['session_name'] . "' LIMIT 1;");
      				$athenrow = mysql_fetch_array($athenresult);
      				if (!strrpos($athenrow['atheneum'], $compurow['captchalogue_code']) && strpos($compurow['effects'], "OBSCURED|") === false) {
      					$newatheneum = $athenrow['atheneum'] . $compurow['captchalogue_code'] . "|";
      					mysql_query("UPDATE `Sessions` SET `atheneum` = '" . $newatheneum . "' WHERE `Sessions`.`name` = '" . $userrow['session_name'] . "' LIMIT 1 ;");
      				}
      				return "inv-1"; //we didn't actually obtain the item, so return failure
      			}
      		}
      		$j++;
      	}
      	echo "<br />This item is too big for you to captchalogue! You will need to find a way to upgrade your Fetch Modus first.<br />";
      	return "inv-1";
      }
    }
    $i++;
  }
  return "inv-1";
}

function addAbstratus($absstring,$userrow) {
	echo "WARNING: addAbstratus function is now defunct. Please use addSpecibus (includes/fieldparser.php) instead. If you're not a developer and you see this message, please submit a bug report immediately!<br />";
	$strifeslots = 16;
	//require_once("includes/SQLconnect.php");
	/*$result = mysql_query("SELECT * FROM Players WHERE `Players`.`username` = '" . $username . "'");
  while($row = mysql_fetch_array($result)) {
    if ($row['username'] == $username) {
      $userrow = $row;
    }
  }*/
  $i=1;
  if (strrpos($absstring, ",")) {
  	$mainabstratus = "";
    $alreadydone = False;
    $foundcomma = False;
    $j = 0;
    while ($foundcomma != True) {
			$char = "";
			$char = substr($absstring,$j,1);
			if ($char == ",") { //Found a comma. We know there is one because of the if statement above. Break off the string as the main abstratus.
	  		$mainabstratus = substr($absstring,0,$j);
	  		$foundcomma = True;
			} else {
	  		$j++;
			}
    }
    if ($alreadydone == False && $mainabstratus != "notaweapon" && $mainabstratus != "headgear" && $mainabstratus != "bodygear" && $mainabstratus != "facegear" && $mainabstratus != "accessory" && $mainabstratus != "computer") { //New abstratus to add to the options.
      $newabstratus = $mainabstratus; //only add the main abstratus of the weapon, consider having it choose a random one instead later
    }
  } else {
  	$newabstratus = $absstring;
  }
  while ($i <= $strifeslots) {
    $invstr = "abstratus" . strval($i);
    //echo $invstr;
    if ($userrow[$invstr] == "") { //First empty strife slot
    	mysql_query("UPDATE `Players` SET `abstrati` = '" . strval($userrow['abstrati'] + 1) . "' WHERE `Players`.`username` = '" . $userrow['username'] . "' LIMIT 1 ;");
      mysql_query("UPDATE `Players` SET `" . $invstr . "` = '" . $newabstratus . "' WHERE `Players`.`username` = '" . $userrow['username'] . "' LIMIT 1 ;");
      $userrow[$invstr] = $newabstratus;
      return $invstr;
    }
    $i++;
  }
  //mysql_close($con);
  return "abstratus-1";
}

function autoUnequip($userrow,$exception,$invslot) {
	if ($exception != "headgear" && $userrow['headgear'] == $invslot) {
		mysql_query("UPDATE `Players` SET `headgear` = '' WHERE `Players`.`username` = '$userrow[username]'");
		if ($userrow['facegear'] == "2HAND") mysql_query("UPDATE `Players` SET `facegear` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "headgear";
	}
	if ($exception != "facegear" && $userrow['facegear'] == $invslot) {
		mysql_query("UPDATE `Players` SET `facegear` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "facegear";
	}
	if ($exception != "bodygear" && $userrow['bodygear'] == $invslot) {
		mysql_query("UPDATE `Players` SET `bodygear` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "bodygear";
	}
	if ($exception != "accessory" && $userrow['accessory'] == $invslot) {
		mysql_query("UPDATE `Players` SET `accessory` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "accessory";
	}
	if ($exception != "equipped" && $userrow['equipped'] == $invslot) {
		mysql_query("UPDATE `Players` SET `equipped` = '' WHERE `Players`.`username` = '$userrow[username]'");
		if ($userrow['offhand'] == "2HAND") mysql_query("UPDATE `Players` SET `offhand` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "equipped";
	}
	if ($exception != "offhand" && $userrow['offhand'] == $invslot) {
		mysql_query("UPDATE `Players` SET `offhand` = '' WHERE `Players`.`username` = '$userrow[username]'");
		$lookfor = "offhand";
	}
	if (strpos($userrow['permstatus'], "." . $lookfor) !== false && !empty($lookfor)) { //this wearable is granting a perm effect
		$statusarray = explode("|", $userrow['permstatus']);
		$i = 0;
		while (!empty($statusarray[$i])) {
			$currentarray = explode(":", $statusarray[$i]);
			if ($currentarray[0] != "ALLY") { //allies are always permanent until their loyalty drops to 0, but that's handled elsewhere
				$duration = explode(".", $currentarray[0]);
				if ($duration[2] == $lookfor) {
					$statusarray[$i] = ""; //this effect wears off
				}
			}
			$i++;
		}
		$newstatus = implode("|", $statusarray);
		$newstatus = preg_replace("/\\|{2,}/","|",$newstatus); //eliminate all blanks
		if ($newstatus == "|") $newstatus = "";
		if ($newstatus != $userrow['permstatus']) {
			mysql_query("UPDATE `Players` SET `permstatus` = '$newstatus' WHERE `Players`.`username` = '" . $userrow['username'] . "'");
		}
	}
}

function itemSize($size) {
	switch ($size) {
		case "intangible":
			return 0;
			break;
		case "miniature":
			return 1;
			break;
		case "tiny":
			return 5;
			break;
		case "small":
			return 10;
			break;
		case "average":
			return 20;
			break;
		case "large":
			return 40;
			break;
		case "huge":
			return 100;
			break;
		case "immense":
			return 250;
			break;
		case "ginormous":
			return 1000;
			break;
		default: //in case some weird value is listed
			return 20; //treat it as "average"
			break;
	}
}

function storageSpace($storestring) {
	$boom = explode("|", $storestring);
	$totalitems = count($boom);
	$i = 0;
	$space = 0;
	while ($i <= $totalitems) {
		$args = explode(":", $boom[$i]);
		$itemresult = mysql_query("SELECT `captchalogue_code`,`size` FROM `Captchalogue` WHERE `Captchalogue`.`captchalogue_code` = '$args[0]' LIMIT 1");
		$irow = mysql_fetch_array($itemresult);
		if ($irow['captchalogue_code'] == $args[0]) { //Item found.
			$space += itemSize($irow['size']) * $args[1];
		} else echo "ERROR: Items with code $args[0] stored, but no matching item was found. Please inform a dev immediately.</br>";
		$i++;
	}
	return $space;
}

function compuRefresh($userrow) {
	//echo "running compuRefresh() for $userrow[username]<br />";
	$complevel = 0;
	if (strpos($userrow['storeditems'], "ISCOMPUTER") !== false) $complevel = 1; //the player has a computer in storage
	$max_items = 50;
	$i = 1;
	$captchalogue = "SELECT `name`,`abstratus`,`size` FROM Captchalogue WHERE";
	$firstinvslot = array();
  while ($i <= $max_items) {
    $invslot = 'inv' . strval($i);
    if ($userrow[$invslot] != "") { //This is a non-empty inventory slot.
	  	$pureitemname = str_replace("\\", "", $userrow[$invslot]);
	  	$pureitemname = str_replace("'", "", $pureitemname);
      $itemname = str_replace("'", "\\\\''", $userrow[$invslot]); //Add escape characters so we can find item correctly in database. Also those backslashes are retarded.
      $itemname = str_replace("\\\\\\''", "\\\\''", $itemname); //Fix extra backslash irregularities if any occur.
      //$captchalogue = mysql_query("SELECT * FROM Captchalogue WHERE `Captchalogue`.`name` = '" . $itemname . "'");
	  	if (empty($captchaloguequantities[$pureitemname])) {
				$captchalogue = $captchalogue . "`Captchalogue`.`name` = '" . $itemname . "' OR ";
				$firstinvslot[$pureitemname] = $invslot;
	  	} else {
				$captchaloguequantities[$pureitemname] += 1;
	  	}
		}
		$i++;
  }
  $captchalogue = substr($captchalogue, 0, -4);
  //echo $captchalogue . "<br />";
  $captchalogueresult = mysql_query($captchalogue);
	while ($compurow = mysql_fetch_array($captchalogueresult)) {
		$pureitemname = str_replace("\\", "", $compurow['name']);
	  $pureitemname = str_replace("'", "", $pureitemname);
		$invstr = $firstinvslot[$pureitemname];
    if (strrpos($compurow['abstratus'], "computer") !== false) {
    	if (itemSize($compurow['size']) <= itemSize("average") && $complevel <= 1) $complevel = 2; //the computer is portable and can be used from inventory
    	if ($complevel <= 2) {
    		if ($userrow['equipped'] == $invstr) $complevel = 3;
    		if ($userrow['offhand'] == $invstr) $complevel = 3;
    		if ($userrow['headgear'] == $invstr) $complevel = 3;
    		if ($userrow['facegear'] == $invstr) $complevel = 3;
    		if ($userrow['bodygear'] == $invstr) $complevel = 3;
    		if ($userrow['accessory'] == $invstr) $complevel = 3;
    	}
    	//echo "complevel is $complevel</br>";
    }
	}
	//echo "final compulevel: $complevel<br />";
	if ($complevel != $userrow['hascomputer']) mysql_query("UPDATE `Players` SET `hascomputer` = $complevel WHERE `Players`.`username` = '$userrow[username]'");
}

function specialArray($itemeffects, $search) { //finds a tag in the "effects" field and returns the array associated with it, useful for looking up single effects
	$effectarray = explode('|', $itemeffects);
	$effectnumber = 0;
	while (!empty($effectarray[$effectnumber])) {
		$currenteffect = $effectarray[$effectnumber];
		$currentarray = explode(':', $currenteffect);
		if ($currentarray[0] == $search) return $currentarray;
		$effectnumber++;
	}
	$currentarray[0] == "notfound"; //indicates the searched tag doesn't appear
	return $currentarray;
}

function grantEffects($userrow, $itemeffects, $slot) { //finds a tag in the "effects" field and returns the array associated with it, useful for looking up single effects
	$grantarray = specialArray($itemeffects, "GRANT");
	if ($grantarray[0] == "GRANT") {
		$granted = explode(".", $grantarray[1]);
		$i = 0;
		while (!empty($granted[$i])) {
			$grantedtag = explode("/", $granted[$i]);
			$grantedtag[0] .= ".-1." . $slot;
			$granted[$i] = implode(":", $grantedtag);
			$i++;
		}
		if (!empty($userrow['permstatus']))
		$newstatus = $userrow['permstatus'] . "|" . implode("|", $granted) . "|";
		else
		$newstatus = implode("|", $granted) . "|";
		$newstatus = preg_replace("/\\|{2,}/","|",$newstatus); //eliminate all blanks
		mysql_query("UPDATE `Players` SET `permstatus` = '$newstatus' WHERE `Players`.`username` = '" . $userrow['username'] . "'");
	}
}

function storeItem($item, $tostorage, $userrow, $stackcode = "00000000") { //making this a function because it's too useful.
  $compuname = str_replace("'", "\\\\''", $item); //Add escape characters so we can find item correctly in database. Also those backslashes are retarded.
  $compuname = str_replace("\\\\\\", "\\\\", $compuname); //really hope this works
  $compuresult = mysql_query("SELECT * FROM Captchalogue WHERE `Captchalogue`.`name` = '" . $compuname . "' LIMIT 1;");
  $itemrow = mysql_fetch_array($compuresult);
	$space = storageSpace($userrow['storeditems']);
	$boom = explode("|", $userrow['storeditems']);
	$totalitems = count($boom);
	$i = 0;
	$storesize = itemSize($itemrow['size']);
	$nospace = false;
	$itemstored = false;
	$madeanewone = false;
	$updatestring = "";
	$actualstore = 0;
	$maxstorage = $userrow['house_build_grist'] + 1000;
	$codetag = specialArray($itemrow['effects'], "CODEHOLDER");
	if ($codetag[0] == "CODEHOLDER") $stackwith = "CODE=" . $stackcode . ".";
	while ($i < $totalitems) {
		if (!empty($boom[$i]))
		$args = explode(":", $boom[$i]);
		else { //this is the one beyond the final line, which will always be empty
			if (!$itemstored) { //Paranoia: make sure we didn't already send items to storage
				$args[0] = $itemrow['captchalogue_code']; //make this slot the item we're making since it doesn't exist in storage
				$args[1] = 0;
				$storagetag = specialArray($itemrow['effects'], "STORAGE");
				$args[2] = "";
				if (strpos($itemrow['abstratus'], "computer") !== false) $args[2] .= "ISCOMPUTER.";
				if ($storagetag[0] == "STORAGE") $args[2] .= $storagetag[1]; //semicolons should be included in the effect string
				if ($codetag[0] == "CODEHOLDER") {
					$args[2] .= "CODE=" . $stackcode . ".";
					$stackwith = $args[2];
				}
				$madeanewone = true;
			} else {
				$args[0] = ""; //lol forgot to unset these when they should be blank
				$args[1] = 0;
				$args[2] = "";
			}
		}
		if ($args[0] == $itemrow['captchalogue_code'] && (strpos($args[2], $stackwith) !== false || empty($stackwith))) {
			while ($tostorage > 0) {
					if ($space + $storesize <= $maxstorage) {
						$tostorage--;
						$space += $storesize;
						$actualstore++;
						$args[1]++;
					} else {
						$tostorage = 0;
						$nospace = true;
					}
			}
		}
		$i++;
		if (!empty($args[0]) && $args[1] != 0) {
			$updatestring .= $args[0] . ":" . $args[1] . ":" . $args[2] . "|";
		}
	}
	if ($updatestring != $userrow['storeditems']) {
		mysql_query("UPDATE `Players` SET `storeditems` = '$updatestring' WHERE `Players`.`username` = '" . $userrow['username'] . "' LIMIT 1");
		$userrow['storeditems'] = $updatestring;
		compuRefresh($userrow);
	}
	return $actualstore; //returns number of items stored
}
?>