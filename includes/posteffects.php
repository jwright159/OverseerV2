<?php
$i = 1;
while ($i <= $n) {
	//Check out EOT effects in the status field here
	$strifers[$i]['status'] = $updatedstatus[$strifers[$i]['ID']];
	//The above line is necessary so that new status effects aren't applied until AFTER the round is over so they don't care about when during the round
	//they are applied.
	$strifers[$i]['subaction'] = 0; //Everyone's action refreshes.
	$status = explode('|', $strifers[$i]['status']); //Expand out the status string so we can look at it
	$j = 0;
	$newstatus = "";
	$removed = false; //This is set to true if we want this status removed
	while(!empty($status[$j])) { //We've found a status entry
		$currentstatus = explode(':', $status[$j]); //Expand it out so we can work with it
		//Each status with an end of turn effect has an entry in this switch statement. Note that the current strifer is $strifers[$i]
		switch ($currentstatus[0]) {
			case "TIMESTOP": //Format is TIMESTOP:<duration>|
				//If the afflicted strifer is going to be frozen next turn, they can't take a consumable action either.
				if ($currentstatus[1] != "1") $strifers[$i]['subaction'] = 1;
				break;
			case "PINATA": //Format is PINATA:<duration>| (should last forever)
				//Pinatas can't do anything!
				if ($currentstatus[1] != "1") $strifers[$i]['subaction'] = 1;
				break;
			case "PARALYZED": //Format is PARALYZED:<duration>:<severity>|
				$minroll = 1 + floor($strifers[$i]['luck'] / 5);
				$save = rand($minroll,100) + findResist($strifers[$i]['resistances'], "Life"); //life resistance has a chance of aiding with paralysis
				if ($save < $currentstatus[2]) {
					$output .= $strifers[$i]['name'] . "'s paralysis prevents them from acting between rounds!<br />";
					$strifers[$i]['subaction'] = 1;
				}
				break;
			case "FROZEN": //Format is FROZEN:<duration>|
				$minroll = 1 + floor($strifers[$i]['luck'] / 8);
				$save = rand($minroll,100);
				if ($save > 80 || $currentstatus[1] === "1") {	
					$output .= $strifers[$i]['name'] . " thawed out!<br />";
					$removed = true;
				} else {
					$strifers[$i]['subaction'] = 1;	//If the afflicted strifer is going to be frozen next turn, they can't take a consumable action either.
					$output .= $strifers[$i]['name'] . " is frozen solid!<br />";
				}
				break;
			case "HOPELESS": //Format is HOPELESS:<duration>|
				//Hopeless gets a roll to disappear if the strifer manages to deal basic damage
				$save = rand(1,100);
				if (($strifers[$i]['damagedealt'] > 0) && ($save > 50)) {
					$removed = true;
					$output .= $strifers[$i]['name'] . " has managed to regain hope!<br />";
				}
				break;
			case "POISON": //Format is POISON:<duration>:<severity>|. Severity is in thousandths of max health debited per round.
				//Attempt to save first
				$save = rand(1,100) + findResist($strifers[$i]['resistances'], "Doom");
				if ($save >= 95) {
					$removed = true;
					$output .= $strifers[$i]['name'] . " manages to fight off poison!<br />";
				} else {
					$damage = floor((intval($currentstatus[2]) * $strifers[$i]['maxhealth']) / 1000);
					$strifers[$i]['health'] -= $damage;
					$output .= $strifers[$i]['name'] . " loses some health to poison!<br />";
				}
				break;
			case "BLEEDING": //Format is BLEEDING:<duration>:<severity>|. Severity is in thousandths of max health/power debited per round.
				//Attempt to save first
				$save = rand(1,100) + findResist($strifers[$i]['resistances'], "Blood");
				if ($save >= 80) {
					$removed = true;
					$output .= $strifers[$i]['name'] . " manages to staunch a wound!<br />";
				} else {
					$damage = floor((intval($currentstatus[2]) * $strifers[$i]['maxhealth']) / 1000);
					$strifers[$i]['health'] -= $damage;
					$powerloss = floor((intval($currentstatus[2]) * $strifers[$i]['maxpower']) / 1000);
					$strifers[$i]['power'] -= $powerloss;
					$output .= $strifers[$i]['name'] . " loses some blood or blood analogue!<br />";
				}
			case "BURNING": //Format is BURNING:<duration>:<damage>|. Does <damage> damage every round.
				//Attempt to save first
				$save = rand(1,100) + (findResist($strifers[$i]['resistances'], "Rage") / 2);
				if ($save > 75) {
					$removed = true;
					$output .= $strifers[$i]['name'] . " stops, drops, and rolls around a bit, extinguishing itself somewhat.<br />";
				} else {
					$firedamage = intval($currentstatus[2]);
					$distaction = rand(1,100) + findResist($strifers[$i]['resistances'], "Mind");
					if ($distaction < (($firedamage / $strifers[$i]['health']) * 100)) {
						$output .= $strifers[$i]['name'] . " is on fire and panicking!<br />";
						$newstatus .= "DISTRACTED:1|";
					} else {
						$output .= $strifers[$i]['name'] . " is on fire! Oh no!<br />";
					}
					$strifers[$i]['health'] -= $firedamage;
				}
				break;
			case "IRRADIATED": //Format is IRRADIATED:<duration>:<severity>|. Does <severity> damage and drains <severity> power every round.
				//No save for radiation poisoning once you have it
				$severity = intval($currentstatus[2]);
				$strifers[$i]['health'] -= $severity;
				$strifers[$i]['power'] -= $severity;
				break;
			case "DELAYED": //Format is DELAYED:<duration>:<status string>|
				if ($currentstatus[1] == "1") { //Delay is over
					$toadd = str_replace("@", ":", $currentstatus[2]);
					$toadd = str_replace("&", "@", $currentstatus[2]);
					$newstatus .= $toadd . "|";
				}
				break;
			case "SELFINFLICT": //Format is SELFINFLICT:<duration>:<status string>:<chance>:<resist>:<message>|
				$save = rand(1,100) + (findResist($strifers[$i]['resistances'], $currentstatus[4]));
				$chance = intval($currentstatus[3]);
				if ($save <= $chance) {
					$toadd = str_replace("@", ":", $currentstatus[2]);
					$toadd = str_replace("&", "@", $currentstatus[2]);
					$newstatus .= $toadd . "|";
					$output .= $strifers[$i]['name'] . $currentstatus[5] . "<br />"; //<message> is printed directly after the player's name.
				}
				break;
			case "UNLUCKY":
				$minroll = 1 + floor($strifers[$i]['luck'] / 10); //-200 or more means you can't throw off this debuff. hehehehehe.
				$misfortune = rand($minroll,100);
				if ($misfortune == 1) { //Natural 1. Oh dear!
					if ($strifers[$i]['owner'] == 0) { //NPC
						$output .= $strifers[$i]['name'] . " spontaneously winks out of existence. How very unfortunate!<br />";
						$newstatus .= "PINATA:0|";
						$strifers[$i]['power'] = 0;
						$strifers[$i]['health'] = 1;
					} else { //PC
						$output .= $strifers[$i]['name'] . " has been unfortuitously felled!";
						$strifers[$i]['health'] = -888888; //SUCKA BE DEAD
					}
				} elseif ($misfortune >= 2) { //This becomes a lot more likely if the target has negative luck
					$output .= $strifers[$i]['name'] . " is struck by lightning!<br />";
					$newstatus .= "DISORIENTED:2|KNOCKDOWN:1|BURNING:8:8888|";
					$strifers[$i]['health'] -= 88888; //Yes, this is a lot of damage.
				} elseif ($misfortune >= 10) {
					$output .= "The ground gives way beneath " . $strifers[$i]['name'] . " and it plummets, shaking it up pretty badly when it lands.<br />";
					$newstatus .= "KNOCKDOWN:2|";
					$strifers[$i]['health'] -= 8888;
					$strifers[$i]['power'] -= 888;
				} elseif ($misfortune >= 20) {
					$output .= "A meteor hits " . $strifers[$i]['name'] . ". What are the chances?<br />";
					$strifers[$i]['health'] -= 10000;
				} elseif ($misfortune >= 50) {
					$output .= $strifers[$i]['name'] . " trips and falls. Hilarious!<br />";
					$newstatus .= "ENRAGED:1|";
					$strifers[$i]['health'] -= floor($strifers[$i]['health'] / 100);
					$strifers[$i]['power'] -= floor($strifers[$i]['power'] / 100);
				} elseif ($misfortune >= 80) {
					//No misfortune this round
				} else { //Managed to throw off the unluck effect
					$output .= $strifers[$i]['name'] . " appears less unlucky. This concept is just as visually nebulous as the idea that it appeared unlucky in the first place.<br />";
					$removed = true;
				}
				break;
			case "REGENERATION": //Format is REGENERATION:<duration>:<quantity>|
				$strifers[$i]['health'] += intval($currentstatus[2]);
				break;
			case "ENERGIZED": //Format is ENERGIZED:<duration>:<quantity>|
				$strifers[$i]['energy'] += intval($currentstatus[2]);
				break;
			case "RECOVERY": //Format is RECOVERY:<duration>:<quantity>|
				$strifers[$i]['power'] += intval($currentstatus[2]); //Note that if this goes over maxpower it'll be brought back down.
				//So this isn't really a power boost.
				break;
			case "BOOSTDRAIN": //Format is POWERDRAIN:<duration>:<amount>|
				$j = 1;
				while ($j <= $n) {
					//Check that the strifer is not an ally and does have some bonuses to look at
					if ($strifers[$j]['side'] != $strifers[$i]['side'] && $strifers[$j]['bonuses'] != "") {
						$newbonus = "";
						$offensedrain = intval($currentstatus[2]); //Set up the amounts to be drained
						$defensedrain = intval($currentstatus[2]);
						$bonuses = explode("|", $strifers[$j]['bonuses']);
						$k = 0;
						while (!empty($bonuses[$k])) { //We still have a bonus to evaluate
							if (($offensedrain + $defensedrain) > 0) { //We still have drain to apply
								//A note on format: <Bonus type>:<duration>:<Bonus value>| is the standard format for bonuses.
								$removed = false;
								$currentbonus = explode(":", $bonuses[$k]);
								$value = intval($currentbonus[2]);
								switch ($currentbonus[0]) {
									case "POWER":
										if ($value > min($offensedrain,$defensedrain)) { //Enough power boost to soak up the entire reduction
											$value -= min($offensedrain,$defensedrain);
											$offensedrain -= min($offensedrain,$defensedrain);
											$defensedrain -= min($offensedrain,$defensedrain);
										} else { //Reduction removes this power boost. DELETE!
											$removed = true;
											$offensedrain -= $value;
											$defensedrain -= $value;
										}
										break;
									case "OFFENSE":
										if ($value > $offensedrain) {
											$value -= $offensedrain;
											$offensedrain = 0;
										} else {
											$removed = true;
											$offensedrain -= $value;
										}
										break;
									case "DEFENSE":
										if ($value > $defensedrain) {
											$value -= $defensedrain;
											$defensedrain = 0;
										} else {
											$removed = true;
											$defensedrain -= $value;
										}
										break;
									default: //We only care about those three.
										break;
								}
								$currentbonus[2] = strval($value);
								if (!$removed) $newbonus .= implode(':', $currentbonus) . "|";
							} else { //Just append it to the new bonus string.
								$newbonus .= $bonuses[$k] . "|";
							}
							$k++;
						}
						$strifers[$j]['bonuses'] = $newbonus;
					}
					$j++;
				}
				break;
			case "POWERLOSS": //A special status effect that is applied during the strife round to reduce power without
							  //affecting the target's remaining attacks this round.
				$strifers[$i]['power'] -= intval($currentstatus[2]);
				$removed = true; //Paranoia. Client-side this status effect doesn't exist! We don't want it to ever try to appear.
			case "POWERGAIN": //A special status effect that is applied during the strife round to increase power without
							  //affecting the target's remaining attacks this round.
				$strifers[$i]['bonuses'] .= "POWER:0:" . $currentstatus[2] . "|";
				$removed = true; //Paranoia. Client-side this status effect doesn't exist! We don't want it to ever try to appear.
			default:
				break;
		}
		if ($currentstatus[1] == "1") $removed = true; //Duration 1: This status just expired
		if (!$removed) { //Only run the code to reassemble and reattach the status if it still exists
			if ($currentstatus[1] == "0") { //Duration 0: This status lasts for the entire strife. Return it as-is
				$newstatus .= implode(':', $currentstatus) . "|";
			}
			if ($currentstatus[1] != "1" && $currentstatus[1] != "0") { //Duration > 1: This status loses a turn
				$currentstatus[1] = strval(intval($currentstatus[1] - 1));
				$newstatus .= implode(':', $currentstatus) . "|";
			}
		}
		$j++;
	}
	$strifers[$i]['status'] = $newstatus;
	//We now do pretty much the exact same thing, except with the bonuses field.
	//The two fields are separate so we don't end up having to check combat bonuses everywhere we look for status effects, and can
	//limit looking at the bonus field to here and the power calculating functions
	$bonus = explode('|', $strifers[$i]['bonuses']); //Expand out the bonus string so we can look at it
	$j = 0;
	$newbonus = "";
	$removed = false; //This is set to true if we want this bonus removed
	while(!empty($bonus[$j])) { //We've found a bonus entry
		$currentbonus = explode(':', $bonus[$j]); //Expand it out so we can work with it
		//We don't need to check the bonus field for EOT effects. Nothing that goes in there has EOT effects.
		if ($currentbonus[1] == "1") $removed = true; //Duration 1: This bonus just expired
		if (!$removed) { //Only run the code to reassemble and reattach the bonus if it still exists
			if ($currentbonus[1] == "0") { //Duration 0: This bonus lasts for the entire strife. Return it as-is
				$newbonus .= implode(':', $currentbonus) . "|";
			}
			if ($currentbonus[1] != "1" && $currentbonus[1] != "0") { //Duration > 1: This status loses a turn
				$currentbonus[1] = strval(intval($currentbonus[1] - 1));
				$newbonus .= implode(':', $currentbonus) . "|";
			}
		}
		$j++;
	}
	$strifers[$i]['bonuses'] = $newbonus;
	//Now we do it again, but with the abilities to check if any of them have EOT triggers.
	$abilities = explode('|', $strifers[$i]['abilities']); //Expand out the bonus string so we can look at it
	$j = 0;
	while (!empty($abilities[$j])) {
		switch($abilities[$j]) {
			case "12": //Battle Fury (ID 12)
				if ($strifers[$i]['damagetaken'] > 0) { //Strifer took damage in combat this round: Battle Fury triggers
					$offenseboost = ($strifers[$i]['damagetaken'] / 8);
					if ($offenseboost > ($strifers[$i]['maxpower'] / 8)) $offenseboost = ($strifers[$i]['maxpower'] / 8);
					$offenseboost = ceil($offenseboost);
					$strifers[$i]['bonuses'] .= "OFFENSE:1:$offenseboost|"; //This happens after the check on bonus durations
					$output .= $strifers[$i]['name'] . "'s Lv. 67 Angerbility Battle Fury activates! Taking damage makes them...well, angry.<br />";
				}
				break;
			case "-8": //Metis's post-round ability.
				$cost = 7;
				if ($strifers[$i]['subaction'] == 0 && $strifers[$i]['energy'] >= $cost) { //Didn't use another action, so we can do this.
					$k = 0;
					$enemies = 0;
					while(!empty($strifers[$k])) {
						if ($strifers[$k]['side'] != $strifers[$i]['side']) $enemies++;
					}
					$weighting = 22222; //To give a sense of scale to Metis's decisions.
					//Wants to reduce high offense values and further cripple high defense values.
					//Will look into disabling the subaction if it was used last round.
					//If no value is greater than zero, Metis decides to save her energy for later rounds.
					$actiondisable = $strifers[$i]['subactiondata'] * ($weighting / 3);
					$offensedown = $strifers[$i]['offensedata'] - ($weighting * $enemies);
					$defensedown = ($weighting * $enemies) - $strifers[$i]['defensedata'];
					$decision = max($actiondisable,$offensedown,$defensedown);
					if ($decision > 0) {
						$strifers[$i]['energy'] -= $cost;
						switch($decision) {
							//NOTE: If two of these are equal, the order presented here is the order of priority they have.
							case $offensedown:
								$output .= $strifers[$i]['name'] . " lashes out mentally, impairing the offense of opposition!<br />";
								while(!empty($strifers[$k])) {
									if ($strifers[$k]['side'] != $strifers[$i]['side']) $strifers[$k]['bonuses'] .= "OFFENSE:0:-413|OFFENSE:3:-413|";
								}
								break;
							case $defensedown:
								$output .= $strifers[$i]['name'] . " lashes out mentally, impairing the defense of opposition!<br />";
								while(!empty($strifers[$k])) {
									if ($strifers[$k]['side'] != $strifers[$i]['side']) $strifers[$k]['bonuses'] .= "DEFENSE:0:-413|DEFENSE:3:-413|";
								}
								break;
							case $actiondisable:
								$output .= $strifers[$i]['name'] . " lashes out mentally, preventing opponents from taking complex actions!<br />";
								while(!empty($strifers[$k])) {
									if ($strifers[$k]['side'] != $strifers[$i]['side']) $strifers[$k]['subaction'] = 1;
								}
								break;
							default:
								break;
						}
					}
				}
				break;
			case "-9": //Armok summons a slave. Guaranteed one per round. Won't "exist" until next round.
				$output .= $strifers[$i]['name'] . " summons an ally from realms unknown!<br />";
				$enemylist = array(0 => "Urist McStrifer");
				$enemies = generateEnemies($enemylist,$strifers[$i]['strifeID'],$connection,"Event",0);
				break;
			default:
				break;
		}
		$j++;
	}
	//Now we do it again, but with the fraymotif! If the strifer has an active fraymotif we look for any EOT effects it may have.
	//NOTE - This code does NOT handle stopping the fraymotif. That happens after the KO code.
	//NOTE - Bonuses and statuses applied here will NOT decrement, since that happened above. They should be set at the intended duration.
	if (!empty($strifers[$i]['currentmotif'])) {
		switch($strifers[$i]['currentmotif']) {
			case "Heart/I":
				$strifers[$i]['bonuses'] .= "POWER:33:1500|"; //Ongoing bonus of 1500 applied as it wears off
				break;
			case "Life/I":
				$j = 1;
				while ($j <= $n) {
					if ($strifers[$j]['side'] == $strifers[$i]['side']) $strifers[$j]['health'] += ceil($strifers[$j]['maxhealth'] * 0.413);
					$strifers[$i]['health'] += ceil($strifers[$i]['maxhealth'] * 0.587); //So it adds up to *1 for the user
					$j++;
				}
				$output .= $strifers[$i]['name'] . "'s fraymotif rejuvenates them and their allies!<br />";
				break;
			case "Rage/I":
				$strifers[$i]['bonuses'] .= "OFFENSE:1:420|OFFENSE:2:420|OFFENSE:3:420|OFFENSE:4:420|OFFENSE:5:420|";
				break;
			case "Life/II";
				$j = 1;
				while ($j <= $n) {
					if ($strifers[$j]['side'] == $strifers[$i]['side']) $strifers[$j]['health'] += 1025413612; //An obscene amount
					$j++;
				}
				$output .= $strifers[$i]['name'] . "'s fraymotif infuses all allies with Life!<br />";
				break;
			case "Mind/II";
				$strifers[$i]['bonuses'] .= "POWER:33:2000|"; //Ongoing boost of 2000 applied as it wears off.
				break;
			default:
				break;
		}
	}
	$i++;
}
//NOTE - We have a new loop so that post-round effects can save allies without it depending on their order in the table.
//...and also so that enemyslain works properly.
$i = 1;
$enemyslain = false;
$moonprince = 0;

