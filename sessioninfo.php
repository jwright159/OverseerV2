<?php
$pagetitle = "Session Viewer";
$headericon = "/images/header/spirograph.png";
require_once "header.php";
require_once "includes/global_functions.php";

echo '<form action="sessioninfo.php" method="post">';
echo 'Session to retrieve info about: <input id="session" name="session" type="text" /><input type="submit" value="Examine it!" /> </form></br>';
if (!empty($_POST['session'])) $session = mysqli_real_escape_string($connection, $_POST['session']);
else if (!empty($_GET['session'])) $session = mysqli_real_escape_string($connection, $_GET['session']);
else{
	$ownsession = mysqli_query($connection, "SELECT name FROM Sessions WHERE `ID` = $charrow[session];");
	$sessiont = mysqli_fetch_array($ownsession);
	$session = str_replace("''", "'", $sessiont['name']);
}
if (!empty($session)) { //Session to examine
	$sessionesc = str_replace("'", "''", $session); //Add escape characters so we can find session correctly in database.
	$sessionresult = mysqli_query($connection, "SELECT * FROM Sessions WHERE `name` = '$sessionesc';");
	$sessionrow = mysqli_fetch_array($sessionresult);
	if ($sessionrow == '') {
		echo "ERROR - Session does not exist.</br>";
	} else {
		$gateresult = mysqli_query($connection, "SELECT * FROM Gates;"); //begin new chain-following code, shamelessly copypasted and trimmed down from Dungeons
		$gaterow = mysqli_fetch_array($gateresult); //Gates only has one row.
		$gaterow['gate0'] = 0;
		$adv = false;
		//if (strpos($userrow['storeditems'], "ADVSESSIONVIEW.") !== false || strpos($userrow['permstatus'], "SVISION") !== false) $adv = true;
		$sessionurl = str_replace("#", "%23", $session);
		$sessionurl = str_replace(" ", "%20", $sessionurl);
		echo '<a href="/sessioninfo.php?session=' . $sessionurl . '">Permanent link to this page.</a></br>';
		echo '<a href="/chainviewer.php?session=' . $sessionurl . '">Chain viewer for this session.</a></br>';
		echo "This session's head admin: " . $sessionrow['creator'] . "<br />";
		if (!empty($sessionrow['exchange']))
			echo "Player whose land hosts the Stock Exchange: " . $sessionrow['exchange'] . "<br />";
		else
			echo "This session's Stock Exchange is not yet available.<br /><br>";
		//echo "Dersite army power destroyed by this session: $sessionrow[battlefield_power]</br></br>";
		//if ($sessionrow['checkmate'] == 1) echo "This session has successfully defeated The Black King!</br></br>";


  		$characterids = explode("|", $sessionrow['members']);
  		$characterids2 = implode(",", $characterids);
  		$characterids2 = rtrim($characterids2, ', ');


  		$strifersqlquery = "SELECT * FROM Strifers WHERE owner IN (" . $characterids2 . ");";
  		$striferquery = mysqli_query($connection, $strifersqlquery);

  		$strifernames = array();
  		$striferpower = array();
  		while ($striferrow = mysqli_fetch_assoc($striferquery)){
  			$strifernames[$striferrow['ID']] = $striferrow['name'];
  			$striferpower[$striferrow['ID']] = $striferrow['power'];
  		}

  		$itemquery = mysqli_query($connection, "SELECT * FROM Captchalogue;");
  		$itemnames = array();
  		while ($itemrow = mysqli_fetch_assoc($itemquery)){
  			$itemnames[$itemrow['ID']] = $itemrow['name'];
  		}


		foreach ($characterids as $characterid) {
			$row=getChar($characterid);
			if ($row['session'] == $sessionrow['ID']) { //Paranoia: Player is a participant in this session.
				if ($row['colour'] != "") {
					$favcolour = $row['colour'];
				} else {
					$favcolour = "000000";
				}
				echo 'Player: <strong>' . rowProfileStringSoft($row) . '</strong>';
				if (!empty($row['class']) && !empty ($row['aspect'])) echo ", $row[class] of $row[aspect]";
				echo "</br>Dream status: $row[dreamer]</br>";
				#$status = str_replace("\'", "'", $row['status']);
				#echo "Currently: $status</br>";
				echo "Echeladder height: $row[echeladder]</br>";

				if($row['abstratus']){
					$abstratus = explode("|", $row['abstratus']);
					$abstratus = $abstratus[1];
					if($abstratus!="") echo "Strife Specibus: $abstratus</br>";
				}else echo "Strife Specibus: Unassigned</br>";

				if($row['strifedeck']){
	  				$weaponids = explode("|", $row['strifedeck']);
					echo "Currently equipped weapons: ";
					if ($weaponids[0] != ""){
						echo $itemnames[$weaponids[0]] ?? '[WEAPON '. $weaponids[0] . ' NOT FOUND]';
						if ($weaponids[1]!="") echo ", " . $itemnames[$weaponids[1]];
					}
					else if($weaponids[1]!="") echo $itemnames[$weaponids[1]];
				}else echo "Currently equipped weapons: Fists";
				echo "</br>";
				echo "Currently wearing: ";

				$bodygear=array();
				$headgear=array();
				$facegear=array();
				$accessory=array();

				#I'm so sorry, future code mantainer

				$equipment = explode("|", $row['equips']);
				foreach ($equipment as $equip){
					$item = explode (":", $equip);
					$item[0] = str_replace(' ', '', $item[0]);
					switch($item[0]){
						case 'bodygear':
							$bodygear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'body':
							$bodygear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'headgear':
							$headgear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'head':
							$headgear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'face':
							$facegear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'facegear':
							$facegear[$row['name']] = $itemnames[$item[1]];
							break;
						case 'accessory':
							$accessory[$row['name']] = $itemnames[$item[1]];
							break;
					}
				}
				$addcomma = False;
				if (isset($headgear[$row['name']])) {
					echo $headgear[$row['name']];
					$addcomma = True;
				}
				if (isset($facegear[$row['name']])) {
					if ($addcomma == True) echo ", ";
					echo $facegear[$row['name']];
					$addcomma = True;
				}
				if (isset($bodygear[$row['name']])) {
					if ($addcomma == True) echo ", ";
					echo $bodygear[$row['name']];
					$addcomma = True;
				} else {
					if ($addcomma == True) echo ", ";
					echo "Basic Clothes";
					$addcomma = True;
				}
				if (isset($accessory[$row['name']])) {
					if ($addcomma == True) echo ", ";
					echo $accessory[$row['name']];
				}
				echo "</br>";
				if($row['inmedium']){
					echo "Sprite: " . $strifernames[$row['sprite']] . "</br>";
					echo $strifernames[$row['sprite']] . "'s power: " . $striferpower[$row['sprite']] . "</br>";
					$spriteeffects = explode("|", $row['proto_effects']);
					if(!$spriteeffects[0]) $powerbonus = 'None';
					else $powerbonus = substr($spriteeffects[0],6);
					echo "Power bonus for enemies who receive this player's prototypings: $powerbonus</br>";
				}
				$client = getChar($row['client']);
				$server = getChar($row['server']);
				echo "Server player: ";
				if (!$server) echo "None";
				else echo rowProfileString($server);
				echo "</br>";
				echo "Client player: ";
				if (!$client['name']) echo "None";
				else echo rowProfileString($client);
				echo "</br>";
				if($row['inmedium']){
					echo "Land: Land of $row[land1] and $row[land2]</br>";

					$_gates = array(
	                       7 => 24000000,
	                       6 => 11111100,
	                       5 => 1111100,
	                       4 => 111100,
	                       3 => 11100,
	                       2 => 1100,
	                       1 => 100,
	                       0 => 0,
	                      );

					foreach ($_gates as $gate => $reqheight) {
		                if ($row['house_build'] >= $reqheight) {
		                    $gatereached=$gate;
		                    break;
		                }
		            }



					echo "Gates reached by this player: $row[gatescleared]/$gatereached</br>";
					echo "Grist types available on this player's Land: ";
					$gristtype = explode("|", $row['grist_type']);
					for($i = 0; $i < 9; $i++) { //Nine types of grist. Magic numbers >_>
						$gr = $gristtype[$i];
						echo $gr;
						if ($i != 8) {
							echo ", ";
						} else {
							echo "</br>";
						}
					}
				}
				else echo "This player hasn't entered the medium yet<br/>";
				if ($adv) { //user has a session viewer upgrade
					echo "Highest gate reached: " . strval(highestGate($gaterow, $row['house_build_grist'])) . "<br />";
					echo "Dreaming status: " . $row['dreamingstatus'] . "<br />";
					if (!empty($row['aiding'])) echo "Currently assisting in the strife of: " . $row['aiding'] . '<br />';
					else {
						echo "Strifing against: ";
						$strifestring = "";
						$row = parseEnemydata($row);
						$e = 1;
						while ($e <= 5) {
							$enamestr = 'enemy' . strval($e) . 'name';
							if (!empty($row[$enamestr])) {
								if ($strifestring == "") $strifestring = $row[$enamestr];
								else $strifestring .= ", " . $row[$enamestr];
							}
							$e++;
						}
						if ($strifestring == "") $strifestring = "Nobody.";
						echo $strifestring . '<br />';
					}
					if ($row['indungeon'] != 0) echo "Currently exploring a dungeon.<br />";
					echo "Land wealth: " . strval($row['econony']) . "<br />";
					echo "Consorts: " . $row['consort_name'];
				}
				$timediff = number_format((time() - $row['lasttick'])/(60*60));
				if($row['lasttick']!=0) echo "Last online: " . date("F j, G:i \U\T\C", $row['lasttick']) . " (" . $timediff . " hours ago)";
				else echo "Last online: Never";
				echo "<br />";
				echo "<br />";
			}
		}
	}
}
require_once "footer.php";
?>
