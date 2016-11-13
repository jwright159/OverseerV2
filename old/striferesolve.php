<?php
$pagetitle = "Strife!";
$headericon = "/images/header/rancorous.png";
require_once("header.php");
require_once("includes/strifefunctions.php"); //This file contains powerCalc, which takes a strife row and returns an array containing offense and defense
require_once("includes/glitches.php"); //For printing glitchy shit
//The above file also contains buildMegaquery, which makes the database updating query at the end of the file

?>
<script type="text/javascript">
 document.onkeydown=function(evt){
	var keyCode = evt ? (evt.which ? evt.which : evt.keyCode) : event.keyCode;
   switch(keyCode){
   	  //spacebar or enter
   	  case 13:
        evt.preventDefault();
        document.getElementById("advance").click();
      break;
      case 32:
        evt.preventDefault();
        document.getElementById("advance").form.submit();
      break;
  }
}
</script>
<?php

if ($charrow['dreamingstatus'] == "Awake") {
	$sid = $charrow['wakeself']; //$sid for strife ID
} else {
	$sid = $charrow['dreamself']; //Implement method for recognizing Godtiers and change dreamself id accordingly please
}
$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $sid LIMIT 1;");
$striferow = mysqli_fetch_array($striferesult);
if ($striferow['strifeID'] == 0 || empty($striferow['strifeID'])) { //This user is not actually strifing!
	echo "You are not currently engaged in strife!<br />";
} elseif ($striferow['leader'] != 1) { //This user is not the leader of this strife and should not be executing the round
	echo "You are not the leader of your current strife. Only the leader can advance the round.<br />";
} else {
	$output = "A whole mess of strifing takes place.<br />"; //This is the output string for this combat round
	$n = 0; //n for "number of strifers"
	$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];"); //Grab all strifers
	while ($row = mysqli_fetch_array($striferesult)) {
		$n++; //NOTE - This means the first strifer will be entry 1 in the array, NOT entry 0
		$strifers[$n] = $row; //Store each strifer in a successive index
		$strifers[$n]['damagedealt'] = 0;
		$strifers[$n]['damagetaken'] = 0;
		$strifers[$n]['attacks'] = 1;
		$strifers[$n]['timeattack'] = false; //This is an internally used variable that determines whether an automatic bonus attack due to
		//Time has been generated as the result of an attack yet. Only one of these is allowed to be GUARANTEED since otherwise
		//we'd end up in an infinite loop of attacks.
		$strifers[$n]['luck'] += $strifers[$n]['brief_luck']; //Add temporary luck to the luck value
		$strifers[$n]['bonuses'] .= $strifers[$n]['equipbonuses']; //Add equip bonuses to the bonuses field here
		$updatedstatus[$strifers[$n]['ID']] = $strifers[$n]['status']; //We will add new statuses to this if they pop up over the course of resolution.
    $strifers[$n]['status'] .= $strifers[$n]['equipstatus']; //Add in status effects granted by equips here so that they don't get duplicated
		//IMPORTANT NOTE - Every entry will tick down immediately in the post effects area! Always add an additional turn to the duration of statuses
		//added to updatedstatus.
		if ($strifers[$n]['owner'] == $charrow['ID']) $playerside = $strifers[$n]['side']; //Set $playerside to the side the player is on
	}
	$i = 1;
	while ($i <= $n) { //Change the "active" and "passive" entries of entities that receive them
		//First, we set them to lastactive and lastpassive. This way if nothing acts to change them, they will remain at those values.
		$strifers[$i]['active'] = $strifers[$i]['lastactive'];
		$strifers[$i]['passive'] = $strifers[$i]['lastpassive'];
		$activestr = strval($strifers[$i]['ID']) . "active";
		$passivestr = strval($strifers[$i]['ID']) . "passive";
		//Set the commands according to form input, if any.
		if(!empty($_POST[$activestr]) && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) $strifers[$i]['active'] = $_POST[$activestr];
		if(!empty($_POST[$passivestr]) && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) $strifers[$i]['passive'] = $_POST[$passivestr];
		//Set lastactive and lastpassive again. (If no change, they'll just end up the same as they were!)
		$strifers[$i]['lastactive'] = $strifers[$i]['active'];
		$strifers[$i]['lastpassive'] = $strifers[$i]['passive'];
		//If for some reason we want NPC strifers to have commands, an AI file can go here
		$i++;
	}
	require_once("includes/preeffects.php"); //Pre-resolution effects go here. Note that immobilizing effects are resolved here so that
	//if they are applied in an attack later they do not prevent the victim from performing their own attack.
	//A note on teamwork: Leaderless enemy encounters will not receive any teamwork boosts. Gotta have a team leader for teamwork to work, apparently.
	//A note on teamwork-granted boosts: The duration of 1 is a formality. Since the boost is not added to the updated status effect list, it takes effect
	//immediately but is not preserved. This trick can be used in preeffects to grant one-turn status effects (say, on a random roll) as well. Which
	//will be useful for having an effect trigger and last for a turn.
	//NOTE - Due to the way the teamwork code is structured, the entire team's offense/defense is invested into the leader, who then provides
	//a blanket defense boost according to their own teamwork value. Hence if everyone is at 100%, everyone will share the same defense value:
	//the sum of everyone's defenses. However, only the leader will receive the sum of everyone's offenses.
	$leaderoffarray = array();
	$leaderdefarray = array();
	$defarray = array();
	$i = 1;
	while ($i <= $n) { //Establish the power boosts for the leaders
		if ($strifers[$i]['leader'] != 1) {
			$power = powerCalc($strifers[$i]);
			if (empty($leaderoffarray[$strifers[$i]['side']])) $leaderoffarray[$strifers[$i]['side']] = 0; //Initialize these if they're not already.
			if (empty($leaderdefarray[$strifers[$i]['side']])) $leaderdefarray[$strifers[$i]['side']] = 0;
			$leaderoffarray[$strifers[$i]['side']] += floor($power['offense'] * ($strifers[$i]['teamwork'] / 100));
			$leaderdefarray[$strifers[$i]['side']] += floor($power['defense'] * ($strifers[$i]['teamwork'] / 100));
		}
		$i++;
	}
	$i = 1;
	while ($i <= $n) { //Apply the power boosts to the leaders and establish the boost for non-leaders
		if ($strifers[$i]['leader'] == 1) {
			if (!empty($leaderoffarray[$strifers[$i]['side']])) $strifers[$i]['bonuses'] .= "OFFENSE:1:" . strval($leaderoffarray[$strifers[$i]['side']]) . "|";
			if (!empty($leaderdefarray[$strifers[$i]['side']])) $strifers[$i]['bonuses'] .= "DEFENSE:1:" . strval($leaderdefarray[$strifers[$i]['side']]) . "|";
			$power = powerCalc($strifers[$i]);
			$defarray[$strifers[$i]['side']] = floor($power['defense'] * ($strifers[$i]['teamwork'] / 100));
			$teamworkarray[$strifers[$i]['side']] = $strifers[$i]['teamwork'];
		}
		$i++;
	}
	$i = 1;
	while ($i <= $n) { //Establish teamwork-based defense boosts for non-leaders.
		if ($strifers[$i]['leader'] != 1) {
			//The below line subtracts the amount of this strifer's defensive power that got into the teamwide boost from the boost they receive.
			//This is to prevent them from getting to double-up on their own defensive power due to teamwork. (Otherwise two players with 100% teamwork
			//working together would have 3x defense each, which is an issue)
			$bonus = $defarray[$strifers[$i]['side']] - floor($power['defense'] * ($strifers[$i]['teamwork'] / 100) * ($teamworkarray[$strifers[$i]['side']] / 100));
			if (!empty($defarray[$strifers[$i]['side']])) $strifers[$i]['bonuses'] .= "DEFENSE:1:" . strval($bonus) . "|";
		}
		$i++;
	}
	$i = 1;
	//The resolution loop begins here. Each strifer will now perform an attack against each strifer who is not on their side.
	while ($i <= $n) {
		$t = 1; //$t for "target"
		while ($t <= $n) {
			if ($strifers[$i]['side'] != $strifers[$t]['side']) { //Strifer $t is an enemy of strifer $i. ATTACK!
				$attacks = $strifers[$i]['attacks']; //So we can modify the number of attacks for THIS attack series without affecting others.
				while ($attacks > 0) {
					$attacks--;
					$attackerpower = powerCalc($strifers[$i]);
					$defenderpower = powerCalc($strifers[$t]);
					$minoffenseroll = 90 + floor($strifers[$i]['luck'] / 5);
					if ($minoffenseroll > 110) $minoffenseroll = 110;
					$mindefenseroll = 90 + floor($strifers[$t]['luck'] / 5);
					if ($mindefenseroll > 110) $mindefenseroll = 110;
					$offense = floor($attackerpower['offense'] * (rand($minoffenseroll,110) / 100));

					if($strifers[$i]['owner']==$charrow['ID']){
						maxStat($charrow, 'maxdamage', $offense);
					}

					$defense = floor($defenderpower['defense'] * (rand($mindefenseroll,110) / 100));
					$damage = $offense - $defense;
					$basedamage = $damage; //The base damage of the attack (i.e. only the power comparison) may be used by some abilities
					if ($basedamage < 0) $basedamage = 0; //It can't be less than 0.
					require_once("includes/damageeffects.php"); //This file checks any damage-relevant effects on the two strifers involved.
					//This file will check on-hit effects as well as status effects on the attacker and defender and any relevant abilities.
					//Note that bonuses have already been handled by this point.
					if ($damage < 0) $damage = 0; //No, failing to beat their defense does not heal them
					$strifers[$t]['health'] -= $damage; //This CAN go negative, if it does that's handled in the end of turn area.
					$strifers[$i]['damagedealt'] += $damage;
					$strifers[$t]['damagetaken'] += $damage;
					//Since a round of combat represents extended fighting, the felled strifer still gets to make their own attacks
				}
			}
			$t++;
		}
		$i++;
	}
	$enemynumber=0;
	require_once("includes/posteffects.php"); //Post-resolution effects go here. This includes and will largely consist of end-of-turn considerations.
	//It also covers KOing, loot, rung climbing, and anything that happens with regards to exploration/dungeons/etc as a result of strife
	sumStat($charrow, 'enemiesbeaten',$enemynumber);
	sumStat($charrow, 'moonprince', $moonprince);
	if(getStat($charrow, 'moonprince')>11) setAchievement($charrow,'moonprince'); 
	$megaquery = buildMegaquery($strifers,$n,$connection);
	mysqli_query($connection, $megaquery);
	echo $output;
	require_once("strifedisplay.php");
}
require_once("footer.php");
?>
