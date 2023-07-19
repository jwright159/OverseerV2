<?php
$pagetitle = "Item Editor";
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/glitches.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/inc/database.php";
//require_once "includes/grist_icon_parser.php";

function anyglitch() {
	$gtype = rand(1,5);
	if ($gtype == 5) $glitch = generateGlitchString();
	elseif ($gtype == 4) $glitch = generateStatusGlitchString();
	else {
		$glitch = "";
		while ($gtype > 0) {
			$glitch .= horribleMess();
			$gtype--;
		}
	}
	return $glitch;
}

function heaviestBonus($workrow){
	$bonusrow['abstain']=$workrow['abstain'];
	$bonusrow['abjure']=$workrow['abjure'];
	$bonusrow['accuse']=$workrow['accuse'];
	$bonusrow['abuse']=$workrow['abuse'];
	$bonusrow['aggrieve']=$workrow['aggrieve'];
	$bonusrow['aggress']=$workrow['aggress'];
	$bonusrow['assail']=$workrow['assail'];
	$bonusrow['assault']=$workrow['assault'];
	$bonusrow = array_map('intval', $bonusrow); //Make sure they're all integer values.
	$bestbonus=max($bonusrow);
	if($bestbonus==0)return "none";
	elseif($bonusrow['abstain']==$bestbonus)return "abstain";
	elseif($bonusrow['abjure']==$bestbonus)return "abjure";
	elseif($bonusrow['accuse']==$bestbonus)return "accuse";
	elseif($bonusrow['abuse']==$bestbonus)return "abuse";
	elseif($bonusrow['aggrieve']==$bestbonus)return "aggrieve";
	elseif($bonusrow['aggress']==$bestbonus)return "aggress";
	elseif($bonusrow['assail']==$bestbonus)return "assail";
	elseif($bonusrow['assault']==$bestbonus)return "assault";
}

