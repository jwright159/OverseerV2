<?php

/**
 * This function takes an array of enemy names (starting at index 0), a strife ID, a database connection, and optionally a character ID and tier list.
 * It uses the character ID to select grist types from that character's Land, applying them to the enemy based on the listed tier. It gives the enemies
 * a side of 1, so they're opposed to the player side.
 */
function generateEnemies($enemylist, $strifeID, $connection, $appearson, $generateleader, $sessionID = 0, $landID = 0, $tierlist = "", $persist = 0) {
	$i = 0;
	$rowfetchquery = "SELECT * FROM `Enemy_Types` WHERE `Enemy_Types`.`basename` IN (";
	while (!empty($enemylist[$i])) {
		$rowfetchquery .= "'" . $enemylist[$i] . "', "; //Build a query containing all the enemy details we will need.
		$i++;
	}
	$rowfetchquery = substr($rowfetchquery, 0, -2) . ")";
	$enemyresult = mysqli_query($connection, $rowfetchquery);
	while ($row = mysqli_fetch_array($enemyresult)) {
		if (strpos($appearson,$row['appearson']) === false && $appearson != "ANY") return false; //Enemy not permitted
		$enemyrows[$row['basename']] = $row;
	}
	if ($landID != 0 && $landID != 'battlefield') {
		$landresult = mysqli_query($connection, "SELECT `grist_type` FROM `Characters` WHERE `Characters`.`ID` = $landID LIMIT 1;");
		$landrow = mysqli_fetch_array($landresult);
		$gristarray = explode("|", $landrow['grist_type']); //Entries 0 through 8 are grist tiers 1 through 9. Entries 9 through 17 are the bonus grists.
	}
	$enemymaker = "INSERT INTO `Strifers` (`name`, `strifeID`, `side`, `leader`, `teamwork`, `grist`, `description`, `power`, `maxpower`, `health`, `maxhealth`, `energy`, `maxenergy`, `ondeath`, `status`, `bonuses`, `resistances`, `abilities`, `effects`, `persist`) VALUES ";
	$i = 0;
	while (!empty($enemylist[$i])) {
		$leader = 0;
		if ($i == 0) $leader = $generateleader; //The first enemy in the list is the leader IF generateleader is 1.
		$enemyrow = $enemyrows[$enemylist[$i]];
		$griststr = "None";
		$ondeath = "";
		if ($enemyrow['underling'] == 1) { //This enemy is an underling. Set up their drops, power, and health accordingly.
			$power = ($enemyrow['basepower'] * $tierlist[$i]) + ($tierlist[$i] * $tierlist[$i]);
			$health = ($enemyrow['basehealth'] * $tierlist[$i]) + ($tierlist[$i] * $tierlist[$i]);
			$griststr = $gristarray[$tierlist[$i] - 1]; //Entries 0 through 8 are tiers 1 through 9, so we subtract 1 from the tier to find the correct grist.
			$drops = explode('|', $enemyrow['drops']);
			$ondeath = "";
			$j = 0;
			while (!empty($drops[$j])) { //Found a drop
				if (strpos($drops[$j], "GRIST") !== false) { //This is a grist drop. Replace the tier entry (i.e. the second one) with an actual grist.
					//IMPORTANT NOTE: !== is necessary because it will always be at 0, which will otherwise register as false
					$currentdrop = explode(':', $drops[$j]);
					if (intval($currentdrop[1]) == 0) { //It's a Build Grist drop
						$currentdrop[1] = "Build_Grist";
					} else {
						$tier = $tierlist[$i] - 1 + intval($currentdrop[1]); //Calculate the tier
						if ($tier > 9) $tier = 9;
						$tierindex = $tier - 1; //The array's index starts at 0, so we shift once to the left.
						$currentdrop[1] = $gristarray[$tierindex];
						if (rand(0,100) > 66) { //Bonus drop has triggered
							$bonusdrop = $currentdrop;
							$bonusdrop[1] = $gristarray[$tierindex + 9]; //This offset puts us in the bonus array
							$ondeath .= implode(':', $bonusdrop) . "|";
						}
					}
					$ondeath .= implode(':', $currentdrop) . "|";
				} else {
					$ondeath .= $drops[$j] . "|"; //Default, copy the drop as-is. If other drops need editing, they can get if cases.
				}
				$j++;
			}
		} else { //Not an underling. Copy their drops, power, and health, and append boondollars if relevant.
			$power = $enemyrow['basepower'];
			$health = $enemyrow['basehealth'];
			$ondeath = $enemyrow['drops'];
			if ($enemyrow['maxboons'] > 0) $ondeath .= "BOONDOLLARS:$enemyrow[minboons]:$enemyrow[maxboons]|"; //Set Boondollar drop if it exists
		}
		$spawnstatus = explode("|", $enemyrow['spawnstatus']);
		$j = 0;
		$ABILITY = "";
		$EFFECT = "";
		$STATUS = "";
		$BONUS = "";
		$RESISTANCE = "";
		while (!empty($spawnstatus[$j])) {
			//Spawnstatus entry formats: Abilities are prefaced with ABILITY, effects with EFFECT, statuses with STATUS, bonuses with BONUS, and...
			//...resistances with RESISTANCE. What a shocker, eh?
			$k = 1;
			$stufftoadd = explode(':', $spawnstatus[$j]);
			while (!empty($stufftoadd[$k])) {
				${$stufftoadd[0]} .= $stufftoadd[$k] . ":"; //Adds all the non-first tags to the end of the string that uses the first tag
				$k++;
			}
			${$stufftoadd[0]} = substr(${$stufftoadd[0]}, 0, -1); //Drop the last colon off the end
			${$stufftoadd[0]} .= "|";
			$j++;
		}
		$j = 0;
		if ($enemyrow['prototypings'] > 0) { //We have a prototyping. Get shit for the prototyping application loop initialized.
			$chumroll = mysqli_query($connection, "SELECT `proto_effects` FROM `Characters` WHERE `Characters`.`session` = $sessionID;");
			$chumcount = 0;
			while ($chumrow = mysqli_fetch_array($chumroll)) {
				$chumcount++;
				$prototypes[$chumcount] = $chumrow['proto_effects'];
			}
		}
		while ($j < $enemyrow['prototypings']) { //Process prototypings, doing stuff to the values above as we do.
			$selector = rand(1,$chumcount); //Pick a prototyping string at random
			$protoeffects = explode('|', $prototypes[$selector]);
			$k = 0;
			while(!empty($protoeffects[$k])) {
				$currenteffect = explode(':', $protoeffects[$k]);
				switch ($currenteffect[0]) { //Find out what kind of effect this is.
					case "POWER":
						$power += intval($currenteffect[1]);
						break;
					case "DESCRIPTION":
						$enemyrow['description'] .= " $currenteffect[1]";
						break;
					default: //Default: It's an on-hit effect.  Copy the whole thing through.
						$EFFECT .= mysqli_real_escape_string($connection,$protoeffects[$k]) . "|";
						break;
				}
				$k++;
			}
			$j++;
		}
		$enemyrow['description'] = mysqli_real_escape_string($connection,$enemyrow['description']);
		$enemymaker .= "('$enemylist[$i]', '$strifeID', '1', '$leader', '$enemyrow[teamwork]', '$griststr', '$enemyrow[description]', '$power', '$power', '$health', '$health', '$enemyrow[energy]', '$enemyrow[energy]', '$ondeath', '$STATUS', '$BONUS', '$RESISTANCE', '$ABILITY', '$EFFECT', $persist), ";
		$i++;
	}
	$enemymaker = substr($enemymaker, 0, -2); //Chop the ", " off the end as usual...
	mysqli_query($connection, $enemymaker);
	return true; //Success
}

