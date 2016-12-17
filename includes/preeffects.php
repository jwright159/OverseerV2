<?php
$i = 1;
$unstuck = false;
while ($i <= $n) {
	$status = explode('|', $strifers[$i]['status']); //First, check statuses for relevance.
	$j = 0;
	while (!empty($status[$j])) { //Status found, let's take a look at it
		$currentstatus = explode(':', $status[$j]); //Expand it out so we can work with it
		switch ($currentstatus[0]) {
			//Each status with a pre-calculation effect has an entry here
			case "TIMESTOP":
				$strifers[$i]['status'] .= "CANTATTACK:1|";
				break;
			case "HOPELESS":
				$roll = rand(1,100);
				if ($roll <= 50) {
					$strifers[$i]['status'] .= "CANTATTACK:1|";
					$output .= $strifers[$i]['name'] . " feels too despondent to attack this round. How sad.<br />";
				}
				break;
			case "GLITCHED":
				$roll = rand(1,100);
				if ($roll <= 20) {
					$strifers[$i]['status'] .= "CANTATTACK:1|";
					$updatedstatus[$strifers[$i]['ID']] .= "GLITCHED:6|";
					$output .= generateGlitchString() . "<br />";
				}
				break;
			case "DISORIENTED":
				$strifers[$i]['teamwork'] = floor($strifers[$i]['teamwork'] / 2);
				break;
			case "DISTRACTED":
				$strifers[$i]['attacks'] -= 1;
				break;
			case "FURY":
				$strifers[$i]['attacks'] += 1;
				break;
			case "KNOCKDOWN":
				$strifers[$i]['status'] .= "CANTATTACK:1|";
				break;
			case "CHARMED": //Format is CHARMED:<duration>:<side>|
				$strifers[$i]['side'] = intval($currentstatus[2]);
				break;
			case "PARALYZED": //Format is PARALYZED:<duration>:<severity>|
				$roll = rand(1,100);
				if ($roll <= intval($currentstatus[2])) {
					$strifers[$i]['status'] .= "CANTATTACK:1|";
					$output .= $strifers[$i]['name'] . " is fully paralyzed!<br />";
				}
				break;
			case "PINATA":
				$strifers[$i]['status'] .= "CANTATTACK:1|CANTDEFEND:1|";
				break;
			case "HASEFFECT": //Format is HASEFFECT:<duration>:<effectstring>|
				//As usual, the effect string uses @ as a separator, which is replaced with : before pasting
				$currentstatus[2] = str_replace('@', ':', $currentstatus[2]);
				//The effect string may contain a status effect string to inflict on a struck enemy.
				//This status effect string will want to contain @
				// Thus we will use & as a separator in that status string, which is replaced with @ before pasting.
				$currentstatus[2] = str_replace('&', '@', $currentstatus[2]);
				$strifers[$i]['effects'] .= $currentstatus[2] . '|';
				break;
			case "HASRESIST": //Format is HASRESIST:<duration>:<effectstring>|
				//As usual, the resist string uses @ as a separator, which is replaced with : before pasting
				$currentstatus[2] = str_replace('@', ':', $currentstatus[2]);
				$strifers[$i]['resistances'] .= $currentstatus[2] . '|';
				break;
			case "ISOLATED":
				$strifers[$i]['teamwork'] = 0;
				break;
			case "UNSTUCK":
				$roll = rand(1,100);
				if ($roll <= 40) {
					$output .= $strifers[$i]['name'] . " is fake as shit this round.<br />";
					unset($strifers[$i]);
					$unstuck = true;
				}
				break;
			case "LUCKBOOST": //Format is LUCKBOOST:<duration>:<amount>|
				$strifers[$i]['luck'] += intval($currentstatus[2]);
				break;
			default:
				break;
		}
		$j++;
	}
	$abilities = explode('|', $strifers[$i]['abilities']); //Then check for relevant abilities. A lot of these will be monster abilities, since
	//they only get the chance to use those once the leader has decided to advance the strife round.
	$j = 0;
	while(!empty($abilities[$j])) { //We've found an ability
		switch ($abilities[$j]) {
			//Each ability with a pre-calculation effect has an entry here.
			case "1": //Passive Aggress (ID 1)
				if ($strifers[$i]['active'] == "AGGRESS") { //Triggers
					$strifers[$i]['teamwork'] += 10;
					$output .= $strifers[$i]['name'] . "'s Lv. 7 Seer ability Passive Aggress activates! They cooperate more effectively with allies this round.<br />";
				}
				break;
			case "3": //Chaotic Assault (ID 3)
				if ($strifers[$i]['active'] == "ASSAULT") { //Triggers
					$minroll = floor(-50 + ($strifers[$i]['luck'] / 2)); //100 luck means no negative roll
					if ($minroll > 150) $minroll = 150;
					$modifier = $strifers[$i]['power'] * (rand($minroll,150) / 100);
					$strifers[$i]['bonuses'] .= "OFFENSE:1:$modifier|";
					$output .= $strifers[$i]['name'] . "'s Lv. 397 Minstreltech Chaotic Assault activates! Their attack power goes haywire.<br />";
				}
				break;
			case "7": //Aspect Fighter (ID 7)
				$strifers[$i]['effects'] .= "AFFINITY:" . $strifers[$i]['aspect'] . ":10|";
				$output .= $strifers[$i]['name'] . "'s Lv. 83 Knightskill Aspect Fighter activates! They gain affinity on their attacks.<br />";
				break;
			case "11": //Blockhead (ID 11)
				$amount = ceil($strifers[$i]['maxpower'] / 33.3); //3% of max power recovered every round if it's drained.
				$updatedstatus[$strifers[$i]['ID']] .= "RECOVERY:1:$amount|";
				$output .= $strifers[$i]['name'] . "'s Level 29 Mindcraft Blockhead activates! A combination of combat focus and stubbornness removes some of the power drain affecting them, if any.<br />";
				break;
			case "17": //Blood Bonds (ID 17)
				$strifers[$i]['teamwork'] += 10;
				$output .= $strifers[$i]['name'] . "'s Lv. 78 Bloodbending Blood Bonds activates! GO GO TEAMWORK!<br />";
				break;
			case "19": //Light's Favour
				$strifers[$i]['luck'] += (5 + floor($strifers[$i]['echeladder'] / 60)); //15% at max rung, 5% minimum.
				//No message. Luck is mysterious. oooooOOOOOooooo!
				break;
			case "-1": //Typheus's fire ability
				//SHOULD be designed to encourage "freedom" in some sense...not sure how.
				$likelihood = 20; //Is used whenever possible, but conditions don't always allow it.
				$cost = 9;
				$roll = rand(1,100);
				if ($roll <= $likelihood && $strifers[$i]['energy'] >= $cost && $strifers[$i]['subaction'] == 0) {
					$strifers[$i]['subaction'] = 1;
					$strifers[$i]['energy'] -= $cost;
					$output .= $strifers[$i]['name'] . " breathes on one of the small flames illuminating the battle. The entire chamber is engulfed in fire!<br />";
					$k = 0;
					$damage = 1000;
					while(!empty($strifers[$k]) && $strifers[$k]['side'] != $strifers[$i]['side']) {
						$strifers[$k]['health'] -= $damage;
						$strifers[$k]['damagetaken'] += $damage;
						$k++;
					}
				}
				break;
			case "-2": //Sophia's self-buff
				//Designed to encourage a speedy takedown
				$output .= $strifers[$i]['name'] . " draws on some inner strength, becoming more powerful.<br />";
				$strifers[$i]['bonuses'] .= "POWER:0:1000|";
				break;
			case "-3": //Hemera's flat damage ability
				//Designed to put a bit of pressure on the Life player
				$likelihood = 25; //Is used whenever possible, but conditions don't always allow it.
				$cost = 13;
				$roll = rand(1,100);
				if ($roll <= $likelihood && $strifers[$i]['energy'] >= $cost && $strifers[$i]['subaction'] == 0) {
					$strifers[$i]['subaction'] = 1;
					$strifers[$i]['energy'] -= $cost;
					$output .= $strifers[$i]['name'] . " reaches up above your head and gives your Health Vial a good flick.<br />";
					$k = 0;
					while(!empty($strifers[$k]) && $strifers[$k]['side'] != $strifers[$i]['side']) {
						$damage = floor($strifers[$k]['maxhealth'] / 6);
						$strifers[$k]['health'] -= $damage;
						$strifers[$k]['damagetaken'] += $damage;
						$k++;
					}
				}
				break;
			case "-4": //Hemera's self-heal
				//Designed to require a longer battle plan
				$likelihood = (1 - ($strifers[$i]['health'] / $strifers[$i]['maxhealth'])) * 100; //More likely to be used at low HP
				$roll = rand(1,100);
				$cost = 20;
				if ($roll <= $likelihood && $strifers[$i]['energy'] >= $cost) { //Ability is effortless, no subaction required.
					$strifers[$i]['energy'] -= $cost;
					$output .= $strifers[$i]['name'] . " closes her eyes for a moment, her wounds beginning to close as well.<br />";
					$heal = floor($strifers[$i]['maxhealth'] / 10);
					$strifers[$i]['health'] += $heal;
				}
				break;
			case "-6": //Abraxas's Hope aura attack
				//Designed to require the Hope player to take Abraxas down fast to avoid taking heavy damage
				$likelihood = ($strifers[$i]['health'] / $strifers[$i]['maxhealth']) * 100; //More likely to be used at high HP
				$cost = 8;
				$roll = rand(1,100);
				if ($roll <= $likelihood && $strifers[$i]['energy'] >= $cost && $strifers[$i]['subaction'] == 0) {
					$strifers[$i]['subaction'] = 1;
					$strifers[$i]['energy'] -= $cost;
					$output .= $strifers[$i]['name'] . " bathes the battle in a brilliant, damaging aura!<br />";
					$k = 0;
					$damage = floor(2000 * ($strifers[$i]['health'] / $strifers[$i]['maxhealth']));
					while(!empty($strifers[$k]) && $strifers[$k]['side'] != $strifers[$i]['side']) {
						$strifers[$k]['health'] -= $damage;
						$strifers[$k]['damagetaken'] += $damage;
						$k++;
					}
				}
				break;
			case "-7": //Cetus's random ability selection
				//Designed to force the Light player to adapt to rapidly changing conditions and fortunes
				$cost = 5; //Cheap to make up for being randomized
				if ($strifers[$i]['energy'] >= $cost && $strifers[$i]['subaction'] == 0) {
					$strifers[$i]['subaction'] = 1;
					$strifers[$i]['energy'] -= $cost;
					$roll = rand(1,100);
					$damage = 0;
					$powerdown = 0;
					$status = "";
					$bonus = "";
					if ($roll == 100) {
						$output .= $strifers[$i]['name'] . " fires a gigantic beam of light from her gaping maw!<br />";
						$damage = rand(5888,8888);
					} elseif ($roll >= 80) {
						$output .= $strifers[$i]['name'] . " thrashes around, injuring you and making it difficult to strike reliably.<br />";
						$damage = rand(1,1000);
						$strifers[$i]['bonuses'] .= "DEFENSE:1:2000|";
					} elseif ($roll >= 60) {
						$output .= $strifers[$i]['name'] . " bends probability, making a mockery of your defensive efforts!<br />";
						$bonus .= "DEFENSE:3:-1888|ACCUSE:3:-1888|ABJURE:3:-1888|ABSTAIN:3:-1888|";
					} elseif ($roll >= 40) {
						$output .= $strifers[$i]['name'] . " creates a brilliant flash of light, blinding you both physically and to the currents of fortune.<br />";
						$powerdown += 88;
						$bonus .= "OFFENSE:3:-888|OFFENSE:1:-888|"; //Large effect this round, moderate for a couple rounds after, small lingering
						$status .= "UNLUCKY:3|";
					} elseif ($roll >= 20) {
						$output .= $strifers[$i]['name'] . " fires a gigantic beam of light from her gaping maw, but it mostly misses.<br />";
						$damage = rand(200,1000);
					} else {
						$output .= $strifers[$i]['name'] . " casts a spell, a light shining from her briefly.<br />";
						$strifers[$i]['brief_luck'] += 10;
					}
					$k = 0;
					while(!empty($strifers[$k]) && $strifers[$k]['side'] != $strifers[$i]['side']) {
						$strifers[$k]['health'] -= $damage;
						$strifers[$k]['damagetaken'] += $damage;
						$strifers[$k]['power'] -= $powerdown;
						$updatedstatus[$strifers[$k]['ID']] .= $status; //Don't want unlucky appearing and affecting before the player can do anything
						$strifers[$k]['bonuses'] .= $bonus;
						$k++;
					}
				}
				break;
			case "-10": //Armok's multi-limbed multiattack. May be possessed by other monsters!
				$cost = 13;
				if ($strifers[$i]['energy'] >= $cost && $strifers[$i]['subaction'] == 0) {
					$strifers[$i]['subaction'] = 1;
					$strifers[$i]['attacks'] += 1;
					$output .= $strifers[$i]['name'] . " strikes out at you with multiple appendages!<br />";
				}
				break;
			case "-11": //Moros's aura of doom
				$output .= $strifers[$i]['name'] . "'s presence draws you slowly, inevitably, towards your doom.<br />";
				$k = 0;
				while(!empty($strifers[$k]) && $strifers[$k]['side'] != $strifers[$i]['side']) {
					$damage = floor($strifers[$k]['maxhealth'] / 40); //Saps 2.5% of max HP per round
					$strifers[$k]['health'] -= $damage;
					$strifers[$k]['damagetaken'] += $damage;
					$k++;
				}
				break;
			default:
				break;
		}
		$j++;
	}
	//Finally, check the fraymotif if it exists.
	if (!empty($strifers[$i]['currentmotif'])) {
		switch($strifers[$i]['currentmotif']) {
			case "Light/I":
				$strifers[$i]['brief_luck'] = 100;
				break;
			case "Time/I":
				$strifers[$i]['attacks'] += 3;
				break;
			case "Time/II":
				$j = 1;
				while ($j <= $n) {
					if ($strifers[$j]['ID'] != $strifers[$i]['ID']) $strifers[$j]['status'] .= "CANTATTACK:1|CANTDEFEND:1|";
					$j++;
				}
				break;
			default:
				break;
		}
	}
	$i++;
}
?>