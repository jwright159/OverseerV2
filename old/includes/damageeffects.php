<?php
//IMPORTANT NOTE! The order of effects being applied here should not matter. If an effect causes order to be relevant, it should be processed somewhere else.
//Probably in the preeffects file. Also remember that $damage is the current damage, $offense is the offense applied, and $defense is the defense applied.
//$i is the attacker, $t is the defender. Things that add to the damage probably care about order and need to be handled earlier somehow.

//Reminder: Append new statuses to $updatedstatus[$strifers[$t]['ID']] so they don't affect strifer $t's later damage resolutions
//Note that they will need a duration of at least 2 if they are not to be removed immediately. (A duration of 1 might be useful for some specific cases
//such as removing an ability at the end of the turn)
$flatdamage = 0; //Anything added to "flatdamage" will be added to the damage without being affected by multipliers of any kind.
$status = explode('|', $strifers[$i]['status']); //First, check statuses for relevance.
$j = 0;
$offinherit = false;
while (!empty($status[$j])) { //Status found, let's take a look at it
	$currentstatus = explode(':', $status[$j]); //Expand it out so we can work with it
	switch ($currentstatus[0]) {
		//Statuses on the attacker that affect their damage dealt have an entry here
		case "INHERITANCE":
			$offinherit = true;
			break;
		default:
			break;
	}
	$j++;
}
$abilities = explode('|', $strifers[$i]['abilities']); //Then check for relevant abilities.
$j = 0;
while(!empty($abilities[$j])) { //We've found an ability
	switch ($abilities[$j]) {
		//Abilities on the attacker that affect their damage dealt have an entry here
		case "21": //Inevitability (ID 21)
			$multiplier = 1 + ($strifers[$t]['health'] / $strifers[$t]['maxhealth']); //1 at full health, 2 at no health. Nice and simple.
			$damage = ceil($damage * $multiplier);
			//No message. It activates literally every attack on an enemy who's not on full health.
			break;
		case "22": //Broken Record (ID 22)
			$minroll = min((1 + floor($strifers[$i]['luck'] / 12)), 100);
			$roll = rand($minroll,100);
			$target = 100 - ($strifers[$t]['echeladder'] / 20); //Approx. 30% chance at max rung
			if (($roll >= $target) || ($offinherit && ($strifers[$i]['timeattack'] != true))) {
				$output .= $strifers[$i]['name'] . "'s Lv. 327 Timetech Broken Record activates! Time skips back as they attack " . $strifers[$t]['name'] . ", allowing them to strike again.<br />";
				$strifers[$i]['timeattack'] = true;
				$attacks++;
			}
			break;
		default:
			break;
	}
	$j++;
}
$status = explode('|', $strifers[$t]['status']); //Then, check defender statuses for relevance.
$j = 0;
$definherit = false;
while (!empty($status[$j])) { //Status found, let's take a look at it
	$currentstatus = explode(':', $status[$j]); //Expand it out so we can work with it
	switch ($currentstatus[0]) {
		//Statuses on the defender that affect their damage taken have an entry here
		case "WATERYGEL":
			$damage = floor($damage * 1.15);
			break;
		case "INHERITANCE":
			$definherit = true;
			break;
		default:
			break;
	}
	$j++;
}
$abilities = explode('|', $strifers[$t]['abilities']); //Then check for relevant defender abilities.
$j = 0;
while(!empty($abilities[$j])) { //We've found an ability
	switch ($abilities[$j]) {
		//Abilities on the defender that affect their damage taken have an entry here
		case "4": //Dissipate: Check for activation
			$minroll = min((1 + floor($strifers[$t]['luck'] / 12)), 100);
			$roll = rand($minroll,100);
			$target = 100 - ($strifers[$t]['echeladder'] / 40); //Approx. 15% chance at max rung
			if (($roll >= $target) || $definherit) { //Triggers, either due to a successful proc or inheritance being used.
				$output .= $strifers[$t]['name'] . " briefly transforms into wind and evades " . $strifers[$i]['name'] . "'s attack!<br />";
				$damage = 0;
			}
			break;
		case "13":
			if ($basedamage > 0) { //Attack has base damage, attacker will receive recoil
				$recoil = ceil($basedamage / 10);
				$strifers[$i]['health'] -= $recoil;
				$output .= $strifers[$t]['name'] . "'s Lv. 239 Spacebending Spatial Warp activates, dealing recoil damage to " . $strifers[$i]['name'] . "!<br />";
			}
			break;
		case "-8": //Metis's decision point
			if ($strifers[$i]['subaction'] == 0) { //Metis has an action and needs to decide what to do with it
				$dodgechance = $damage / 100; //Guaranteed dodge at 10k damage and above
				$dodgecost = 17;
				$roll = rand(1,100);
				if ($roll <= $dodgechance && $strifers[$i]['energy'] <= $dodgecost) {
					//Roll passes and we have enough energy for a perfect dodge.
					$output .= $strifers[$t]['name'] . " perfectly predicts " . $strifers[$i]['name'] . " 's attack, avoiding it flawlessly!<br />";
					$strifers[$i]['energy'] -= $dodgecost;
					$strifers[$i]['subaction'] = 1;
					$damage = 0;
				} else { //Not dodging. Take down data from this enemy to make a decision with later.
					$strifers[$t]['offensedata'] += $attackerpower['offense']; //Pull the raw offense and defense calculated for this attacker
					$strifers[$t]['defensedata'] += $attackerpower['defense'];
					$strifers[$t]['subactiondata'] += $strifers[$i]['subaction'];
				}
			}
			break;
		default:
			break;
	}
	$j++;
}
if ($damage > 0) { //On-hit effects only apply if damage is dealt!
	if (!empty($strifers[$i]['currentmotif'])) { //Now we check for and apply on-hit fraymotifs.
		//NOTE - We append to updatedstatus because POWERLOSS and POWERGAIN need to go off post-round
		switch($strifers[$i]['currentmotif']) {
			case "Mind/I":
				$flatdamage += 413;
				$updatedstatus[$strifers[$t]['ID']] .= "POWERLOSS:1:2413|"; //Special status effect: Causes power loss and disappears
				break;
			case "Blood/I":
				$flatdamage += 690;
				$updatedstatus[$strifers[$t]['ID']] .= "BLEEDING:3:5|";
				$j = 1;
				while ($j <= $n) {
					if ($strifers[$j]['side'] == $strifers[$i]['side']) $updatedstatus[$strifers[$j]['ID']] .= "POWERGAIN:1:690|"; //See POWERLOSS
					$j++;
				}
				break;
			case "Doom/I":
				$factor = 3 - ((($strifers[$t]['health'] + $strifers[$t]['damagetaken']) / $strifers[$t]['maxhealth']) * 2);
				if ($factor < 1) $factor = 1;
				if ($factor > 3) $factor = 3; //Paranoia: Prevent weird values.
				//Factor is 1 against full health enemies, linearly approaches 3 against 0HP enemies. Damage taken this round is NOT considered,
				//thus it looks at the enemy's health at the start of the round.
				$damage = ceil($damage * $factor);
				break;
			case "Void/I":
				$strifers[$t]['power'] = floor((rand(80,100) / 100) * $strifers[$t]['power']);
				$flatdamage += floor((rand(0,20) / 100) * $strifers[$t]['maxhealth']);
				break;
			case "Space/I":
				if ($strifers[$t]['leader'] == 1) $flatdamage += 40000;
				break;
			case "Breath/II":
				$flatdamage += 5000;
				$updatedstatus[$strifers[$t]['ID']] .= "KNOCKDOWN:2|";
				break;
			case "Heart/II":
				$flatdamage += ($strifers[$t]['power'] * 2);
				break;
			case "Blood/II":
				$updatedstatus[$strifers[$t]['ID']] .= "ISOLATED:3|POWERLOSS:1:690|";
				break;
			case "Doom/II":
				if ($strifers[$t]['leader'] == 1) {
					$flatdamage += 50000;
					$updatedstatus[$strifers[$t]['ID']] .= "ISOLATED:2|HOPELESS:2|";
				}
				break;
			case "Rage/II":
				$side = rand(0,255);
				$updatedstatus[$strifers[$t]['ID']] .= "CHARMED:2:$side|";
				break;
			case "Void/II":
				$updatedstatus[$strifers[$t]['ID']] .= "UNSTUCK:5|";
				break;
			case "Space/II":
				$updatedstatus[$strifers[$t]['ID']] .= "BURNING:0:2500|BURNING:0:2500|BURNING:0:2500|BURNING:0:2500|BURNING:0:2500|BURNING:0:2500|";
				$flatdamage += 10000;
				break;
			case "Light/II":
				$damage += $offense;
				$output .= "A critical hit!<br />";
				break;
			default:
				break;
		}
	}
	$effects = explode('|', $strifers[$i]['effects']); //Lastly, check on-hit effects, since we don't want these affecting the outcome of THIS attack.
	$j = 0;
	while(!empty($effects[$j])) { //We've found an effect
		$currenteffect = explode(':', $effects[$j]);
		switch ($effects[$j]) {
			//On-hit effects the attacker produces are processed here
			case "AFFINITY": //Format is AFFINITY:<type>:<percentage>|
				$resistfactor = 1 - (findResist($strifers[$t]['resistances'], $currenteffect[1]) / 100); //1 if resistance is 0, 0.5 if resistance is 50, etc.
				$multiplier = (intval($currenteffect[2]) * $resistfactor) / 100;
				$damage += ($damage * $multiplier);
				break;
			case "RANDAMAGE": //Format is RANDAMAGE:<%variance>|
				$variance = intval($currenteffect[1]);
				$luckfactor = ($strifers[$i]['luck'] / 100) * $variance; //100 luck means you always roll 100 or better. 200 luck means you always roll max.
				if ($luckfactor > ($variance * 2)) $luckfactor = $variance * 2;
				$roll = rand((100 - $variance + $luckfactor),(100 + $variance)) / 100; //Roll a percentage between (100 - variance)% and (100 + variance)%
				$damage = floor($damage * $roll);
				break;
			case "LIFESTEAL": //Format is LIFESTEAL:<%chance>:<%absorbed>|
				$lowestresult = 1 + floor($strifers[$i]['luck'] / 12);
				if ($lowestresult > 100) $lowestresult = 100; //Failsafe: Lowest roll can't be greater than 100
				$roll = rand($lowestresult,100);
				$target = 100 - intval($currenteffect[1]); //Enemy resistance does not affect the CHANCE of lifesteal applying
				if ($roll > $target) {
					$resistfactor = 1 - (findResist($strifers[$t]['resistances'], "Blood") / 100); //Blood resistance applies to lifesteal
					$multiplier = (intval($currenteffect[2]) * $resistfactor) / 100;
					$strifers[$i]['health'] += ($damage * $multiplier);
					$output .= $strifers[$i]['name'] . " drains life from " . $strifers[$t]['name'] . "!<br />";
				}
				break;
			case "KNOCKDOWN": //Format is KNOCKDOWN:<%multiplier>|
				$resistfactor = 1 - (findResist($strifers[$t]['resistances'], "Breath") / 100); //Breath resistance applies to lifesteal
				$effectivedamage = $damage * (intval($currenteffect[1]) / 100) * $resistfactor;
				$target = 100 - (($effectivedamage / $strifers[$t]['maxhealth']) * 400); //25% of the target's max health guarantees a knockdown
				$lowestresult = 1 + floor($strifers[$i]['luck'] / 20); //Luck doesn't affect this roll much
				if ($lowestresult > 100) $lowestresult = 100; //Failsafe: Lowest roll can't be greater than 100
				$roll = rand($lowestresult,100);
				if ($roll > $target) { //Knockdown applied
					$output .= $strifers[$t]['name'] . " is sent flying by the force of " . $strifers[$i]['name'] . "'s blow!<br />";
					$updatedstatus[$strifers[$t]['ID']] .= "KNOCKDOWN:2|"; //This will reduce to 1 in the end of turn status check.
				}
				break;
			case "COMPUTER": //We don't care about this property here, so skip it.
				break;
			default: //Format for "apply the first entry as a status effect": <status string>:<chance>:<resistance>:<message>|
				//The vast majority of on-hit effects will simply fall into this category.
				//<status string> has all :'s replaced with @'s so it stays coherent when we do the explode
				$lowestresult = 1 + floor($strifers[$i]['luck'] / 12);
				if ($lowestresult > 100) $lowestresult = 100; //Failsafe: Lowest roll can't be greater than 100
				$roll = rand($lowestresult,100);
				$resistfactor = 1 - (findResist($strifers[$t]['resistances'], $currenteffect[2]) / 100); //1 if resistance is 0, 0.5 if resistance is 50, etc.
				$target = 100 - (intval($currenteffect[1]) * $resistfactor); //Shrink the application window according to the resistance found
				if ($roll > $target) {
					$currenteffect[0] = str_replace("@", ":", $currenteffect[0]); //Substitute out the @'s in preparation for writing to the target's status
					$updatedstatus[$strifers[$t]['ID']] .= $currenteffect[0] . "|";
					$currenteffect[3] = str_replace("%USER%", $strifers[$i]['name'], $currenteffect[3]);
					$currenteffect[3] = str_replace("%TARGET%", $strifers[$t]['name'], $currenteffect[3]);
					$output .= $currenteffect[3] . "<br />";
				}
				break;
		}
		$j++;
	}
	$damage += $flatdamage;
}
?>