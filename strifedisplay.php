<?php
$pagetitle = "Strife!";
$headericon = "/images/header/rancorous.png";
require_once "header.php";
require_once "includes/glitches.php";
require_once "includes/displayfunctions.php";
require_once "includes/strifefunctions.php";

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
	$sid = $charrow['dreamself'];
}
$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $sid LIMIT 1;");
$striferow = mysqli_fetch_array($striferesult);
if ($striferow['strifeID'] == 0 || empty($striferow['strifeID'])) { //This user is not currently strifing.
	echo "You are not currently engaged in strife!<br />";
	if ($charrow['dreamingstatus'] == "Awake") { //Player is waking self
		if ($charrow['down'] != 1 && $charrow['dungeon'] == 0) { //Player is not KOed and not in a dungeon
			if ($charrow['inmedium'] == 1) { //Player is in the medium
				$connected = chainArray($charrow); //Get an array of connected Lands
				$chumroll = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = '$charrow[session]';");
				$n = 0;
				while ($chumrow = mysqli_fetch_array($chumroll)) {
					if($connected[$chumrow['ID']] || $chumrow['ID'] == $charrow['ID']) { //Can always fight your own underlings, even with no building done
						$lands[$n] = $chumrow;
						$n++;
					}
				}
				$i = 0;
				echo '<form action="strifeselect.php" method="post"><select name="land">';
				while ($i < $n) { //Note that the last value of n will not correspond to an index.
					echo '<option value="' . $lands[$i]['ID'] . '">Land of ' . $lands[$i]['land1'] . ' and ' . $lands[$i]['land2'] . '</option>';
					$i++;
				}
				if ($charrow['denizendown'] == 1) echo '<option value="battlefield">The Battlefield</option>';
				echo '</select><br />';
				$i = 1;
				$maxenemies = 12; //More or less arbitrary. Does affect progression speed though.
				echo 'Number of underlings to fight: <select name="quantity">';
				while ($i <= $maxenemies) {
					echo "<option value=\"$i\">$i</option>";
					$i++;
				}
				echo '</select><br /><input type="submit" value="Fight on this Land" /></form><br />';
				//Re-fight code here.
				if (strpos($charrow['oldenemydata'],"land") !== false) { //Old enemy data is applicable to Lands
					echo "Your last completed strife can be repeated, if you wish.<br />";
					echo '<form action="strifebegin.php" method="post">';
					$enemyarray = explode("|", $charrow['oldenemydata']);
					$i = 0;
					while (!empty($enemyarray[$i])) {
						$currentitem = explode(":", $enemyarray[$i]);
						echo "<input type='hidden' name='$currentitem[0]' value='$currentitem[1]'>";
						$i++;
					}
					echo '<input type="submit" value="Repeat last strife"></form><br />';
				}
				//Waking assistance code here. If you can reach a character's Land, you can assist that character (as you can reach everywhere they can)
				echo 'If any of your reachable allies are engaged in strife, you may assist them here:<br />';
				$allyquery = "SELECT strifeID, name from Strifers WHERE Strifers.ID IN (";
				$i = 0;
				while ($i < $n) {
					if ($lands[$i]['ID'] != $charrow['ID']) $allyquery .= $lands[$i]['wakeself'] . ", "; //No self-assisting!
					$i++;
				}
				$allyquery = substr($allyquery, 0, -2); //Chop the last ", " off the end
				$allyquery .= ");";
				$allyresult = mysqli_query($connection, $allyquery);
				echo '<form action="strifeassist.php" method="post"><select name="strifetojoin">';
				while ($allyrow = mysqli_fetch_array($allyresult)) { //Note that the last value of n will not correspond to an index.
					if ($allyrow['strifeID'] != 0) echo '<option value="' . $allyrow['strifeID'] . '">' . $allyrow['name'] . '</option>';
				}
				echo '</select><br /><input type="submit" value="Assist!" /></form><br />';
			} else {
				echo "You will need to enter the Medium to engage in waking strife.<br />";
			}
		} else {
			if ($charrow['down'] == 1) echo "You are in no condition to be strifing right now!<br />";
			if ($charrow['dungeon'] != 0) echo "<a id='advance' href='dungeons.php'>You are currently exploring a dungeon!</a> You can probably find enemies to fight in there.<br />";
		}
	} else {
		if ($charrow['dreamdown'] != 1) { //Player is not dream KOed
			$location = $charrow['dreamingstatus']; //Grab their dreaming status as their current location
			$maxenemies = 12;
			$enemyresult = mysqli_query($connection, "SELECT `basename`, `basepower` FROM `Enemy_Types` WHERE `Enemy_Types`.`appearson` = '$location' ORDER BY basepower ASC;");
			$n = 0;
			while ($row = mysqli_fetch_array($enemyresult)) {
				$enemyarray[$n] = $row;
				$n++;
			}
			$i = 1;
			echo '<form action="strifebegin.php" method="post">';
			while ($i <= $maxenemies) {
				$enemyname = "enemy" . strval($i);
				echo "<select name='$enemyname'><option value=''></option>";
				$j = 0;
				while (!empty($enemyarray[$j])) {
					echo "<option value='" . $enemyarray[$j]['basename'] . "'>" . $enemyarray[$j]['basename'] . " (Power: " . $enemyarray[$j]['basepower'] . ")</option>";
					$j++;
				}
				echo "</select><br />";
				$i++;
			}
			echo '<input type="submit" value="Go looking for these enemies!" /></form><br />';
			//Re-fight code here.
			if (strpos($charrow['oldenemydata'],"dreaming") !== false) { //Old enemy data is applicable to dreaming
				echo "Your last completed strife can be repeated, if you wish.<br />";
				echo '<form action="strifebegin.php" method="post">';
				$enemyarray = explode("|", $charrow['oldenemydata']);
				$i = 0;
				while (!empty($enemyarray[$i])) {
					$currentitem = explode(":", $enemyarray[$i]);
					echo "<input type='hidden' name='$currentitem[0]' value='$currentitem[1]'>";
					$i++;
				}
				echo '<input type="submit" value="Repeat last strife"></form><br />';
			}
			//Dream assist code here
		} else {
			echo "You are in no condition to be strifing right now!<br />";
		}
	}
} elseif (!empty($_POST['abscond'])) { //User loading the page is absconding from strife
	$abscondquery = "UPDATE `Strifers` SET `strifeID` = 0 WHERE `Strifers`.`ID` IN (";
	$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];"); //Grab all strifers
	$newleader = false;
	while ($row = mysqli_fetch_array($striferesult)) {
		if ($row['owner'] == $charrow['ID']) { //Strifer is part of the fleeing player's entourage
			$abscondquery .= $row['ID'] . ", ";
		} elseif ($row['aspect'] != "" && !$newleader) { //We found another player character. They're the leader now.
			mysqli_query($connection, "UPDATE `Strifers` SET `leader` = 1 WHERE `Strifers`.`ID` = $row[ID] LIMIT 1;");
		}
	}
	$abscondquery = substr($abscondquery, 0, -2) . ")"; //Remove the final trailing ", " and end the query
	mysqli_query($connection, $abscondquery);
	echo "You abscond from strife.<br />";
	if (strpos($strifers[$i]['description'], "dreamself") === false && $charrow['dungeon'] != 0) { //Waking self was KOed
		echo "You are forced back into the room you just left! <a id='advance' href='dungeons.php'>Return to dungeon ==></a><br />";
		$olddungeonrow = $charrow['olddungeonrow'];
		$olddungeoncol = $charrow['olddungeoncol'];
		mysqli_query($connection, "UPDATE Characters set dungeonrow = $olddungeonrow, dungeoncol = $olddungeoncol WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
	}
} else {
	if (!empty($strifers)) { //We came from striferesolve. We can just reuse the results from there!
		//NOTE - I'm pretty sure this never actually happens. But if $strifers does exist at this point, we'll use it.
		$strifers = array_values($strifers);
		$playerside = $striferow['side']; //Set $playerside to the side the player is on
	} else {
		if(!empty($_POST['newleader'])) { //Player is changing the strife's leader. Can't happen coming from striferesolve.
			if ($charrow['dungeon'] != 0) {
				echo "As the player exploring the dungeon, you must continue to lead the strife!<br />";
			} elseif ($striferow['leader'] == 1) {
				$newleaderid = $_POST['newleader'];
				$leaderfound = false;
			} else {
				echo "ERROR: You are not the leader!<br />";
			}
		}
		$n = -1; //n for "number of strifers". We want to start from 0 for strifedisplay because array_values, which we are using to recycle the strifers
		//array from striferesolve if it exists, starts from 0 by default. We start with -1 so that the initial $n++ sets n to 0.
		$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];"); //Grab all strifers
		while ($row = mysqli_fetch_array($striferesult)) {
			$n++;
			$strifers[$n] = $row; //Store each strifer in a successive index
			if ($strifers[$n]['owner'] == $charrow['ID']) $playerside = $strifers[$n]['side']; //Set $playerside to the side the player is on
			if (!empty($newleaderid)) { //Checking if this player is the leader swap target.
				if (!empty($strifers[$n]['aspect']) && ($strifers[$n]['side'] == $striferow['side']) && ($strifers[$n]['ID'] == $newleaderid)) {
					//The new leader has been found. Set them as leader, unset the current player.
					$striferow['leader'] = 0;
					$strifers[$n]['leader'] = 1;
					//This can almost certainly be done in one query...
					mysqli_query($connection, "UPDATE `Strifers` SET `leader` = 0 WHERE `Strifers`.`ID` = " . $striferow['ID'] . " LIMIT 1;");
					mysqli_query($connection, "UPDATE `Strifers` SET `leader` = 1 WHERE `Strifers`.`ID` = " . $strifers[$n]['ID'] . " LIMIT 1;");
					$leaderfound = true;
				}
			}
		}
		if(!empty($leaderfound) && !$leaderfound) {
			echo "ERROR: The strifer you tried to give the lead to was not found or was not another player!<br />";
		} elseif (!empty($_POST['actiondecision'])) { //Updating combat commands. DEFINITELY can't happen coming from striferesolve!
			$i = 0;
			while (!empty($strifers[$i])) {
				$activestr = strval($strifers[$i]['ID']) . "active";
				$passivestr = strval($strifers[$i]['ID']) . "passive";
				//Set the commands according to form input, if any.
				if(!empty($_POST[$activestr]) && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) $strifers[$i]['lastactive'] = $_POST[$activestr];
				if(!empty($_POST[$passivestr]) && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) $strifers[$i]['lastpassive'] = $_POST[$passivestr];
				mysqli_query($connection, "UPDATE Strifers SET lastactive = '" . $strifers[$i]['lastactive'] . "', lastpassive = '" . $strifers[$i]['lastpassive'] . "' WHERE Strifers.ID = " . $strifers[$i]['ID'] . " LIMIT 1;");
				$i++;
			}
		}
	}
	echo "Your current opponents:<br />";
	$i = 0;
	while (!empty($strifers[$i])) {
		if (($strifers[$i]['ID'] == $striferow['ID']) && ($strifers[$i]['leader'] != $striferow['leader'])) $strifers[$i]['leader'] = $striferow['leader'];
		//Above: If we gave away leadership, update the relevant $strifers row with this information.
		if ($strifers[$i]['side'] != $playerside) { //This strifer is an enemy
			if ($strifers[$i]['grist'] != "None") echo $strifers[$i]['grist'] . " ";
			if ($strifers[$i]['name'] == "The Bug") setAchievement($charrow, 'thebug');
			echo $strifers[$i]['name'] . "<br />";
			echo $strifers[$i]['description'] . "<br />";
			$strifers[$i]['active'] = ""; //Blank these so powerCalc doesn't take command bonuses into account
			$strifers[$i]['passive'] = "";
			$powerarray = powerCalc($strifers[$i]);
			echo "Offense/Defense:" . $powerarray['offense'] . "/" . $powerarray['defense'] . "<br />";
			echo "Health Vial:" . strval(floor(($strifers[$i]['health'] / $strifers[$i]['maxhealth']) * 100)) . "%<br />";
			displayStatus($strifers[$i]['status']);
			displayBonus($strifers[$i]['bonuses']);
		}
		echo "<br />";
		$i++;
	}
	echo "Yourself and your allies:<br />";
	$i = 0;
	while (!empty($strifers[$i])) {
		if ($strifers[$i]['side'] == $playerside) { //This strifer is an ally or the player themselves
			if (!empty($strifers[$i]['currentmotif'])) $fraymessage = fraymotifMessage($strifers[$i]);
			if ($strifers[$i]['grist'] != "None") echo $strifers[$i]['grist'] . " ";
			if ($strifers[$i]['owner'] != 0 && $strifers[$i]['energy'] != 0) echo profileStringSoft($strifers[$i]['owner']) . "<br>";
			else echo $strifers[$i]['name'] . "<br />";
			echo $strifers[$i]['description'] . "<br />";
			$strifers[$i]['active'] = ""; //Blank these so powerCalc doesn't take command bonuses into account
			$strifers[$i]['passive'] = "";
			$powerarray = powerCalc($strifers[$i]);
			echo "Offense/Defense:" . $powerarray['offense'] . "/" . $powerarray['defense'] . "<br />";
			echo "Health Vial:" . strval(floor(($strifers[$i]['health'] / $strifers[$i]['maxhealth']) * 100)) . "%<br />";
			//Below: Print the aspect vial if this is a player character (i.e. the strife row is "owned" by a character)
			if ($strifers[$i]['owner'] != 0 && $strifers[$i]['energy'] != 0) echo "Aspect Vial:" . strval(floor(($strifers[$i]['energy'] / $strifers[$i]['maxenergy']) * 100)) . "%<br />";
			displayStatus($strifers[$i]['status']);
			displayBonus($strifers[$i]['bonuses']);
		}
		echo "<br />";
		$i++;
	}
	if (!empty($fraymessage)) {
		echo $fraymessage;
	}
	if ($striferow['leader'] == 1) { //This strifer is the leader. Load up the control forms.
		$leaderform = "<form action='strifedisplay.php' method='post'><select name='newleader'>";
		echo "Enter commands for strifers under your control to proceed:";
		$i = 0;
		echo '<form action="striferesolve.php" method="post">';
		while (!empty($strifers[$i])) {
			if ($strifers[$i]['side'] == $playerside && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) { //We can input the commands for this strifer
				echo $strifers[$i]['name'] . ":<br />";
				$activestr = strval($strifers[$i]['ID']) . "active";
				$passivestr = strval($strifers[$i]['ID']) . "passive";
				$bonusarray = array("AGGRIEVE" => 0, "AGGRESS" => 0, "ASSAIL" => 0, "ASSAULT" => 0, "ABUSE" => 0, "ACCUSE" => 0, "ABJURE" => 0, "ABSTAIN" => 0);
				$bonuses = explode("|", $strifers[$i]['bonuses']); //We evaluate command bonuses here so we can print them
				$j = 0;
				while (!empty($bonuses[$j])) { //We still have a bonus to evaluate.
					$currentbonus = explode(":", $bonuses[$j]);
					$value = intval($currentbonus[2]);
					$bonusarray[$currentbonus[0]] += $value; //Won't do anything if it's not for a strife command, so we don't care.
					$j++;
				}
				$bonuses = explode("|", $strifers[$i]['equipbonuses']); //We evaluate command bonuses here so we can print them
				$j = 0;
				while (!empty($bonuses[$j])) { //We still have a bonus to evaluate.
					$currentbonus = explode(":", $bonuses[$j]);
					$value = intval($currentbonus[2]);
					$bonusarray[$currentbonus[0]] += $value; //Won't do anything if it's not for a strife command, so we don't care.
					$j++;
				}
				//Build select statement. Set the defaults to lastactive and lastpassive.
				$select = "<select name='" . $activestr . "'>
				<option value='AGGRIEVE'>AGGRIEVE (" . bonusStr($bonusarray['AGGRIEVE']) . ")</option>
				<option value='AGGRESS'>AGGRESS (" . bonusStr($bonusarray['AGGRESS']) . ")</option>
				<option value='ASSAIL'>ASSAIL (" . bonusStr($bonusarray['ASSAIL']) . ")</option>
				<option value='ASSAULT'>ASSAULT (" . bonusStr($bonusarray['ASSAULT']) . ")</option>";
				$select = str_replace("<option value='" . $strifers[$i]['lastactive'] . "'>", "<option value='" . $strifers[$i]['lastactive'] . "' selected='selected'>", $select);
				echo $select;
				$select = "</select><br /><select name='" . $passivestr . "'>
				<option value='ABUSE'>ABUSE (" . bonusStr($bonusarray['ABUSE']) . ")</option>
				<option value='ACCUSE'>ACCUSE (" . bonusStr($bonusarray['ACCUSE']) . ")</option>
				<option value='ABJURE'>ABJURE (" . bonusStr($bonusarray['ABJURE']) . ")</option>
				<option value='ABSTAIN'>ABSTAIN (" . bonusStr($bonusarray['ABSTAIN']) . ")</option>";
				$select = str_replace("<option value='" . $strifers[$i]['lastpassive'] . "'>", "<option value='" . $strifers[$i]['lastpassive'] . "' selected='selected'>", $select);
				echo $select;
				echo "</select><br />";
			} elseif ($strifers[$i]['side'] == $playerside) {
				echo $strifers[$i]['name'] . ":<br />";
				echo "Selected actions: " . $strifers[$i]['lastactive'] . "/" . $strifers[$i]['lastpassive'] . "<br />";
			}
			if (!empty($strifers[$i]['aspect']) && ($strifers[$i]['side'] == $striferow['side']) && $strifers[$i]['ID'] != $striferow['ID']) { //If this strifer is an allied player...
				//...we add them to the leader change form that is coming up.
				//This strifer is another player and therefore a potential new leader. Only players will have an entry under Aspect. Not even
				//Denizens get one of those!
				$leaderform .= "<option value='" . $strifers[$i]['ID'] . "'>" . $strifers[$i]['name'] . "</option>";
			}
			$i++;
		}
		echo "<input type='submit' id='advance' value='Advance!' /></form><br />";
		$leaderform .= "</select><br /><input type='submit' value='Grant leadership' /></form><br />";
		$emptyleaderform = "<form action='strifedisplay.php' method='post'><select name='newleader'></select><br /><input type='submit' value='Grant leadership' /></form><br />";
		if ($leaderform != $emptyleaderform) echo $leaderform; //Finish off the form and print it up.
	} else { //Strifer not the leader. Let them select actions for the current round.
		echo "You may select commands for strifers under your control:";
		$i = 0;
		echo '<form action="strifedisplay.php" method="post">';
		while (!empty($strifers[$i])) {
			if ($strifers[$i]['side'] == $playerside && $strifers[$i]['control'] == 1 && $strifers[$i]['owner'] == $charrow['ID']) { //We can input the commands for this strifer
				echo $strifers[$i]['name'] . ":<br />";
				$activestr = strval($strifers[$i]['ID']) . "active";
				$passivestr = strval($strifers[$i]['ID']) . "passive";
				$bonusarray = array("AGGRIEVE" => 0, "AGGRESS" => 0, "ASSAIL" => 0, "ASSAULT" => 0, "ABUSE" => 0, "ACCUSE" => 0, "ABJURE" => 0, "ABSTAIN" => 0);
				$bonuses = explode("|", $strifers[$i]['bonuses']); //We evaluate command bonuses here so we can print them
				$j = 0;
				while (!empty($bonuses[$j])) { //We still have a bonus to evaluate.
					$currentbonus = explode(":", $bonuses[$j]);
					$value = intval($currentbonus[2]);
					$bonusarray[$currentbonus[0]] += $value; //Won't do anything if it's not for a strife command, so we don't care.
					$j++;
				}
				$bonuses = explode("|", $strifers[$i]['equipbonuses']); //We evaluate command bonuses here so we can print them
				$j = 0;
				while (!empty($bonuses[$j])) { //We still have a bonus to evaluate.
					$currentbonus = explode(":", $bonuses[$j]);
					$value = intval($currentbonus[2]);
					$bonusarray[$currentbonus[0]] += $value; //Won't do anything if it's not for a strife command, so we don't care.
					$j++;
				}
				//Build select statement. Set the defaults to lastactive and lastpassive.
				$select = "<select name='" . $activestr . "'>
				<option value='AGGRIEVE'>AGGRIEVE (" . bonusStr($bonusarray['AGGRIEVE']) . ")</option>
				<option value='AGGRESS'>AGGRESS (" . bonusStr($bonusarray['AGGRESS']) . ")</option>
				<option value='ASSAIL'>ASSAIL (" . bonusStr($bonusarray['ASSAIL']) . ")</option>
				<option value='ASSAULT'>ASSAULT (" . bonusStr($bonusarray['ASSAULT']) . ")</option>";
				$select = str_replace("<option value='" . $strifers[$i]['lastactive'] . "'>", "<option value='" . $strifers[$i]['lastactive'] . "' selected='selected'>", $select);
				echo $select;
				$select = "</select><br /><select name='" . $passivestr . "'>
				<option value='ABUSE'>ABUSE (" . bonusStr($bonusarray['ABUSE']) . ")</option>
				<option value='ACCUSE'>ACCUSE (" . bonusStr($bonusarray['ACCUSE']) . ")</option>
				<option value='ABJURE'>ABJURE (" . bonusStr($bonusarray['ABJURE']) . ")</option>
				<option value='ABSTAIN'>ABSTAIN (" . bonusStr($bonusarray['ABSTAIN']) . ")</option>";
				$select = str_replace("<option value='" . $strifers[$i]['lastpassive'] . "'>", "<option value='" . $strifers[$i]['lastpassive'] . "' selected='selected'>", $select);
				echo $select;
				echo "</select><br />";
			}
			$i++;
		}
		echo "<input type='hidden' name='actiondecision' value='true'>";
		echo "<input type='submit' value='Decide!' /></form><br />";
	}
	//Print out an abscond button.
	echo "<form action='strifedisplay.php' method='post'><input type='hidden' name='abscond' value='yes'><input type='submit' value='Abscond'></form><br />";
}
require_once "footer.php";
?>