if ($accrow['modlevel'] < 4) {
	echo "What are you doing here?";
} else {
	$grist = initGrists();
	
	if (!empty($_POST['publishlog'])) {
		if ($accrow['modlevel'] >= 6) {
		$sysresult = mysqli_query($connection, "SELECT `addlog` FROM `System` WHERE 1");
		$sysrow = mysqli_fetch_array($sysresult);
		if ($sysrow['addlog'] != "") {
			if (!empty($_POST['publishtitle']))
				$titletext = mysqli_real_escape_string($connection, $_POST['publishtitle']);
			else {
				$titletext = "Auto-posted addlog number ";
				$titletext .= strval(rand(1000000,9999999));
			}
			$datetext = mysqli_real_escape_string($connection, date("Y-m-d H:i:s"));
			$nametext = mysqli_real_escape_string($connection, $accrow['username']);
			if (!empty($_POST['publishbody']))
				$leadintext = $_POST['publishbody'];
			else $leadintext = "This is an automatically generated addlog of items that were created using the on-site Item Editor. The person posting this is too lazy to actually include a message, so enjoy these items:";
			$bodytext = mysqli_real_escape_string($connection, $leadintext . "<br />" . $sysrow['addlog']);
			mysqli_query($connection, "INSERT INTO `News` (`date`, `title`, `postedby`, `news`) VALUES ('$datetext', '$titletext', '$nametext', '$bodytext')");
			echo $bodytext; //in case it fails to post
			mysqli_query($connection, "UPDATE `System` SET `addlog` = '' WHERE 1");
			echo "<br />News has been posted, and the addlog has been cleared.<br />";
		} else echo "ERROR: Addlog is empty. Someone might have beaten you to it!<br />";
		} else echo "You don't have permission to post an addlog.<br />";
	}
	
	if (!empty($_POST['code'])) {
		if (!empty($_POST['weighted'])) {
			if (!empty($_POST['costoverride'])) {
				$basetotalcost = intval($_POST['costoverride']);
			} else {
				$wornpower = intval($_POST['power']) + intval($_POST['aggrieve']) + intval($_POST['aggress']) + intval($_POST['assail']) + intval($_POST['assault']) + intval($_POST['abuse']) + intval($_POST['accuse']) + intval($_POST['abjure']) + intval($_POST['abstain']);
				$heaviestbonus = heaviestBonus($_POST);
				$wpnpower = intval($_POST['power']) + intval($_POST[$heaviestbonus]);
				if ($_POST['size'] == "large" && strpos($_POST['abstratus'], "headgear") !== false && $_POST['hybrid'] != 1) {
					$basetotalcost = (pow($wornpower, 3) / 150) + ($wornpower * 3);
				} elseif ((strpos($_POST['abstratus'], "headgear") !== false || strpos($_POST['abstratus'], "facegear") !== false || strpos($_POST['abstratus'], "accessory") !== false) && $_POST['hybrid'] != 1) {
					$basetotalcost = (pow($wornpower, 3) / 150) + ($wornpower * 3);
				} elseif (strpos($_POST['abstratus'], "bodygear") !== false && $_POST['hybrid'] != 1) {
					$basetotalcost = (pow($wornpower, 2) * 1.25) + ($wornpower * 2);
				} else {
					$basetotalcost = (pow($wpnpower, 2) / 8) + ($wpnpower * 1.5);
				}
				if (!empty($_POST['costtweak'])) {
					$tweak = (intval($_POST['costtweak']) / 100) + 1;
					$basetotalcost *= $tweak;
				}
			}
			$totalweight = 0;
			foreach ($grist as $g) {
				if (!empty($_POST[$g['name']])) $totalweight += intval($_POST[$g['name']]);
			}
			if (!empty($_POST['weightround'])) $round = intval($_POST['weightround']);
			else $round = 1;
			foreach ($totalgrists as $g) {
				$griststr = $g['name'];
				if (!empty($_POST[$griststr])) {
					$percent = intval($_POST[$griststr]) / $totalweight;
					$_POST[$griststr] = round(($percent * $basetotalcost) / $round) * $round;
				}
			}
		}
		$blocked = false;
		if (empty($_POST['processing']) && $accrow['modlevel'] < 5) {
			echo "You don't have permission to edit items or create items from scratch (need level 5).<br />";
			$blocked = true;
		}
		if (empty($_POST['abstratus'])) {
			echo "You didn't give the item an abstratus.<br />";
			$blocked = true;
		}
		if (empty($_POST['size'])) {
			echo "You didn't give the item a size.<br />";
			$blocked = true;
		}
		if (empty($_POST['name'])) {
			echo "You didn't give the item a name.<br />";
			$blocked = true;
		} else {
			$_POST['name'] = str_replace("\\", "", $_POST['name']); //we don't need these anymore!!!!!! :O
		}
		if ($_POST['power'] > 9999) {
			echo "You can't add an item with more than 9999 power.<br />";
			$blocked = true;
		}
		if (empty($_POST['wearable'])) {
		  $_POST['wearable'] = "none"; //assume not wearable
		}
		if ($_POST['power'] > 333 && ((strpos($_POST['wearable'], "head") !== false && $_POST['size'] != "large") || strpos($_POST['wearable'], "face") !== false || strpos($_POST['wearable'], "accessory") !== false) && $_POST['hybrid'] != 1) {
			echo "The item breaks the power cap for non-body wearables. Lower the power or, if it's also a weapon, set hybrid to 1.<br />";
			$blocked = true;
		}
		if ($_POST['power'] > 666 && strpos($_POST['wearable'], "head") !== false  && $_POST['size'] == "large" && $_POST['hybrid'] != 1) {
			echo "The item breaks the power cap for large headgear. Lower the power or, if it's also a weapon, set hybrid to 1.<br />";
			$blocked = true;
		}
		if ($_POST['power'] > 1000 && strpos($_POST['wearable'], "body") !== false && $_POST['hybrid'] != 1) {
			echo "The item breaks the power cap for body wearables. Lower the power or, if it's also a weapon, set hybrid to 1.<br />";
			$blocked = true;
		}
		if (strlen($_POST['code']) != 8 || strpos($_POST['code'], " ") || strpos($_POST['code'], "-")) {
			echo "That is an invalid captchalogue code.<br />";
			$blocked = true;
		}
		if ($_POST['base'] == 1 && $_POST['power'] > 10) {
			echo "That item is way too powerful to be a base item.<br />";
			$blocked = true;
		}
		if (!$blocked) {
			$fieldresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` LIMIT 1;");
		while ($field = mysqli_fetch_field($fieldresult)) {
			$fname = $field->name;
				if ($fname == 'code') {
					$founditem = false;
					$editcode = $_POST['code'];
					$editresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`code` = '$editcode' LIMIT 1;");
					while($row = mysqli_fetch_array($editresult)) {
						$founditem = true;
						$erow = $row;
					}
					if (!$founditem) {
						$updatequery = "INSERT INTO `Captchalogue` VALUES (NULL, '$editcode'";
					} else {
						if ($_POST['populate'] == "no") {
							echo 'An item with this code already exists! ';
							if ($_POST['processing'] == 0)
								echo 'If you intended to edit it, <a href="itemedit.php?editcode=' . $editcode . '">click here</a>.<br />';
							else echo 'Check to see if the submission has the correct code for its recipe.<br />';
							$blocked = true;
							break;
						} else
						$updatequery = "UPDATE `Captchalogue` SET ";
					}
				} elseif ($fname != 'ID') {
					if ($fname == "gristcosts") {
						$i = 0;
						$gstring = ""; //lol
						while (!empty($grist[$i]['name'])) {
							$gristnam = $grist[$i]['name'];
							if (!empty($_POST['gristify'])) {
								if ($_POST['size'] == "large" && strpos($_POST['abstratus'], "headgear") !== false && $_POST['hybrid'] != 1) {
									$_POST[$gristnam] = 4000000 * ($_POST[$gristnam] / 100);
								} elseif ((strpos($_POST['abstratus'], "headgear") !== false || strpos($_POST['abstratus'], "facegear") !== false || strpos($_POST['abstratus'], "accessory") !== false) && $_POST['hybrid'] != 1) {
									$_POST[$gristnam] = 2000000 * ($_POST[$gristnam] / 100);
								} elseif (strpos($_POST['abstratus'], "bodygear") !== false && $_POST['hybrid'] != 1) {
									$_POST[$gristnam] = 5000000 * ($_POST[$gristnam] / 100);
								} elseif ($_POST['size'] == "large") {
									$_POST[$gristnam] = 50000000 * ($_POST[$gristnam] / 100);
								} else {
									$_POST[$gristnam] = 25000000 * ($_POST[$gristnam] / 100);
								}
							}
							if ($_POST[$gristnam] != 0) {
								$gstring .= $gristnam . ":" . $_POST[$gristnam] . "|";
							}
							$i++;
						}
						if (empty($gstring)) $gstring = "Build_Grist:0|";
						$_POST[$fname] = $gstring;
					}
					if ($fname == "nonsense") {
						if (empty($_POST['nonsense'])) $_POST['nonsense'] = anyglitch();
            else {
              while(strpos($_POST['nonsense'], 'GLITCH') !== false) {
                $_POST['nonsense'] = preg_replace('/GLITCH/', horribleMess(), $_POST['nonsense'], 1);
              }
            }
					}
					if (!$founditem) {
						$updatequery .= ", '" . mysqli_real_escape_string($connection, $_POST[$fname]) . "'";
					} else {
						$updatequery .= "`" . $fname . "` = '" . mysqli_real_escape_string($connection, $_POST[$fname]) . "', ";
					}
				}
			}
		}
		if (!$blocked) {
			if (!$founditem) {
				$updatequery .= ");";
			} else {
				$updatequery = substr($updatequery, 0, -2);
				$updatequery .= " WHERE `Captchalogue`.`code` = '$editcode';";
			}
			echo $updatequery . "<br />";
			mysqli_query($connection, $updatequery);
			//now test to see if it worked
			if ($_POST['populate'] == "no") {
				$victory = false;
				$testresult = mysqli_query($connection, "SELECT `code` FROM `Captchalogue` WHERE `Captchalogue`.`code` = '$editcode'");
				$testrow = mysqli_fetch_array($testresult);
				if ($testrow['code'] == $editcode) {
					$victory = true;
					$sysresult = mysqli_query($connection, "SELECT `addlog` FROM `System` WHERE 1");
					$sysrow = mysqli_fetch_array($sysresult);
					$sysrow['addlog'] .= "<br />" . $accrow['username'] . " - Added " . $_POST['name'];
					if (!empty($_POST['devcomments'])) $sysrow['addlog'] .= " (" . $_POST['devcomments'] . ")";
					mysqli_query($connection, "UPDATE `System` SET `addlog` = '" . mysqli_real_escape_string($connection, $sysrow['addlog']) . "' WHERE 1");
					echo "Addlog updated.<br />";
				} else {
					echo "Oops, something is wrong! The query didn't go through, and the item wasn't created. If all else fails, send that query to Blah!<br />";
				}
			} else {
				$victory = true;
				$sysresult = mysqli_query($connection, "SELECT `addlog` FROM `System` WHERE 1");
					$sysrow = mysqli_fetch_array($sysresult);
					$sysrow['addlog'] .= "<br />" . $accrow['username'] . " - Edited " . $_POST['name'];
					if (!empty($_POST['devcomments'])) $sysrow['addlog'] .= " (" . $_POST['devcomments'] . ")";
					mysqli_query($connection, "UPDATE `System` SET `addlog` = '" . mysqli_real_escape_string($connection, $sysrow['addlog']) . "' WHERE 1");
					echo "Addlog updated.<br />";
			}
			if ($victory) {
				if ($_POST['processing'] != 0) {
					mysqli_query($connection, "DELETE FROM `Feedback` WHERE `Feedback`.`ID` = " . strval($_POST['processing']) . " LIMIT 1;");
					echo "Submission cleared. There's no turning back now, so be sure to copy the above query somewhere safe if you suspect that the item didn't add properly.<br />";
				}
				if ($_POST['consumable'] == 1) {
					echo "<a href='consumedit.php?editcode=" . $editcode . "'>Click here to edit this item's consumable row.</a><br />";
				}
			}
		}
	}
	
	if (!empty($_GET['dobase'])) {
		$lookupresult = mysqli_query($connection, "SELECT `code`,`name` FROM `Captchalogue` WHERE `base` = 1 AND `refrance` = 0 AND `old` = 1 LIMIT 1;");
		while ($srow = mysqli_fetch_array($lookupresult)) {
			$_GET['editcode'] = $srow['code'];
		}
	}
	
	if (!empty($_GET['editname'])) {
		$sname = mysqli_real_escape_string($connection, $_GET['editname']);
		$lookupresult = mysqli_query($connection, "SELECT `code`,`name` FROM `Captchalogue` WHERE `name` = '$sname' LIMIT 1;");
		while ($srow = mysqli_fetch_array($lookupresult)) {
			$_GET['editcode'] = $srow['code'];
		}
		if (empty($_GET['editcode'])) {
			echo $_GET['editname'] . " could not be found in the database, please check spelling and remove any backslashes before trying again.<br />";
		}
	}
	$populate = false;
	if (!empty($_GET['editcode'])) {
		$editcode = $_GET['editcode'];
		$populate = true;
	} else {
		$editcode = "00000000";
	}
	
	$processing = 0;
	if (!empty($_GET['sub']) && !$victory) {
		$feedresult = mysqli_query($connection, "SELECT * FROM `Feedback` WHERE `Feedback`.`ID` = '" . strval($_GET['sub']) . "'");
		$feedrow = mysqli_fetch_array($feedresult);
		if ($feedrow['greenlight'] == 1) {
			//logDebugMessage($accrow['username'] . " - began working on the submission " . $feedrow['name']);
			$founditem = true;
			echo 'Submission ID: ' . strval($feedrow['ID']) . '<br />';
			echo 'Submitted by: ' . $feedrow['user'] . '<br />';
			echo 'Item code: ' . $feedrow['code'] . '<br />';
			$erow['code'] = $feedrow['code'];
			echo 'Item name: ' . $feedrow['name'] . '<br />';
			$erow['name'] = $feedrow['name'];
			echo 'Recipe: ' . $feedrow['recipe'] . '<br />';
			echo 'Power level: ' . strval($feedrow['power']) . '<br />';
			$erow['power'] = $feedrow['power'];
			echo 'Description: ' . $feedrow['description'] . '<br />';
			$erow['description'] = $feedrow['description'];
			echo "Submitter's comments: " . $feedrow['comments'] . "<br />";
			echo "<br />";
			echo "Viewers' comments:<br />";
			$count = 0;
			$boom = explode("|", $feedrow['usercomments']);
			$allmessages = count($boom);
			while ($count <= $allmessages) {
				$boom[$count] = str_replace("THIS IS A LINE", "|", $boom[$count]);
				echo $boom[$count] . "<br />";
				$count++;
			}
			echo "<br /><br />";
			$processing = $feedrow['ID'];
			if (!empty($feedrow['size'])) $erow['size'] = $feedrow['size'];
			else $erow['size'] = "average";
			if ($feedrow['catalogue'] == 1) $erow['catalogue'] = 1;
			if ($feedrow['lootonly'] == 1) $erow['lootonly'] = 1;
			if ($feedrow['consumable'] == 1) $erow['consumable'] = 1;
			if ($feedrow['refrance'] == 1) $erow['refrance'] = 1;
			if (!empty($feedrow['bonuses'])) {
				$barray = explode("|", $feedrow['bonuses']);
				$i = 0;
				while (!empty($barray[$i])) {
					$aarray = explode(":", $barray[$i]);
					$amoutn = intval($aarray[1]);
					$erow[$aarray[0]] = $amoutn;
					$i++;
				}
			}
			$allgristshere = 0;
			if (!empty($feedrow['abstratus'])) {
				$erow['abstratus'] = $feedrow['abstratus'];
				if (strpos($erow['abstratus'], "kind") !== false) $weapon = true;
			} else {
				$erow['abstratus'] = "notaweapon";
			}
			if (!empty($feedrow['wearable'])) {
				$erow['wearable'] = $feedrow['wearable'];
				if (strpos($erow['wearable'], "body") !== false) $bodygear = true;
			} else {
				$erow['wearable'] = "none";
			}
			$wornpower = $erow['power'] + $erow['aggrieve'] + $erow['aggress'] + $erow['assail'] + $erow['assault'] + $erow['abuse'] + $erow['accuse'] + $erow['abjure'] + $erow['abstain'];
			$wpnpower = $erow['power'] + $erow[heaviestBonus($erow)];
			if (!empty($weapon))
				$basetotalcost = (pow($wpnpower, 2) / 8) + ($wpnpower * 1.5);
			elseif (!empty($bodygear))
				$basetotalcost = (pow($wornpower, 2) * 1.25) + ($wornpower * 2);
			else
				$basetotalcost = (pow($wornpower, 3) / 150) + ($wornpower * 3);
			$erow['basetotalcost'] = $basetotalcost;
			if (!empty($feedrow['grists'])) {
				$barray = explode("|", $feedrow['grists']);
				$gristw = [];
				$totalweight = 0;
				foreach ($barray as $b) {
					$aarray = explode(":", $b);
					$amoutn = intval($aarray[1]);
					$gristw[$aarray[0]] = $amoutn;
					$totalweight += $amoutn;
				}
				foreach ($grist as $g) {
					$griststr = $g['name'] . '_Cost';
					/*
					$percent = $gristw[$g['name']] / $totalweight;
					$erow[$griststr] = round(($percent * $basetotalcost));
					*/
					$erow[$griststr] = $gristw[$g['name']];
				}
				$erow['totalweight'] = $totalweight;
			}
		} else echo "Either no submission with that ID exists, or it isn't ready to be processed.<br />";
	}
	//print_r($suggrist);
	if ($processing == 0) $founditem = false;
	if ($populate) {
		$editresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`code` = '$editcode' LIMIT 1;");
		while($row = mysqli_fetch_array($editresult)) {
			$founditem = true;
			echo $row['name'] . " loaded<br />";
			$erow = $row;
		}
	}
	
	if (!$founditem) {
		echo "No item loaded. You may use the following to search for one:<br />";
		echo '<form action="itemedit.php" method="get">Item code: <input type="text" name="editcode" /><br />-OR-<br />Item name: <input type="text" name="editname" /><br /><input type="submit" value="Search" /></form>';
	}
	echo '<form action="itemedit.php?';
	if (!empty($_GET['sub'])) {
		echo 'sub=' . strval($_GET['sub']) . '&';
	} elseif (!empty($_GET['dobase'])) {
		echo 'dobase=' . strval($_GET['dobase']) . '&';
	}
	echo '" method="post" id="itemeditor"><table cellpadding="0" cellspacing="0"><tbody><tr><td align="right">Item Editor:</td><td> Ultra Hyper Mega Reboot Edition. Handy links: <a href="developerguidetoeffects.txt">Effect documentation</a> <a href="itemeditguide.txt">Item editing information</a></td></tr>';
	if ($populate) echo '<input type="hidden" name="populate" value="yes">';
	else echo '<input type="hidden" name="populate" value="no">';
	echo '<input type="hidden" name="processing" value="' . strval($processing) . '">';
	$fieldresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` LIMIT 1;");
	while ($field = mysqli_fetch_field($fieldresult)) {
		echo '<tr><td align="right">';
		$fname = $field->name;
		if ($fname == "description") {
			echo $fname . ':</td><td><textarea name="description" rows="6" cols="40" form="itemeditor">';
			if ($founditem) echo $erow[$fname];
			elseif (!empty($_POST[$fname])) echo $_POST[$fname];
			echo '</textarea><br />';
		} elseif ($fname != "gristcosts" && $fname != "ID") {
			echo $fname . ':</td><td> <input type="text" name="' . $fname . '"';
			if ($founditem) echo ' value="' . $erow[$fname] . '"';
			elseif (!empty($_POST[$fname])) echo ' value="' . $_POST[$fname] . '"';
			elseif ($fname == "size") echo ' value="average"';
			elseif ($fname == "abstratus") echo ' value="notaweapon"';
			elseif ($fname == "wearable") echo ' value="none"';
			echo '></td></tr>';
		}
	}
	echo '</tbody></table><br /><table cellpadding="0" cellspacing="0"><tbody>';
	$col = 1;
	foreach ($grist as $g) { //go through the grists now that they're being done differently
		if ($col == 1) echo '<tr>';
		echo '<td align="right">';
		$gristname = $g['name'];
		if ($g['gif'] == 1) $gristimg = $gristname . ".gif";
		else $gristimg = $gristname . ".png";
		echo "<img src='../images/grist/".$gristimg."' height='15' width='15' alt = 'xcx'/>";
		//$ctgc += $erow[$fname];
		echo $gristname . '(' . strval($g['tier']) . '):</td><td> <input type="text" name="' . $gristname . '"';
		if ($loadold || !empty($_GET['sub'])) {
			if (!empty($erow[$gristname . "_Cost"])) {
				$suggrist[$gristname] = $erow[$gristname . "_Cost"];
			}
		} elseif ($founditem) {
			$am = howmuchGrist($erow['gristcosts'], $gristname);
			if ($am != 0) $suggrist[$gristname] = $am;
		}
		if (!empty($suggrist[$gristname])) echo ' value="' . strval($suggrist[$gristname]) . '"';
		//echo strval($suggrist[$gristname]);
		echo '></td>';
		$col++;
		if ($col == 4) {
			echo '</tr>';
			$col = 1;
		}
	}
	echo '</tbody></table>';
	echo '<input type="checkbox" name="gristify" value="yes" /> Enable Endgamifier (grist values given are treated as percentage of endgame cost)<br />NOTE: Hybrids should always be balanced manually.<br />';
	echo '<input type="checkbox" name="weighted" value="yes"' . (!empty($erow['totalweight']) ? ' checked' : '') . ' /> Values given are not exact, but weights of the recommended cost rounded to the nearest: <input type="text" name="weightround" value="1" /><br />';
	echo '- Tweak auto-balanced costs by <input type="text" name="costtweak" value="0" /> percent.<br />';
	echo '- Force an override of the auto-balanced cost, instead using a manual total grist cost of <input type="text" name="costoverride" ' . (!empty($erow['totalweight']) ? 'value="' . strval($erow['totalweight']) . '" ' : '') . '/>' . (!empty($erow['basetotalcost']) ? ' (Suggested total cost is ' . strval($erow['basetotalcost']) . ')' : '') . '<br />';
	if ($ctgc != 0) echo "Current total grist cost: $ctgc<br />";
	echo 'Dev comments about the item: If you changed the item significantly, such as changing a component or switching the operation, say so here. Or just whatever you want to add to its entry in the addlog.<br /><textarea name="devcomments" rows="6" cols="40" form="itemeditor"></textarea><br />';
	echo '<input type="submit" value="Edit/Create"></form><br />';
	if ($accrow['modlevel'] >= 6) {
		$sysresult = mysqli_query($connection, "SELECT `addlog` FROM `System` WHERE 1");
		$sysrow = mysqli_fetch_array($sysresult);
		if (empty($sysrow['addlog'])) $sysrow['addlog'] = " Empty!";
		echo "Current addlog:" . $sysrow['addlog'];
		echo "<br />When you're done with your batch of items, please use the following form to publish the current addlog into a news post.<br />(Note: All fields are optional and will be filled with placeholders if left blank.)<br />";
		echo '<form action="itemedit.php" method="post" id="publishaddlog"><input type="hidden" name="publishlog" value="yes">Title: <input type="text" name="publishtitle"><br />Body: <textarea name="publishbody" rows="6" cols="40" form="publishaddlog"></textarea><br /><input type="submit" value="Publish addlog"></form>';
	}
}

require_once$_SERVER['DOCUMENT_ROOT'] . "/footer.php";
