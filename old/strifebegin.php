<?php
$pagetitle = "Strife!";
$headericon = "/images/header/rancorous.png";
require_once("header.php");
require_once("includes/strifefunctions.php");
$i = 1;
$enemystr = "enemy" . strval($i);
$enemycreated = false;
$legit = false;
$savedcommand = "";
if (!empty($_POST['land'])) { //Waking strife. Check that the Land is legal.
	$savedcommand .= "land:" . $_POST['land'] . "|"; 
	if ($_POST['land'] == "battlefield") {
		if ($charrow['denizendown'] == 1) {
			$legit = true;
			$appearson = "Battlefield";
		} else {
			echo "ERROR: You cannot visit the Battlefield until your Denizen has been defeated!<br />";
		}
	} else {
		$connected = chainArray($charrow);
		if($connected[$_POST['land']] || $_POST['land'] == $charrow['ID']) {
			$landresult = mysqli_query($connection, "SELECT `grist_type` FROM `Characters` WHERE `Characters`.`ID` = '$_POST[land]' LIMIT 1;");
			$landrow = mysqli_fetch_array($landresult);
			$gristarray = explode('|', $landrow['grist_type']); //Items 0 through 8 are the grists for tiers 1 through 9 here.
			//Note that items 9 through 17 are the bonus grists for those tiers!
			$legit = true;
			$appearson = "Lands";
		} else {
			echo "ERROR: You do not have access to that Land!<br />";
		}
	}
} elseif (!empty($_POST['Denizen'])) { //Denizen Strife. Check that conditions are met and select Denizen.
	$savedcommand .= "DONOTSAVE";
	if ($charrow['gatescleared'] >= 7 && $charrow['denizendown'] == 0) {
		$legit = true;
		switch ($charrow['aspect']) {
			case 'Breath':
				$denizen = "Typheus";
				break;
			case 'Blood':
				$denizen = "Armok";
				break;
			case 'Heart':
				$denizen = "Sophia";
				break;
			case 'Life':
				$denizen = "Hemera";
				break;
			case 'Hope':
				$denizen = "Abraxas";
				break;
			case 'Light':
				$denizen = "Cetus";
				break;
			case 'Mind':
				$denizen = "Metis";
				break;
			case 'Doom':
				$denizen = "Moros";
				break;
			case 'Rage':
				$denizen = "Lyssa";
				break;
			case 'Void':
				$denizen = "Nix";
				break;
			case 'Space':
				$denizen = "Echidna";
				break;
			case 'Time':
				$denizen = "Hephaestus";
				break;
			default:
				echo 'Aspect ' . $charrow['aspect'] . ' unrecognized. This is probably a bug, please submit a report!<br />';
				$denizen = "Nix";
				break;
		}
		$appearson = "Event";
		$_POST['enemy1'] = $denizen;
		$_POST['land'] = $charrow['ID'];
	} else {
		echo "ERROR: You do not currently qualify to fight your Denizen!<br />";
	}
} else { //Dreaming strife. Ensure player is actually asleep!
	$savedcommand .= "dreaming:$charrow[dreamingstatus]|"; //Not actually necessary, but I wanted to put something there
	if ($charrow['dreamingstatus'] != "Awake") {
		$legit = true;
		$appearson = $charrow['dreamer'];
	} else {
		echo "ERROR: You cannot strife dream enemies while awake!<br />";
	}
}
if ($legit) {
	while (!empty($_POST[$enemystr])) {
		$griststr = $enemystr . "grist";
		$enemycreated = true;
		$enemyarray[$i-1] = $_POST[$enemystr]; //We need to use $i - 1 so that generateEnemies accepts the array.
		$savedcommand .= $enemystr . ":" . $_POST[$enemystr] . "|";
		//We can't just start with i = 0 because the first argument is enemy1, not enemy0
		if (!empty($_POST[$griststr])) {
			$tierlist[$i-1] = $_POST[$griststr];
			$savedcommand .= $griststr . ":" . $_POST[$griststr] . "|";
		}
		$i++;
		$enemystr = "enemy" . strval($i);
	}
	if ($enemycreated) {
		$fatiguestr = 'wakefatigue'; //Should be overwritten by the below code, but just in case
		if ($charrow['dreamingstatus'] == "Awake") {
			$sid = $charrow['wakeself']; //$sid for strife ID
			$fatiguestr = 'wakefatigue';
		} else {
			$sid = $charrow['dreamself'];
			$fatiguestr = 'dreamfatigue';
		}
		//Retrieve the next ID from masterID and use it
		mysqli_multi_query($connection, "UPDATE System SET masterID = masterID + 1; SELECT masterID from System WHERE 1;");
		mysqli_next_result(mysqli_next_result($connection));
		$masterresult = mysqli_store_result($connection); //Store the second result
		$masterrow = mysqli_fetch_array($masterresult);	
		$newID = $masterrow['masterID']; //Grab the master ID
		mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID WHERE `Strifers`.`ID` = $sid LIMIT 1;"); //To minimize the time this strifer is part of the group getting an ID
		$enemies = generateEnemies($enemyarray,$newID,$connection,$appearson,1,$charrow['session'],$_POST['land'],$tierlist);
		if ($enemies) {
			$charrow = spendFatigue(10, $charrow); //Dumb magic number: strifes cost 10 fatigue
			$playerside = 0;
			mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID, `side` = $playerside, `leader` = 1, `fatigue` = " . $charrow[$fatiguestr] . " WHERE `Strifers`.`ID` = $sid LIMIT 1;"); //Add the player
			if ($charrow['dreamingstatus'] == "Awake") { //Allies can't follow you to the moons. Temporary, we might add moon allies later.
				mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID, `side` = $playerside WHERE `Strifers`.`owner` = " . $charrow['ID'] . " AND `Strifers`.`Aspect` = '';"); //Add allies
			}
			if (strpos($savedcommand, "DONOTSAVE") === false) {
				mysqli_query($connection, "UPDATE Characters SET oldenemydata = '$savedcommand' WHERE Characters.ID = $charrow[ID] LIMIT 1;");
			}
			require_once("strifedisplay.php");
		} else {
			echo "ERROR: Enemy creation failed!<br />";
		}
	} else {
		echo "No enemies were queued up to be processed, so strifebegin failed to produce any enemies.<br />";
	}
}
require_once("footer.php");
?>
