<?php
$pagetitle = "Dungeon";
$headericon = "/images/header/compass.png";
require_once "header.php";
require_once "includes/strifefunctions.php";
require_once "includes/additem.php";

//WARNING: The coordinate system in here is convoluted. "row" means column, "column" means row, up is down, north is west...
//...yeah, it's a bit of a mess. Just do things experimentally and try to reference nearby stuff. Sorry. --Overseer
?>
<script type="text/javascript">
 document.onkeydown=function(evt){
	var keyCode = evt ? (evt.which ? evt.which : evt.keyCode) : event.keyCode;
   switch(keyCode){
   	  //enter
      case 13:
        evt.preventDefault();
        document.getElementById("strifelink").click();
      break;
      //left arrow
      case 37:
        document.getElementById("west").form.submit();
      break;
      //right arrow
      case 39:
        document.getElementById("east").form.submit();
      break;
      //up arrow
      case 38:
        document.getElementById("north").form.submit();
      break;
      //down arrow
      case 40:
      	 evt.preventDefault();
         document.getElementById("south").form.submit();
      break;
      //spacebar
      case 32:
      	evt.preventDefault();
        document.getElementById("transportalize").form.submit();
      break;
   }
};
</script>
<?php
function genEncounter($minpower, $maxpower, $search) {
	global $connection;
	$enemyresult = mysqli_query($connection, "SELECT * FROM Enemy_Types WHERE ($search) AND basepower * 9 + 81 > $minpower AND basepower < $maxpower ORDER BY RAND()"); //return a random enemy that fits the criteria
	if ($row = mysqli_fetch_array($enemyresult)) { //see if we found anything
		$i = 1;
		while ($i < 10) {
			if (($row['basepower'] * $i) + ($i * $i) <= $maxpower) $i++;
			else break; //This value is over the maximum, stop here
		}
		$i--; //subtract off the tier that exceeded maxpower
		$maxtier = $i;
		$i = 9;
		while ($i > 1) {
			if (($row['basepower'] * $i) + ($i * $i) >= $minpower) $i--;
			else break; //This value is under the minimum, stop here
		}
		$mintier = $i;
		if ($maxtier < 1 || $mintier > 9 || $maxtier < $mintier) return false; //error protection~
		$tier = rand($mintier, $maxtier);
		$power = ($row['basepower'] * $tier) + ($tier * $tier);
		return $row['basename'] . "|" . $tier . "|" . $power; //return a string containing the enemy name, tier, and power level for other functions
	} else return false;
}

function genLoot($minloot, $maxloot, $search, $session) {
	global $connection;
	$lootresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE $search AND (session = 0 OR session = $session) ORDER BY RAND()");
	while ($row = mysqli_fetch_array($lootresult)) {
		$grists = explode("|", $row['gristcosts']);
		$i = 0;
		$gtotal = 0;
		while (!empty($grists[$i])) {
			$g = explode(":", $grists[$i]);
			if ($g[0] != "Artifact") {
				$gtotal += $g[1];
			}
			$i++;
		}
		if ($gtotal > $minloot && $gtotal < $maxloot) { //this loot is good!
			return strval($row['ID']) . "|" . strval($gtotal);
		}
	}
	return false; //could not find loot that matches criteria
}

