<?php
require_once "header.php";
require_once "includes/global_functions.php";

function getEnemy($gate) { //retrieves standard enemy spawns from this gate
	switch ($gate) {
		case 1: return "basename = 'Imp'"; break;
		case 2: return "basename = 'Imp' OR basename = 'Ogre'"; break;
		case 3: return "basename = 'Imp' OR basename = 'Ogre' OR basename = 'Basilisk'"; break;
		case 4: return "basename = 'Imp' OR basename = 'Ogre' OR basename = 'Basilisk' OR basename = 'Lich'"; break;
		case 5: return "basename = 'Imp' OR basename = 'Ogre' OR basename = 'Basilisk' OR basename = 'Lich' OR basename = 'Giclops'"; break;
		case 6: return "basename = 'Imp' OR basename = 'Ogre' OR basename = 'Basilisk' OR basename = 'Lich' OR basename = 'Giclops' OR basename = 'Titachnid'"; break;
		case 7: return "basename = 'Imp' OR basename = 'Ogre' OR basename = 'Basilisk' OR basename = 'Lich' OR basename = 'Giclops' OR basename = 'Titachnid' OR basename = 'Acheron'"; break;
	}
}

function getPower($gate) {
	switch ($gate) {
		case 1: return 1; break;
		case 2: return 30; break;
		case 3: return 150; break;
		case 4: return 450; break;
		case 5: return 700; break;
		case 6: return 1000; break;
		case 7: return 2000; break;
		case 8: return 4000; break;
	}
}

function getLoot($gate) {
	switch ($gate) {
		case 1: return 5; break;
		case 2: return 160; break;
		case 3: return 3000; break;
		case 4: return 26000; break;
		case 5: return 62500; break;
		case 6: return 126500; break;
		case 7: return 500000; break;
		case 8: return 2000000; break;
	}
}

function getSpecs($dungeonkind,$charrow) {
	//Fills out the specs for the specified dungeon kind
	//First, set defaults. These values can be modified by a specific dungeon kind, but often aren't.
	$specs['maxdistance'] = 10; //the most amount of rooms the dungeon can go from the start
	$specs['complexity'] = 10; //the most amount of branches this dungeon can have
	$specs['telechance'] = 10; //chance of spawning a teleporter
	$specs['encchance'] = 40; //chance of spawning an encounter
	$specs['lootchance'] = 30; //chance of spawning loot
	$specs['loots'] = "1";
	$specs['floors'] = 1;
	$specs['enemies'] = "appearson = \'Lands\' OR appearson = \'Dungeons\'";
	$specs['error'] = 0;
	//Other values are boss, minpower, maxpower, minloot, and maxloot. These have no defaults because they are almost always different.
	switch ($dungeonkind) {
		case "gate1dungeon":
			$specs['minpower'] = 1;
			$specs['maxpower'] = 200;
			$specs['minloot'] = 5;
			$specs['maxloot'] = 1000;
			$specs['boss'] = "Kraken|";
			break;
		case "gate3dungeon":
			$specs['minpower'] = 400;
			$specs['maxpower'] = 3500;
			$specs['minloot'] = 500;
			$specs['maxloot'] = 62500;
			$specs['boss'] = "Hekatonchire|";
			break;
		case "gate5dungeon":
			$specs['minpower'] = 4000;
			$specs['maxpower'] = 6500;
			$specs['minloot'] = 50000;
			$specs['maxloot'] = 400000;
			$specs['boss'] = "Lich Queen|Lich:9|Lich:9|Lich:9|Lich:9|";
			break;
		case "bug":
			$specs['minpower'] = 7000;
			$specs['maxpower'] = 9000;
			$specs['minloot'] = 200000;
			$specs['maxloot'] = 500000;
			$specs['boss'] = "The Bug|";
			break;
		/*case "DENIZENPALACE":
			break;*/
		default:
			echo "Dungeon '$dungeonkind' does not exist!<br />";
			$specs['error'] = 1;
			break;
	}
	return $specs;
}

