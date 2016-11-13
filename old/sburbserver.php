<?php
$pagetitle = "SBURB Server";
$headericon = "/images/header/spirograph.png";
require_once("header.php");
require 'includes/additem.php';

$dreambot = false;
if (strpos($charrow['storeditems'], "DREAMBOT.") !== false && $charrow['dreamingstatus'] != "Awake") {
	//items in storage with the DREAMBOT tag will grant access to the server program as one's dreamself
	$dreambot = true;
}

if (empty($_SESSION['username'])) {
  echo "Log in to mess with the server program.<br />";
} elseif (empty($_SESSION['character'])) {
	echo "Choose a character to mess with the server program.<br />";
} elseif ($charrow['dreamingstatus'] != "Awake" && !$dreambot) {
  echo "Your dream self can't access your computer!";
} else {
	echo "<!DOCTYPE html><html><head><style>gristvalue{color: #FF0000; font-size: 60px;}</style><style>gristvalue2{color: #0FAFF1; font-size: 60px;}</style><style>itemcode{font-family:'Courier New'}</style></head><body>";
	$compugood = true;
	$computability = calculateComputability($charrow);
	//Checks for whether an appropriate computing device is possessed occur here.
	if ($dreambot) {
		if (strpos($charrow['storeditems'], "ISCOMPUTER.") == 0) { //dreambot checks for a computer in storage, regardless of player computability
			echo "Your dreambot can't use the SBURB server program without access to a computer in storage!<br />";
			$compugood = false;
		}
	} else {
  if (!empty($charrow['enemydata']) || !empty($charrow['aiding'])) {
  	if (($charrow['enemydata'] != "" || $charrow['aiding'] != "") && $computability < 3) {
  		if ($compugood == true) echo "You don't have a hands-free computer equipped, so you can't use the SBURB server program during strife.<br />";
  		$compugood = false;
  	}
  }
  if (!empty($charrow['indungeon'])) {
	if ($charrow['indungeon'] != 0 && $computability < 2) {
		if ($compugood == true) echo "You'll need to use a computer from your inventory in order to retrieve and use it for SBURB on the go.<br />";
			$compugood = false;
	}
  }
  if ($computability == 0) {
  	if ($compugood == true) echo "<div class='alert alert-danger' role='alert'>You need a computer in storage or hands-free device in your inventory to use the SBURB server program.</div>";
  	$compugood = false;
  }
	}
  if ($compugood) {
  	$sesrow = loadSessionrow($charrow['session']);
  	if (!empty($_POST['client'])) { //User is registering a client player
    	$playerfound = False;
    	$registered = "";
    	$sessionmates = mysqli_query($connection, "SELECT * FROM Characters WHERE `Characters`.`ID` = " . $_POST['client']);
    	while ($row = mysqli_fetch_array($sessionmates)) {
      	if ($row['session'] == $charrow['session']) { //Make sure the client is actually in the correct session!
					if ($row['ID'] == $_POST['client'] && ($row['server'] == 0 || $row['server'] == $cid)) { //Ensure they don't already have a server player
	  				$playerfound = True;
	  				$client = mysqli_real_escape_string($connection, $_POST['client']);
	  				mysqli_query($connection, "UPDATE `Characters` SET `server` = $cid WHERE `Characters`.`ID` = $client LIMIT 1 ;");
	  				mysqli_query($connection, "UPDATE `Characters` SET `client` = $client WHERE `Characters`.`ID` = $cid LIMIT 1 ;");
	  				notifyCharacter($row['ID'], $charrow['name'] . " has become your server player!");
	  				echo "Client registered.<br />";
	  				$charrow['client'] = $client;
					} else {
	  				if ($row['server'] != "" && $playerfound != True) {
	    				$playerfound = True;
	    				echo "Client already possesses a server player: " . $row['server'] . "<br />";
	  				}
					}
      	}
    	}
    	if ($playerfound == False) {
      	echo "Target player was not found in your session.<br />";
    	}
  	}

 		if (empty($charrow['client'])) { //No client: Print out a form with potential options.
			echo "<div class='alert alert-warning' role='alert'>You haven't registered a player as your client yet.</div>";
			$clients = explode("|", $sesrow['members']);
			$i = 0;
			$idsearch = "";
			while (!empty($clients[$i])) {
				$idsearch .= "`ID` = " . strval($clients[$i]) . " OR ";
				$i++;
			}
			$idsearch = substr($idsearch, 0, -4);
			if ($idsearch != "") {
				$foundone = false;
				$clientresult = mysqli_query($connection, "SELECT `ID`, `name` FROM `Characters` WHERE `server` = 0 AND ($idsearch)"); //Don't list players who already have a server player
				while ($clientrow = mysqli_fetch_array($clientresult)) {
					if (!$foundone) {
						$foundone = true;
						echo '<form action="sburbserver.php" method="post">Register client player: <select name="client">';
					}
					echo '<option value="' . strval($clientrow['ID']) . '">' . $clientrow['name'] . '</option>';
				}
				if ($foundone) {
  				echo '</select><input type="submit" value="Connect it!" /></form><br />';
				} else echo "There are no players in your session to whom you can connect! Maybe you should pester some friends?<br />";
			} else echo "There are no players in your session to whom you can connect! Maybe you should pester some friends?<br />";
		} else { //Player has a client registered
			$clientresult = mysqli_query($connection, "SELECT * FROM Characters WHERE `ID` = '" . $charrow['client'] . "'");
			$clientrow = mysqli_fetch_array($clientresult);
			$landgrists = explode("|", $clientrow['grist_type']);
			if (!empty($landgrists[1])) $tier1grist = $landgrists[1];
			$cgrists = explode("|", $clientrow['grists']);
			$builds = explode(":", $cgrists[0]); //build grist will always be first
			$build = $builds[1];
			$t1s = explode(":", $cgrists[1]); //land first tier will always be second (canon)
			if (!empty($t1s[1]))$tier1 = $t1s[1];
			if ($clientrow['server'] != $cid) {
				echo "Something went amiss, and your client player doesn't have you set as their server! We've just attempted to fix this, but if you see this message multiple times, please submit a bug report.<br />";
				mysqli_query($connection, "UPDATE `Characters` SET `server` = $cid WHERE `Characters`.`ID` = '" . $clientrow['ID'] . "' LIMIT 1;");
			}

		  if (!empty($_POST['build'])) { //Working on the client player's house
   			if (intval($_POST['build']) > 0) {
     			if (intval($_POST['build'] <= $build)) {
						$buildit = intval($_POST['build']);
						$newtotal = $buildit + $clientrow['house_build'];
						$newgrist = modifyGrist($clientrow['grists'], "Build_Grist", ($buildit * -1));
						mysqli_query($connection, "UPDATE `Characters` SET `house_build` = '$newtotal', `grists` = '$newgrist' WHERE `Characters`.`ID` = '" . $clientrow['ID'] . "' LIMIT 1 ;"); //Debit the grist
						echo "Build successful!<br />";
						if($newtotal>=24000000) setAchievement($charrow, 'gate7');
						notifyCharacter($clientrow['ID'], $charrow['name'] . " has spent " . $buildit . " Build Grist building your house!");
						$clientrow['house_build'] = $newtotal;
						$build -= $buildit;
     			} else {
						echo "Build failed: Client lacks required Build Grist.<br />";
     			}
   			} elseif (intval($_POST['build']) < 0) { //you can unbuild to get build grist back now!
   				if (intval($_POST['build'] <= $clientrow['house_build'])) {
					if ($clientrow['house_build'] + intval($_POST['build']) < 0) {
						echo "You can't take more grist than you invested into the house! There's probably laws against it.<br>";
					} else {
						$buildit = intval($_POST['build']);
						$newtotal = $buildit + $clientrow['house_build'];
						$newgrist = modifyGrist($clientrow['grists'], "Build_Grist", ($buildit * -1));
						mysqli_query($connection, "UPDATE `Characters` SET `house_build` = '$newtotal', `grists` = '$newgrist' WHERE `Characters`.`ID` = '" . $clientrow['ID'] . "' LIMIT 1 ;"); //Credit the grist
						echo "Unbuild successful!<br />";
						$clientrow['house_build'] = $newtotal;
						$build -= $buildit;
					}
     			} else {
						echo "Unbuild failed: That much Build Grist has not been invested in the house yet.<br />";
     			}
   			}
 			}

 			if (!empty($_POST['deployitem'])) { //Deploying an item into the client player's house
 				$deployresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`ID` = '" . mysqli_real_escape_string($connection, $_POST['deployitem']) . "'");
				while ($drow = mysqli_fetch_array($deployresult)) {
					$deploytag = specialArray($drow['effects'], "DEPLOYABLE");
					if ($deploytag[0] == "DEPLOYABLE") {
						$existtag = specialArray($clientrow['storeditems'], $_POST['deployitem']); //this also works for storage items, fancy that
						if ($existtag[0] == $_POST['deployitem']) $currentstack = $existtag[1];
						else $currentstack = 0;
						if ($deploytag[1] == "MAXSTORE") $fullstack = $deploytag[2];
						else $fullstack = 1;
						if ($currentstack < $fullstack || $drow['code'] == "11111111") { //the user doesn't have this in their storage yet
							$canafford = false;
							$extras = "";
							if ($deploytag[1] == "FREE" || ($clientrow['inmedium'] == 0 && $drow['ID'] == 11)) {
								$canafford = true;
								$newgrist = $clientrow['grists'];
								if ($drow['ID'] == 11) $extras = "CODE=cZCMY4Qf."; //This is the code and ID for the pre-punched card.
							} elseif ($deploytag[1] == "TIER1") {
								if ($tier1 > $deploytag[2]) {
									if ($charrow['inmedium'] == 1) $canafford = true;
									$newgrist = modifyGrist($clientrow['grists'], $tier1grist, ($deploytag[2] * -1));
								}
							} else {
								$bcost = howmuchGrist($drow['gristcosts'], "Build_Grist");
								if ($build > $bcost) {
									if ($charrow['inmedium'] == 1) $canafford = true;
									$newgrist = modifyGrist($clientrow['grists'], "Build_Grist", ($bcost * -1));
								}
							}
							if ($canafford) {
								$success = storeItem($clientrow, $drow['ID'], 1, $extras);
								if ($success) {
									mysqli_query($connection, "UPDATE `Characters` SET `grists` = $newgrist WHERE `ID` = '" . $clientrow['ID'] . "' LIMIT 1;");
									notifyCharacterOnce($charrow, $clientrow['ID'], $charrow['name'] . " has deployed a " . $drow['name'] . " in your house!");
									if ($clientrow['inmedium'] == 0 && $drow['ID'] == 11) echo "Pre-punched Card successfully deployed!<br />";
									else echo $drow['name'] . " successfully deployed!<br />";
								} else echo "Deploy failed: you can't find enough room in the client's house to put down the item! You'll have to make some room first.<br />";
							} else {
								if ($clientrow['inmedium'] == 1) echo "Deploy failed: client lacks the required $coststring.<br />";
								else echo "Deploy failed: you don't have access to that item yet!<br />";
							}
						} else echo "Deploy failed: Your client already has as many of those items as they'll need.<br />";
					} else echo "Deploy failed: you can't deploy that item!<br />";
				}
 			}

 			if (!empty($_POST['recycling'])) { //Recycling the client player's storage items.
 				$gristed = false;
 				if (!empty($clientrow['storeditems'])) {
 					$updatestore = "";
 					$boom = explode("|", $clientrow['storeditems']);
					$totalitems = count($boom);
					$i = 1;
					$newgrist = $clientrow['grists'];
					while ($i < $totalitems) {
						$args = explode(":", $boom[$i - 1]);
						if (!empty($_POST['r-' . $args[0]])) { //This item is being recycled
							$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`ID` = " . $args[0] . " LIMIT 1;");
							$irow = mysqli_fetch_array($iresult);
							if ($irow['ID'] == $args[0]) {
								if ($_POST['q-' . $args[0]] < 1 || empty($_POST['q-' . $args[0]])) $_POST['q-' . $args[0]] = 1; //set to 1 if blank or less than 0
								if (intval($args[1]) >= $_POST['q-' . $args[0]]) {
									$nothing = true;
									echo "You recycle your client's " . $irow['name'] .  " x " . strval($_POST['q-' . $args[0]]) . " into ";
									if (!$gristed) {
										$gristname = initGrists();
										$totalgrists = count($gristname);
										$gristed = true;
									}
									$deploytag = specialArray($irow['effects'], "DEPLOYABLE"); //should always return an array because of the search query above
									if ($deploytag[1] == "FREE") $zerobuild = true;
									elseif ($deploytag[1] == "TIER1") {
										$zerot1 = true;
										$zerobuild = true;
									}
									$g = 0;
									$boomi = explode("|", $irow['gristcosts']);
									while (!empty($boomi[$g])) {
										$boomo = explode(":", $boomi[$g]);
										if ((!$zerobuild || $boomo[0] != "Build_Grist") && (!$zerot1 || $boomo[0] != $tier1)) {
											$nothing = false;
											$boomo[1] = intval($boomo[1]);
											$boomo[1] *= $_POST['q-' . $args[0]];
											$newgrist = modifyGrist($newgrist, $boomo[0], $boomo[1]);
											echo '<img src=
													"images/grist/'.
													$boomo[0].".png".
													//gristImage($boomo[0]).
													 '" height="50" width="50" title="' . $boomo[0] . '"></img>';
	      									echo " <gristvalue2>" . strval($boomo[1]) . "</gristvalue2>";
										}
	      								$g++;
									}
									$args[1]-=$_POST['q-' . $args[0]];
									$clientrow['storedspace'] -= itemSize($irow['size']) * $_POST['q-' . $args[0]];
									if ($nothing) echo "nothing, because you originally deployed it for free or for a non-build cost."; //items will ALWAYS have a grist cost listed, even if just a vanity cost of 0
									echo '<br />';
								} else "Error: Client does not have " . $args[1] . " of " . $irow['name'] . "<br />";
							} else echo 'Error: unknown item<br />';
						}
						if ($args[1] > 0) {
							$updatestore .= implode(":", $args) . "|";
						}
						$i++;
					}
					if ($updatestore != $clientrow['storeditems']) {
						mysqli_query($connection, "UPDATE `Characters` SET `grists` = '$newgrist', `storeditems` = '$updatestore', `storedspace` = " . strval($clientrow['storedspace']) . " WHERE `Characters`.`ID` = " . $clientrow['ID'] . " LIMIT 1;");
					}
 				} else echo "Your client has nothing to recycle!<br />";
 				//compuRefresh($clientrow);
 			}

 			$clientresult = mysqli_query($connection, "SELECT * FROM Characters WHERE `Characters`.`ID` = " . $charrow['client']);
			$clientrow = mysqli_fetch_array($clientresult);
			//refresh clientrow so that things like grist and storage are up-to-date. yeah it's inefficient but I'm lazy so

			echo "SBURB Server Menu<br />";
			echo "Client player: " . profileString($clientrow['ID']) . "<br />";
			echo "Client's build grist: $build<br /><br />";

			echo "&gt;Revise<br />";
			echo "Client's house investment: " . strval($clientrow['house_build']) . "<br />";
			echo "Highest gate reached by your client's dwelling: ";
  		$gates = 0;
  		$i = 1;
  		$gateresult = mysqli_query($connection, "SELECT * FROM Gates");
  		$gaterow = mysqli_fetch_array($gateresult); //Gates only has one row.
  		while ($i <= 7) {
    		$gatestr = "gate" . strval($i);
    		if ($gaterow[$gatestr] <= $clientrow['house_build']) {
      		$gates++;
    		} else {
      		$i = 7; //We are done.
    		}
    		$i++;
  		}
  		if(strval($gates+1)<8) $gategrist = $gaterow["gate" . strval($gates+1)]-$clientrow['house_build'];
  		else $gategrist = 0;
  		echo strval($gates) . "<br />";
  		echo "Next gate: " . $gategrist . "<br>";

			if ($charrow['gatescleared'] < $gates) {
				echo "The space you built is unexplored, full of underlings and various items they brought with them. Your client must brave this new area in order to make use of the gates to which you've built.<br />";

			}
			echo "Building up your client's house will increase their item storage space, as well as help them reach higher gates.<br />";
			echo '<form action="sburbserver.php" method="post">Amount of build grist to spend on client\'s housing: <input id="build" name="build" type="text" /><br />';
			echo 'Note: You can input a negative number to recycle parts of your client\'s house, refunding the grist spent.<br />';
      echo '<input type="submit" value="Build it!" /></form><br />';

			echo "&gt;Deploy<br />";
			echo '<form method="post" action="sburbserver.php">Select a machine to deploy:<br /><select name="deployitem">';
			$deployresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`effects` LIKE '%DEPLOYABLE%'");
			while ($drow = mysqli_fetch_array($deployresult)) {
				$deploytag = specialArray($drow['effects'], "DEPLOYABLE"); //should always return an array because of the search query above
				if ($deploytag[1] == "FREE") $coststring = "--";
				elseif ($deploytag[1] == "TIER1") $coststring = strval($deploytag[2]) . " " . $tier1grist;
				else $coststring = howmuchGrist($drow['gristcosts'], "Build_Grist") . " Build Grist";
				if ($coststring == "--" || $clientrow['inmedium'] == 1) //don't allow other stuff to be deployed until the player enters the medium
				echo '<option value="' . $drow['ID'] . '">' . $drow['name'] . ' (Cost: ' . $coststring . ')</option>';
			}
			if ($clientrow['inmedium'] == 0) echo '<option value="11">Pre-punched Card (Cost: --)</option>';
			echo '</select><br /><input type="submit" value="Deploy it!"></form><br />';

			echo "&gt;Recycle<br />";
			echo "Your client may be unable to recycle items directly from their storage, but you sure can!<br />";
			if (!empty($clientrow['storeditems'])) {
				echo '<form method="post" action="sburbserver.php"><input type="hidden" name="recycling" value="yes">';
				$boom = explode("|", $clientrow['storeditems']);
				$totalitems = count($boom);
				$i = 1;
				while ($i < $totalitems) {
					$args = explode(":", $boom[$i - 1]);
					$iresult = mysqli_query($connection, "SELECT `ID`,`name` FROM `Captchalogue` WHERE `ID` = " . $args[0] . " LIMIT 1;");
					$irow = mysqli_fetch_array($iresult);
					if ($irow['ID'] == intval($args[0])) {
						echo '<input type="checkbox" name="r-' . $args[0] . '" value="yes">';
						echo $irow['name'] . ' x ' . $args[1];
						if ($args[1] > 1) {
							echo ' - Amount to recycle: <input type="text" name="q-' . $args[0] . '">';
						} else {
							echo '<input type="hidden" name="q-' . $args[0] . '" value="1">';
						}
					} else {
						echo 'Error: unknown item' . $args[0];
					}
					echo '<br />';
					$i++;
				}
				echo '<input type="submit" value="Recycle it!"></form>';
			} else {
				echo "...if your client HAD anything in storage, that is.<br />";
			}
		}
	}
}

require_once("footer.php");
?>
