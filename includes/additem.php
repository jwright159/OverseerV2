<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/global_functions.php";

/**
 * Adds an item to the player's inventory, returns true if the item was added
 */
function addItem(array $charrow, string $id, string $extras = "", bool $shouldeject = true, bool $shouldrefreshatheneum = true)
{
	global $connection;
	$taken = count($_SESSION['inv']); //find the number of occupied slots. $_SESSION['inv'] will always be more up to date.
	if (!$shouldeject) $eject = false;
	else $eject = willEject($charrow);
	if (!$eject && $taken >= $charrow['invslots']) { //no room in inventory, can't add item
		return false;
	} else {
		array_unshift($_SESSION['inv'], $id);
		if (!empty($extras)) array_unshift($_SESSION['imeta'], "1:" . $extras);
		else array_unshift($_SESSION['imeta'], "1"); //temporary
		if ($taken >= $charrow['invslots']) { //if we're here, it means the modus will eject
			$charrow['inventory'] = ejectItem($charrow);
		}
		//mysqli_query($connection, "UPDATE `Characters` SET `inventory` = '" . $charrow['inventory'] . "', `metadata` = '" . $charrow['metadata'] . "' WHERE `Characters`.`ID` = " . strval($charrow['ID']));
		if ($shouldrefreshatheneum) {
		  refreshAtheneum($charrow, $id, 2);
		}
		return true;
	}
}

/**
 * Removes an item from the player's inventory in a specific slot
 */
function removeItem(int $slot)
{
	$inv = $_SESSION['inv'];
	$imeta = $_SESSION['imeta'];
	$invbefore = array_slice($inv, 0, $slot - 1);
	$invafter = array_slice($inv, $slot + 1);
	$inv = array_merge($invbefore, $invafter);
	$imetabefore = array_slice($imeta, 0, $slot - 1); //gotta keep these synced
	$imetaafter = array_slice($imeta, $slot + 1);
	$imeta = array_merge($imetabefore, $imetaafter);
	$_SESSION['inv'] = $inv;
	$_SESSION['imeta'] = $imeta;
}

function storeItem(&$charrow, $id, $amount, $extras = "", $shouldrefreshatheneum = true)
{
	global $connection;
	$sresult = mysqli_query($connection, "SELECT `size`,`effects` FROM `Captchalogue` WHERE `ID` = $id");
	$srow = mysqli_fetch_array($sresult);
	$size = itemSize($srow['size']) * $amount;
	if ($size + $charrow['storedspace'] > 1000 + $charrow['house_build']) return false;
	$storearray = specialArray($srow['effects'], "STORAGE");
	if (empty($extras) && $storearray[0] == "STORAGE") $extras = $storearray[1]; //extras from the database will be preserved via metadata.
	//this prevents it from adding extra effects every time and allows for extra things to appear alongside individual variables
	$newstorage = "";
	$stored = explode("|", $charrow['storeditems']);
	$i = 0;
	$done = false;
	$iscomputer = "";
	if (strpos($srow['effects'], "COMPUTER") !== false) $iscomputer = "ISCOMPUTER.";
	if (strpos($extras, "ISCOMPUTER.") === false) $extras .= $iscomputer;
	while (!empty($stored[$i])) {
		$thisitem = explode(":", $stored[$i]);
		if ($thisitem[0] == strval($id)) {
			if (strpos($thisitem[2], "ISCOMPUTER.") === false) $thisitem[2] .= $iscomputer;
			if ($thisitem[2] == $extras) {
				$thisitem[1] = strval(intval($thisitem[1]) + $amount);
				$newstorage .= $thisitem[0] . ":" . $thisitem[1] . ":" . $thisitem[2] . "|";
				echo "Stacking item ID " . $thisitem[0] . ", quantity " . $thisitem[1] . ", with flags " . $thisitem[2] . "<br />";
				$done = true;
			}
		} else $newstorage .= $stored[$i] . "|";
		$i++;
	}
	if (!$done) $newstorage .= strval($id) . ":$amount:" . $extras . "|";
	$newstorage = mysqli_real_escape_string($connection, $newstorage);
	mysqli_query($connection, "UPDATE `Characters` SET `storeditems` = '$newstorage', `storedspace` = " . strval($charrow['storedspace'] + $size) . " WHERE `ID` = " . strval($charrow['ID']));
	$charrow['storeditems'] = $newstorage;
	$charrow['storedspace'] += $size;
	if ($shouldrefreshatheneum) {
	  refreshAtheneum($charrow, $id, 2);
	}
	return true;
}

