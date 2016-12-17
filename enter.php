<?php
require_once("header.php");
require ("includes/additem.php");
/*function initRandomGrists($charrow) {
	global $connection;
	$charresult = mysqli_query($connection, "SELECT `grist_type` FROM `Characters` WHERE `session` = " . $charrow['session'] . " AND `inmedium` = 1"); //query all medium-entered players for their grist types
	while ($row = mysqli_fetch_array($charresult)) {
		$grists = explode("|", $row['grist_type']);
		$i = 0;
		while (!empty($grists[$i])) {
			$appears[$grists[$i]] = true; //grist type already appears in the session
			$i++;
		}
	}
	$gristresult = mysqli_query($connection, "SELECT * FROM Grists WHERE tier > 0 AND tier < 10 ORDER BY tier DESC"); //pull all land-available grists
	$j = 1;
	while ($j <= 9) {
		$grists[$j] = array(); //initialize grist choice list arrays
		$j++;
	}
	while ($row = mysqli_fetch_array($gristresult)) {
		array_push($grists[$row['tier']], $row['name']); //add to list of available grists for this tier
		if (empty($appears[$row['name']])) array_push($grists[$row['tier']], $row['name']); //double its chances if we didn't find it in the session yet
	}
	return $grists;
}*/
//$invarray = explode("|",$charrow['inventory']);
//$item;
//$a;
//for($a=0;$item=14;$a++){
	//$item = $invarray[$a];