if (empty($_SESSION['character'])) {
	echo "Select a character to go dungeon diving.<br />";
} else {
	$ready = false;
	if (!empty($_POST['servergen'])) { //Dungeon is being constructed for house advancement purposes
		$gateresult = mysqli_query($connection, "SELECT * FROM Gates");
		$gaterow = mysqli_fetch_array($gateresult);
		$clientresult = mysqli_query($connection, "SELECT * FROM Characters WHERE `ID` = '" . $charrow['client'] . "'");
		$clientrow = mysqli_fetch_array($clientresult);
		if ($gaterow['gate' . $_POST['gate']] <= $clientrow['house_build']) {
			$gate = $_POST['gate'];
			$land = $clientrow['ID'];
			$telechance = 0; //transportalizers are too complicated for servers to build (might change later though)
			$maxdistance = 10 + $gate;
			$complexity = $_POST['complex'] + $gate;
			$encchance = $_POST['danger'] + ($gate * 5);
			$lootchance = floor(($_POST['danger'] + ($gate * 5))/2);
			$floors = $gate;
			$enemies = getEnemy($gate);
			$grists = explode("|", $clientrow['grists']); //get all of the client's land grists so we can define possible loot
			/*$i = 0;
			$loots = "";
			while (!empty($grists[$i])) {
				$loots .= "GRIST:" . $grists[$i] . "|";
				$i++;
			}*/
			$loots = 1; //Sort out that shit later.
			$minpower = getPower($gate);
			$maxpower = getPower($gate + 1);
			$minloot = getLoot($gate);
			$maxloot = getLoot($gate + 1);
			$boss = "NONE";
			$ready = true;
		} else {
			echo "You have not built your client up to that gate yet!<br />";
		}
	} elseif (!empty($_POST['dungeonkind']) && $charrow['dungeon'] == 0) {
		$land = $charrow['ID']; //Default the land to the current character's land
		if (!empty($_POST['land'])) $land = $_POST['land'];
		$specs = getSpecs($_POST['dungeonkind'],$charrow);
		$maxdistance = $specs['maxdistance'];
		$complexity = $specs['complexity'];
		$telechance = $specs['telechance'];
		$encchance = $specs['encchance'];
		$lootchance = $specs['lootchance'];
		$enemies = $specs['enemies'];
		$loots = $specs['loots'];
		$minpower = $specs['minpower'];
		$maxpower = $specs['maxpower'];
		$minloot = $specs['minloot'];
		$maxloot = $specs['maxloot'];
		$gate = 0; //who cares
		$floor = 1; //First floor generated is always the first floor
		$floors = $specs['floors'];
		$boss = $specs['boss'];
		if ($specs['error'] == 0)$ready = true;
	}
	if ($ready) {
		$x = 0; //the current X position
		$y = 0; //the current Y position
		$dir = 0; //the direction we're going
		$distance = 0; //distance from the origin
		$dist[0][0] = 0; //set the origin's distance to 0
		$leftmost = 0; //the lowest X value
		$rightmost = 0; //the highest X value
		$upmost = 0; //the lowest Y value
		$downmost = 0; //the highest Y value
		$continue = true; //whether or not to continue the while loop
		$firstbranch = true; //whether or not this is the first branch (this'll place the boss and ensure it's at max distance)
		while ($continue) {
			if ($dir == 0) $dir = rand(1,4); //no preceding direction (new branch or we were just transportalized), so get a random one
			else {
				if (rand(1,100) <= $telechance && ($x != 0 || $y != 0) && (strpos($room[$x][$y], "TRANSPORT") === false) && false) {
					//Teleporting disabled due to weird generation bugs. Fix this soon.
					//deploy a teleporter here! But not on the entrance tile or a tile with one already
					//Stair tiles will also need to be banned from having transportalizers on them.
					$dir = 0; //so that the switch statement ahead knows this is a transportalizer
					$tx = 0;
					$ty = 0;
					while ($tx == 0 && $ty == 0) {
						$tx = rand($leftmost,$rightmost); //find a new room within the confines of the current dungeon to tele to
						$ty = rand($upmost,$downmost);
					}
					$room[$x][$y] .= "TRANSPORT:$tx,$ty:"; //link the two rooms together
					$room[$tx][$ty] .= "TRANSPORT:$x,$y:";
				} else { //no transportalizer, just a boring room link
					$dir += floor(rand(1,4)/2)-1; //50% to go straight, 50% to turn left or right
					$i = 0;
					while ($i < 4) {
						if ($dir < 1) $dir+=4; //loop around
						if ($dir > 4) $dir-=4;
						switch ($dir) { //check if the current direction collides with an existing room
							case 1: if (empty($exits[$x][$y-1])) $i = 4; break; //if it doesn't, end the loop here and keep the direction
							case 2: if (empty($exits[$x-1][$y])) $i = 4; break;
							case 3: if (empty($exits[$x][$y+1])) $i = 4; break;
							case 4: if (empty($exits[$x+1][$y])) $i = 4; break;
						}
						$i++; //will make $i = 5 if the previous check passed
						if ($i == 4) $dir = rand(1,4); //loop ended, all exits are taken, so just choose a random one
						elseif ($i < 4) $dir += 1; //loop is still going, check a different direction
					}
				}
				if ($x != 0 || $y != 0) { //No encounters or loot on 0,0 since this is where the player appears
					if (rand(1,100) <= $encchance) $enc[$x][$y] .= $dist[$x][$y] . ":"; //put an encounter here
					if (rand(1,100) <= $lootchance) $loot[$x][$y] .= $dist[$x][$y] . ":"; //put loot here
				}
			}
			switch ($dir) { //configure exits of this room and destination room
				case 0: $x = $tx; $y = $ty; break; //this is a transportalizer, so set x and y to the destination we came up with earlier
				case 1: $exits[$x][$y] .= "n"; $y-=1; $exits[$x][$y] .= "s"; break;
				case 2: $exits[$x][$y] .= "w"; $x-=1; $exits[$x][$y] .= "e"; break;
				case 3: $exits[$x][$y] .= "s"; $y+=1; $exits[$x][$y] .= "n"; break;
				case 4: $exits[$x][$y] .= "e"; $x+=1; $exits[$x][$y] .= "w"; break;
			}
			$distance++;
			if (empty($dist[$x][$y])) $dist[$x][$y] = $distance;
			else $dist[$x][$y] = max($dist[$x][$y], $distance); //if multiple branches collide, keep the distance value of the highest one
			$leftmost = min($x, $leftmost); //keep track of the outer reaches of the map
			$rightmost = max($x, $rightmost);
			$upmost = min($y, $upmost);
			$downmost = max($y, $downmost);
			if ($firstbranch) {
				if ($distance >= $maxdistance && ($x != 0 || $y != 0)) { //first branch will always go the maximum distance
					$enc[$x][$y] = "BOSS:$distance:" . $enc[$x][$y]; //put the boss here. the boss is placed in front so that the map renderer can read it
					$endbranch = true;
					$firstbranch = false;
				}
			} elseif ($x != 0 || $y != 0) {
				$stopchance = $maxdistance - $distance + 1;
				//echo "Stopping on 1 out of $stopchance<br />";
				if (rand(1,$stopchance) <= 1) { //chance to terminate branch is based on how close it is to $maxdistance
					$endbranch = true;
					if (rand(1,100) <= $encchance) $enc[$x][$y] .= $dist[$x][$y] . ":"; //additional chance to put an encounter on this dead end
					if (rand(1,100) <= $lootchance) $loot[$x][$y] .= $dist[$x][$y] . ":"; //same with loot
				}
			}
			if ($endbranch) { //branch has terminated
				$endbranch = false;
				$branches++;
				if (rand($branches,$complexity) >= $complexity) { //chance to terminate dungeon increases as $branches gets closer to $complexity
					$continue = false; //terminate generation here
				} else {
					$x = array_rand($exits); //pick a new random room to start a new branch from
					$y = array_rand($exits[$x]);
					$distance = $dist[$x][$y]; //get distance of that room
					$newdir = rand(1,strlen($exits[$x][$y])); //select a random exit from the room we're picking up from
					$newexit = substr($exits[$x][$y], $newdir, 1);
					switch ($newexit) { //reset the dir as if we're coming from one of the exits of the room
						case "n": $dir = 3; break;
						case "w": $dir = 4; break;
						case "s": $dir = 1; break;
						case "e": $dir = 2; break;
						default: $dir = 0; break; //an exit wasn't found, probably, so we'll pick a random one on loopback
					}
				}
			}
		}
		$room[0][0] .= "ENTRANCE:VISITED:"; //add the entrance and visited tags to the first room
		$roomstr = "";
		$encstr = "";
		$lootstr = "";
		foreach ($exits as $x => $yy) { //now we'll go through each existing room and actually build the strings
			foreach ($yy as $y => $stuff) {
				$roomstr .= "ROOM:$x,$y:EXITS:$stuff:" . $room[$x][$y] . "|";
				if (!empty($enc[$x][$y])) $encstr .= "$x,$y:" . $enc[$x][$y] . "|";
				if (!empty($loot[$x][$y])) $lootstr .= "$x,$y:" . $loot[$x][$y] . "|";
			}
		}
		$dungeonquery = "INSERT INTO `Dungeons` (`gate`, `topmost`, `leftmost`, `rightmost`, `bottommost`, `floor`, `room`, `enc`, `loot`, `land`, `boss`, `loottypes`, `enemytypes`, `minpower`, `maxpower`, `minloot`, `maxloot`) VALUES ($gate, $upmost, $leftmost, $rightmost, $downmost, $floor, '$roomstr', '$encstr', '$lootstr', $land, '$boss', '$loots', '$enemies', $minpower, $maxpower, $minloot, $maxloot);";
		mysqli_query($connection, $dungeonquery); //insert da dungone.
		$dungeonid = mysqli_insert_id($connection);
		if (empty($_POST['servergen'])) {
			$charrow['dungeon'] = $dungeonid;
			$charrow = spendFatigue(15,$charrow);
			mysqli_query($connection, "UPDATE Characters SET dungeon = $dungeonid, dungeonrow = 0, dungeoncol = 0 WHERE Characters.ID = " . $charrow['ID'] . " LIMIT 1;");
			echo "Dungeon generated!<br />";
			include 'dungeons.php';
		} else {
			//Code to store the ID of the dungeon on the character so that they go into and clear it when they reach that part of their house goes here
		}
	} else {
		echo "ERROR: Invalid dungeon specified. Or you're already in a dungeon!<br />";
	}
}

require_once "footer.php";
?>