if (empty($_SESSION['character'])) {
	echo "Select a character to go dungeon diving.<br />";
} elseif ($charrow['dreamingstatus'] != "Awake") {
	echo "No dungeons while asleep!<br />";
} else {
	$strife = loadStriferow($charrow['wakeself']);
	if ($strife['strifeID'] != 0) {
		echo "You're <a href='/strifedisplay.php'>strifing</a>! You should go take care of that first.<br />";
	} else {
		if ($charrow['dungeon'] == 0) {
			echo "You are not currently in a dungeon.<br />";
			if ($charrow['gatescleared'] > 0) {
				echo '<form action="dungeongen.php" method="post">';
				$i = 1;
				echo '<select name="dungeonkind">';
				while ($i <= $charrow['gatescleared']) {
					if($i<7) echo '<option value="gate' . strval($i) . 'dungeon">Native dungeon: Gate ' . strval($i) . ' territory</option>';
					$i += 2; //Only odd-numbered gates provide new dungeon access
				}
				//Special selectable dungeons will have a special entry here.
				echo '</select><br />';
				echo '<input type="submit" value="Enter this dungeon" /></form><br />';
			} else echo "You don't have access to the main part of your land yet, and cannot access any dungeons. Tell your server to build up your house!<br />";
		} else {
			$dungeonresult = mysqli_query($connection, "SELECT * FROM Dungeons WHERE ID = " . strval($charrow['dungeon']));
			$dungeonrow = mysqli_fetch_array($dungeonresult);
			$topmost = $dungeonrow['topmost'];
			$leftmost = $dungeonrow['leftmost'];
			$bottommost = $dungeonrow['bottommost'];
			$rightmost = $dungeonrow['rightmost'];
			$row = $charrow['dungeonrow'];
			$col = $charrow['dungeoncol'];
			$canmove = false;
			if (!empty($_POST['targetdir'])) { //player wants to move to a new room
				$tdir = $_POST['targetdir'];
				$trow = $row;
				$tcol = $col;
				switch ($tdir) {
					case "n": $trow = $row - 1; break;
					case "w": $tcol = $col - 1; break;
					case "e": $tcol = $col + 1; break;
					case "s": $trow = $row + 1; break;
				}
				$newroom = $tcol . "," . $trow;
			}
			if (!empty($_POST['transportalize'])) { //Player wants to transportalize to a new room
				$troom = $_POST['transportalize'];
				$troomarray = explode(",", $troom);
				$trow = $troomarray[1];
				$tcol = $troomarray[0];
				$newroom = $tcol . "," . $trow;
			}
			$currentroom = $col . "," . $row;
			if (!empty($_POST['exit'])) {
				if ($currentroom != "0,0") {
					echo "You cannot exit from here!<br />";
				} else {
					echo "<a id='strifelink' href='dungeonexit.php'>Click here to confirm dungeon exit.</a><br />";
				}
			}
			$allrooms = explode("|", $dungeonrow['room']);
			$i = 0;
			while (!empty($allrooms[$i])) {
				$map = explode(":", $allrooms[$i]);
				$coord = explode(",", $map[1]);
				$dungeonstring[$coord[0]][$coord[1]] = $allrooms[$i];
				if ($currentroom == $map[1]) {
					$exits = $map[3];
					$j = 0;
					while (!empty($map[$j])) {
						if ($map[$j] == "TRANSPORT") {
							$j++;
							$transport = $map[$j];
						}
						$j++;
					}
					if (!empty($tdir)) {
						if (strpos($exits, $tdir) !== false) { //chosen movement direction is available
							if (!$canmove) { //Move hasn't happened yet.
								mysqli_query($connection, "UPDATE Characters SET dungeonrow = $trow, dungeoncol = $tcol, olddungeonrow = $row, olddungeoncol = $col WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
							}
							$canmove = true;
							$row = $trow;
							$col = $tcol;
						} else {
							echo "You cannot move there!<br />";
						}
					}
					if (!empty($troom)) {
						$j = 0;
						while (!empty($map[$j])) {
							if ($map[$j] == "TRANSPORT") {
								$j++;
								if ($map[$j] == $troom) { //Transportation success
									if (!$canmove) { //Move hasn't happened yet.
										mysqli_query($connection, "UPDATE Characters SET dungeonrow = $trow, dungeoncol = $tcol, olddungeonrow = $row, olddungeoncol = $col WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
									}
									$canmove = true;
									$row = $trow;
									$col = $tcol;
								}
							}
							$j++;
						}
						if (!$canmove) echo "You cannot transportalize there!<br />";
					}
				}
				if ($newroom == $map[1]) {
					$nexits = $map[3];
					$j = 0;
					while (!empty($map[$j])) {
						if ($map[$j] == "TRANSPORT") {
							$j++;
							$ntransport = $map[$j];
						}
						$j++;
					}
				}
				$i++;
			}
			if ($canmove) {
				$exits = $nexits;
				$currentroom = $newroom;
				$transport = $ntransport;
				if (strpos($dungeonrow['room'], ("ROOM:" . $currentroom . ":")) !== false) { //Room not yet visited, flag it as visited.
					$dungeonrow['room'] = str_replace(("ROOM:" . $currentroom . ":"), ("VISITED:" . $currentroom . ":"), $dungeonrow['room']);
					$dungeonstring[$tcol][$trow] = str_replace("ROOM:", "VISITED:", $dungeonstring[$tcol][$trow]);
					mysqli_query($connection, "UPDATE Dungeons SET room = '" . $dungeonrow['room'] . "' WHERE Dungeons.ID = " . $dungeonrow['ID'] . " LIMIT 1;");
				}
			}
			$newID = 0;
			if (strpos('|' . $dungeonrow['enc'], '|' . $currentroom . ':') !== false) {
				$enc = surgicalSearch("|" . $dungeonrow['enc'], "|" . $currentroom . ":", true);
				$ensearchstr = "";
				if ($enc[1] == "EXISTS") { //encounter was already generated, so the remaining args are the enemy IDs
					$newID = $enc[2]; //second arg is the strife ID containing all of the enemies, allowing any number of enemies to be stored here and quickly pulled up
					if ($newID == "BOSS") $newID = $enc[3]; //Second arg was the boss flag! Use the third arg instead.
				} elseif ($enc[1] == "BOSS") { //This is the boss encounter!
					$bossencounter = explode("|", $dungeonrow['boss']);
					$j = 0;
					while(!empty($bossencounter[$j])) {
						$currentitem = explode(":", $bossencounter[$j]);
						if ($currentitem[0] == "NONE") { //Dungeon lacks a boss. This should be the only tag.
							echo "There doesn't seem to be a boss here...<br />";
							//In future we will spawn stairs here if the dungeon is multifloor
						} else {
							if (empty($names)) {
								$names[0] = $currentitem[0];
							} else {
								$names[] = $currentitem[0];
							}
							if (empty($currentitem[1])) { //Tierless enemy. Tier will be ignored.
								$tier = 0;
							} else {
								$tier = $currentitem[1];
							}
							if (empty($tiers)) {
								$tiers[0] = $tier;
							} else {
								$tiers[] = $tier;
							}
						}
						$j++;
					}
					//Retrieve the next ID from masterID and use it
					mysqli_multi_query($connection, "UPDATE System SET masterID = masterID + 1; SELECT masterID from System WHERE 1;");
					mysqli_next_result(mysqli_next_result($connection));
					$masterresult = mysqli_store_result($connection); //Store the second result
					$masterrow = mysqli_fetch_array($masterresult);	
					$newID = $masterrow['masterID']; //Grab the master ID
					echo "<br />";
					generateEnemies($names, $newID, $connection, "ANY", 1, $charrow['session'], $dungeonrow['land'], $tiers, 1);
					$newencstr = $currentroom . ":EXISTS:BOSS:" . strval($newID);
					$encstr = implode(":", $enc);
					$dungeonrow['enc'] = str_replace($encstr, $newencstr, $dungeonrow['enc']);
					mysqli_query($connection,"UPDATE Dungeons SET enc = '" . $dungeonrow['enc'] . "' WHERE Dungeons.ID = " . $dungeonrow['ID'] . " LIMIT 1;");
				} else { //Encounter not generated yet, so arg 1 is the difficulty
					$done = false;
					$mini = $dungeonrow['minpower'] * (1 + ($enc[1] / 10));
					$maxi = $dungeonrow['maxpower'] * (1 + ($enc[1] / 10));
					$tpower = 0;
					while (!$done) {
						$newenc = genEncounter($mini, $maxi, $dungeonrow['enemytypes']);
						if ($newenc != "") {
							$encstuff = explode("|", $newenc);
							if (empty($names)) {
								$names[0] = $encstuff[0];
							} else {
								$names[] = $encstuff[0];
							}
							if (empty($tiers)) {
								$tiers[0] = $encstuff[1];
							} else {
								$tiers[] = $encstuff[1];
							}
							$tpower += $encstuff[2];
						} else break; //there was an error, break out of the loop
						if ($tpower >= $maxi) $done = true;
						else {
							$cchance = rand(1,100);
							if ($cchance < ($tpower * 50 / $maxi)) $done = true; //50% chance of bailing prematurely at $maxi
						}
					}
					if (!$done) {
						echo "There was an error. Encounter could not be generated.<br />";
						$newID = 0;
					} else {
						//Retrieve the next ID from masterID and use it
						mysqli_multi_query($connection, "UPDATE System SET masterID = masterID + 1; SELECT masterID from System WHERE 1;");
						mysqli_next_result(mysqli_next_result($connection));
						$masterresult = mysqli_store_result($connection); //Store the second result
						$masterrow = mysqli_fetch_array($masterresult);	
						$newID = $masterrow['masterID']; //Grab the master ID
						generateEnemies($names, $newID, $connection, "ANY", 1, $charrow['session'], $dungeonrow['land'], $tiers, 1);
						$newencstr = $currentroom . ":EXISTS:" . strval($newID);
						$encstr = implode(":", $enc);
						$dungeonrow['enc'] = str_replace($encstr, $newencstr, $dungeonrow['enc']);
						mysqli_query($connection,"UPDATE Dungeons SET enc = '" . $dungeonrow['enc'] . "' WHERE Dungeons.ID = " . $dungeonrow['ID'] . " LIMIT 1;");
					}
				}
				if ($newID != 0) {
					$enemiesresult = mysqli_query($connection, "SELECT * FROM Strifers WHERE strifeID = $newID");
					$enemypresent = false;
					$playerpresent = false;
					$message = "";
					while ($row = mysqli_fetch_array($enemiesresult)) {
						if (!$enemypresent) $message .= "As you enter the room, you are assaulted by enemies:<br />";
						$enemypresent = true;
						if (!empty($row['grist']) && $row['grist'] != "None") $message .= $row['grist'] . " ";
						$message .= $row['name'] . "<br />";
						if ($row['Aspect'] != "") { //There's another player in the strife! We're not interested in it.
							$enemypresent = false;
							$playerpresent = true;
						}
					}
					if ($enemypresent) { //Encounter is still here! Engage it.
						echo $message;
						echo "<a id='strifelink' href='strifedisplay.php'>==&gt;</a><br />";
						spendFatigue(10,$charrow);
						$playerside = 0;
						mysqli_query($connection, "UPDATE Strifers SET strifeID = $newID, side = $playerside, leader = 1 WHERE ID = " . strval($strife['ID'])); //Add player
						mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID, `side` = $playerside WHERE `Strifers`.`owner` = " . $charrow['ID'] . " AND `Strifers`.`Aspect` = '';"); //Add allies
					} else { //Encounter was beaten by someone else while the explorer was elsewhere. Remove it.
						echo "Victory!<br />";
						$newencstr = $currentroom . ":EXISTS:" . strval($newID);
						$dungeonrow['enc'] = str_replace($newencstr, "", $dungeonrow['enc']);
						$newencstr = $currentroom . ":EXISTS:BOSS:" . strval($newID);
						$dungeonrow['enc'] = str_replace($newencstr, "", $dungeonrow['enc']);
						mysqli_query($connection,"UPDATE Dungeons SET enc = '" . $dungeonrow['enc'] . "' WHERE Dungeons.ID = " . $dungeonrow['ID'] . " LIMIT 1;");
						$newID = 0; //Not fighting anything!
					}
				}
			}

			if ($newID == 0) { //don't get loot if there's an encounter here
				if (strpos(("|" . $dungeonrow['loot']), ("|" . $currentroom)) !== false) {
					$loot = surgicalSearch($dungeonrow['loot'], "|" . $currentroom . ":", true);
					$loots = implode(":", $loot);
					$startingloots = $loots;
					$grabbinloot = true;
					if ($loot[1] != "EXISTS") { //loot has not yet been generated here, so arg 1 is the value of the loot
						$loots = $currentroom . ":EXISTS:";
						$done = false;
						$mini = $dungeonrow['minloot'] * (1 + ($loot[1] / 10));
						$maxi = $dungeonrow['maxloot'] * (1 + ($loot[1] / 10));
						$tvalue = 0;
						while (!$done) {
							$boonchance = rand(1,100);
							if ($boonchance < 25) {
								$boons = rand($mini * 100, $maxi * 100);
								$loots .= "BOONS:" . strval($boons) . ":";
								$tvalue += ($boons / 100);
							} else {
								$newloot = genLoot($mini, $maxi, $dungeonrow['loottypes'], $charrow['session']);
								if ($newloot != "") {
									$lootstuff = explode("|", $newloot);
									$loots .= $lootstuff[0] . ":";
									$tvalue += $lootstuff[1];
								} else break;
							}
							if ($tvalue >= $maxi) $done = true;
							else {
								$cchance = rand(1,100);
								if ($cchance < ($tvalue * 50 / $maxi)) $done = true; //50% chance to abort early if we're at max
							}
						}
						$loot = explode(":", $loots);
					}
					if (false) { //Condition was !$done, but if the loot is pre-existing that will ALWAYS be the case and if not it CAN'T be...
						$grabbinloot = false;
						echo "Error generating loot.<br />";
					}
					$i = 2;
					if ($grabbinloot) { //loot was already generated, remaining args are item IDs
						echo "You find in the room:<br />";
						$gotloot = "";
						$boonplus = 0;
						while (!empty($loot[$i])) {
							if ($loot[$i] == "BOONS") {
								$i++;
								$boonplus += $loot[$i];
								$loots = str_replace("BOONS:" . strval($loot[$i]) . ":", "", $loots); //boondollars can always be taken automatically
								//Note that duplicate boondollar quantities will be deleted, but this is irrelevant since they're all gonna be deleted anyway
							} else {
								$gotloot .= "ID = " . $loot[$i] . " OR ";
							}
							$i++;
						}
						$gotloot = substr($gotloot, 0, -4); //remove the final OR
						if ($boonplus > 0) {
							echo strval($boonplus) . " Boondollars<br />";
							$charrow['boondollars'] += $boonplus;
							mysqli_query($connection, "UPDATE Characters SET boondollars = " . $charrow['boondollars'] . " WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
						}
						$lootresult = mysqli_query($connection, "SELECT ID,name FROM Captchalogue WHERE $gotloot AND (session = 0 OR session = $charrow[session])");
						while ($row = mysqli_fetch_array($lootresult)) {
							$got = addItem($charrow, $row['ID']);
							echo $row['name'];
							if (!$got) {
								echo ", but you can't pick it up.";
							} else {
								echo ", which you captchalogue.";
								$copies = substr_count($loots, strval($row['ID']) . ":");
								$loots = str_replace(strval($row['ID']) . ":", "", $loots); //item was taken, so remove it from the loot string
								//str_replace will delete ALL instances of the item, but preg_replace was erroring. Hence we then restore all but one.
								$copies--;
								while ($copies > 0) {
									$loots .= strval($row['ID'] . ":");
									$copies--;
								}
							}
							echo "<br />";
						}
					}
					if ($loots != $startingloots) { //loot was acquired/generated, write the changes to the database
						//There's some weirdness here, but it boils down to stopping some loot strings from being substrings of other loot strings
						//and thus causing double replacements (e.g. "1,0:1:" and "-1,0:1:")
						$dungeonrow['loot'] = '|' . $dungeonrow['loot'];
						$startingloots = '|' . $startingloots . '|';
						$loots = '|' . $loots . '|';
						if (substr_count($loots, ":") > 2) { //at least one item argument remains
							$dungeonrow['loot'] = str_replace($startingloots, $loots, $dungeonrow['loot']);
						} else {
							$dungeonrow['loot'] = str_replace($startingloots, "|", $dungeonrow['loot']); //no args left, remove completely
							echo "DEBUG: $loots contains " . substr_count($loots, ":") . " colons.<br />";
						}
						$dungeonrow['loot'] = substr($dungeonrow['loot'], 1);
						mysqli_query($connection, "UPDATE Dungeons SET loot = '" . $dungeonrow['loot'] . "' WHERE ID = " . $dungeonrow['ID']);
					}
				}
			}

			//draw that dungeon grid
			$i = $topmost;
			$j = $leftmost;
			$tiles = true;
			$onentrance = False;
			$borderstr = "1px solid black;";
			if ($tiles) echo '<table cellspacing="0" cellpadding="0">';
			while ($i <= $bottommost) {
				if ($tiles) echo '<tr>';
				while ($j <= $rightmost) {
					$thisroom = strval($j) . "," . strval($i);
					$roomstr = $dungeonstring[$j][$i];
					$blank = true;
					if (!empty($roomstr)) {
						$map = explode(":", $roomstr);
						if (strpos($roomstr,"VISITED") !== False) {
							$blank = false;
						} else {
							if (strpos($map[3],"n") !== false && strpos($dungeonstring[$j][$i-1], "VISITED") !== false) $blank = false;
							elseif (strpos($map[3],"s") !== false && strpos($dungeonstring[$j][$i+1], "VISITED") !== false) $blank = false;
							elseif (strpos($map[3],"w") !== false && strpos($dungeonstring[$j-1][$i], "VISITED") !== false) $blank = false;
							elseif (strpos($map[3],"e") !== false && strpos($dungeonstring[$j+1][$i], "VISITED") !== false) $blank = false;
						}
						//$blank = false;
					}
					echo '<td style="width:64;height:64;line-height:0px;';
					if ($blank) {
						if ($tiles) {
							echo 'background-image:url(./images/dungeon/unknown_tile.png);border-left:' . $borderstr . 'border-bottom:' . $borderstr . 'border-top:' . $borderstr . 'border-right:' . $borderstr;
						} else {
							echo '&nbsp;';
						}
					} else {
						if (strpos($map[3],"w") === False) { //Rooms not connected.
							echo 'border-left:' . $borderstr;
						}
						if (strpos($map[3],"s") === False) { //Rooms not connected.
							echo 'border-bottom:' . $borderstr;
						}
						if (strpos($map[3],"n") === False) { //Rooms not connected.
							echo 'border-top:' . $borderstr;
						}
						if (strpos($map[3],"e") === False) { //Rooms not connected.
							echo 'border-right:' . $borderstr;
						}
						//player location will be handled differently below
						$tilename = "";
						if (strpos($roomstr,"VISITED") !== False) {
							if ((strpos('|' . $dungeonrow['enc'], '|' . $thisroom . ":BOSS") !== false) || (strpos('|' . $dungeonrow['enc'], '|' . $thisroom . ":EXISTS:BOSS") !== false)) $tilename .= "boss_";
							elseif (strpos('|' . $dungeonrow['enc'], '|' . $thisroom) !== false) $tilename .= "enemy_";
							elseif (strpos($roomstr,"ENTRANCE") !== False) $tilename .= "entrance_";
							if (strpos('|' . $dungeonrow['loot'], '|' . $thisroom) !== false) $tilename .= "loot_";
							if (strpos($map[3],"u") !== false || strpos($map[3],"d") !== false) $tilename .= "stairs_";
							elseif (strpos($roomstr,"TRANSPORT") !== false) $tilename .= "transport_";
						} else $tilename .= "unknown";
						$tilename .= "tile";
						echo 'background-image:url(./images/dungeon/' . $tilename . '.png);';
						
					}
					echo 'background-repeat:no-repeat;">';
					if ($thisroom == $currentroom) { //This is the current room.
						echo "<img src='" . $charrow['symbol'] . "' title='You'>";
					} elseif (!empty($playerarray[$room])) { //somebody else is in this room
						echo "<img src='" . $symbolarray[$room] . "' title='" . $playerarray[$room] . "'>";
					} else {
						echo "<img src='/images/symbols/nobody.png'>";
					}
					echo '</td>';
					$j++;
				}
				echo "</tr>";
				$j = $leftmost; //Reset it again for the next actual loop
				$i++;
			}
			echo '</table>';

			echo "<div style='float:right;position:relative; top:-" . strval((abs($topmost) + abs($bottommost) + 1) * 64) . "px;'>";
			echo "<table style='width:64;height:64;line-height:0px;'><tr><td><img src='/images/symbols/nobody.png'></td><td>";
			if (strpos($exits,"n") !== False) {
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='targetdir' id='north' value='n'>";
				echo "<input type='image' src='/images/dungeon/dgnbtn_north.png' alt='North'></form>";
			} else echo "<img src='/images/dungeon/dgnbtn_north_blocked.png'>";
			echo "</td><td><img src='/images/symbols/nobody.png'></td></tr><tr><td>";
			if (strpos($exits,"w") !== False) {
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='targetdir' id='west' value='w'>";
				echo "<input type='image' src='/images/dungeon/dgnbtn_west.png' alt='West'></form>";
			} else echo "<img src='/images/dungeon/dgnbtn_west_blocked.png'>";
			echo "</td><td>";
			if (!empty($transport)) { //Transportalizer: Display a transportalizing button.
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='transportalize' id='transportalize' value='$transport'>";
				echo "<input type='image' src='/images/dungeon/stair_tile.png' alt='Transportalize'></form>";
			} elseif ($currentroom == "0,0") { //Entrance: Display a dungeon exit button.
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='exit' id='transportalize' value='true'>";
				echo "<input type='image' src='/images/dungeon/entrance_tile.png' alt='Exit Dungeon'></form>";
			} elseif (false) { //Staircase: Display a "use stairs" button.
				
			} else echo "<img src='/images/symbols/nobody.png'>";
			echo "</td><td>";
			if (strpos($exits,"e") !== False) {
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='targetdir' id='east' value='e'>";
				echo "<input type='image' src='/images/dungeon/dgnbtn_east.png' alt='East'></form>";
			} else echo "<img src='/images/dungeon/dgnbtn_east_blocked.png'>";
			echo "</td></tr><tr><td><img src='/images/symbols/nobody.png'></td><td>";
			if (strpos($exits,"s") !== False) {
				echo "<form action='dungeons.php#display' method='post'><input type='hidden' name='targetdir' id='south' value='s'>";
				echo "<input type='image' src='/images/dungeon/dgnbtn_south.png' alt='South'></form>";
			} else echo "<img src='/images/dungeon/dgnbtn_south_blocked.png'>";
			echo "</td><td><img src='/images/symbols/nobody.png'></td></tr></table></br>";
		}
	}
}

require_once "footer.php";
?>