//health gel stats
$gelchance=15; //% chance of the first health gel dropping, to be divided by 2 each drop
$topgel=3; //max number of dropped gels
$healthfraction=10; //top% of health to be restored per gel unit
$toheal=0; //to be applied later
$starthealth=$striferow['health'];
while ($i <= $n) {
	if ($strifers[$i]['health'] > $strifers[$i]['maxhealth']) $strifers[$i]['health'] = $strifers[$i]['maxhealth']; //Ensure strifer is not above their maximum
	if ($strifers[$i]['power'] > $strifers[$i]['maxpower']) $strifers[$i]['power'] = $strifers[$i]['maxpower']; //Also ensure they're not above their max power
	if ($strifers[$i]['power'] < 0) $strifers[$i]['power'] = 0; //Power should not drop below 0.
	if ($strifers[$i]['health'] <= 0) { //This strifer has been KOed
		$strifers[$i]['health'] = 1; //Set their health to 1 immediately so nothing dumb happens.
		if ($strifers[$i]['owner'] != 0 && $strifers[$i]['aspect'] != "") { //This strifer is a player character!
			//Set character details about that player being KOed here. (Set them to being down, etc)
			setAchievement($charrow, 'ko'); //ko achievement
			$koresult = mysqli_query($connection, "SELECT * FROM Characters WHERE Characters.ID = " . $strifers[$i]['owner'] . " LIMIT 1;");
			$korow = mysqli_fetch_array($koresult);
			if (strpos($strifers[$i]['description'], "dreamself") !== false) { //Dreamself was KOed
				$newfatigue = $korow['dreamfatigue'] + 100;
				mysqli_query($connection, "UPDATE Characters set dreamfatigue = $newfatigue WHERE Characters.ID = " . $strifers[$i]['owner'] . " LIMIT 1;");
			} else { //Waking self was KOed
				$newfatigue = $korow['fatigue'] + 100;
				$olddungeonrow = $korow['olddungeonrow'];
				$olddungeoncol = $korow['olddungeoncol'];
				mysqli_query($connection, "UPDATE Characters set fatigue = $newfatigue, dungeonrow = $olddungeonrow, dungeoncol = $olddungeoncol WHERE Characters.ID = " . $strifers[$i]['owner'] . " LIMIT 1;");
			}
			if ($strifers[$i]['leader'] != 0) { //This strifer was the leader! We'll need to try and find another one
				$j = 0;
				$anotherplayer = false;
				while ($j <= $n && !$anotherplayer) {
					if ($strifers[$j]['side'] == $playerside && $strifers[$j]['Aspect'] != "") { //Found one
						$strifers[$j]['leader'] = 1;
						$anotherplayer = true;
					}
					$j++;
				}
				if (!$anotherplayer) { //That was the last player character in the strife on that side. Remove that side from the strife.
					$j = 0;
					while ($j <= $n) {
						if ($strifers[$j]['side'] == $playerside) {
							$strifers[$j] = endStrife($strifers[$j]);
						}
						$j++;
					}
				}
			}
			$strifers[$i] = endStrife($strifers[$i]);
		} elseif ($strifers[$i]['side'] == $playerside) { //An ally has been slain
			$health = $strifers[$i]['maxhealth'];
			$energy = $strifers[$i]['maxenergy'];
			$power = $strifers[$i]['maxpower'];
			$strifers[$i]['health'] = $health;
			$strifers[$i]['energy'] = $energy;
			$strifers[$i]['power'] = $power;
			mysqli_query($connection, "UPDATE `Strifers` SET `health` = $health, `energy` = $energy, `power` = $power WHERE `Strifers`.`ID` = " . $strifers[$i]['ID'] . " LIMIT 1;");
			$strifers[$i] = endStrife($strifers[$i]);
			//Code to handle allies being KOed goes in here.
		} else { //An enemy has been slain
			$enemyslain = true;
			if ($strifers[$i]['owner'] == 0) { //The enemy is an NPC. Give loot to the current player and remove their strife row. Also report their death.
				//On-death effects go here. Just loot for now. NOTE - Loot goes to the person executing the resolution i.e. the leader!
				//Better hope they know how to share.
				$output .=  "The ";
				if ($strifers[$i]['grist'] != "None") $output .=  $strifers[$i]['grist'] . " ";
				$output .=  $strifers[$i]['name'] . " is defeated!<br />";
				if($strifers[$i]['name']=="Kraken") setAchievement($charrow,'dungeon1');
				if($strifers[$i]['name']=="Hekatonchire") setAchievement($charrow,'dungeon2');
				if($strifers[$i]['name']=="Lich Queen") setAchievement($charrow,'dungeon3');
				if($strifers[$i]['name']=="Construct New Building"){
					$moonprince = $moonprince+1;
				}
				if($strifers[$i]['name']=="Royal Assassin"){
					$moonprince = $moonprince+1; 
				}
				$ondeath = explode('|', $strifers[$i]['ondeath']); //Expand out the status string so we can look at it
				$j = 0;
				$enemynumber+=1;
				$lootstr = "It drops: ";
				while(!empty($ondeath[$j])) { //We are looking at an on-death effect
					$current = explode(":", $ondeath[$j]);
					switch ($current[0]) { //Look for what effect this is
						case "GRIST": //Format: GRIST:<Grist type>:<Amount>:<Chance of this drop>|
							if (rand(0,100) <= intval($current[3])) {
								$charrow['grists'] = modifyGrist($charrow['grists'], $current[1], intval($current[2]));
								$lootstr .= $current[2] . "<img src='" . gristImage($current[1]) . "' title='$current[1]' width='24' height='24'>, ";
							}
							break;
						case "PRISMATICGRIST": //Format: PRISMATICGRIST:<Amount>|
							$charrow['grists'] = modifyGrist($charrow['grists'], "Polychromite", intval($current[1]));
							$charrow['grists'] = modifyGrist($charrow['grists'], "Rainbow", intval($current[1]));
							$charrow['grists'] = modifyGrist($charrow['grists'], "Plasma", intval($current[1]));
							$charrow['grists'] = modifyGrist($charrow['grists'], "Opal", intval($current[1]));
							$lootstr .= $current[1] . "<img src='/images/grist/Polychromite.gif' title='Polychromite' width='24' height='24'>, ";
							$lootstr .= $current[1] . "<img src='/images/grist/Rainbow.gif' title='Rainbow' width='24' height='24'>, ";
							$lootstr .= $current[1] . "<img src='/images/grist/Plasma.gif' title='Plasma' width='24' height='24'>, ";
							$lootstr .= $current[1] . "<img src='/images/grist/Opal.gif' title='Opal' width='24' height='24'>, ";
							break;
						case "BOONDOLLARS": //Format: BOONDOLLARS:<minimum>:<maximum>|
							$boonies = intval($current[1]) + floor((rand(0,1000) * (intval($current[2]) - intval($current[1]))) / 1000);
							$charrow['boondollars'] += $boonies;
							$lootstr .= "$boonies Boondollars, ";
							break;
						case "ITEM": //Format: ITEM:<ID>:<Metadata>|. Metadata separated by @'s. addItem adds the "1" by default, so metadata can be empty.
							require_once("includes/additem.php"); //Necessary for item drops
							$metadata = str_replace("@", ":", $current[2]);
							$itemcreate = addItem($charrow,intval($current[1]),$metadata);
							$itemname = itemName($current[1], $connection);
							if ($itemcreate) { //Item creation succeeded
								$lootstr .= $itemname . "(which you captchalogue), ";
							} else {
								setAchievement($charrow, 'itemfull'); //achievement
								$lootstr .= $itemname . "(which you don't have room for so you leave it behind), ";
							}
							break;
						case "BATTLEFIELD": //Format: BATTLEFIELD:<value>|. Enemy is part of the Dersite army, and killing them weakens it.
							$sessionresult = mysqli_query($connection, "SELECT battlefield_power FROM Sessions WHERE Sessions.ID = " . $charrow['session'] . " LIMIT 1;");
							$sessionrow = mysqli_fetch_array($sessionresult);
							sumStat($charrow, 'battlefield',intval($current[1]));
							$sessionrow['battlefield_power'] -= intval($current[1]);
							if ($sessionrow['battlefield_power'] < 0) $sessionrow['battlefield_power'] = 0;
							mysqli_query($connection, "UPDATE Sessions SET battlefield_power = " . $sessionrow['battlefield_power'] . " WHERE Sessions.ID = " . $charrow['session'] . " LIMIT 1;");
							$lootstr .= 'the power of the Dersite Army (where by "drops" we mean "decreases", and by that we mean not at all because the Black King hasn\'t been awoken by the "devs" yet, whatever that means), ';
							break;
						case "SPECIAL": //Miscellaneous on-death events.
							switch ($current[1]) {
								case "Denizen":
									setAchievement($charrow, 'denizen');
									notifySession($charrow, $charrow['name'] . " has defeated their Denizen!");
									$lootstr = "You have defeated your Denizen, granting you access to the Battlefield.<br />";
									mysqli_query($connection, "UPDATE `Characters` SET `denizendown` = 1 WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
									break;
								default:
									break;
							}
							break;
						default:
							break;
					}
					$j++;
				}
				//HEALTH GEL CODE

				if (rand(0,100)<$gelchance && $topgel>0){
					$gelchance=$gelchance/2; //it becomes exponentially less likely to get more units
					$topgel=$topgel-1;

					if($charrow['dreamingstatus']!='Prospit'){
						$lootstr .= 'and some Vitality Gel cubes., '; //using 'and' in both because it'll always happen at the end of the string
					}else{
						$lootstr .= 'and a prospitian kindly offers you a refreshing glass of home-made lemonade for a job well done!, ';
					}

					$keys= array_keys($strifers);
					$firstkey = array_shift($keys);
					$lead = $strifers[$firstkey];


					if($charrow['dreamingstatus']!='Awake') $powerdifference=($strifers[$i]['power']*1.5)/$lead['power']; //acounting for lack of equipment and passive class bullshit
					elseif($charrow['dungeon']!=0){
						$healthfraction=15;
						$powerdifference=($strifers[$i]['power']*1.5)/$lead['power']; //buff to dungeon health gel to make up for wild power differences
					} else $powerdifference=($strifers[$i]['power'])/$lead['power'];
					if($powerdifference>1) $powerdifference = 1; //you get minimum penalty if your power is lower than the enemy's, so you'll recover $healthfraction
					elseif($powerdifference>0.90 && $powerdifference<0.95) $powerdifference*=0.75; //penalties
					elseif($powerdifference>0.80) $powerdifference*= 0.50;  
					elseif($powerdifference<=0.80) $powerdifference*= 0.1;  //this is so much more complicated than it needs to be

					$toheal=$toheal+$healthfraction*$powerdifference;

					$finalhealth = floor($lead['maxhealth']/(100/$toheal));
					if($finalhealth < 10) $finalhealth=10;
					if($lead['health']+$finalhealth > $lead['maxhealth'])
						$lead['health'] = $lead['maxhealth'];
					else $lead['health'] +=$finalhealth;

					$strifers[$firstkey]=$lead;
				}

				//END OF HEALTH GEL CODE
				

				$lootstr = substr($lootstr, 0, -2);
				if ($lootstr == "It drops") $lootstr = "It drops: nothing!";
				$output .=  $lootstr . "<br />";
				if ($strifers[$i]['power'] > $charrow['enemiesbeaten']) { //This is the strongest enemy the leader has ever taken out!
					mysqli_query($connection, "UPDATE `Characters` SET `enemiesbeaten` = " . $strifers[$i]['power'] . " WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1");
				}
				mysqli_query($connection, "DELETE FROM `Strifers` WHERE `Strifers`.`ID` = " . $strifers[$i]['ID'] . " LIMIT 1;");
				unset($strifers[$i]); //Remove their row from the database, then zap it out of the current combat array.
			} else { //Since player deaths are picked out above, this should never happen.
			}
			//We'll need to hand out Echeladder rungs in here
		}
		//NOTE - If we're killing off the strifer, this is where we do it. 
	} elseif ($strifers[$i]['strifeID'] != 0 && !empty($strifers[$i]['strifeID'])) { //Make sure the strifer is still involved in strife.
		$exists[$strifers[$i]['side']] = true; //This strifer's side of the strife still has a representative since they were not KOed this round
	}
	//Work with fraymotif values here, since they can affect everything up to this point, including being KOed.
	if (!empty($strifers[$i])) { //Don't add values to a strifer that doesn't exist or it confuses the megaquery code
		$strifers[$i]['currentmotif'] = "";
		$strifers[$i]['teammotif'] = 0;
	}
	$i++;
}
if ($enemyslain) {
	mysqli_query($connection, "UPDATE `Characters` SET
	`grists` = '$charrow[grists]', `inventory` = '$charrow[inventory]', `metadata` = '$charrow[metadata]', `boondollars` = '$charrow[boondollars]'
	WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1;"); //Update these characteristics if an enemy was slain this turn.
}
if (count($exists) == 1 && !$unstuck) { //There is only one side left in the strife. Strife is over!
	$j = 1;
	$strifeID = 0;
	while ($j <= $n) {
		if (!empty($strifers[$j])) {//Don't add values to a strifer that doesn't exist or it confuses the megaquery code
			if ($strifeID == 0) $strifeID = $strifers[$j]['strifeID']; //Save off strife ID for working with dungeon string.
			if ($strifers[$j]['owner'] == 0 && $strifers[$j]['persist'] == 0) { //No "owner" and not persisted. Strifer will be removed.
				mysqli_query($connection, "DELETE FROM `Strifers` WHERE `Strifers`.`ID` = " . $strifers[$j]['ID'] . " LIMIT 1;");
			} elseif ($strifers[$j]['persist'] == 0) { //Persisters should hold on to their strife ID so they can be dialed up later
				$strifers[$j] = endStrife($strifers[$j]);
			}
		}
		$j++;
	}
	if ($exists[$playerside]) { //Player victory!
		$output .= "You're a winner!<br />";
		$j = 0;
		while ($j <= $n) {
			if (!empty($strifers[$j])) {
				//In here we iterate over all strifers and grant rungs to any who need them.
				if ($strifers[$j]['side'] == $playerside && $strifers[$j]['echeladder'] != 0) { //A winning strifer who has an Echeladder value
					if ($strifers[$j]['owner'] != 0) { //Strifer is a player
						$allyresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`ID` = " . $strifers[$j]['owner'] . " LIMIT 1;");
						$allyrow = mysqli_fetch_array($allyresult);
						$output .= gainRungs($allyrow, 1);
						if($strifers[$j]['leader']==0) sendAchievement(getChar($strifers[$j]['owner']), 'assist');
					}
				} else { //Non-player strifer. Heal to max health, power, and energy.
					$health = $strifers[$j]['maxhealth'];
					$energy = $strifers[$j]['maxenergy'];
					$power = $strifers[$j]['maxpower'];
					$strifers[$j]['health'] = $health;
					$strifers[$j]['energy'] = $energy;
					$strifers[$j]['power'] = $power;
					mysqli_query($connection, "UPDATE `Strifers` SET `health` = $health, `energy` = $energy, `power` = $power WHERE `Strifers`.`ID` = " . $strifers[$j]['ID'] . " LIMIT 1;");
				}
			}
			$j++;
		}
		if ($charrow['dungeon'] != 0 && $charrow['dreamingstatus'] == "Awake") { //Player is in a dungeon. Take out the strife instance they just beat.
			$output .= "You have cleared out the dungeon room!<br />";
			$dungeonresult = mysqli_query($connection, "SELECT * FROM Dungeons WHERE ID = " . strval($charrow['dungeon']));
			$dungeonrow = mysqli_fetch_array($dungeonresult);
			$row = $charrow['dungeonrow'];
			$col = $charrow['dungeoncol'];
			$currentroom = $col . "," . $row;
			$newencstr = $currentroom . ":EXISTS:" . strval($strifeID);
			$dungeonrow['enc'] = str_replace($newencstr, "", $dungeonrow['enc']);
			$newencstr = $currentroom . ":EXISTS:BOSS:" . strval($strifeID);
			$dungeonrow['enc'] = str_replace($newencstr, "", $dungeonrow['enc']);
			mysqli_query($connection,"UPDATE Dungeons SET enc = '" . $dungeonrow['enc'] . "' WHERE Dungeons.ID = " . $dungeonrow['ID'] . " LIMIT 1;");
		}
	} else { //Player has been defeated
		$output .= "You're a loser!<br />";
		//Probably just print a defeat message in here
		if ($charrow['dreamingstatus'] == "Awake" && $charrow['dungeon'] != 0) { //Waking self was KOed
			echo "You are forced back into the room you just left! <a id='advance' href='dungeons.php'>Return to dungeon ==></a><br />";
			$olddungeonrow = $charrow['olddungeonrow'];
			$olddungeoncol = $charrow['olddungeoncol'];
			mysqli_query($connection, "UPDATE Characters set dungeonrow = $olddungeonrow, dungeoncol = $olddungeoncol WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
		}
	}
	if ($charrow['dreamingstatus'] == "Awake" && $charrow['dungeon'] != 0) echo "<a id='advance' href='dungeons.php'>Return to dungeon ==></a><br />";
	$i++;
}
?>