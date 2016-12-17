<?php
//Include this file whenever a consumable effect is used.
//This file assumes you have three variables ready: $consumeffectstr, $consumuser, and $strifers.
//$strifers is an array with the strife rows of every strifer in the current strife. It will be edited as the effect is processed.
//$consumeffectstr is the string listing all consumable effects to be used, presumably pulled from the item row prior to including this page.
//$consumuser is the ID of the strifer who is using the item
//$targetuser is the ID of the strifer being targeted (may be the same as $consumuser)
//$consumerindex is the consuming strifer's index in the strife array i.e. $strifers[$consumerindex] will return the strife row of the consumer
//$targetindex is the targeted strifer's index in the strife array i.e. $strifers[$targetindex] will return the strife row of the target
//If the user targets another strifer, set $targetuser to the target's ID; otherwise, set it to the user's ID.
//Even if targeting isn't possible, setting the target values to the consumer values is necessary

//NOTE: This file does not create a megaquery or pass changes to status effects, so those should be done after this file is called.

//Common keyword arguments:
//scope - If 0, this will only affect the user. If 1, all strifers on the user's side. If 2, ALL strifers in the strife. If 3, all strifers who are on the target's side. If 4, only the specified target.
//overs - If 1, this effect can raise the modified value to over maximum or lower it to under minimum
//	(e.g. HEALTH with overs set to 1 can overheal if positive or kill if negative)
//message - This message will print if the effect is applicable; it will usually replace %USER% with $consumuser's name and %TARGET% with the current target
//Note that for USEMESSAGE it'll replace both with the user's name.