function findResist($resiststr,$resist) {
	if (strpos($resiststr,$resist) !== false) { //The resistance string is in there somewhere
		$resistances = explode("|", $resiststr);
		$i = 0;
		while (!empty($resistances[$i])) {
			$currentresistance = explode(":", $resistances[$i]);
			if ($currentresistance[0] === $resist) { //This is the resist we're looking for!
				return intval($currentresistance[1]);
			}
			$i++;
		}
	}
	return 0; //The resistance was not found, so the resist value returned is 0.
}
function buildMegaquery($strifers,$n,$connection) { //$n is the number of strifers. We could just count, but we have the value on hand so why not pass it in?
	$values = array("health", "power", "status", "bonuses", "energy", "lastactive", "lastpassive", "subaction", "strifeID", "currentmotif", "motifsused", "teammotif", "brief_luck");
	$types = array("num", "num", "str", "str", "num", "str", "str", "num", "num", "str", "str", "num", "num"); //Contains the type of data (we need this for proper formatting)
	$j = 0;
	$megaquery = "UPDATE `Strifers` SET ";
	while (!empty($values[$j])) {
		$megaquery .= "`" . $values[$j] . "` = CASE `ID` ";
		$idlist = "";
		$k = 1;
		while ($k <= $n) {
			if (!empty($strifers[$k])) { //Defeated enemies will leave "holes" in the array. These are to be ignored.
				if ($types[$j] == "num") { //Data is a number. No quotes.
					$megaquery .= "WHEN " . $strifers[$k]['ID'] . " THEN " . strval($strifers[$k][$values[$j]]) . " ";
				} else { //Data is a string. We need quotes.
					$megaquery .= "WHEN " . $strifers[$k]['ID'] . " THEN '" . mysqli_real_escape_string($connection, strval($strifers[$k][$values[$j]])) . "' ";
				}
				$idlist .= $strifers[$k]['ID']; //This is a number, so no quotes around it.
				$idlist .= ", ";
			}
			$k++;
		}
		$idlist = substr($idlist, 0, -2); //Chop the last ", " off the end
		if ($types[$j] == "num") { //Data is a number. No quotes.
			$megaquery .= "ELSE 0 END";
		} else { //Data is a string. We need quotes.
			$megaquery .= "ELSE '0' END";
		}
		if (!empty($values[$j+1])) { //We still have another case after this
			$megaquery .= ", ";
		} else {
			$megaquery .= " WHERE `ID` IN ($idlist);";
		}
		$j++;
	}
	return $megaquery;
}
function powerCalc($strifer) {
	//Initially, set both values to the strifer's power
	$offense = $strifer['power'];
	$defense = $strifer['power'];
	//The below array contains bonuses to the various strife commands
	$bonusarray = array("AGGRIEVE" => 0, "AGGRESS" => 0, "ASSAIL" => 0, "ASSAULT" => 0, "ABUSE" => 0, "ACCUSE" => 0, "ABJURE" => 0, "ABSTAIN" => 0);
	//First, we process the bonus field, since this is relevant to the strife commands
	$bonuses = explode("|", $strifer['bonuses']);
	for ($i = 0; !empty($bonuses[$i]); $i++) { //We still have a bonus to evaluate.
		//A note on format: <Bonus type>:<duration>:<Bonus value>| is the standard format for bonuses.
		$currentbonus = explode(":", $bonuses[$i]);
		$value = intval($currentbonus[2]);
		switch ($currentbonus[0]) {
			case "POWER":
				$offense += $value;
				$defense += $value;
				break;
			case "OFFENSE":
				$offense += $value;
				break;
			case "DEFENSE":
				$defense += $value;
				break;
			default: //Should be a bonus to a strife command if we get to here without a match
				$bonusarray[$currentbonus[0]] += $value;
				break;
		}
	}
	//Then, we check their fraymotif if they have one active, since fraymotifs may provide a flat boost
	if (!empty($strifer['currentmotif'])) {
		switch($strifer['currentmotif']) {
			case "Breath/I":
				$defense *= 2;
				break;
			case "Heart/I":
				$offense = ceil($offense * 1.2) + 3300;
				$defense = ceil($offense * 1.2) + 3300;
				break;
			case "Hope/I":
				$factor = 1 + ((($strifer['health'] + $strifer['damagetaken']) / $strifer['maxhealth']) * 0.75); //Max is 1.75, min is 1
				$offense = ceil($offense * $factor);
				$defense = ceil($defense * $factor);
				break;
			case "Rage/I":
				$factor = 3 - ((($strifer['health'] + $strifer['damagetaken']) / $strifer['maxhealth']) * 1.8); //Max is 3, min is 1.2.
				if ($factor < 1.2) $factor = 1.2; //Paranoia: Hard minimum.
				$offense = ceil($offense * $factor);
				break;
			case "Mind/II":
				$offense = ceil($offense * 1.33);
				$defense *= 413;
				break;
			default:
				break;
		}
	}
	//Then, we apply the strife commands, if any. (If none, no modification will occur)
	switch ($strifer['active']) {
		case "AGGRIEVE":
			$offense = floor($offense * 1.05) + $bonusarray['AGGRIEVE']; //Multiplier doesn't apply to the bonus!
			break;
		case "AGGRESS":
			$offense = floor($offense * 1.2) + $bonusarray['AGGRESS'];
			$defense = floor($defense * 0.83);
			break;
		case "ASSAIL":
			$offense = floor($offense * 1.5) + $bonusarray['ASSAIL'];
			$defense = floor($defense * 0.66);
			break;
		case "ASSAULT":
			$offense = floor($offense * 2) + $bonusarray['ASSAULT'];
			$defense = floor($defense * 0.5);
			break;
		default:
			break;
	}
	switch ($strifer['passive']) {
		case "ABUSE":
			$offense = floor($offense * 1.05) + $bonusarray['ABUSE']; //Multiplier doesn't apply to the bonus!
			break;
		case "ACCUSE":
			$defense = floor($defense * 1.2) + $bonusarray['ACCUSE'];
			$offense = floor($offense * 0.83);
			break;
		case "ABJURE":
			$defense = floor($defense * 1.5) + $bonusarray['ABJURE'];
			$offense = floor($offense * 0.66);
			break;
		case "ABSTAIN":
			$defense = floor($defense * 2) + $bonusarray['ABSTAIN'];
			$offense = floor($offense * 0.5);
			break;
		default:
			break;
	}
	//Then, we check abilities on the strifer to see if any of those are relevant
	$abilities = explode('|', $strifer['abilities']); //Expand out the ability string so we can look at it
	for ($i = 0; !empty($abilities[$i]); $i++) { //We've found an ability
		switch ($abilities[$i]) {
			//Each ability with an effect on offense or defense power has an entry in this switch statement.
			case "15": //One with Nothing
				if (strpos($strifer['description'], "dreamself") !== false) { //Dreamself unaffected by One with Nothing
					$minimum = floor($strifer['echeladder'] * (1 + ($strifer['echeladder'] * 0.04))); //Just under 15k at rung 612.
					if ($offense < $minimum && $defense < $minimum)  {
						$offense = $minimum;
						$defense = $minimum;
					}
				}
				break;
			case "-5": //Abraxas's hopeful power boost. 12k at max health, linearly down to 0 at 0 health.
				$boost = floor(12000 * ($strifer['health'] / $strifer['maxhealth']));
				$offense += $boost;
				$defense += $boost;
				break;
			default:
				break;
		}
	}
	//Finally, we check statuses on the strifer to see if any of those affect the result
	$status = explode('|', $strifer['status']); //Expand out the status string so we can look at it
	for ($i = 0; !empty($status[$i]); $i++) { //We've found a status entry
		$currentstatus = explode(':', $status[$i]); //Expand it out so we can work with it
		switch ($currentstatus[0]) {
			//Each status with an effect on offense or defense power has an entry in this switch statement.
			case "CANTATTACK": //Strifer is unable to attack this round for whatever reason
				$offense = 0;
				break;
			case "CANTDEFEND": //USE SPARINGLY. Strifer cannot defend this round. Like cantattack, should never be present in the database field.
				$defense = 0;
				break;
			case "FROZEN":
				$offense = 0;
				$defense = floor($defense * 1.1);
				break;
			case "SHRUNK":
				$offense = floor($offense * 0.8);
				$defense = floor($defense * 0.8);
				break;
			case "DISORIENTED":
				$offense = floor($offense * 0.96);
				break;
			case "ENRAGED":
				$defense = floor($defense * 0.9);
				break;
			case "MELLOW":
				$offense = floor($offense * 0.9);
				break;
			case "PINATA":
				$offense = 0;
				$defense = 0;
				break;
			default:
				break;
		}
	}
	//We check fatigue effects at the absolute end so that they affect EVERYTHING and can't be simply ignored with any kind of boost.
	if ($strifer['fatigue'] > 1025) {
		if($strifer['fatigue'] > 1500) setAchievement(getChar($strifer['owner']), 'fatigue');
		$reduction = 1 - (($strifer['fatigue'] - 1025) / 500); //2% for each 10 fatigue points over the limit
		$offense = floor($offense * $reduction);
		$defense = floor($defense * $reduction);
	}
	$power = array("offense" => $offense, "defense" => $defense);
	return $power;
}
function endStrife($strifer) { //Function takes a strife row, returns a strife row prepared for strife exit
	$strifer['strifeID'] = 0; //We win! No more strife
	$strifer['status'] = "";
	$strifer['bonuses'] = "";
	$strifer['power'] = $strifer['maxpower']; //Exiting strife: Reset power level
	$strifer['brief_luck'] = 0;
	$motifsused = explode("|", $strifer['motifsused']);
	$j = 1; //motifsused starts with a |, so the first entry is empty and we need to skip it. I think.
	$newstr = "";
	while(!empty($motifsused[$j])) { //Decrement the cooldown. If it's now zero, remove the fraymotif's entry.
		$currentmotif = explode(":", $motifsused[$j]);
		$currentmotif[1] = intval($currentmotif[1]) - 1;
		if ($currentmotif[1] > 0) $newstr .= "|" . $currentmotif[0] . ":" . $currentmotif[1];
		$j++;
	}
	$strifer['motifsused'] = $newstr;
	return $strifer;
}