//}
//echo $a;
if (empty($_SESSION['character'])) {
	echo "Choose a character to enter the Medium.<br />";	
} elseif ($charrow['inmedium'] == 1) {
	echo "You are already in the Medium!<br />";
} else if(strstr($charrow['inventory'],"14|") == false){
	echo "Enter where?<br />";
} else {
	 if (!empty($_POST['land1']) && !empty($_POST['land2'])) {
		$newinv = str_replace("14|","",$charrow['inventory']);
	 	$alreadyentered = true;
		$i = 1;
		$offset = 0;
		$griststr = "";
		//$grists = initRandomGrists($charrow);
		while ($offset < 2) { //do this twice, once for each preset choice
			if ($offset == 0) $thischoice = $_POST['preset'];
			if ($offset == 1) $thischoice = $_POST['preset2'];
			if ($thischoice == "manual") {
				echo "Entering with manually selected grists...<br />";
				while ($i <= ($offset+1) * 9) {
					if ($i > 9) {
						$gristresult = mysqli_query($connection, "SELECT * FROM Grists WHERE name = '" . $_POST['tierb' . strval($i - 9)] . "'");
						$tier = $i - 9;
					} else {
						$gristresult = mysqli_query($connection, "SELECT * FROM Grists WHERE name = '" . $_POST['tier' . strval($i)] . "'");
						$tier = $i;
					}
					$row = mysqli_fetch_array($gristresult);
					if (empty($row['name'])) {
						echo "Error entering Medium: No such grist exists.<br />";
						$alreadyentered = false;
					} elseif ($tier < $row['tier'] || $row['tier'] < 1) { //check that the grist is actually available at that tier
						echo "Error entering Medium: " . $row['name'] . " is not available at tier $tier.<br />";
						$alreadyentered = false;
					}
					if ($offset == 0) { //no need to do this if we're already on bonus grists
						$alls = implode("|", $grists[$i]); //the greatest way to remove all copies of this grist from available grists
						$alls = str_replace($row['name'] . "|", "", $alls);
						$grists[$i] = explode("|", $alls);
					}
					$griststr .= $row['name'] . "|";
					$i++;
				}
				if ($alreadyentered) echo "Successful.<br />";
			} elseif (/*$thischoice == "random"*/0) {
				$available = array();
				while ($i <= ($offset+1) * 9) {
					$j = $i - ($offset * 9);
					while ($j > 0) { //add this tier's grists a number of times equal to the tier so that they're weighted towards higher tiers, when available
						$available = array_merge($available, $grists[$i - ($offset * 9)]); //add the grists of this tier to the potential random picks
						$j--;
					}
					//$grist = array_rand($available); //pick a random grist from the remaining available ones
					if ($grist === null) break; //something went wrong, there aren't enough grist types so escape
					$griststr .= $available[$grist] . "|"; //add it to the grist string
					$alls = implode("|", $available); //the greatest way to remove all copies of this grist from $available
					$alls = str_replace($available[$grist] . "|", "", $alls);
					$available = explode("|", $alls);
					if ($offset == 0) { //no need to do this if we're already on bonus grists
						$alls = implode("|", $grists[$i]); //the greatest way to remove all copies of this grist from available grists
						$alls = str_replace($row['name'] . "|", "", $alls);
						$grists[$i] = explode("|", $alls);
					}
					$i++;
				}
				echo "<br />";
				$griststr = $normalstr . $bonusstr;
				if ($i <= ($offset+1) * 9) { //there was an error and it broke out of the while loop before completion
					echo "Error: could not find enough grists to populate land! This is probably a bug, please submit a report.<br />";
					$alreadyentered = false;
				}
			} else {
				echo "Entering with grist preset " . $thischoice . "...<br />";
				$presetresult = mysqli_query($connection, "SELECT * FROM Presets WHERE name = '" . $thischoice . "'");
				$row = mysqli_fetch_array($presetresult);
				if (empty($row['name'])) {
					echo "Error entering Medium: No grist preset exists with that name.<br />";
					$alreadyentered = false;
				} else {
					$griststr .= $row['grists'] . "|";
					/*$presetgrists = explode("|", $row['grists']);
					$available = array();
					while ($i <= ($offset+1) * 9) {
						$thisgrist = $presetgrists[$i - ($offset * 9) - 1];
						if ($offset == 1) {
							$j = $i - ($offset * 9);
							while ($j > 0) { //add this tier's grists a number of times equal to the tier so that they're weighted towards higher tiers, when available
								$available = array_merge($available, $grists[$i - ($offset * 9)]); //add the grists of this tier to the potential random picks
								$j--;
							}
							if (!in_array($thisgrist, $available)) { //grist is already on this land, so attempt to replace it
								$grist = array_rand($available); //pick a random grist from the remaining available ones
								if ($grist === null) { //something went wrong, there aren't enough grist types so escape
									echo "Error: could not find enough grists to populate land! This is probably a bug, please submit a report.<br />";
									$alreadyentered = false;
								}
								$griststr .= $available[$grist] . "|"; //add it to the grist string
								$alls = implode("|", $available); //the greatest way to remove all copies of this grist from $available
								$alls = str_replace($available[$grist] . "|", "", $alls);
								$available = explode("|", $alls);
								$thisgrist = $grist;
							}
						} else { //no need to do this if we're already on bonus grists
							$alls = implode("|", $grists[$i]); //the greatest way to remove all copies of this grist from available grists
							$alls = str_replace($row['name'] . "|", "", $alls);
							$grists[$i] = explode("|", $alls);
						}
						$griststr .= $thisgrist . "|";
						$i++;
					}*/
				}
			}
			$offset++;
		}
		if ($alreadyentered) { //everything checks out, let's enter that medium!
			$land1 = mysqli_real_escape_string($connection, $_POST['land1']);
			$land2 = mysqli_real_escape_string($connection, $_POST['land2']);
			$griststr = substr($griststr, 0, -1);
			$consorts = mysqli_real_escape_string($connection, $_POST['consorts']);
			mysqli_query($connection, "UPDATE Characters SET inmedium = 1, land1 = '$land1', land2 = '$land2', grist_type = '$griststr', consort = '$consorts' WHERE ID = $cid"); //uncomment when done debugging
			echo "A blinding white light engulfs you as you break your Cruxite Artifact.<br />
			When you come to, you realize the outside world has changed. You, as well as your entire dwelling, have been transported to the Land of " . $_POST['land1'] . " and " . $_POST['land2'] . ".<br />";
			if (empty($charrow['proto_obj1'])) { //player entered without prototyping, whoops
				echo "Your Kernelsprite, unchanged, appears to be gravely disappointed.<br />";
			} else {
				echo "Your sprite, having taken on a more humanoid, ghostly form, hovers next to you proudly.<br />";
			}
			echo "Your adventure has only just begun...<br />";
			setAchievement($charrow,'medium');
			notifySession($charrow, $charrow['name'] . " has entered the Land of ". $land1 . " and " .$land2 . "!");
			mysqli_query($connection, "UPDATE `Characters` SET `inventory` = '$newinv' WHERE `Characters`.`ID` = '" . $charrow['ID'] . "'");
		}
	} else $alreadyentered = false;
	
	if (!$alreadyentered) {
	echo "You are entering the Medium. Fill out the form below to create your land and settle upon it.<br />";
	if (empty($charrow['proto_obj1'])) { //player has not yet prototyped
		echo "The Kernelsprite gesticulates wildly. <a href='sprite.php'>It seems to be trying to urge you not to proceed just yet...</a><br />";
	}
	echo "<form action='enter.php' method='post'>Land name: The Land of <input type='text' name='land1' /> and <input type='text' name='land2' /><br />";
	echo "Consort color/species: <input type='text' name='consorts' /><br />"; //we don't have a colors table yet so consorts can be manually named for now
	$psoptions = "<option value='manual'>Choose manually using the below fields</option>"/*<option value='random'>Choose randomly</option>*/;
	$presetresult = mysqli_query($connection, "SELECT * FROM Presets");
	while ($prow = mysqli_fetch_array($presetresult)) {
		$psoptions .= "<option value='" . $prow['name'] . "'>" . $prow['name'] . " - ";
		$gstr = explode("|", $prow['grists']);
		$i = 0;
		while ($i < 9) {
			$psoptions .= $gstr[$i];
			if ($i < 8) $psoptions .= ", ";
			$i++;
		}
		$psoptions .= "</option>";
	}
	echo "Primary grists: <select name='preset'>" . $psoptions . "</select><br />Secondary grists: <select name='preset2'>" . $psoptions;
	echo "</select><br />Exact grists (applicable only if you have \"choose manually\" selected):<br />";
	$gristresult = mysqli_query($connection, "SELECT * FROM Grists WHERE tier > 0 AND tier < 10 ORDER BY tier DESC"); //pull all land-available grists
	//We order by tier descending so that higher tiers show up first on the lists for higher tier slots, as they are recommended choices for those slots
	$j = 1;
	while ($j <= 9) {
		$grists[$j] = array(0=>"nothing"); //initialize grist choice list arrays
		$tiers[$j] = array(0=>0); //and tiers too
		$j++;
	}
	while ($row = mysqli_fetch_array($gristresult)) {
		$j = $row['tier'];
		while ($j <= 9) {
			array_push($grists[$j], $row['name']); //add to list of available grists for all tiers at this level and above
			array_push($tiers[$j], $row['tier']);
			$j++;
		}
	}
	$i = 1;
	while ($i <= 9) { //run through all the tiers and provide a drop-down for each one
		echo "Tier $i - Primary: <select name='tier$i'>";
		$j = 1;
		while (!empty($grists[$i][$j])) { //sort through the arrays we built earlier to print all available grists at this tier
			echo "<option value='" . $grists[$i][$j] . "'>" . $grists[$i][$j] . " (base tier " . strval($tiers[$i][$j]) . ")</option>";
			$j++;
		}
		echo "</select> ";
		echo "Secondary: <select name='tierb$i'>";
		$j = 1;
		while (!empty($grists[$i][$j])) { //sort through the arrays we built earlier to print all available grists at this tier
			echo "<option value='" . $grists[$i][$j] . "'>" . $grists[$i][$j] . " (base tier " . strval($tiers[$i][$j]) . ")</option>";
			$j++;
		}
		echo "</select><br />";
		$i++;
	}
	//will provide a "grist preset select" dropdown at some point, when we actually have presets
	//more potential land options in the future?
	echo "<b><span style=color:red>BE WARNED: ONCE YOU ENTER THE MEDIUM, THERE'S NO COMING BACK.</span></b>";
	echo "<input type='submit' value='&gt;" . $charrow['name'] . ": Enter.' /></form>";
	}
}

require_once("footer.php");
?>