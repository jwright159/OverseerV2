<?php
function spendFatigue($fatigue, $charrow) {
	global $connection;
	if ($charrow['dreamingstatus'] == "Awake") {
		$currentfatiguestr = 'wakefatigue';
		$otherfatiguestr = 'dreamfatigue';
		$otherstriferstr = 'dreamself';
	} else {
		$currentfatiguestr = 'dreamfatigue';
		$otherfatiguestr = 'wakefatigue';
		$otherstriferstr = 'wakeself';
	}
	$newfatigue = $charrow[$currentfatiguestr] + $fatigue;
	$charrow[$currentfatiguestr] = $newfatigue;
	$reducedfatigue = max($charrow[$otherfatiguestr] - floor($fatigue * 0.3), 0);
	$charrow[$otherfatiguestr] = $reducedfatigue;
	mysqli_query($connection, "UPDATE `Characters` SET `$currentfatiguestr` = $newfatigue, `$otherfatiguestr` = $reducedfatigue WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
	$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = " . $charrow[$otherstriferstr] . " LIMIT 1");
	$striferow = mysqli_fetch_assoc($striferesult);
	$newhealth = $striferow['health'] + floor($striferow['maxhealth'] * ($fatigue / 100));
	if ($newhealth > $striferow['maxhealth']) $newhealth = $striferow['maxhealth'];
	$newenergy = $striferow['energy'] + floor($striferow['maxenergy'] * ($fatigue / 100));
	if ($newenergy > $striferow['maxenergy']) $newenergy = $striferow['maxenergy'];
	mysqli_query($connection, "UPDATE `Strifers` SET `health` = $newhealth, `energy` = $newenergy WHERE `Strifers`.`ID` = " . $charrow[$otherstriferstr] . " LIMIT 1");
	return $charrow;
}

function loadSessionrow($id) {
	global $connection;
	$result = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `ID` = $id");
	$row = mysqli_fetch_array($result);
	return $row;
}

function loadStriferow($id) {
	global $connection;
	$result = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `ID` = $id");
	$row = mysqli_fetch_array($result);
	return $row;
}

function initGrists() { //compiles an array with all grists in the game
	global $connection;
	$result2 = mysqli_query($connection, "SELECT * FROM `Grists` ORDER BY `tier` ASC"); //document grist types now so we don't have to do it later
  $totalgrists = 0;
  while ($gristrow = mysqli_fetch_array($result2)) {
    $grist[$totalgrists] = $gristrow;
    $totalgrists++;
  }
  return $grist;
}

//STAT/ACHIEVEMENT LIBRARY DOCUMENTATION

//1. if you want to get a stat value, use getStat(character row, stat you want)
//2. if you want to create or modify a stat, use updateStat(character row, stat you want, value you want it to have it)
//3. if you want to just increment a stat by 1 use incrementStat(character row, stat you want to increment), sumStat(row, stat, increment) for everything else
//4. if you want to call by ID instead of character row just use function(getChar($char), whatever)
//5. onStat and maxStat are useful for ranking/achievement values

function getStat($char, $stat){ //gets a charrow and a stat and returns the value of that stat
	$stats = explode("|", $char['stats']); // [0] => [creation:143423423423], [1] => [stat2: 1]...
	for($i=0; !empty($stats[$i]); $i++){
		$result = explode(":", $stats[$i]); // [0] => [creation], [1] => [1424242323224124] ....
		if ($result[0] == $stat) return $result[1]; //if any stat found is $stat, return its value
	}
	return null; //if there's no stat found return null
}

function incrementStat($char, $stat){ //adds 1 to a stat, for the lazy
	$statvalue = getStat($char, $stat);
	if($statvalue==null) $statvalue = 0; //to save a function call
	updateStat($char, $stat, $statvalue+1); //if it doesn't exist, it'll create it
}

function sumStat($char, $stat, $value){ //adds $value to a stat
	$statvalue = getStat($char, $stat);
	if($statvalue==null) $statvalue = 0; //to save a function call
	updateStat($char, $stat, $statvalue+$value); //if it doesn't exist, it'll create it
}

function updateStat($char, $stat, $value){ //gets a charrow, a stat and a value and adds the stat if it doesn't exist, calls modifyStat if it does
	if(getStat($char,$stat)!=null) modifyStat($char,$stat,$value);
	else{
		$string = $char['stats'] . $stat . ':' . $value . '|'; //just adds the stat at the end if it doesn't exist
		writeStat($char,$string); //no point in even searching the row again
	}
}

function maxStat($char, $stat, $value){ //ensures a value only gets stored if it's the new highest value
	$top = getStat($char,$stat);
	if($top<$value OR $top==null) updateStat($char,$stat,$value);
}

function onStat($char, $stat){ //sets a stat to 1
	if(getStat($char,$stat)!=1) updateStat($char,$stat,1);
}

function toggleStat($char,$stat){ //toggles between 1 and 0
	if(getStat($char,$stat)!=1) updateStat($char,$stat,1);
	else updateStat($char,$stat,0);
}

// Achievements

function setAchievement($char, $achievement){//adds an achievement, displays message and returns true if it's successfully added
	if(!getAchievement($char,$achievement)){
		echo '<div id="notification"><img src="/images/achievements/'.$achievement.'.png" align="middle"><strong>ACHIEVEMENT UNLOCKED!</strong></div>';
		$string = $char['achievements'] . $achievement . '|';
		writeAchievement($char,$string);
		return true;
	}
	return false;
}

function sendAchievement($char, $achievement){//adds an achievement to a different character, displays message and returns true if it's successfully added
	if(!getAchievement($char,$achievement)){
		notifyCharacter($char['ID'], '<img src="/images/achievements/'.$achievement.'.png" align="middle">ACHIEVEMENT UNLOCKED!');
		$string = $char['achievements'] . $achievement . '|';
		writeAchievement($char,$string);
		return true;
	}
	return false;
}


function getAchievement($char, $achievement){ //returns true if achievement is found, false otherwise
	$achievements = explode("|", $char['achievements']);
	foreach($achievements as $cheev){
		if($cheev==$achievement) return true;
	}
	return false;
}


// Notifications
// notifySession and notifyCharacter/notifyCharacterOnce are the only functions you'll ever need to use, everything else is already coded

function checkNotifications($char){ //displays the oldest notification found in the character's notification table and removes it
	$notes = explode("|", $char['notifications']);
	if($notes[0]!=''){
		echo '<div id="notification"><strong>' . $notes[0] .'</strong></div>';
		if(count($notes)>2){
			unset($notes[0]);
			$notes=array_values($notes);
			$string = implode("|", (array)$notes);
		}
		else $string = '';
		writeNotifications($char, $string);
	}
}

function notifySession($char, $message){ //sends an unique notification to every other player in the session
	$send = true;
	$sent = explode("|",$char['notif_history']);
	foreach($sent as $notif){
		if($notif==$message) $send=false;
	}
	if($send) appendNotifications($char, $message);
	else return false;
}

function notifyCharacterOnce($char, $charid, $message){ //sends an unique notification to charid, which is the ID, not the row
	$send = true;
	$sent = explode("|",$char['notif_history']);
	foreach($sent as $notif){
		if($notif==$message) $send=false;
	}
	if($send) appendNotificationsOnceChar($char, $charid, $message);
	else return false;
}

function notifyCharacter($charid, $message){ //sends a notification to charid, which is the ID, not the row
	//make sure to only use this where it can't be spammed
	global $connection;
	$fixedstring=str_replace("|","",$message);
	$query= "UPDATE Characters SET notifications=concat(notifications,'" . $fixedstring . "|') WHERE ID=" . $charid . ";";
	mysqli_query($connection, $query);
}


//You probably won't ever need to actually use these yourself

function appendNotificationsOnceChar($char, $charid, $string){ //appends a unique string to the notifications column of a single charid
	global $connection;
	$fixedstring=str_replace("|","",$string);
	$query= "UPDATE Characters SET notifications=concat(notifications,'" . $fixedstring . "|') WHERE ID=" . $charid . ";";
	mysqli_query($connection, $query);
	$query2= "UPDATE Characters SET notif_history=concat(notif_history,'" . $fixedstring . "|') WHERE ID=" . $char['ID'] . ";";
	mysqli_query($connection, $query2);
}

function appendNotifications($char, $string){ //appends a string to the notifications column of every sessionmate of $char 
	global $connection;
	$members = mysqli_query($connection, "SELECT members FROM Sessions WHERE ID=". $char['session'] . ";");
	$membersarray = mysqli_fetch_array($members);
	$sesids = rtrim($membersarray['members'], "|"); //removes the last slash, 1|2|3| => 1|2|3 
	$sesidsarray = explode("|", $sesids); // [1,2,3]
	$sess= implode(",", $sesidsarray); // 1,2,3
	$fixedstring=str_replace("|","",$string);
	$query= "UPDATE Characters SET notifications=concat(notifications,'" . $fixedstring . "|') WHERE ID IN (" . $sess . ") AND ID!=" . $char['ID'] .";";
	mysqli_query($connection, $query);
	$query2= "UPDATE Characters SET notif_history=concat(notif_history,'" . $fixedstring . "|') WHERE ID=" . $char['ID'] . ";";
	mysqli_query($connection, $query2);
}

function writeNotifications($char, $string){ //for when you want to write a new state of the notifications table for a single character, DOESN'T APPEND | BY ITSELF
	global $connection;
	$query= "UPDATE Characters SET notifications='" . $string . "' WHERE ID=" . $char['ID'] . ";";
	mysqli_query($connection, $query);
}


function removeStat($char, $stat){
	if(getStat($char, $stat)!=null); //if it doesn't exist, do nothing
	else modifyStat($char, $stat, null); //call deletion otherwise
}

function offStat($char,$stat){
	if(getStat($char,$stat)!=0) updateStat($char,$stat,0);
}

function writeStat($char, $string){ //writes a new stat string for a character into the DB, pretty much to get SQL out of the rest
	global $connection;
	$query= "UPDATE Characters SET stats='" . $string . "' WHERE ID=" . $char['ID'] . ";";
	mysqli_query($connection, $query);
}

function writeAchievement($char, $string){ //writes a new achievement string for a character into the DB, pretty much to get SQL out of the rest
	global $connection;
	$query= "UPDATE Characters SET achievements='" . $string . "' WHERE ID=" . $char['ID'] . ";";
	mysqli_query($connection, $query);
}

function modifyStat($char, $stat, $value){ //gets a charrow, a stat and a value and updates it
	$stats = explode("|", $char['stats']);
	for($i=0; !empty($stats[$i]); $i++){
		$candidate = explode(":", $stats[$i]); //checks if it already exists
		if ($candidate[0]==$stat){ //if it's found, we update it
			if($value==null){// if it's null, trigger deletion
				unset($stats[$i]); //removes the stat, but the indexes are fucked up now
				$stats = array_values($stats); //so we fix em
				writeStat($char, (implode("|", $stats))); //and we write the changes without the array 
				return 1; //stops the function
			}

			$stats[$i] = $candidate[0] . ':' . $value; //[$i] => [$stat:oldvalue] becomes [$i] => [$stat:$value]
			writeStat($char,(implode("|", $stats))); // writes ....stat:value|stat2:value2|$stat:$value...| into the char's achievementrow
			return 0; //stops the function
		}
	}
	//does nothing if it doesn't find the stat
}

//GRIST
//
//

function modifyGrist($griststr, $type, $amount) { //adds/subtracts $amount from the user's reserve of $type grist and writes to $griststr
	$grist = explode("|", $griststr);
	$newstr = "";
	$i = 0;
	$foundgrist = false;
	while (!empty($grist[$i])) {
		$thisgrist = explode(":", $grist[$i]);
		if ($thisgrist[0] == $type) {
			$thisgrist[1] = strval(intval($thisgrist[1]) + $amount);
			$foundgrist = true;
		}
		$newstr .= $thisgrist[0] . ":" . $thisgrist[1] . "|";
		$i++;
	}
	if (!$foundgrist) { //the player is discovering this grist for the first time!
		$newstr .= $type . ":" . $amount . "|";
	}
	return $newstr;
}

function howmuchGrist($griststr, $type) { //returns the amount of $type grist in $griststr, works for players and items
	if (strpos($griststr, $type . ":") !== false) { //skip the whole calculation if the grist isn't in the string
		$grist = explode("|", $griststr);
		$i = 0;
		while (!empty($grist[$i])) {
			$thisgrist = explode(":", $grist[$i]);
			if ($thisgrist[0] == $type) {
				return intval($thisgrist[1]);
			}
			$i++;
		}
	}
	return 0;
}

function specialArray($string, $search) { //looks up and returns the full tag with the keyword $search
	$boom = explode("|", $string);
	$i = 0;
	while (!empty($boom[$i])) {
		$boomo = explode(":", $boom[$i]);
		if ($boomo[0] == $search) return $boomo;
		$i++;
	}
	$boomo[0] = "nope";
	return $boomo;
}

function gristImage($name) { //old hardcoded grist image producer because sometimes the cheapest solution is the best
	if ($name == "Rainbow" || $name == "Polychromite" || $name == "Opal" || $name == "Plasma") $name .= ".gif";
	else $name .= ".png";
	$name = "images/grist/" . $name;
	return $name;
}

function bonusStr($bonus) { //converts a number to a string and puts a + in front if positive
	if ($bonus > 0) $b = "+" . strval($bonus);
	else $b = strval($bonus);
	return $b;
}

function chainArray($charrow) {
	//Function takes a character row and returns a boolean array which contains true or false at each player index in the
	//session, true meaning the player is part of the chain, false meaning they are not

        global $connection;
	$chumroll = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = '$charrow[session]';");
  $auto = false; //Condition for automatic success (e.g. flight, god tier).
	while ($chumrow = mysqli_fetch_array($chumroll)) {
		$sessionmates[$chumrow['ID']] = $chumrow;
		$chain[$chumrow['ID']] = false;
		if ($auto) $chain[$chumrow['ID']] = true;
    else $duderow[$chumrow['ID']] = $chumrow; //set this so that we can use the row later by ID
		//NOTE - We could save some processing by having a flag in the session that is set to true when the whole session is connected
		//and making that one of the success conditions...if there's a good way to deal with de-building the house negating that.
	}
  //we no longer need to pull gaterow because gatescleared directly stores gates... well, cleared
  if ($charrow['gatescleared'] >= 1) $chain[$charrow['ID']] = true; //Access to your own Land is granted when you reach your first Gate.
	if ($charrow['gatescleared'] >= 2 && !$auto) { //Can access /second/ gate #CANON. Check out access chain, but no need if auto is true
		$currentrow = $charrow;
    $nobreak = true; // fix an error caused by $nobreak not being defined yet
		while (($currentrow['server'] != $charrow['ID']) && ($currentrow['server'] != 0) && $nobreak) {
			//Above: Keep checking as long as there's a server player that isn't this player.
			$nobreak = false;
			$minus3row = $minus2row;
			$minus2row = $minus1row;
			$minus1row = $currentrow; //These might not all exist, but the ones that don't will just be empty so they'll fail the relevant checks
			$currentrow = $duderow[$currentrow['server']]; //Guaranteed to exist due to the while condition
      //echo "DEBUG: Checking gate availability for " . $currentrow['name'] . "<br />";
			if ($currentrow['gatescleared'] >= 6 && $minus3row['gatescleared'] >= 6) $nobreak = true;
			if ($currentrow['gatescleared'] >= 4 && $minus2row['gatescleared'] >= 4) $nobreak = true;
			if ($currentrow['gatescleared'] >= 2 && $minus1row['gatescleared'] >= 2) $nobreak = true;
			if ($nobreak) $chain[$currentrow['ID']] = true; //A successful connection was found, so this character is added to the chain
		}
	}
	return $chain; //Will be all false if we weren't build up to the first gate yet
}
function getBonusname($n) {
	switch ($n) {
		case 0: return "aggrieve";
		case 1: return "aggress";
		case 2: return "assail";
		case 3: return "assault";
		case 4: return "abuse";
		case 5: return "accuse";
		case 6: return "abjure";
		case 7: return "abstain";
		case 8: return "defense";
		default: return "ERROR";
	}
}

function getChar($charid) {
  global $connection, $characters, $querycount;
  if($charid==-1) return array("name"=>"[MISSING PLAYER]", "colour"=>"000000");
  if (!isset($characters[$charid])) {
    $querycount++;
    $charquery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '" . $charid . "' LIMIT 1;");
    $characters[$charid] = mysqli_fetch_array($charquery, MYSQLI_ASSOC);
  }
  return $characters[$charid];
}

//PROFILE """"API""""

function profileString($charid){ //returns a formatted link to a profile, colored with player's color
	$chara=getChar($charid);
	if($chara) return "<a href='profile.php?ID=" . $chara['ID'] . "'><span style='color:#" . $chara['colour'] . "'>" . $chara['name'] . "</span></a>";
	else return "[ERROR RETRIEVING PLAYER ID]";
}

function profileStringSoft($charid){ //same as profileString but no underlines
	$chara=getChar($charid);
	if($chara) return "<a style='text-decoration:none' href='profile.php?ID=" . $chara['ID'] . "'><span style='color:#" . $chara['colour'] . "'>" . $chara['name'] . "</span></a>";
	else return "[ERROR RETRIEVING PLAYER ID]";
}


//more efficient versions but require the charrow instead of IDs, also it's like I'm a real java programmer
function rowProfileString($row){ //returns a formatted link to a profile, colored with player's color;
	if($row) return "<a href='profile.php?ID=" . $row['ID'] . "'><span style='color:#" . $row['colour'] . "'>" . $row['name'] . "</span></a>";
	else return "[ERROR RETRIEVING PLAYER ID]";
}

function rowProfileStringSoft($row){ //same as rowProfileString but no underlines
	if($row) return "<a style='text-decoration:none' href='profile.php?ID=" . $row['ID'] . "'><span style='color:#" . $row['colour'] . "'>" . $row['name'] . "</span></a>";
	else return "[ERROR RETRIEVING PLAYER ID]";
}

//end of profile shit

function hex2RGB($hexStr, $returnAsString = false, $seperator = ',')
{
    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
    $rgbArray = array();
    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
        $colorVal = hexdec($hexStr);
        $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
        $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
        $rgbArray['blue'] = 0xFF & $colorVal;
    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
        $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
        $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
        $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    } else {
        return false; //Invalid hex color code
    }
    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
}

