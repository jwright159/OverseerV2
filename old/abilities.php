<?php
require_once("header.php");
if (empty($_SESSION['username'])) {
	echo "Log in to access roletechs and other abilities.<br />";
} else {
	if (!empty($_POST['abilityused'])) {
		$charrow['abilities'] = "|" . $charrow['abilities'];
		$tofind = "|" . $_POST['abilityused'] . "|";
		$usedescape = mysqli_real_escape_string($connection, $_POST['abilityused']);
		if (strpos($charrow['abilities'], $tofind) !== false) { //Player has this ability
			$abilityresult = mysqli_query($connection, "SELECT * FROM `Abilities` WHERE `Abilities`.`ID` = $usedescape LIMIT 1;");
			$abilityrow = mysqli_fetch_array($abilityresult);
			if ($striferow['energy'] >= $abilityrow['Aspect_Cost']) { //Player has the Aspect Vial necessary to use this ability
				$reach = true;
				$notarget = false;
				$success = false;
				if (!empty($_POST['target'])) {
					$targetescape = mysqli_real_escape_string($connection, $_POST['target']);
					$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`name` = $targetescape AND `Characters`.`session` = $charrow[session] LIMIT 1;");
					if ($targetrow = mysqli_fetch_array($targetresult)) { //Target was found
						if ($targetrow['dreamingstatus'] != $charrow['dreamingstatus']) $reach = false; //Cannot reach the target. This MIGHT not matter.
						if ($targetrow['dreamingstatus'] == "Awake") $rowID = $targetrow['wakeself']; //Find and fetch the strife row relevant to their dreaming status
						if ($targetrow['dreamingstatus'] != "Awake") $rowID = $targetrow['dreamself'];
						$targetstriferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $rowID LIMIT 1;");
						$targetstriferow = mysqli_fetch_array($targetstriferesult);
					} else {
						echo "Player $_POST[target] was not found in your session!<br />";
						$notarget = true;
					}
				} else {
					$notarget = true;
				}
				switch ($abilityrow['ID']) {
					case 6: //Esauna (ID 6)
						if (!$notarget) {
							if ($reach) {
								//NOTE - The following string defines which status effects are removed by Esauna. Additional ones will need to be added here!
								$removestr = "|TIMESTOP|HOPELESS|KNOCKDOWN|WATERYGEL|POISON|BLEEDING|DISORIENTED|DISTRACTED|ENRAGED|MELLOW|GLITCHED|CHARMED|UNLUCKY|FROZEN|BURNING|UNSTUCK|ISOLATED|SHRUNK|PARALYZED|IRRADIATED|";
								$statuses = explode("|", $targetstriferow['status']);
								$newstatus = "";
								$i = 0;
								while (!empty($statuses[$i])) { //Evaluate this status
									$currentstatus = explode(":", $statuses[$i]); //Blow it out so we can see what it is.
									$tofind = "|" . $currentstatus[0] . "|"; //Slight reformatting
									if ($strpos($removestr,$tofind) === false) $newstatus .= $statuses[$i] . "|"; //If NOT found as a debuff, add it to the new status string.
									$i++;
								}
								$bonuses = explode("|", $targetstriferow['bonuses']);
								$newbonuses = "";
								$i = 0;
								while (!empty($bonuses[$i])) {
									$currentbonus = explode(":", $bonuses[$i]);
									if (intval($currentbonus[2]) > 0) $newbonuses .= $bonuses[$i] . "|"; //If the bonus is GREATER than 0, add it to the new bonus string
									$i++;
								}
								mysqli_query($connection, "UPDATE `Strifers` SET `status` = '$newstatus', `bonuses` = '$newbonuses' WHERE `Strifers`.`ID` = $targetstriferow[ID] LIMIT 1;");
								$success = true;
							} else {
								echo "You must be able to reach $targetrow[name] to use Esauna on them!<br />";
							}
						} else {
							echo "Your player targeting failed, so Esauna was not used.<br />";
						}
						break;
					case 8: //Seek Fortune's Path (ID 8)
						$luckbonus = 10;
						$findstr = "(";
						$allyresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = $charrow[session];");
						while ($allyrow = mysqli_fetch_array($allyresult)) {
							if(true && $allyrow['ID'] != $charrow['ID']) { //NOTE - The TRUE condition will be "if the player currently has a computer"
								if ($allyrow['wakeself']  != 0) $findstr .= strval($allyrow['wakeself']) . ", "; //Add strife selves to list of beneficiaries
								if ($allyrow['dreamself']  != 0) $findstr .= strval($allyrow['dreamself']) . ", ";
							}
						}
						$findstr = substr($findstr, 0, -2) . ")"; //Drop off the last comma, add a bracket
						$allyresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` IN $findstr;");
						$megaquery = "UPDATE `Strifers` SET `brief_luck` = CASE `ID` "; //Build a megaquery to update luck of all beneficiaries
						while ($allyrow = mysqli_fetch_array($allyresult)) { //Found one, add a case for them
							$megaquery .= "WHEN $allyrow[ID] THEN " . strval($allyrow['brief_luck'] + $luckbonus) . " ";
						}
						$megaquery .= "END WHERE `Strifers`.`ID` IN $findstr;"; //Cap off the query and execute it!
						mysqli_query($connection, $megaquery);
						break;
					default:
						echo "Ability ID $abilityrow[ID] unrecognized. This is probably a bug, please submit a report!<br />";
						break;
				}
				if ($success) { //This flag sets if the ability did what it was supposed to
					$newenergy = $striferow['energy'] - $abilityrow['Aspect_Cost'];
					mysqli_query($connection, "UPDATE `Strifers` SET `energy` = $newenergy WHERE `Strifers`.`ID` = $striferow[ID] LIMIT 1;"); //Debit the energy for the ability
				}
			} else {
				echo "You do not have enough Aspect Vial remaining to use that ability!<br />";
			}
		} else {
			echo "ERROR: You do not actually possess ability ID $_POST[abilityused]!<br />";
		}
	}
	echo "Roletechs and other abilities possessed:<br /><br />";
	$abilities = substr(str_replace("|", ", ", $charrow['abilities']), 0, -2); //Replace "|" with ", ", knock off the last ", "
	$abilityresult = mysqli_query($connection, "SELECT * FROM `Abilities` WHERE `Abilities`.`ID` IN ($abilities);");
	while ($abilityrow = mysqli_fetch_array($abilityresult)) { //Ability the player possesses: Print it!
		echo "Name: $abilityrow[Name]<br />";
		echo "Description: $abilityrow[Description]<br />";
		if ($abilityrow['Aspect_Cost'] != 0) {
			$abilitycost = floor(($abilityrow['Aspect_Cost'] / $striferow['maxenergy']) * 100);
			echo "Aspect cost: $abilitycost%<br />";
		}
		if ($abilityrow['Active'] == 1) {
			echo '<form action="abilities.php" method="post"><input type="hidden" name="abilityused" value="' . $abilityrow['ID'] . '">';
			if ($abilityrow['targets'] == 1) {
				echo 'Ability target: <input type="text" name="target"><br />';
			}
			echo '<input type="submit" value="Use it!"></form><br />';
		}
		echo "<br />";
	}
}
require_once("footer.php");
?>