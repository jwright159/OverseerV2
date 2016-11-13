<?php
$pagetitle = "Aspect Patterns";
$headericon = "/images/header/rancorous.png";
function getHintStr($effectiveness) { //Takes an effectiveness value (assume 10k average) and spits out the appropriate hint string.
	if ($effectiveness <= 0) {
		return "nonexistent";
	} elseif ($effectiveness <= 2000) {
		return "terrible";
	} elseif ($effectiveness <= 4000) {
		return "very bad";
	} elseif ($effectiveness <= 6000) {
		return "bad";
	} elseif ($effectiveness <= 8000) {
		return "not great";
	} elseif ($effectiveness <= 12500) {
		return "average";
	} elseif ($effectiveness <= 16666) {
		return "good";
	} elseif ($effectiveness <= 25000) {
		return "great";
	} elseif ($effectiveness <= 50000) {
		return "incredible";
	} else {
		return "completely ridiculous!";
	}
}
require_once("includes/strifefunctions.php");
require_once("header.php");
if (empty($charrow)) {
	echo "Select a character to manipulate your aspect.</br>";
} else {
	//Define $aspectrow and $classrow here. Save them off to the session so we can avoid having to load them again, potentially.
	if (empty($_SESSION['aspectrow']['name'])) $_SESSION['aspectrow']['name'] = "";
	if (empty($_SESSION['classrow']['name'])) $_SESSION['classrow']['name'] = "";
	if ($_SESSION['aspectrow']['name'] != $charrow['aspect'] || $_SESSION['classrow']['name'] != $charrow['class']) {
		$aspectresult = mysqli_query($connection, "SELECT * FROM `Aspect_modifiers` WHERE `Aspect_modifiers`.`Aspect` = '$charrow[aspect]';");
		$aspectrow = mysqli_fetch_array($aspectresult);
		$_SESSION['aspectrow'] = $aspectrow;
		$classresult = mysqli_query($connection, "SELECT * FROM `Class_modifiers` WHERE `Class_modifiers`.`Class` = '$charrow[class]';");
		$classrow = mysqli_fetch_array($classresult);
		$_SESSION['classrow'] = $classrow;
	} else {
		$aspectrow = $_SESSION['aspectrow'];
		$classrow = $_SESSION['classrow'];
	}
	$classrow = array_map(intval, $classrow);
	$aspectrow = array_map(intval, $aspectrow);
	//Pull strife row here. It is necessary every page for at least determining a list of valid targets.
	if ($charrow['dreamingstatus'] == "Awake") { //Grab the character's waking row
		$sid = $charrow['wakeself'];
	} else { //Grab their dream row
		$sid = $charrow['dreamself'];
	}
	$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $sid LIMIT 1;");
	$striferow = mysqli_fetch_array($striferesult);
	//Calculate aspectpower i.e. relative power of aspect manipulation for this character.
	$aspectpower = floor($charrow['echeladder'] * (pow(($classrow['godtierfactor'] / 100),$charrow['godtier'])));
	$factor = ((612 - $charrow['echeladder']) / 611);
	$aspectpower = ceil($aspectpower * ((($classrow['level1factor'] / 100) * $factor) + (($classrow['level612factor'] / 100) * (1 - $factor))));
	//A check for relevant abilities will go here.
	if (!empty($_POST['deletepattern'])) { //Deleting a pattern. We do not use it in this case!
		$patternarray = explode("|", $charrow['aspectpatterns']);
		$i = 0;
		$newlist = "";
		while(!empty($patternarray[$i])) {
			if ($_POST['index'] != $i) $newlist .= $patternarray[$i] . "|";
			$i++;
		}
		$charrow['aspectpatterns'] = $newlist;
		mysqli_query($connection, "UPDATE `Characters` SET `aspectpatterns` = '" . $charrow['aspectpatterns'] . "' WHERE `ID` = $charrow[ID] LIMIT 1;");
		echo "Pattern deleted.</br>";
	} elseif (!empty($_POST['usepattern'])) { //usepattern is used to signal, erm, a pattern being used.
		//We can check some failure conditions here that apply regardless of target. Failure conditions:
		//User doesn't have enough Aspect Vial
		//Pattern uses more than 100% (less than 100% puts the rest into conservation by default)
		$valuestrs = array(0 => 'damage', 'offenseup', 'defenseup', 'powerdown', 'heal', 'aspectvial'); //The values a pattern can have that define it
		$total = 0;
		$i = 0;
		$savestr = "";
		while(!empty($valuestrs[$i])) {
			//NOTE - $_POST values are run through intval first, so they don't need to be escaped.
			$values[$valuestrs[$i]] = intval($_POST[$valuestrs[$i]]); //Read the posted values into an array
			if ($values[$valuestrs[$i]] < 0) $fail = "That pattern has a negative value! Nice try though.<br />";
			$total += $values[$valuestrs[$i]]; //Sum the total of the values we're getting
			if (!empty($_POST['savepattern'])) { //Player is saving this pattern: Add to the string to save.
				$savestr .= $valuestrs[$i] . ":" . $_POST[$valuestrs[$i]] . ",";
				//Division of patterns is as follows: ":" between a value and its argument, "," between two values, and "|" between two patterns.
			}
			echo $valuestrs[$i] .  "=" . $values[$valuestrs[$i]] . " ";
			$i++;
		}
		echo "<br />";
		if ($total > 100) {
			$fail = "That pattern uses more than 100% of your pattern-wielding potential! This is unfortunately not possible.<br />";
		} else {
			$values['aspectvial'] += (100 - $total); //If we got less than 100%, pour the extras into conservation automatically
			if (!empty($_POST['savepattern'])) {
				$_POST['name'] = mysqli_real_escape_string($connection, $_POST['name']); //Escape the name
				$charrow['aspectpatterns'] .= "name:" . $_POST['name'] . "," . $savestr . "|";
				mysqli_query($connection, "UPDATE `Characters` SET `aspectpatterns` = '" . $charrow['aspectpatterns'] . "' WHERE `ID` = $charrow[ID] LIMIT 1;");
			}
		}
		$cost = 100 - floor(pow(($values['aspectvial'] * ($aspectrow['Aspect_vial'] / 100) * ($classrow['Aspect_vial'] / 100)), (1/2)) * 10);
		//NOTE - Reduces cost to about 1/3 with about 27 points in cost reduction
		if ($cost < 10) $cost = 10; //No ability may cost less than 20% of the aspect vial.
		$cost = floor(($cost / 100) * $striferow['maxenergy']); //Converts cost from percentage to actual value
		if ($cost > $striferow['energy'] && empty($fail)) $fail = "You do not have enough Aspect Vial remaining to use that pattern!";
		if (empty($fail)) { //Don't bother checking for these failure conditions if we have already failed!
			$_POST['target'] = mysqli_real_escape_string($connection, $_POST['target']);
			$active = false;
			$passive = false;
			if ($striferow['strifeID'] != 0) { //User is strifing
				//Target will be a specified strife row. Failure conditions:
				//Target is not in the same strife as user. That is pretty much it.
				$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $_POST[target] LIMIT 1;");
				$targetrow = mysqli_fetch_array($targetresult);
				if ($targetrow['strifeID'] != $striferow['strifeID']) $fail = "You are currently strifing and cannot target outside your current strife.";
				//Active conditions: You are the leader, OR you are targeting yourself.
				if ($striferow['leader'] == 1 || $targetrow['ID'] == $striferow['ID']) $active = true;
				if (!$active) $passive = true;
			} else {
				//Target will be a character row. Failure conditions:
				//Target not in this session
				//Target cannot currently be assisted
				//Target is not currently in the same location (Prospit/Derse/Waking world)
				//User lacks a consumable action
				$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`ID` = $_POST[target] LIMIT 1;");
				$targetrow = mysqli_fetch_array($targetresult);
				if ($targetrow['session'] != $charrow['session']) {
					$fail = "You cannot use your abilities on players not in your session.";
				} elseif ($targetrow['dreamingstatus'] != $charrow['dreamingstatus']) {
					$fail = "You cannot reach that player to use an ability on them!";
				} elseif ($striferow['subaction'] == 1) {
					$fail = "You have already used your consumable action for this round!";
				} else {
					if ($targetrow['dreamingstatus'] == "Awake") { //Grab the character's waking row
						$tid = $targetrow['wakeself'];
					} else { //Grab their dream row
						$tid = $targetrow['dreamself'];
					}
					$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = " . $tid . " LIMIT 1;");
					$targetrow = mysqli_fetch_array($targetresult);
					if ($targetrow['noassist'] == 1) $fail = "The player you have targeted is currently unable to receive assistance!";
					if ($targetrow['ID'] == $striferow['ID']) $active = true; //Outside strife, only active if you target yourself
					if (!$active) $passive = true;
				}
			}
		}
		//Either way, at this point we will have a strife row (called $targetrow) to place the effects of the pattern on, or a notification of failure
		if (empty($fail)) { //NOTE - $fail is an error message. If it isn't empty, we print it since it contains the error. If it is empty, we didn't fail!
			$values['powerup'] = 0;
			if ($values['offenseup'] > 0 && $values['defenseup'] > 0) {
				$values['powerup'] = min($values['offenseup'],$values['defenseup']);
				$values['offenseup'] -= $values['powerup'];
				$values['defenseup'] -= $values['powerup'];
			}
			$abilities = explode('|', $charrow['abilities']); //Here we check for abilities that affect the pattern, now that it's sure to go through.
			$i = 0;
			while(!empty($abilities[$i])) {
				switch($abilities[$i]) { //Abilities only ever have the one argument: Their ID number.
					case "10": //Hey! Listen! (ID 10)
						if ($targetrow['ID'] != $striferow['ID'] && $targetrow['side'] == $striferow['side']) { //Only applies if an ally is targeted.
							$aspectpower *= 1.2;
							echo "Lv. 135 Navitech HEY! LISTEN! activates!! You offer high-quality assistance to your ally, increasing the effectiveness of the buff.<br />";
						}
						break;
					case "14": //Strength of Spirit
						$aspectpower *= 1.175;
						echo "Lv. 87 Heartpower Strength of Spirit activates, empowering your aspect pattern.<br />";
						break;
					case "24": //Aspect Obliteration (ID 24)
						if($values['damage'] > 0) { //Only works if the power being used is damaging!
							$aspectrow['Damage'] = max($aspectrow); //Should lift the largest value from the array.
							echo "Lv. 93 Royaltech Aspect Obliteration activates! You manipulate your Aspect's strength to annihilate your foes.<br />";
						}
						break;
					case "25": //Siphon (ID 25)
						if ($targetrow['side'] != $striferow['side'] && ($values['damage'] + $values['heal'] + $values['powerup'] + $values['powerdown']) > 0) {
							//Only works if you target an enemy and one of damage, healing, powerup, and powerdown is nonzero.
							$siphon = true;
							echo "Lv. 37 Thief Art Siphon activates! Your aspect pattern functions by draining from your target.<br />";
						}
						break;
					case "29": //Lifebringer (ID 29)
						if($values['heal'] > 0) { //Only works if the power being used is healing!
							$aspectrow['Heal'] = max($aspectrow); //Should lift the largest value from the array.
							echo "Lv. 93 Fairytech Lifebringer activates! You manipulate your Aspect's strength to, er, bring life.<br />";
						}
						break;
					default:
						break;
				}
				$i++;
			}
			//Constants defining how much each function gets. 10000 equates to one per Echeladder rung. Less is better, as you can see.
		    $powerupdivider = 500000;
			$healdivider = 20000;
			$damagedivider = 8000;
			$powerdowndivider = 150000;
			if ($active) $aspectpower *= ($classrow['activefactor'] / 100);
			if ($passive) $aspectpower *= ($classrow['passivefactor'] / 100);
			$resistance = findResist($targetrow['resistances'],$charrow['Aspect']);
			$resistance = 1 - ($resistance / 100);
			$damage = ceil(($values['damage'] * $aspectpower * $classrow['Damage'] * $aspectrow['Damage'] * $resistance) / $damagedivider);
			$powerdown = ceil(($values['powerdown'] * $aspectpower * $classrow['Power_down'] * $aspectrow['Power_down'] * $resistance) / $powerdowndivider);
			$offenseup = ceil(($values['offenseup'] * $aspectpower * $classrow['Offense_up'] * $aspectrow['Offense_up']) / $powerupdivider);
			$defenseup = ceil(($values['defenseup'] * $aspectpower * $classrow['Defense_up'] * $aspectrow['Defense_up']) / $powerupdivider);
			$powerup = ceil(($values['powerup'] * $aspectpower * $classrow['Power_up'] * $aspectrow['Power_up']) / $powerupdivider);
			$heal = ceil(($values['heal'] * $aspectpower * $classrow['Heal'] * $aspectrow['Heal']) / $healdivider);
			//Hack - Siphon needs to trigger here so it can work with the post-aspect modified values. SOME other abilities may also need to.
			//Consider this a second trigger point, but use sparingly.
			if (!empty($siphon)) {
				$olddamage = $damage;
				$oldpowerdown = $powerdown;
				$damage += $heal;
				$powerdown += $powerup;
				$powerup += $oldpowerdown;
				$heal += $olddamage;
			}
			if($targetrow['side'] == $striferow['side'] || $striferow['strifeID'] == 0) { //Ally targeted: Apply all effects to them. Including negative ones.
				//NOTE - It counts as ally targeting if you target outside strife, since you can only target enemies you are strifing with.
				$aspectname='an Aspect Pattern';
				if(!empty($_POST['name']) && $_POST['name']!='') $aspectname=$_POST['name'];
				$notification = $charrow['name'] . ' has used ' . mysqli_real_escape_string($connection, $aspectname) . '!';
				$targethealth = $targetrow['health'] - $damage + $heal;
				if($damage>0) $notification=$notification . " Ouch!";
				if($heal>0) {setAchievement($charrow, 'aspectheal'); $notification = $notification . " Health up!";}
				if ($offenseup != 0) {$targetrow['bonuses'] = $targetrow['bonuses'] . "OFFENSE:0:$offenseup|"; $notification = $notification . " Offense up!";}
				if ($defenseup != 0) {$targetrow['bonuses'] = $targetrow['bonuses'] . "DEFENSE:0:$defenseup|"; $notification = $notification . " Defense up!";}
				if ($powerup != 0) {$targetrow['bonuses'] = $targetrow['bonuses'] . "POWER:0:$powerup|"; $notification = $notification . " Power up!";}
				notifyCharacter($targetrow['owner'], $notification);
			} else { //Enemy targeted: Apply negative effects to them and positive effects to self. Add self-effects to database here.
				$targethealth = $targetrow['health'] - $damage;
				if ($offenseup != 0) $striferow['bonuses'] = $striferow['bonuses'] . "OFFENSE:0:$offenseup|";
				if ($defenseup != 0) $striferow['bonuses'] = $striferow['bonuses'] . "DEFENSE:0:$defenseup|";
				if ($powerup != 0) $striferow['bonuses'] = $striferow['bonuses'] . "POWER:0:$powerup|";
				$playerhealth = $striferow['health'] + $heal;
				mysqli_query($connection, "UPDATE `Strifers` SET `bonuses` = '$striferow[bonuses]', `health` = $playerhealth WHERE `Strifers`.`ID` = $striferow[ID] LIMIT 1;");
			}
			incrementStat($charrow, 'aspect');
			//Powerdown is the same regardless of target, so do it afterwards
			$targetpower = $targetrow['power'] - $powerdown;
			//Finally, make sure target values are within acceptable constraints, then update them.
			if ($targethealth > $targetrow['maxhealth']) $targethealth = $targetrow['maxhealth'];
			if ($targethealth < 1) $targethealth = 1;
			if ($targetpower < 0) $targetpower = 0;
			//Below: Query to update the target's strife row
			mysqli_query($connection, "UPDATE `Strifers` SET `bonuses` = '$targetrow[bonuses]', `health` = $targethealth, `power` = $targetpower WHERE `Strifers`.`ID` = $targetrow[ID] LIMIT 1;");
			//Pay for the pattern
			$newenergy = $striferow['energy'] - $cost;
			if ($newenergy < 0) $newenergy = 0; //This should never happen, but the check is included here for safety
			mysqli_query($connection, "UPDATE `Strifers` SET `energy` = $newenergy WHERE `Strifers`.`ID` = $striferow[ID] LIMIT 1;");
			echo "You invoke your Aspect in the " . $_POST['name'] . " pattern, targeting $targetrow[name]!</br>";
		} else {
			echo $fail . "</br>";
		}
	}
	echo "To perform freeform Aspect manipulation, enter values that add up to 100%. If your values sum to less than that, the remainder will automatically count towards reducing the usage cost.</br>";
	echo "<form action='aspectpatterns.php' method='post'>";
	echo "Name for usage: <input type='text' name='name'></br>";
	echo "Damage: <input type='text' name='damage'></br>";
	echo "Power down: <input type='text' name='powerdown'></br>";
	echo "Offense up: <input type='text' name='offenseup'></br>";
	echo "Defense up: <input type='text' name='defenseup'></br>";
	echo "Healing: <input type='text' name='heal'></br>";
	echo "Cost reduction: <input type='text' name='aspectvial'></br>";
	echo "<input type='hidden' name='usepattern' value='gogogo'>";
	echo "Select a target: <select name='target'>";
	if ($striferow['strifeID'] == 0) {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = $charrow[session];");
	} else {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];");
	}
	while ($targetrow = mysqli_fetch_array($targetresult)) {
		echo "<option value='$targetrow[ID]'>$targetrow[name]</option>";
	}
	echo "</select></br>";
	echo '<input type="checkbox" name="savepattern" value="Yes"> Save this pattern for future usage in addition to using it now<br />';
	echo "<input type='submit' value='Manipul8 it!'></form></br>";
	//Will need a checkbox for "save this pattern" and an appropriate part of the form
	if ($charrow['aspectpatterns'] != "") echo "Or select a pattern you have previously saved:</br>";
	$patternarray = explode("|", $charrow['aspectpatterns']);
	$i = 0;
	while(!empty($patternarray[$i])) {
		echo "<form action='aspectpatterns.php' method='post'>";
		echo "<input type='hidden' name='index' value='$i'>"; //Pattern index in case we want to delete it
		$valuearray = explode(",", $patternarray[$i]);
		$j = 0;
		while (!empty($valuearray[$j])) {
			$currentvalue = explode(":", $valuearray[$j]); //Index 0 is the name of the value, index 1 is the argument
			if (empty($currentvalue[1])) $currentvalue[1] = 0;
			switch ($currentvalue[0]) {
				case "name":
					echo "Name of pattern: $currentvalue[1]<input type='hidden' name='$currentvalue[0]' value='$currentvalue[1]'></br>";
					break;
				case "aspectvial":
					echo "Cost reduction: $currentvalue[1]%<input type='hidden' name='$currentvalue[0]' value='$currentvalue[1]'></br>";
					break;
				default:
					echo "$currentvalue[0]: $currentvalue[1]%<input type='hidden' name='$currentvalue[0]' value='$currentvalue[1]'></br>";
					break;
			}
			$j++;
		}
		echo "<input type='hidden' name='usepattern' value='gogogo'>";
		echo "Select a target: <select name='target'>";
		if ($striferow['strifeID'] == 0) {
			$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = $charrow[session];");
		} else {
			$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];");
		}
		while ($targetrow = mysqli_fetch_array($targetresult)) {
			echo "<option value='$targetrow[ID]'>$targetrow[name]</option>";
		}
		echo "</select>";
		echo '<input type="checkbox" name="deletepattern" value="Yes"> Delete this pattern instead of using it</br>';
		echo "<input type='submit' value='Use it!'></form></br></br>";
		$i++;
	}
}
require_once("footer.php");
?>