$consumeffect = explode("|", $consumeffectstr);
$conscount = 0;
$donotconsume = false;
while (!empty($consumeffect[$conscount])) {
	$args = explode(":", $consumeffect[$conscount]);
	switch ($args[0]) {
		case "ACTCHANCE": //Is the chance of the item activating from this point on. Format: ACTCHANCE:<chance>:<failmessage>:<preserve if fail>.
			//Place this tag before any tags that you want to assign a chance of failure. Note that this can also be used to create an item that has a chance of backfiring (e.g. exploding in your face) for example.
			$roll = rand(1,100);
			if ($roll > $args[1]) { //Roll failed
				$conscount = -2; //Effect ends immediately
				$args[2] = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[2]);
				$args[2] = str_replace("%TARGET%", $strifers[$targetindex]['name'], $args[2]);
				if (!empty($args[2])) echo $args[2] . "<br />";
				if ($args[3] == 1) $donotconsume = true;
				else $donotconsume = false; //this will override any previous PRESERVE tags, allowing you to create an item with a chance of breaking when used
			}
			break;
		case "USEMESSAGE": //Item echos a message when used. Format is USEMESSAGE:<message>.
			$args[1] = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[1]);
			$args[1] = str_replace("%TARGET%", $strifers[$targetindex]['name'], $args[1]);
			echo $args[1] . "<br />";
			break;
    case "RANDMESSAGE": //Item echoes a random message among the given strings. Format is RANDMESSAGE:<message1>:<message2>:<message3>:<etc>
      //Can accept any number of arguments, each one has an equal chance of getting picked.
      $a = 0;
      while (!empty($args[$a])) $a++;
      $msg = rand(1,$a-1);
      echo $args[$msg] . "<br />";
      break;
		case "SITUATIONAL": //Item can only be used in a certain situation. Format is SITUATIONAL:<flags>:<denymessage>.
			//Multiple flags are separated with commas. <denymessage> is optional, if not present a default message will be printed
			$deny = false;
			$situations = explode(",", $args[1]);
			$ii = 0;
			while (!empty($situations[$ii])) {
				switch ($situations[$ii]) {
					case "STRIFE": //user must be strifing
						if ($strifers[$consumerindex]['strifeID'] == 0) $deny = true;
						break;
					case "NONSTRIFE": //user cannot be strifing
						if ($strifers[$consumerindex]['strifeID'] != 0) $deny = true;
						break;
					case "DUNGEON": //user must be in a dungeon
						if ($charrow['indungeon'] == 0) $deny = true;
						break;
					case "NONDUNGEON": //user must not be in a dungeon
						if ($charrow['indungeon'] != 0) $deny = true;
						break;
					case "MEDIUM": //user must be in the medium
						if ($charrow['inmedium'] == 0) $deny = true;
						break;
					case "NONMEDIUM": //user must not be in the medium
						if ($charrow['inmedium'] != 0) $deny = true;
						break;
					default:
						break;
				}
				$ii++;
			}
			if ($deny) {
				if ($args[3] == 1) $donotconsume = true;
				//Skip every tag until we hit another SITUATIONAL tag.
				$conscount++;
				while (!empty($consumeffect[$conscount])) {
					$args = explode(":", $consumeffect[$conscount]);
					if ($args[0] == "SITUATIONAL") break;
					$conscount++;
				}
				$args[2] = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[2]);
				$args[2] = str_replace("%TARGET%", $strifers[$targetindex]['name'], $args[2]);
				if ($args[2] = "None");
				elseif (!empty($args[2])) echo $args[2] . "<br />";
				else echo "OAK: " . $charrow['name'] . "! This isn't the time to use that!<br />";
			}
			break;
		case "HEALTH": //Modifies the current health of targets. Format: HEALTH:<amount>:<scope>:<overs>
			foreach($strifers as $st => $strow) { //cycle through each strifer
				if ($strow['health'] > 0) { //don't affect dead guys
					if (($args[2] == 0 && $consumuser == $strow['ID']) || ($args[2] == 1 && $strow['side'] == $strifers[$consumerindex]['side'])
					|| ($args[2] == 2)
					|| ($args[2] == 3 && $strow['side'] == $strifers[$targetindex]['side']) || ($args[2] == 4 && $targetuser == $strow['ID'])) { //this strifer falls within the scope
						$strifers[$st]['health'] += $args[1];
						if ($args[3] != 1) {
							if ($strifers[$st]['health'] > $strifers[$st]['maxhealth'] && $args[1] > 0) //we don't want overheals
							$strifers[$st]['health'] = $strifers[$st]['maxhealth'];
							if ($strifers[$st]['health'] < 1 && $args[1] < 0) //we don't want it to kill
							$strifers[$st]['health'] = 1;
						}
					}
				}
			}
			break;
		case "ENERGY": //Modifies the current energy of targets. Format: ENERGY:<amount>:<scope>:<overs>:<continue if fail>|
			foreach($strifers as $st => $strow) { //cycle through each strifer
				if ($strow['health'] > 0) { //don't affect dead guys
					if (($args[2] == 0 && $consumuser == $strow['ID']) || ($args[2] == 1 && $strow['side'] == $strifers[$consumerindex]['side'])
					|| ($args[2] == 2)
					|| ($args[2] == 3 && $strow['side'] == $strifers[$targetindex]['side']) || ($args[2] == 4 && $targetuser == $strow['ID'])) { //this strifer falls within the scope
						$strifers[$st]['energy'] += $args[1];
						if ($args[3] != 1) {
							if ($strifers[$st]['energy'] > $strifers[$st]['maxenergy'] && $args[1] > 0) //we don't want overenergizes
							$strifers[$st]['energy'] = $strifers[$st]['maxenergy'];
							if ($strifers[$st]['energy'] < 0 && $args[1] < 0) { //we don't want it to endebt the target's energy
								if ($args[4] != 1) { //User needed the energy in order to use the item!
									echo "You don't have enough energy to use that!<br />";
									$donotconsume = true;
									$conscount = -2; //Effect ends immediately
								} else {
									$strifers[$st]['energy'] = 0;
								}
							}
						}
					}
				}
			}
			break;
    case "BONUS": //Adds a string to the bonuses field of the target strifer. Format: BONUS:<bonus string>:<scope>:<resist>:<message>
      foreach($strifers as $st => $strow) { //cycle through each strifer
				if ($strow['health'] > 0) { //don't affect dead guys
					if (($args[2] == 0 && $consumuser == $strow['ID']) || ($args[2] == 1 && $strow['side'] == $strifers[$consumerindex]['side'])
					|| ($args[2] == 2)
					|| ($args[2] == 3 && $strow['side'] == $strifers[$targetindex]['side']) || ($args[2] == 4 && $targetuser == $strow['ID'])) { //this strifer falls within the scope
						$roll = rand(1,100);
						$resisted = surgicalSearch($strow['resistances'], "|" . $args[3], true); //Pull the target's appropriate resistance if any
						$target = 100 - $resisted[1]; //Simplified because chance is always 100 in this case
						if ($roll < $target) {
							$args[1] = str_replace("@", ":", $args[1]); //Substitute out the @'s in preparation for writing to the target's status
							$updatedbonus[$strow['ID']] .= $args[1] . "|";
							if (empty($args[4])) $args[4] = "%TARGET%'s power was affected."; //extremely generic default message
							$args[4] = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[4]);
							$args[4] = str_replace("%TARGET%", $strow['name'], $args[4]);
							echo $args[4] . "<br />";
						}
					}
				}
			}
      break;
		case "CURE": //Removes one or more status effects. Format: CURE:<curestatus>:<scope>:<message>
			//<curestatus> is a list of status tags to remove from the target, separated by commas.
			//Each string is searched directly, so for more complicated searches, use / for | and @ for :.
			//Example: to cure all instances of bleeding and poison on the target: CURE:/POISON@,/BLEEDING@:0:%TARGET% receives first aid.|
			$args[1] = str_replace("/", "|", $args[1]);
			$args[1] = str_replace("@", ":", $args[1]);
			$cures = explode(",", $args[1]);
			foreach($strifers as $st => $strow) { //cycle through each strifer
				if ($strow['health'] > 0) { //don't affect dead guys
					if (($args[2] == 0 && $consumuser == $strow['ID']) || ($args[2] == 1 && $strow['side'] == $strifers[$consumerindex]['side'])
					|| ($args[2] == 2)
					|| ($args[2] == 3 && $strow['side'] == $strifers[$targetindex]['side']) || ($args[2] == 4 && $targetuser == $strow['ID'])) { //this strifer falls within the scope
						$ii = 0;
						while (!empty($cures[$ii])) {
							$has = surgicalSearch($strow['status'], $cures[$ii]); //see if the target has this status
							$iii = 0;
							while (!empty($has[$iii])) {
								$updatedstatus[$strow['ID']] = str_replace(implode(":", $has[$iii]) . "|", "", $updatedstatus[$strow['ID']]); //remove this instance of the status
								$iii++;
							}
							$ii++;
						}
						if ($iii > 0) { //at least one status was removed
							$tempecho = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[3]);
							$tempecho = str_replace("%TARGET%", $strow['name'], $tempecho);
							echo $tempecho . "<br />";
						}
					}
				}
			}
			break;
		case "SPECIAL": //Perform a "special" (hardcoded) effect. Format is SPECIAL:<effectname>:<additional parameters>.
			include("includes/consumeeffects_hard.php"); //Call the hardcoded effect file. It'll do a switch case and execute the effect corresponding to <effectname>.
			break;
		case "PRESERVE": //Tells the consumable not to disappear after the effect is resolved. Has no arguments.
			$donotconsume = true;
			break;
		default: //Format for "apply the first entry as a status effect": <status string>:<scope>:<resistance>:<message>|
			//<status string> has all :'s replaced with @'s so it stays coherent when we do the explode
			//Chance in this case is always treated as 100. For variable chance, use an ACTCHANCE tag prior to the effect tag.
			if (empty($args[3])) { //no message, assume this is an unknown tag
				echo "ERROR: Unrecognized consumable tag " . $args[0] . ".<br />";
				break;
			}
			foreach($strifers as $st => $strow) { //cycle through each strifer
				if ($strow['health'] > 0) { //don't affect dead guys
					if (($args[2] == 0 && $consumuser == $strow['ID']) || ($args[2] == 1 && $strow['side'] == $strifers[$consumerindex]['side'])
					|| ($args[2] == 2)
					|| ($args[2] == 3 && $strow['side'] == $strifers[$targetindex]['side']) || ($args[2] == 4 && $targetuser == $strow['ID'])) { //this strifer falls within the scope
						$roll = rand(1,100);
						$resisted = surgicalSearch($strow['resistances'], "|" . $args[2], true); //Pull the target's appropriate resistance if any
						$target = 100 - $resisted[1]; //Simplified because chance is always 100 in this case
						if ($roll < $target) {
							$args[0] = str_replace("@", ":", $args[0]); //Substitute out the @'s in preparation for writing to the target's status
							$args[0] = str_replace("&", "@", $args[0]); //Some status effects want their own @'s to substitute out!
							$updatedstatus[$strow['ID']] .= $args[0] . "|";
							$args[3] = str_replace("%USER%", $strifers[$consumerindex]['name'], $args[3]);
							$args[3] = str_replace("%TARGET%", $strow['name'], $args[3]);
							echo $args[3] . "<br />";
						}
					}
				}
			}
			break;
	}
	$conscount++;
}

?>