<?php
$pagetitle = "Inventory";
$headericon = "/images/header/inventory.png";
require_once "header.php";
require_once "includes/additem.php";
require_once "includes/item_render.php";

?>
<script src="/js/clipboard.min.js"></script>
<script type="text/javascript">
	function itemAction(i, action) {
		$("select#invitem").val(i);
		$("select#invaction").val(action);
		$("form#inv").submit();
	}
</script>
<script>
    var clipboard = new Clipboard('.btn');
    </script>
<?php

if (empty($_SESSION['character'])) {
	echo "You can't look at the sylladex of a nonexistent character!<br />";
} else {
	if(isset($_GET['old'])) {
		if($_GET['old'] == '1') {
			// use old view
			$newui = false;
			$_SESSION['oldinvui'] = false;
		} else {
			// use new view
			$newui = true;
			$_SESSION['oldinvui'] = true;
		}
	} else {
		if(isset($_SESSION['oldinvui'])) $newui = $_SESSION['oldinvui'];
		else $newui = true;
	}

	if (!empty($_POST['invaction'])) {
		$item = $_POST['invitem'];
		$meta = explode(":", $_SESSION['imeta'][$item]);
		if ((int)$meta[0] % 2 == 1) { //item is available
			$i = $_SESSION['inv'][$item];
			if (empty($irow[$i]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $i); //first load it up
				$irow[$i] = mysqli_fetch_array($eresult);
			}
			if ($_POST['invaction'] == "use") { //player wants to consume this item
				if (!empty($irow[$i]['consumable'])) { //item can be consumed
					$validtarget = true;
					if (empty($striferow)) $striferow = loadStriferow($charrow['wakeself']); //only the waking self can use items, for now
					$n = 0; //n for "number of strifers"
					if ($striferow['strifeID'] == 0) { //user not in strife
						$strifers[1] = $striferow; //load in the user's row
						$n = 1;
						$consumerindex = 1; //User is your waking self.
						$consumuser = $charrow['wakeself'];
						if (empty($_POST['target'])) $_POST['target'] = $charrow['ID'];
						if ($_POST['target'] != $charrow['ID']) { //Fetch the target's rows and do some verification
							$targetcharresult = mysqli_query($connection, "SELECT * FROM Characters WHERE Characters.ID = ". mysqli_real_escape_string($connection, $_POST[target]) ." LIMIT 1;");
							$targetcharrow = mysqli_fetch_array($targetcharresult);
							if ($targetcharrow['dreamingstatus'] != "Awake") {
								echo "That player is currently asleep!<br />";
								$validtarget = false;
							} elseif ($targetcharrow['session'] != $charrow['session']) {
								echo "That player is not in your session!<br />";
								$validtarget = false;
							} else {
								$targetstriferow = loadStriferow($targetcharrow['wakeself']);
								if ($targetstriferow['strifeID'] != 0) {
									echo "That player is currently strifing! You will need to be assisting them if you wish to use consumables on them.<br />";
									$validtarget = false;
								} else {
									$strifers[2] = $targetstriferow; //load in the target's strife row
									$strifers[2]['side'] = $strifers[1]['side']; //Make sure these two count as on the same side
									$targetindex = 2;
								}
							}
						} else { //Consumable user is also the consumable target
							$targetindex = $consumerindex;
							$targetuser = $consumuser;
						}
					} else {
						$consumuser = $charrow['wakeself']; //if you use an item from this screen, the user is implied to be yourself
						$targetuser = $_POST['target']; //Target's strife ID sent through
						$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];"); //Grab all strifers
						while ($row = mysqli_fetch_array($striferesult)) {
							$n++; //NOTE - This means the first strifer will be entry 1 in the array, NOT entry 0
							$strifers[$n] = $row; //Store each strifer in a successive index
							if ($strifers[$n]['ID'] == $consumuser) $consumerindex = $n;
							if ($strifers[$n]['ID'] == $targetuser) $targetindex = $n;
							$updatedstatus[$strifers[$n]['ID']] = $strifers[$n]['status']; //We will add new statuses to this if they pop up over the course of resolution.
							$updatedbonus[$strifers[$n]['ID']] = $strifers[$n]['bonuses']; //ditto with bonuses
						}
						if (empty($targetindex)) { //Target not found. Default to the consumable user.
							//Yes, this means you need to coordinate so that you don't accidentally target an already dead strifer
							//and end up blowing yourself up or something.
							$targetindex = $consumerindex;
							$targetuser = $consumuser;
						}
					}
					if ($validtarget) {
						$consumeffectstr = $irow[$i]['consumable'];
						include("includes/consumeeffects.php");
						$strifers[$consumerindex]['subaction'] = 1; //Use up the user's subaction
						if (!$donotconsume) { //item was consumed, remove it lol
							array_splice($_SESSION['inv'], $item, 1);
							array_splice($_SESSION['imeta'], $item, 1);
						}
						$i = 1;
						while ($i <= $n) {
							//Check out EOT effects in the status field here
							$strifers[$i]['status'] = $updatedstatus[$strifers[$i]['ID']];
							$strifers[$i]['bonuses'] = $updatedbonus[$strifers[$i]['ID']];
							$i++;
						}
						include("includes/strifefunctions.php"); //for the megaquery
						$megaquery = buildMegaquery($strifers,$n,$connection);
						mysqli_query($connection, $megaquery);
					}
				} elseif ($irow[$i]['ID'] == 14) { //Hard-coded usage of cruxite artifact
					echo "<a href='enter.php'>If you're ready, enter here.</a><br />";
				} else echo "You can't use this item in any special way.<br />";
			} elseif ($_POST['invaction'] == "drop") { //player wants to drop this item
				if (!empty($_POST['target'])) {
					if ($_POST['target'] == $charrow['ID']) echo "OW! Why did you do that?<br />";
					else echo "Your ally is not particularly amused by this development.<br />";
				} else {
					//add checks to see where the player is later, so you can drop items in dungeons and etc. For now it'll just go to storage
					$extras = "";
					if(!empty($meta[1])) $extras = $meta[1]; //metadata now contains all of the extra effects of an item just like storage
					$success = storeItem($charrow, $i, 1, $extras);
					if ($success) {
						array_splice($_SESSION['inv'], $item, 1);
						array_splice($_SESSION['imeta'], $item, 1);
						echo "You take your " . $irow[$i]['name'] . " out of your sylladex and drop it off in your house.<br />";
					} else echo "You don't have enough room in your house to drop that!<br />";
				}
			} elseif ($_POST['invaction'] == "read") { //player wants to read this item
        $read = specialArray($irow[$i]['effects'], "READ");
        if ($read[0] == "READ") {
          $readresult = mysqli_query($connection, "SELECT * FROM Reading WHERE ID = " . strval($read[1]));
          $reading = mysqli_fetch_array($readresult);
          echo "You read your " . $irow[$i]['name'] . "...<br /><br />";
          echo $reading['text'] . "<br />";
        } else echo "Your " . $irow[$i]['name'] . " is not good reading material.<br />(But if you think it should be, feel free to submit a text for it to <a href='http://babbyoverseer.tumblr.com'>the Overseer Item Blog</a>!)<br />";
      } elseif($_POST['invaction'] == "recycle"){
		  $gristed = false;
			if (!empty($charrow['inventory'])) {
				$invNew = $charrow['inventory'];
				$boom = explode("|", $charrow['inventory']);
				$newgrist = $charrow['grists'];
				if(in_array($i ,$boom)){
					$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`ID` = " . $i . " LIMIT 1;");
					$irow = mysqli_fetch_array($iresult);
					$nothing = true;
					$gristname = initGrists();
					$totalgrists = count($gristname);		
					$deploytag = specialArray($irow['effects'], "DEPLOYABLE"); //should always return an array because of the search query above
					if ($deploytag[1] == "FREE") $zerobuild = true;
					elseif ($deploytag[1] == "TIER1") {
						$zerot1 = true;
						$zerobuild = true;
					}
					echo "You recycle a " . $irow['name'] . " into ";
					if($irow['name']=='Perfectly Unique Object') setAchievement($charrow, 'allgrist');	
					$g = 0;
					$boomi = explode("|", $irow['gristcosts']);
					while (!empty($boomi[$g])) {
						$boomo = explode(":", $boomi[$g]);
						if ((!$zerobuild || $boomo[0] != "Build_Grist") && (!$zerot1 || $boomo[0] != $tier1)) {
							$nothing = false;
							$boomo[1] = intval($boomo[1]);
							$newgrist = modifyGrist($newgrist, $boomo[0], $boomo[1]);
                            $imageUrl = gristImage($boomo[0]); 
                            echo '<img src=
									"'.$imageUrl.
									 '" height="50" width="50" title="' . $boomo[0] . '"></img>';
                          
							echo " <gristvalue2>" . strval($boomo[1]) . "</gristvalue2>";
						}
						$g++;
					}
					echo "</br>";
					array_splice($_SESSION['inv'], $item, 1);
					array_splice($_SESSION['imeta'], $item, 1);
					$invNew = str_replace($i."|","",$invNew);
					$invSlotsNew = $charrow['invslots']; //There was a +1 here, but I don't think that's necessary.

					mysqli_query($connection, "UPDATE `Characters` SET `grists` = '$newgrist', `inventory` = '$invNew', `invslots` = " . strval($invSlotsNew) . " WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
				}
			} else echo "You have no items to recycle!<br />";
			//compuRefresh($charrow);
	  }else echo "Unknown action.<br />";
		} else echo "That item is not available.<br />";
	}

	if (!empty($_POST['getitem'])) {
		$get = explode("|", $_POST['getitem']);
		$boom = explode("|", $charrow['storeditems']);
		$updatestore = "";
		$gotten = false;
		$success = false;
		$totalitems = count($boom);
		$i = 1;
		while ($i < $totalitems) {
			if ($gotten == false) {
				$args = explode(":", $boom[$i - 1]);
				if ($args[0] == $get[0] && $args[2] == $get[1]) { //this is the item we want to retrieve
					$gotten = true; //found the item, no need to explode/check any more
					if (!empty($args[2])) $extras = $args[2];
					else $extras = "";
					$success = addItem($charrow, $args[0], $extras);
					if ($success) {
						$args[1] -= 1;
						if ($args[1] > 0) $updatestore .= implode(":", $args) . "|";
						if (empty($irow[$args[0]]['name'])) {
							$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $args[0]);
							$irow[$args[0]] = mysqli_fetch_array($eresult);
						}
						$charrow['storedspace'] -= itemSize($irow[$args[0]]['size']);
						echo "You captchalogue your " . $irow[$args[0]]['name'] . " from storage.<br />";
					} else{
						echo "You have no room in your inventory for this item.<br />";
						setAchievement($charrow, 'itemfull');
					}
				} else $updatestore .= $boom[$i - 1] . "|";
			} else $updatestore .= $boom[$i - 1] . "|";
			$i++;
		}
		if ($success && $charrow['storeditems'] != $updatestore) { //item was retrieved, update storage
			if (empty($updatestore)) $charrow['storedspace'] = 0; //Paranoia: if the storage is empty and the game thinks something is taking up space, reset it to 0
			$charrow['storeditems'] = $updatestore;
			mysqli_query($connection, "UPDATE Characters SET storeditems = '$updatestore', storedspace = " . strval($charrow['storedspace']) . " WHERE ID = $cid");
		} else echo "You don't have that item in storage!<br />";
	}

	$itemlist = "";
	echo "Sylladex<br />";
	if($newui) {
		echo 'You are using the updated interface. <a href="?old=1">Switch to the old one.</a><br/>';
	} else {
		echo 'You are using the old interface. <a href="?old=0">Switch to the updated one.</a><br/>';
	}
	echo "Your Fetch Modus: " . $charrow['modus'] . " Modus<br />";
	$filled = count($_SESSION['inv']);
	echo "Slots Filled/Available: $filled/" . strval($charrow['invslots']) . "<br />";
	echo strval($charrow['invslots']-$filled).' free slots.<br/>';

	$i = 0;
	$j = 0;
	while ($i < $invslots) {
		$newequip = $_SESSION['inv'][$i];
		if (!empty($newequip)) {
			if (empty($irow[$newequip]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $newequip);
				$irow[$newequip] = mysqli_fetch_array($eresult);
			}
			$meta = explode(":", $_SESSION['imeta'][$i]);
			if ($meta[0] % 2 == 1) { //item is available
				$itemlist .= "<option value='$i'>" . $irow[$newequip]['name'] . "</option>";
				$j++;
			}
		}
		$i++;
	}
	
	echo "<br /><form id='inv' action='inventory.php' method='post'>Actions: <select id='invaction' name='invaction'>";
	echo "<option value='use'>Use</option><option value='read'>Read</option><option value='drop'>Drop</option><option value='recycle'>Recycle</option>"; //more actions to be added later
	echo "</select> <select id='invitem' name='invitem'>";
	echo $itemlist;
	echo "</select>";
	echo " on <select name='target'><option value=''>Nobody in particular</option>";
	
	if ($striferow['strifeID'] == 0) {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = $charrow[session];");
	} else {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];");
	}
	while ($targetrow = mysqli_fetch_array($targetresult)) {
		echo "<option value='$targetrow[ID]'>$targetrow[name]</option>";
	}
	echo "</select>";
	echo "<input type='submit' value='Go' /></form>";
	echo "NOTE: target will be ignored if inapplicable<br />";
	
	echo "<br />Your Items: <br /><br />";

	if($newui) {
		echo '<div class="inventory">';
		foreach($_SESSION['inv'] as $i => $item) {
			if($item != "") {
				if (empty($irow[$item]['name'])) {
					$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $item);
					$irow[$item] = mysqli_fetch_array($eresult);
				}
				$meta = explode(":", $_SESSION['imeta'][$i]);
				if(count($meta) < 2) $meta2 = "";
				else $meta2 = $meta[1];

				// context menu
				$menuid = 'menu_'.strval($i);
				echo '<menu type="context" id="'.$menuid.'">';
				echo '<menuitem label="Use" onclick="itemAction('.$i.', \'use\')"></menuitem>';
				echo '<menuitem label="Read" onclick="itemAction('.$i.', \'read\')"></menuitem>';
				echo '<menuitem label="Drop" onclick="itemAction('.$i.', \'drop\')"></menuitem>';
				echo '<menuitem label="Recycle" onclick="itemAction('.$i.', \'recycle\')"></menuitem>';
				if(empty($irow[$item]['art'])) {
					echo '<menuitem label="Submit Art" onclick="window.location.href = \'submitart.php?code='.urlencode($irow[$item]['code']).'\'"></menuitem>';
				}
				echo '</menu>';

				renderItem2($irow[$item], $meta2, $menuid);
			} else {
				renderItem2(NULL);
			}
		}
		echo '</div>';
	} else {
		$i = 0;
		$j = 0;
		while ($i < $invslots) {
			$newequip = $_SESSION['inv'][$i];
			if (!empty($newequip)) {
				if (empty($irow[$newequip]['name'])) {
					$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $newequip);
					$irow[$newequip] = mysqli_fetch_array($eresult);
				}
				renderItem($irow[$newequip]);
				$meta = explode(":", $_SESSION['imeta'][$i]);
				if ($meta[0] % 2 == 1) { //item is available
					echo "Available: Yes<br />";
				} else echo "Available: No<br />";
				echo "<br />";
			}	
			$i++;
		}
	}
	echo "<br /><form action='inventory.php' method='post'>Actions: <select name='invaction'>";
	echo "<option value='use'>Use</option><option value='read'>Read</option><option value='drop'>Drop</option><option value='recycle'>Recycle</option>"; //more actions to be added later
	echo "</select> <select name='invitem'>";
	echo $itemlist;
	echo "</select>";
	echo " on <select name='target'><option value=''>Nobody in particular</option>";
	
	if ($striferow['strifeID'] == 0) {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `Characters`.`session` = $charrow[session];");
	} else {
		$targetresult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`strifeID` = $striferow[strifeID];");
	}
	while ($targetrow = mysqli_fetch_array($targetresult)) {
		echo "<option value='$targetrow[ID]'>$targetrow[name]</option>";
	}
	echo "</select>";
	echo "<input type='submit' value='Go' /></form>";
	echo "NOTE: target will be ignored if inapplicable<br />";

	if (!empty($charrow['storeditems'])) {
		echo "Storage: <br />";
		$allspace = $charrow['house_build'] + 1000;
		echo "Storage space used: " . strval($charrow['storedspace']) . "/" . strval($allspace) . "<br />";
		echo "Select an item to captchalogue.<br />";
		echo '<form method="post" action="inventory.php"><select name="getitem">';
		$boom = explode("|", $charrow['storeditems']);
		$totalitems = count($boom);
		$i = 1;
		while ($i < $totalitems) {
			$args = explode(":", $boom[$i - 1]);
			$newequip = $args[0];
			if (empty($irow[$newequip]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $newequip);
				$irow[$newequip] = mysqli_fetch_array($eresult);
			}
			if ($irow[$newequip]['ID'] == intval($args[0])) {
				echo "<option value='" . $args[0] . "|" . $args[2] . "'>" . $irow[$newequip]['name']; //puts $args[2] as part of options so we can be sure we're
				//retrieving the card with the right punched code, for instance
				if (strpos($args[2], "CODE=") !== false) { //Has a code
					echo " (CODE: " . substr($args[2], strpos($args[2], "CODE=")+5, 8) . ")";
				}
				echo '</option>';
			}
			$i++;
		}
		echo '</select><input type="submit" value="Captchalogue" /></form>';
	} else {
		echo "If you had anything in storage, you could captchalogue it here.<br />";
	}
}

require_once "footer.php";
?>