function itemSize($size)
{
	switch ($size)
	{
		case "miniature": return 1; break;
		case "tiny": return 5; break;
		case "small": return 10; break;
		case "average": return 20; break;
		case "large": return 40; break;
		case "huge": return 100; break;
		case "immense": return 250; break;
		case "ginormous": return 1000; break;
		default: return 20; break;
	}
}

function ejectItem($charrow) { //eject an item yep this is totally happening

}

function willEject($charrow) { //returns true if the user's modus will eject an item if inventory is full
	if ($charrow['modus'] == "Array") return false;
	else return true;
}

/**
 * Returns an array listing all inventory slots that can be accessed
 */
function availableInv($charrow)
{
	if ($_SESSION['lastinv'] != $charrow['inventory']) { //has inventory changed since we last ran this calculation?
		$inv = explode("|", $charrow['inventory']);
		$i = 0;
		switch ($charrow['modus']) {
			case "Array": //all of them
				while (!empty($inv[$i])) {
					$available[$i] = true;
					$i++;
				}
				break;
			case "Queue":
				$available[count($inv) - 1] = true;
				break; //the last one only
			case "Stack":
				$available[0] = true;
				break; //the first one only
			case "Queuestack": //first and last
				$available[0] = true;
				$available[count($inv) - 1] = true;
				break;
		}
		$_SESSION['lastinv'] = $charrow['inventory'];
		unset($_SESSION['invailable']); //so that old array entries won't persist? no idea if they would but better safe than sorry
		$_SESSION['invailable'] = $available;
	} else $available = $_SESSION['invailable']; //if not, just use the results from last time
	return $available;
}

function renderItem($trow) { //shows full item information, for inventory/portfolio page
	echo $trow['name'] . "<br />";
	echo "Code: " . $trow['code'] . "<br />";
	if (!empty($trow['art'])) echo "<img src='images/art/" . $trow['art'] . "' title='Image by " . $trow['credit'] . "' /><br />";
	echo $trow['description'] . "<br />";
	if ($trow['abstratus'] != "notaweapon" && $trow['abstratus'] != "") echo "Abstratus: " . $trow['abstratus'] . "<br />";
	if ($trow['wearable'] != "none" && $trow['wearable'] != "") echo "Wearable type: " . $trow['wearable'] . "<br />";
	if (!empty($trow['power'])) echo "Power: " . $trow['power'] . "<br />";
	if (!empty($trow['aggrieve'])) echo "aggrieve: " . bonusStr($trow['aggrieve']) . "<br />";
	if (!empty($trow['aggress'])) echo "aggress: " . bonusStr($trow['aggress']) . "<br />";
	if (!empty($trow['assail'])) echo "assail: " . bonusStr($trow['assail']) . "<br />";
	if (!empty($trow['assault'])) echo "assault: " . bonusStr($trow['assault']) . "<br />";
	if (!empty($trow['abuse'])) echo "abuse: " . bonusStr($trow['abuse']) . "<br />";
	if (!empty($trow['accuse'])) echo "accuse: " . bonusStr($trow['accuse']) . "<br />";
	if (!empty($trow['abjure'])) echo "abjure: " . bonusStr($trow['abjure']) . "<br />";
	if (!empty($trow['abstain'])) echo "abstain: " . bonusStr($trow['abstain']) . "<br />";
	if ($trow['old'] == 1) echo "This item has been automatically ported from Overseer v1. It has lost all on-hit effects, the consumable effects may be simplified or incorrect, and it does not take advantage of the new grist types. To update the item to v2, <a href='submissions.php'>submit a suggestion!</a><br />";
}