/**
 * Takes the given character row and re-initializes the character's strife rows based on it
 */
function strifeInit($charrow)
{
	//NOTE: Recalculates power, abilities, equipment bonuses (NOT the regular bonus field), and on-hit effects
	//A lot of this will be going over equipped items and getting the proper properties out of them
	//We probably want to grab the current health% and energy% and multiply them into the calculated maximums to get new current health/energy values
	//NOTE: We will calculate waking and dream rows separately, but concurrently. Some things like abilities and rung will affect both.
	//Most will only affect the waking row.

	//General structure: $wakerow and $dreamrow are built containing max health and energy, equip bonuses, abilities, and on-hit effects.

        global $connection;

	//Health formula: +5 for 1 rung, +10 for 3 rungs, +15 for 9 rungs, +20 for 27 rungs, etc. The following code does that.
	$charresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
	$charrow = mysqli_fetch_array($charresult);
	$viscosity = 10; //Used for waking AND dreaming health AND energy
	$rungs = $charrow['echeladder'] - 1; //NOTE - First rung you start with a base of 10. It is not counted here.
	$i = 1;
	$increment = 5;
	while ($rungs > 0) {
		$viscosity += (min($i,$rungs) * $increment); //If we have the rungs to fill in the current block, fill it all in.
		//Otherwise fill in as many rungs as we have. In the seconds case $rungs will end up below or equal to 0 and we are done.
		$rungs -= $i;
		$i *= 3;
		$increment += 5;
	}
	$dreampower = $charrow['echeladder'];
	$wakepower = $charrow['echeladder']; //This needs to be calculated properly!
	//NOTE - Limit weapon-based power based on Echeladder rung. You need a decent level to unlock the potential of high-end weapons.
	$wakeeffects = ""; //This also does!
	$wakebonuses = ""; //As does this!
	$equips = explode("|", $charrow['equips']);
	$portfolio = explode("|", $charrow['strifedeck']);
	$i = 0;
	$bonuses = array();
	$limit = ($charrow['echeladder'] * 9999) / 413;
	$equippedcomputer = 0;
	while (!empty($equips[$i])) { //sort through player's equipped, er, equipment
		$equip = explode(":", $equips[$i]);
		$weapon = false;
		$wid = 0;
		if ($equip[0] == "main") { //main weapon slot
			if ($equip[1] == 1) { //main weapon is equipped
				$wid = $portfolio[0]; //get the item ID of the weapon equipped in main
				$weapon = true;
			}
		} elseif ($equip[0] == "off") { //off weapon slot
			if ($equip[1] == 1) { //off weapon is equipped
				$wid = $portfolio[1]; //get the item ID of the weapon equipped in off
				$weapon = true;
			}
		} else $wid = $equip[1]; //other wearable, this argument will just be the ID of the item
		if ($wid != 0) { //if there's actually something equipped here
			$wresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = $wid");
			$wrow = mysqli_fetch_array($wresult);
			$n = 0;
			while ($n < 8) { //go through each bonus
				$bonus = getBonusname($n);
				if (empty($wrow[$bonus])) $wrow[$bonus] = 0;
				if (empty($bonuses[$bonus])) $bonuses[$bonus] = 0;
				$bonuses[$bonus] += $wrow[$bonus]; //add bonus amount to total bonuses
				$n++;
			}
			if ($weapon) { //is a weapon, add power
				$powerplus = min($wrow['power'], $limit);
				if ($equip[0] == "off") $powerplus = $powerplus / 2; //offhand weapons only add half the weapon's power
				$wakepower += $powerplus;
			} else { //is wearable, add defense
				$bonuses['defense'] += $wrow['power'];
			}
			$wakeeffects .= $wrow['effects']; //add any effects the equipment has
			$wakestatus .= $wrow['status']; //add any status the equipment grants
		}
		$i++;
	}
	if (strpos($wakeeffects, "COMPUTER") !== false) $equippedcomputer = 1;
	if ($equippedcomputer != $charrow['equippedcomputer']) mysqli_query($connection, "UPDATE `Characters` SET `equippedcomputer` = " . $equippedcomputer . " WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
	$n = 0;
	while ($n < 9) { //go through each bonus one last time, this time including defense
		$bonus = getBonusname($n);
		if (empty($bonuses[$bonus])) $bonuses[$bonus] = 0;
		$wakebonuses .= strtoupper($bonus) . ":1:" . strval(min($bonuses[$bonus], $limit)) . "|"; //add final amount to bonus str. Duration 1 so that when put into the bonus field it only lasts 1 turn
		//Duration is because we put the equipbonuses field into the bonuses field every round
		$n++;
	}
	//We do an ability check here to grab abilities relevant to initialization. It happens last so it can affect existing values.
	//Not many abilities will go here. Most will be done "on the fly" in striferesolve
	$abilities = explode('|', $charrow['abilities']);
	$i = 0;
	while(!empty($abilities[$i])) { //We've found an ability
		switch ($abilities[$i]) {
		case "2": //Life's Bounty, ID 2
			$wakerow['maxhealth'] = ceil($wakerow['maxhealth'] * 1.15);
			$dreamrow['maxhealth'] = ceil($dreamrow['maxhealth'] * 1.15);
			break;
		default:
			break;
		}
		$i++;
	}
	//Note - $charrow['abilities'] and $charrow['aspect'] are copied verbatim into both rows.
	//We could use a single CASE query but this function will be occurring infrequently enough that two queries won't really break the bank.
	//Still if anyone feels like updating it that'd be fine. At the moment it sets both rows to the dreamself's parameters
	//and then modifies the wakeself parameters that are different immediately after.
	mysqli_query($connection, "UPDATE `Strifers` SET `maxhealth` = $viscosity, `maxenergy` = $viscosity, `abilities` = '" . $charrow['abilities'] . "', `aspect` = '" . $charrow['aspect'] . "', `echeladder` = " . $charrow['echeladder'] . ", `maxpower` = $dreampower, `power` = $dreampower WHERE `Strifers`.`ID` IN (" . $charrow['wakeself'] . ", " . $charrow['dreamself'] . ") LIMIT 2;");
	mysqli_query($connection, "UPDATE `Strifers` SET `maxpower` = $wakepower, `power` = $wakepower, `effects` = '$wakeeffects', `equipbonuses` = '$wakebonuses', `equipstatus` = '$wakestatus' WHERE `Strifers`.`ID` = " . $charrow['wakeself'] . " LIMIT 1;");
}

/**
 * Takes a character row and a number of rungs, updates all the associated database entries with the rung-up
 */
function gainRungs($charrow,$rungs)
{
     global $connection;
	$class = $charrow['class'];
	$aspect = $charrow['aspect'];
	$oldrung = $charrow['echeladder'];
	$levelcap = 612;
	if ($oldrung >= $levelcap) {
		return false; //The code calling this function should produce a unique "You already topped out your Echeladder" message
	} else {
		$firstnewrung = $oldrung + 1;
		$newrung = $charrow['echeladder'] + $rungs;
		if ($newrung >= $levelcap) { //Echeladder completed
			$newrung = $levelcap; //No going over the level cap!
			$message = $charrow['name'] . " has ascended to the top of their Echeladder!<br />";
			setAchievement($charrow, 'topeche');
		} else {
			$message = $charrow['name'] . " successfully climbs $rungs rungs on their Echeladder.<br />";
		}
		$message .= $charrow['name'] . " comes to rest on rung: Personalized Rungs Not Implemented!<br />"; //Replace this with actual personalized rungs
		$boondollars = $charrow['boondollars'];
		$newdollars = 0;
		$rungprocessor = $firstnewrung;
		while ($rungprocessor <= $newrung) { //Stuff that we're doing once for every rung goes in here
			$newdollars += ($rungprocessor * 55);
			$rungprocessor++;
		}
		$abilities = $charrow['abilities'];
		$abilityresult = mysqli_query($connection, "SELECT `ID`,`Name` FROM `Abilities` WHERE
		`Abilities`.`Class` IN ('$class', 'All') AND `Abilities`.`Aspect` IN ('$aspect', 'All') AND `Abilities`.`Rungreq` BETWEEN 1 AND $newrung;");
		//NOTE - No need to check for god tiers here. They'll be listed as requiring a rung of "1025" and have a god tier requirement instead.
		if ($abilityresult) {
			while ($row = mysqli_fetch_array($abilityresult)) {
				if (strpos("|" . $charrow['abilities'], "|" . $row['ID'] . "|") === false) {
					$abilities .= intval($row['ID']) . "|"; //Add this ability to the ability string
					$message .= $charrow['name'] . " gains a new ability: $row[Name]!<br />";
				}
			}
		}
		mysqli_query($connection, "UPDATE `Characters` SET `echeladder` = $newrung, `abilities` = '$abilities', `boondollars` = $boondollars
		WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1;");
		strifeInit($charrow);
		return $message;
	}
}

/**
 * Take an effect/status/similar string, search for a tag (or component) and build an array containing
 * all bits that have the search term in it and the associated arguments. Should be cleaner/faster than just a straight up explosion search.
 * @param bool $first If true, you only care about the first tag that you find; in this case, it'll return just the one array.
 */
function surgicalSearch($string, $search, $first = false)
{
	$i = 0;
	$string = "|" . $string; //add a | to the beginning so that you can search for "|EFFECT:", for instance, and still be able to count the first tag
	$pos = strpos($string, $search); //Search for ANY occurrence of the search in the string. Add format characters (:, |, etc) as necessary to ensure you pick up the right instances
	//The beauty of this system is that you can search for multiple congruent arguments, such as a status with exactly 1 turn remaining, and they'll still be split into separate array keys
	$returnarray = array();
	while ($pos !== false) {
		$beginning = strrpos(substr($string, 0, $pos + 1), "|"); //grab the | that begins the tag the first term is in.
		//We add 1 in case "|" was used to find the search term in a specific place within the tag, it shouldn't make a difference if not
		$ending = strpos(substr($string, $pos + 1), "|"); //grab the | that ends it
		$thistag = substr($string, $beginning + 1, ($ending + $pos) - $beginning); //pull the entire tag that contains the search
		if ($first) { //We only care about the first tag found.
			$returnarray = explode(":", $thistag);
			$pos = false; //End the loop.
		} else {
			$returnarray[$i] = explode(":", $thistag); //okay, so there are SOME explosions involved
			$string = str_replace($thistag, "", $string); //remove the tag that we just extracted so that it doesn't come up again
			$i++;
			$pos = strpos($string, $search); //conduct the search again to see if there are more
		}
	}
	return $returnarray; //returns every tag that contains the search terms, already split up into arguments
}

/**
 * Writes a message to the debug log
 */
function logDebugMessage($debugmsg)
{
  $time = date('Y-m-d H:i:s');            //gets current time
  $debugmsg = "($time) $debugmsg";
  $debugfile = fopen($_SERVER['DOCUMENT_ROOT'] . "/overseer/devtools/debuglog.txt", "a");    //opens the debug log
  if ($debugfile !== false)
    {
      fwrite($debugfile, "<br />\n" . $debugmsg);          //adds error message
      fclose($debugfile);                                  //closes and saves debug log
    }
}

/**
 * Writes a message to the cheat log, same format as logDebugMessage
 */
function logCheatMessage($cheatmsg)
{
  $time = date('Y-m-d H:i:s');
  $cheatmsg = "($time) $cheatmsg";
  $cheatfile = fopen($_SERVER['DOCUMENT_ROOT'] . "/devtools/cheatpolice.txt", "a");
  if ($cheatfile !== false)
    {
      fwrite($cheatfile, "<br />\n" . $cheatmsg);
      fclose($cheatfile);
    }
}

/**
 * Check to see if a string is a valid grist type
 */
function checkValidGrist($teststring)
{
  $gristarray = initGrists();                         //retrieve grist names
  for ($i = 1; $i <= count($gristarray); $i++) {      //for each grist
    if ($gristarray[$i]['name'] == $teststring) {             //check if it matches the test string
      return true;                                    //if yes, return true
    }
  }
  return false;          //if none match, return false

}

function grist($griststring)
{
  $gristvalues = explode("|", $griststring);
  foreach ($gristvalues as $gristvalue) {
    $gristbreakdown = explode(":", $gristvalue);
    if (!empty($gristbreakdown[0])) {
      $gristarray[$gristbreakdown[0]] = $gristbreakdown[1];
    }
  }
  return $gristarray;
}

function logThis($string, $charID)
{
    $filename = "../logs/char".$charID.".txt";
    $newString = $string.'<br>';
    if (!file_exists($filename)) {
        $myFile = fopen($filename, "w");
		if ($myFile)
		{
			fwrite($myFile, $newString);
			fclose($myFile);
		}
    } else {
        $myFile = fopen($filename, "a");
        fwrite($myFile, $newString);
        fclose($myFile);
    }
}

function readCharLog($charID)
{
	$filename = "logs/char".$charID.".txt";
	if (!file_exists($filename))
	{
		$myFile = fopen($filename, "w");
		if ($myFile)
		{
			fwrite($myFile, '');
			fclose($myFile);
			return "Empty!";
		}
		else
			return "Character log not available - file could not be opened. This is likely a restriction of the platform the server is on.";
	}
	else
	{
		$myFile = fopen($filename, "r+");
		$logContent = fread($myFile,filesize($filename));
		ftruncate($myFile, 0);
		fclose($myFile);
		return !empty($logContent) ? $logContent : "Empty!";
	}
}

function calculateComputability($charrow) //NOTE - computability 2 is currently non-functional.
{
	$computability = 0;
	if (strpos($charrow['storeditems'], "ISCOMPUTER.") !== false) $computability = 1;
	if ($charrow['hascomputer'] != 0) $computability = 2;
	if ($charrow['equippedcomputer'] == 1) $computability = 3;
	return $computability;
}