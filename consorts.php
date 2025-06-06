<?php
$pagetitle = "Consorts";
$headericon = "/images/header/chummy.png";
require($_SERVER['DOCUMENT_ROOT'] . "/inc/header.php");
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/pricesandvalues.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/additem.php";

// start page

if (empty($_SESSION['character'])) {
	echo "You can't explore lands as a nonexistent character!<br />";
} else if ($charrow['inmedium'] != 1) {
	echo "You can't explore lands before entering the Medium!<br />";
} else {
	if (empty($_GET['land']) && $_GET['land'] !== "0") $_GET['land'] = "";
	if (empty($_GET['search'])) $_GET['search'] = "";
	if (empty($_GET['completequest'])) $_GET['completequest'] = "";
	if (empty($_POST['shopaction'])) $_POST['shopaction'] = "";
	if ($charrow['dreamingstatus'] == "Asleep") {
		if ($charrow['dreamer'] == "Prospit") $_GET['land'] = -1;
		else $_GET['land'] = -2;
	}
	if (!is_numeric($_GET['land'])) {
		$connected = chainArray($charrow,$connection); //Get an array of connected Lands
		$chumroll = mysqli_query($connection, "SELECT `ID`, `land1`, `land2` FROM `Characters` WHERE `Characters`.`session` = '$charrow[session]';");
		$n = 0;
		while ($chumrow = mysqli_fetch_array($chumroll)) {
			if ($connected[$chumrow['ID']]) { //Can always fight your own underlings, even with no building done
				if ($chumrow['ID'] == $charrow['ID']) $chumrow['ID'] = 0;
				$lands[$n] = $chumrow;
				$n++;
			}
		}
		$i = 0;
		echo '<form method="get">Select a Land to explore: <select name="land"> ';
		while ($i < $n) { //Note that the last value of n will not correspond to an index.
			echo '<option value="' . $lands[$i]['ID'] . '">Land of ' . $lands[$i]['land1'] . ' and ' . $lands[$i]['land2'] . '</option>';
			$i++;
		}
		// additional lands should be printed here
		// echo '<option value="-1">Prospit</option>';
		// echo '<option value="-2">Derse</option>';
		// echo '<option value="-3">The Battlefield</option>';
		echo '</select> <input type="submit" value="Explore here!"></form><br />';
	} else {
		if ($_GET['land'] !== "" || $_GET['land'] != 0) $land = mysqli_real_escape_string($connection, $_GET['land']);
		else $land = 0;
		if ($land >= 0) {
			$accesses = chainArray($charrow);
			if ($land > 0 && $accesses[$land]) $canaccess = true;
			else if ($land == 0 && $accesses[$charrow['ID']]) $canaccess = true;
			else $canaccess = false;
		} else if (strpos($charrow['canaccess'], $land) !== false) {
			$canaccess = true;
		} else $canaccess = false;
		if ($land > 0) {
			$landresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = $land LIMIT 1;");
			$charland = mysqli_fetch_assoc($landresult);
			$playerland = true;
			$location = "the Land of " . $charland['land1'] . " and " . $charland['land2'];
			$explorelevel = explorationLevel($charland['landexplored']);
		} else if ($land == 0) {
			$charland = $charrow;
			$playerland = true;
			$location = "the Land of " . $charland['land1'] . " and " . $charland['land2'];
			$explorelevel = explorationLevel($charland['landexplored']);
		} else if ($land < 0 && $land >= -3 && $strpos($charrow['canaccess'], strval($land)) !== false) {
			$playerland = false;
			if ($land == -1) $location = "Prospit";
			else if ($land == -2) $location = "Derse";
			else $location = "The Battlefield";
			$explorelevel = 2; // until we have a way to measure progress on Prospit/Derse/Battlefield
			$charland['consort'] = "carapacian";
		} else {
			echo "That is not a currently valid land!<br />";
			$canaccess = false;
		}

		if ($canaccess) {
			$sessresult = mysqli_query($connection, "SELECT `exchange` FROM `Sessions` WHERE `ID` = " . $charrow['session'] . " LIMIT 1;");
			$session = mysqli_fetch_assoc($sessresult);
			$exchangeloc = $session['exchange'];
			$gateresult = mysqli_query($connection, "SELECT * FROM Gates"); //we'll need this to determine the level of the shops
			$gaterow = mysqli_fetch_row($gateresult); //Gates only has one row.
			$gate = 7;
			while ($gate > 0) {
				if ($gaterow[$gate-1] <= $charland['house_build']) break;
				$gate--;
			}
			if (!empty($charland['consort'])) $consort = $charland['consort'];
			else $consort = "consort";
		}

		if ($charland['session'] != $charrow['session']) {
			echo "This character's land is not in your session!<br />";
		} else if (!$canaccess && $land < 0) {
			echo "You cannot reach this land without a way to fly!<br />";
		} else if (!$canaccess && $gate == 0) {
			echo "You need to have access to the first gate in order to explore lands.<br />";
		} else if (!$canaccess) {
			echo "You cannot reach this land with your current gate setup!<br />";
		//} else if ($explorelevel <= 1) {
		//	echo "You head out across $location but cannot find anything! Try doing more on this land first.<br />";
		} else if (false/*$_GET['search'] == 'quest'*/) { //This is currently disabled.
			$charrow['availablequests'] = 99;
			if (!empty($charrow['currentquest']) && $_GET['completequest'] != "cancel") {
				$questresult = mysqli_query($connection, "SELECT * FROM `Consort_Dialogue` WHERE `ID` = " . strval($charrow['currentquest']) . " LIMIT 1;");
				$questrow = mysqli_fetch_assoc($questresult);
				if (strpos($questrow['context'], "linked") !== false) $context = substr($questrow['context'], 0, -6);
				else $context = $questrow['context'];
				if (!empty($_GET['completequest']) && $charrow['currentquest'] != 0) { // quest exists and is being completed
					if ($_GET['completequest'] == "debug") {
						echo "The consort appraises your magical dev powers.<br />";
						if ($accrow['modlevel'] == 99) {
							$completed = true;
							$booncost = 0;
						} else {
							echo "Sadly, you don't seem to have any. What a shame.<br />";
							$completed = false;
						}
					} else if ($context == "questitem") {
						if (in_array($_GET['completequest'], $_SESSION['inv'])) {
							$itemresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = " . mysqli_real_escape_string($connection, $_GET['completequest']) . " LIMIT 1;");
							$itemrow = mysqli_fetch_assoc($itemresult);
							$truename = str_replace("\\", "", $itemrow['name']);
							echo "The consort appraises your $truename.<br />";
							$completed = matchItem($itemrow, $questrow['requirements']);
							if ($completed) {
								$gristcost = totalGristcost($itemrow['gristcosts']);
								if ($gristcost <= $gaterow[$gate-1]) $booncost = totalBooncost($itemrow['gristcosts'], $charland);
								else $completed = false;
							} else {
								echo "The consort turns it away. This isn't the right kind of item.<br />";
								$completed = false;
							}
						} else {
							echo "That item is not in your inventory!<br />";
							$completed = false;
						}
					}
					if ($completed) {
						if ($context == "questitem") echo "The consort is overjoyed, this item is perfect!<br />";
						else $booncost = 0;
						$tested = phatLoot($charrow, $questrow, $charland, $gate, $booncost);
						if ($tested === -1) {
							echo "There was a problem giving a reward! Please contact a developer ASAP.<br />";
						} else if ($context = "questitem" && $_GET['completequest'] != "debug") {
							$slot = array_search($_GET['completequest'], $_SESSION['inv']);
							removeItem($slot);
							echo "Debug: item removed. <br />";
						}
					} else echo '<a href="?search=quest&land=' . $land . '">Try again</a> or ';
					echo '<a href="?search=quest&land=' . $land . '&completequest=cancel">Search for a different quest?</a><br />';
				} else {
					$questrow = parseDialogue($questrow, $charrow);
					echo "A $consort has an ongoing request for you...</br>";
					if ($accrow['modlevel'] >= 10) echo "This quest's ID is " . $questrow['ID'] . ".<br />";
					echo "<br />";
					echo $questrow['dialogue'] . "<br /><br />";
					if ($context = "questitem") {
						echo '<form method="get">Offer the ' . $consort  . ' an item?</br>';
						echo '<select name="completequest">';
						$itemcount = 0;
						while ($itemcount < count($_SESSION['inv'])) {
							$itemrow = mysqli_query($connection, "SELECT `name` FROM `Captchalogue` WHERE `ID` = " . $_SESSION['inv'][$itemcount] . " LIMIT 1;");
							$itemresult = mysqli_fetch_assoc($itemrow);
							$name = $itemresult['name'];
							echo '<option value="' . $_SESSION['inv'][$itemcount] . '">' . $name . '</option>';
							$itemcount++;
						}
						echo '</select></br><input type="hidden" name="land" value="' . $land . '"><input type="hidden" name="search" value="quest"><input type="submit" value="Offer this item"></form></br>';
					}
					echo '<a href="?search=quest&land=' . $land . '&completequest=cancel">Search for a different quest?</a><br />';
					echo '<a href="?search=quest&land=' . $land . '&completequest=debug">(debug) Complete the quest!</a><br />';
					echo "Debug: questrow = <br />";
					echo "<br />";
				}
			} else if ($charrow['availablequests'] > 0) {
				echo "You search $location for a while. Eventually, you are approached by a $consort with a request for you...</br>";
				$rquest = rand(1,100); //first, determine a random quest type so that each type has controlled weight, rather than basing it off of quantity of available quests
				if ($rquest <= 33) $questtype = "questitem"; //1/3 chance of fetch quest
				// elseif ($rquest <= 66) $questtype = "queststrife"; //1/3 chance of strife quest
				// elseif ($rquest <= 83) $questtype = "questrescue"; //1/6 chance of rescue quest
				// elseif ($rquest <= 100) $questtype = "questdungeon"; //1/6 chance of dungeon quest
				else $questtype = "questitem";  // technically bugged, default to fetch quest
				$questresult = getDialogue($questtype, $charrow, $gate);
				if ($accrow['modlevel'] >= 10) echo "This quest's ID is " . $questresult['ID'] . ".<br />";
				echo "<br />";
				echo $questresult['dialogue'] . "<br />";
				echo '<a href="?search=quest&land=' . $land . '">Choose this quest!</a> <a href="?search=quest&land=' . $land . '&completequest=cancel">...or search for another</a><br />';
				mysqli_query($connection, "UPDATE `Characters` SET `currentquest` = " . $questresult['ID'] . ", `questland` = " . $land . " WHERE `ID` = " . $charrow['ID'] . " LIMIT 1;");
			} else {
				echo "You search $location for a while, but cannot find a single $consort in need of anything! Try waiting a bit.</br>";
			}
			echo '<a href="?land=' . $land . '">(Go back)</a><br />';
		} else if ($_GET['search'] == 'shop' && $charrow['dreamingstatus'] == "Asleep") {
			echo "The shops on your dream moon are useless to you as you'd have nowhere to put the purchased items - your sylladex is with your waking self!<br />";
			echo '<a href="?land=' . $land . '">(Go back)</a><br />';
		} else if ($_GET['search'] == 'shop' && $land >= 0) {
			echo "Shop beta:<br />";
			if (!empty($_POST['shopaction'])) {
				$purchased = false;
				$forcerefresh = false;
				if (strpos("|" . $charland['shopstock'], "|" . $_POST['shopaction'] . ":") !== false) {
					// dissect shop string
					$thisone = substr($charland['shopstock'], strpos("|" . $charland['shopstock'], "|" . $_POST['shopaction'] . ":"));
					$thisone = substr($thisone, 0, strpos($thisone, "|"));
					$thisone = explode(":", $thisone);
					$shopitem = mysqli_query($connection, "SELECT `name`, `ID` FROM `Captchalogue` WHERE `ID` = " . $thisone[0] . " LIMIT 1;");
					$shopitem = mysqli_fetch_array($shopitem);
					$shopID = $shopitem['ID'];
					$shopitem = $shopitem['name'];
					$shopprice = $thisone[1];
					$shopstock = intval($thisone[2]);
					if ($charrow['boondollars'] < $shopprice) echo "Sorry " . $charrow['name'] . ", I can't give credit! Come back when you're a little... mmm... RICHER!</br>";
					else {
						$purchased = true;
					}
				} else if ($_POST['shopaction'] == "refresh") {
					// don't do anything if the action was to refresh the shop
					if($accrow['modlevel'] >= 99) {
						$forcerefresh = true;
					} else {
						echo 'I\'m sorry, but I can\'t do that.<br/>';
					}
				} else echo "This shop is not selling that item!<br />";
				if ($purchased) {
					$newitem = addItem($charrow, $_POST['shopaction'], "", false);
					if ($newitem) {
						sumStat($charrow, 'consortboon', $shopprice);
						if(getStat($charrow,'consortboon')>99999999) setAchievement($charrow, 'boonshop');
						$charrow['boondollars'] -= $shopprice; // make them pay.
						$charrow['economy'] += $shopprice;
						if($shopstock-1 < 1 OR $shopstock-1 > 999) {
							// item is out of stock or broke something hard, remove it
							$oldstock = $shopID . ":" . $shopprice . ":" . strval($shopstock) . "|";
							$charland['shopstock'] = str_replace($oldstock, "", $charland['shopstock']);
						} else {
							$stock1 = substr($charland['shopstock'], 0, strpos("|" . $charland['shopstock'], "|" . $_POST['shopaction'] . ":"));
							$stock2 = $shopID . ":" . $shopprice . ":" . strval($shopstock - 1);
							if (substr($stock2, -2)!=':9')
								$charland['shopstock'] = $stock1 . $stock2 . substr($charland['shopstock'], strlen($stock1)+strlen($stock2));
							else
								$charland['shopstock'] = $stock1 . $stock2 . substr($charland['shopstock'], strlen($stock1)+strlen($stock2)+1);
						}
						echo "You purchase " . $shopitem . " x1 for " . $shopprice . " Boondollars.</br>";
						mysqli_query($connection, "UPDATE `Characters` SET `boondollars` = " . $charrow['boondollars'] . ", `economy` = " . $charrow['economy'] . " WHERE `ID` = '" . $charrow['ID'] . "' LIMIT 1;");
						mysqli_query($connection, "UPDATE `Characters` SET `shopstock` = '" . $charland['shopstock'] . "' WHERE `ID` = '" . $charland['ID'] . "' LIMIT 1;");
					} else echo "You don't have room in your inventory for this item! You'll have to clear some space before you can buy it.<br />";
				} else echo "The purchase failed!<br />";
			}
			if ($gate < 1) $gate = 1;  // even on a land with no gates available, shops will stock as if there is at least one, in case the land is reached by a flying player for instance
			if ($forcerefresh || (time() - $charland['lastshoptick']) > 86400) { // the shop is empty or a day has passed since the shop was last refreshed
				$shopinflation = 1 + ((rand(90,110) - econonyLevel($charland['economy'])) / 100); // shop prices deviate +/- 10% from the norm
				if ($shopinflation < 0.5) $shopinflation = 0.5;
				$tsi = 0; // total shop items
				$shopstring = "";
				$maxshopitems = 3 + ($gate * 2) + rand(0,$gate); // the amount of items this shop will have when we're done
				$grists = explode("|", $charland['grist_type']);
				while ($tsi < $maxshopitems) {
					$thisgrist = $grists[rand(0, count($grists)-1)]; // pick a random grist type from that land
					$randitem = randomItem("`gristcosts` LIKE '%" . $thisgrist . "%' ", $gaterow[$gate-1], $charrow['session']);
					if (!$randitem) $randitem = randomItem("", $gaterow[$gate-1], $charrow['session']);
					$shopitemID[$tsi] = $randitem['ID'];
					$shopitemprice[$tsi] = round(totalBooncost($randitem['gristcosts'], $charland) * $shopinflation);
					$shopitemstock[$tsi] = 1+rand(0, $gate*2);
					$shopstring .= $shopitemID[$tsi] . ":" . $shopitemprice[$tsi] . ":" . $shopitemstock[$tsi] . "|";
					$tsi++;
				}
				mysqli_query($connection, "UPDATE `Characters` SET `shopstock` = '" . $shopstring . "', `lastshoptick` = " . time() . " WHERE `ID` = '" . $charland['ID'] . "'");
				$charland['lastshoptick'] = time();
				$charland['shopstock'] = $shopstring;
			} else {
				$shopitems = explode("|", $charland['shopstock'], -1);
				$shopitemID = [];
				$shopitemprice = [];
				$shopitemstock = [];
				foreach($shopitems as $shopitem) {
					$thisone = explode(":", $shopitem);
					$shopitemID[] = $thisone[0];
					$shopitemprice[] = $thisone[1];
					$shopitemstock[] = $thisone[2];
				}
				$maxshopitems = count($shopitems);
			}

			if(!empty($charland['shopstock'])) {
				$time = 86400 - (time() - $charland['lastshoptick']);
				$hourstr = strval(floor($time/3600));
				while (strlen($hourstr) < 2) $hourstr = "0" . $hourstr;
				$minutestr = strval(floor($time/60) % 60);
				while (strlen($minutestr) < 2) $minutestr = "0" . $minutestr;
				$secondstr = strval($time % 60);
				while (strlen($secondstr) < 2) $secondstr = "0" . $secondstr;
				$timestr = $hourstr . ":" . $minutestr . ":" . $secondstr;
				echo "This shop's stock will refresh in $timestr.<br /><br />";
				$shoprow = getDialogue("shop", $charrow, $gate);
				echo "$consort: " . $shoprow['dialogue'] . "<br /><br />";
				
				$strifedeck = implode(", ", explode("|", substr($charrow['strifedeck'], 0, -1)));
				if (!empty($strifedeck))
				{
					$sditems = mysqli_query($connection, "SELECT MAX(`power`) FROM `Captchalogue` WHERE `ID` IN ($strifedeck);");
					$sditems = mysqli_fetch_row($sditems);
					$maxpower = $sditems[0];
				}
				else
					$maxpower = 0;
				
				$shopitems = mysqli_query($connection, "SELECT `ID`, `name`, `power`, `abstratus`, `wearable`, `effects` FROM `Captchalogue` WHERE `ID` IN (" . implode(", ", $shopitemID) . ");");
				echo '<form action="?land=' . $land . '&search=shop" method="post">';
				$csi = 0; //current shop item. no, not crime scene investigation.
				while ($thisitem = mysqli_fetch_assoc($shopitems)) {
					$whichisthis = array_search($thisitem['ID'], $shopitemID);
					if ($whichisthis === false || $shopitemID[$whichisthis] != $thisitem['ID']) {
						logDebugMessage("Something went drastically wrong while printing shop contents of " . $charland['shopstock']);
						echo "Something went drastically wrong while printing shop contents of " . $charland['shopstock'];
						continue;
					}
					echo '<input type="radio" name="shopaction" value="' . $thisitem['ID'] . '"> ' . $thisitem['name'] . ' (Cost: ' . $shopitemprice[$whichisthis] . ' Boondollars, ' . $shopitemstock[$whichisthis] . ' in stock)<br />';
					$canwield = false;
					$abstrati = explode("|", substr($charrow['abstratus'], 1));
					for ($i = 0; $i < count($abstrati); $i++) {
						if (strpos($thisitem['abstratus'], $abstrati[$i]) !== false) {
							$canwield = true;
							break;
						}
					}
					if ($canwield) {
						echo "This weapon is compatible with your strife specibus! ";
						$powerdiff = $thisitem['power'] - $maxpower;
						if ($powerdiff < -9999) echo "It looks like an utter piece of shit, though.";
						elseif ($powerdiff < -5000) echo "However, you outgrew the need for such flimsy weaponry long ago.";
						elseif ($powerdiff < -1000) echo "You doubt you could see any use out of it, though.";
						elseif ($powerdiff < -100) echo "It looks a bit weak for your needs.";
						elseif ($powerdiff < 0) echo "You think your current equipment might be better, but you never know...";
						elseif ($powerdiff == 0) echo "It looks exactly as strong as your current equipment.";
						elseif ($powerdiff > 9999) echo "Whoa, where has this been all your life?!";
						elseif ($powerdiff > 9000) echo "You'd be lucky to ever get your hands on one of these!";
						elseif ($powerdiff > 5000) echo "It looks ridiculously stronger than your current weapon!";
						elseif ($powerdiff > 1000) echo "You could definitely use something as strong as this!";
						elseif ($powerdiff > 100) echo "It looks pretty strong. It'd probably be a decent upgrade.";
						elseif ($powerdiff > 0) echo "You think it might be a little stronger than what you're currently using, but you can't say for sure.";
						echo "</br>";
					} elseif ($thisitem['wearable'] != "none" && $thisitem['wearable'] != "") {
						echo "This looks like something you can wear.</br>";
					} elseif (strpos($thisitem['effects'], "COMPUTER")) {
						echo "You think you can use this to communicate with your friends.</br>";
					} elseif (strpos($thisitem['abstratus'], "notaweapon") !== false) {
						echo "This item doesn't look like it can be equipped.</br>";
					} else echo "You can't wield this weapon.</br>";
				}
				echo '<input type="submit" value="Buy it!"></form><br /><br />';
				if($accrow['modlevel'] >= 99) {
					echo '<form method="POST" action=""><input type="hidden" name="shopaction" value="refresh"/><input type="submit" value="Force refresh" /></form>';
				}
				echo '<a href="?land=' . $land . '">(Go back)</a><br />';
			} else {
				$time = 86400 - (time() - $charland['lastshoptick']);
				$hourstr = strval(floor($time/3600));
				while (strlen($hourstr) < 2) $hourstr = "0" . $hourstr;
				$minutestr = strval(floor($time/60) % 60);
				while (strlen($minutestr) < 2) $minutestr = "0" . $minutestr;
				$secondstr = strval($time % 60);
				while (strlen($secondstr) < 2) $secondstr = "0" . $secondstr;
				$timestr = $hourstr . ":" . $minutestr . ":" . $secondstr;
				echo '<br/>This shop appears to be out of stock!<br/>';
				echo 'Perhaps you should come back later.<br/>';
				echo "This shop's stock will refresh in $timestr.<br /><br />";
				if($accrow['modlevel'] >= 99) {
					echo '<form method="POST" action=""><input type="hidden" name="shopaction" value="refresh"/><input type="submit" value="Force refresh" /></form>';
				}
			}
		} else if ($_GET['search'] == 'exchange' && $exchangeloc == $land) {
			echo "Exchange not ready";
			echo '<a href="?land=' . $land . '">(Go back)</a><br />';
		} else if ($_GET['search'] == 'exchange') {
			if ($land < 0) echo "The grist exchange can only be found on player lands!<br />";
			else {
				echo "There's no grist exchange on this land!<br />";
				if ($exchangeloc == 0 && $charland['economy'] >= 4000000) echo "In fact, there's no grist exchange in the session yet. Would you like to establish one?<br />";
				// add link to create exchange
			}
			echo '<a href="?land=' . $land . '">(Go back)</a><br />';
		} else {
			if ($explorationlevel <= 4) {
				$exploretag1 = " small ";
				$exploretag2 = "a few ";
			} else if ($explorationlevel <= 4) {
				$exploretag1 = " ";
				$exploretag2 = "several ";
			} else {
				$exploretag1 = " huge ";
				$exploretag2 = "a crowd of ";
			}
			echo "Walking across " . $location . ", you come across a" . $exploretag1 . "consort village. You pass $exploretag2 consorts standing near the entrance as you head in. What will you do?<br /><br />";
			echo '<form method="get">Look for something to do: <select name="search"> ';
			//echo '<option value="quest">Go questing</option>';
			echo '<option value="shop">Buy items</option>';
			if ($charrow['ID'] == $exchangeloc) {
				echo '<option value="exchange">Visit the Grist Exchange</option>';
			}
				// echo '<option value="mercenary">Hire a consort</option>';
				echo '</select><input type="hidden" name="land" value="' . $land . '"><input type="submit" value="Explore the village"></form><br />';
		}
	}
}
require($_SERVER['DOCUMENT_ROOT'] . "/inc/footer.php");
?>