function renderGristCosts($gristsfield) { //shows grist costs for an item

}

function itemName($itemID, $connection) {
	$itemresult = mysqli_query($connection, "SELECT `name` FROM `Captchalogue` WHERE `Captchalogue`.`ID` = $itemID LIMIT 1;");
	$itemrow = mysqli_fetch_array($itemresult);
	return $itemrow['name'];
}

function refreshAtheneum($charrow, $itemID, $obtained, $component1 = "", $component2 = "", $recipe = "") {  //add item to atheneum if not already present
/*possible values for $obtained: 0 - known to be valid code, 1 - name, image, description known, 2 - all info known ie. was obtained or viewed with holo+
  recipe may be either "and" or "or"
  items are stored as |$itemID:$obtained:$component1//$component2,$component3&&$component4,etc  */
	global $connection;
	if ($obtained > 2) {
		$obtained = 2;
	}
	if ($recipe == "and") {
		$recipe = "&&";
	} else if ($recipe == "or") {
		$recipe = "//";
	} else if (!empty($recipe)) {
		logDebugMessage("When adding to atheneum recipe was invalid: " . $recipe);
		$recipe = "";
	}
	$atheneumresult = mysqli_query($connection, "SELECT `atheneum` FROM `Sessions` WHERE `Sessions`.`ID` = " . $charrow['session'] . " LIMIT 1;");
	$contents = mysqli_fetch_array($atheneumresult);
	if (strpos($contents['atheneum'], "|" . $itemID . ":") !== false) {   //find if item is already in atheneum
		$currententry = null;
		$entrynumber = null;
		//explode and retrieve specific item entry
		$explodedentries = explode("|", $contents['atheneum']);
		for ($i = 0; $i < count($explodedentries); $i++) {
			if (strpos($explodedentries[$i], "|" . $itemID . ":") !== false) {
				$currententry = explode(":", $explodedentries[$i]);
				$entrynumber = $i;
				break;
			}
		}
		//determine whether $obtained should be updated
		if ($obtained > $currententry[1]) {
			$currententry[1] = $obtained;
		}
		if (!empty($component1) && !empty($component2) && !empty($recipe)) {    //if components and recipe aren't blank (ADD CODE TO WRITE TO DEBUG LOG IF ONLY SOME ARE BLANK)
			//determine whether recipe is already known
			$recipes = explode(",", $currententry[2]);
			$exists = false;
			for ($i = 0; $i <= count($recipes); $i++) {
				if ($recipes[$i] == $component1 . $recipe . $component2 || $recipes[$i] == $component2 . $recipe . $component1) {
					$exists = true;
					break;
				}
			}
			//update with new recipe if necessary
			if ($exists == false) {
				array_push($recipes, $component1 . $recipe . $component2);
				$currententry[2] = implode(",", $recipes);
			}
		} else if (!(empty($component1) && empty($component2) && empty($recipe))) {
			logDebugMessage("Tried to write recipe to atheneum but some fields were blank! " . $component1 . ", " . $component2 . ", " . $recipe);
		}
		//update item entry
		$explodedentries[$entrynumber] = implode(":", $currententry);
		//rebuild atheneum
		$newcontents = implode("|", $explodedentries);
	} else {
		//blank all if any component or recipe field is blank
		if (!(empty($component1) && empty($component2) && empty($recipe)) && (empty($component1) || empty($component2) || empty($recipe))) {
			logDebugMessage("Tried to write recipe to atheneum but some fields were blank! " . $component1 . ", " . $component2 . ", " . $recipe);
		}
		if (empty($component1) || empty($component2) || empty($recipe)) {
			$component1 = "";
			$component2 = "";
			$recipe = "";
		}
		//append string to current atheneum string
		$newcontents = $contents['atheneum'] . "|" . $itemID . ":" . $obtained . ":" . $component1 . $recipe .  $component2;
	}
	if ($newcontents != $contents['atheneum']) {
		mysqli_query($connection, "UPDATE `Sessions` SET `atheneum` = '$newcontents' WHERE `Sessions`.`ID` = " . $charrow['session'] . " LIMIT 1;");
	}
}
