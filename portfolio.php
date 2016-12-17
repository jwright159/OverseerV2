<?php
$pagetitle = "Strife Portfolio";
$headericon = "/images/header/rancorous.png";
require_once("header.php");
require 'includes/additem.php';
require 'includes/item_render.php';

?>
<script src="/js/clipboard.min.js"></script>
<script type="text/javascript">
	function deckAction(i, action) {
		$("select#deckitem").val(i);
		$("select#deckaction").val(action);
		$("form#deckform").submit();
	}
	function addToDeck(i) {
		$("select#eqweapon").val(i);
		$("form#addform").submit();
	}
	function equip(i) {
		$("select#eqwear").val(i);
		$("form#equipform").submit();
	}
</script>
<script>
    var clipboard = new Clipboard('.btn');
    </script>
<?php

if (empty($_SESSION['username'])) {
	echo "Log in to peruse your equipment.<br />";
} elseif (empty($_SESSION['character'])) {
	echo "Choose a character to peruse your equipment.<br />";
} else {
	/*echo '<br>Time for debugging<br>';
	echo 'current equips= ' . $charrow['equips'] . '<br>';
	echo 'current inventory= ' . $charrow['inventory'] . '<br>';
	echo 'current metadata= ' . $charrow['metadata'] . '<br>';
	echo 'current strifedeck ' . $charrow['strifedeck'] . '<br>';
	echo 'current equips ' . $charrow['equips'] . '<br>';

	echo '<br><br>';*/
	$mainp=false;
	$accp=false;
	$bodyp=false;
	$headp=false;
	$facep=false;
	if (empty($charrow['equips'])) $charrow['equips'] = "main:0|off:0|";
	$wequips = $charrow['equips']; //we'll work off of this variable so that ANY changes to it will be updated via the equipment parser
	$unequipped = 0;
	$initted = false;
	$init = false;
	if (!empty($_POST['newabs'])) { //player is assigning an abstratus manually
		if (substr_count($charrow['abstratus'], "|") < $charrow['abslots']) {
			$abresult = mysqli_query($connection, "SELECT * FROM System");
			$row = mysqli_fetch_array($abresult);
			if (strpos($row['allabstrati'], $_POST['newabs']) !== false) {
				$charrow['abstratus'] .= "|" . $_POST['newabs']; //add the abstratus to the character's abstrati
				mysqli_query($connection, "UPDATE Characters SET abstratus = '" . $charrow['abstratus'] . "' WHERE ID = $cid");
				echo "You allocate " . $_POST['newabs'] . " to your strife portfolio, enabling you to equip weapons that contain that specibus.<br />";
			} else echo "Error: that abstratus cannot be assigned.<br />";
		} else echo "Error: you have no open slots to assign a kind abstrati.<br />";
	}

	if (!empty($_POST['eqwpn'])) { //player is adding a weapon to their strife deck. the "eqwpn" is because the "eqweapon" might be 0 and thus treated as empty
		$i = $_POST['eqweapon'];
		$meta = explode(":", $_SESSION['imeta'][$i]);
		$canequip = false;
		if ($meta[0] % 2 == 1) { //odd-numbered first arg, item is available
			$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $_SESSION['inv'][$i]);
			$erow[$_SESSION['inv'][$i]] = mysqli_fetch_array($eresult);
			if (!empty($erow[$_SESSION['inv'][$i]]['abstratus']) && $erow[$_SESSION['inv'][$i]]['abstratus'] != "notaweapon") { //item is a weapon
				$j = 0;
				$itemabses = explode(", ", $erow[$_SESSION['inv'][$i]]['abstratus']);
				while (!empty($itemabses[$j])) {
					if ((strpos($charrow['abstratus'], "|" . $itemabses[$j]) !== false) OR (strpos($charrow['abstratus'], "|jokerkind") !== false)) $canequip = true;
					$j++;
				}

				if ($j == 1 && $canequip == false) { //item has only one abstratus, and the user doesn't have it yet
					$abstaken = substr_count($charrow['abstratus'], "|"); //count how many abstrati the player has
					if ($abstaken < $charrow['abslots']) {
						$charrow['abstratus'] .= "|" . $itemabses[0]; //auto-assign the weapon's abstratus
						mysqli_query($connection, "UPDATE Characters SET abstratus = '" . $charrow['abstratus'] . "' WHERE ID = $cid");
						echo "Your strife portfolio detects that you are trying to equip a weapon of a new abstratus type and \"helpfully\" auto-assigns it for you.<br />";
						$canequip = true;
					} else echo "You cannot add that weapon to your strife deck, and you have no free strife cards in your portfolio to assign its abstratus.<br />";
				} else {
					if ($canequip == false) echo "Your strife deck rejects the multi-abstratus weapon! You must assign one of its abstrati to your strife portfolio manually first.<br />";
				}
			} else echo "Error: that item is not a weapon.<br />";
		} else echo "Error: that item is not available in your sylladex.<br />";
		if ($canequip) {
			$charrow['strifedeck'] .= $_SESSION['inv'][$i] . "|"; //add to strife deck
			$charrow['invslots'] -= 1; //the weapon's card moves to the deck with it
			echo "Your " . $erow[$_SESSION['inv'][$i]]['name'] . " was added to your strife deck.<br />";
			array_splice($_SESSION['inv'], $i, 1);
			array_splice($_SESSION['imeta'], $i, 1);
			mysqli_query($connection, "UPDATE Characters SET strifedeck = '" . $charrow['strifedeck'] . "', invslots = " . $charrow['invslots'] . " WHERE ID = $cid");
		}
	}

	$deck = explode("|", $charrow['strifedeck']); //do this up here so that the equipment parser/deck manipulation thingy can use it
	if (!empty($_POST['deckaction'])) { //player wants to do something with their strife deck
		if ($_POST['deckaction'] == "main") { //equipping a weapon to main slot
			$newequip = $deck[$_POST['deckitem']];
			$deck[$_POST['deckitem']] = $deck[0];
			$deck[0] = $newequip;
			$wequips = str_replace("main:0|", "main:1|", $wequips); //tell the main slot that the first item is equipped now, if it hasn't been already
			if (empty($erow[$newequip]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $newequip);
				$erow[$newequip] = mysqli_fetch_array($eresult);
			}
			if (itemSize($erow[$newequip]['size']) == itemSize("large")) { //weapon is two-handed
				//echo "wequips currently " . $wequips . '<br>';
				$wequips = str_replace("off:0|", "off:2|", $wequips); //fill the off slot with the 2-handed flag
				$wequips = str_replace("off:1|", "off:2|", $wequips); //replacing the equipped offhand weapon if need be
				//echo "wequips changed to " . $wequips . '<br>';
				echo "You equip your " . $erow[$newequip]['name'] . " in both hands.<br />";
				if($erow[$newequip]['power']==9999 and $erow[$newequip]['aggrieve']==9999 and $erow[$newequip]['aggress']==9999 and 
					$erow[$newequip]['assail']==9999 and $erow[$newequip]['assault']==9999 and $erow[$newequip]['abuse']==9999 and 
					$erow[$newequip]['accuse']==9999 and $erow[$newequip]['abjure']==9999 and $erow[$newequip]['abstain']==9999)
					setAchievement($charrow,'ultweapon');

				$init = true;
			} else {
				if(count($deck)<3) str_replace("off:1|", "off:0|", $wequips); //if you're changing weapons from off to main with an empty inventory
				$wequips = str_replace("off:2|", "off:0|", $wequips); //just in case you're substituting a two-handed for a one-handed
				echo "You equip your " . $erow[$newequip]['name'] . " in your main hand.<br />";
				$init = true;
			}
			if(($erow[$newequip]['power']==9999 and $erow[$newequip]['aggrieve']==9999 and $erow[$newequip]['aggress']==9999 and 
				$erow[$newequip]['assail']==9999 and $erow[$newequip]['assault']==9999 and $erow[$newequip]['abuse']==9999 and 
				$erow[$newequip]['accuse']==9999 and $erow[$newequip]['abjure']==9999 and $erow[$newequip]['abstain']==9999) or 
				($erow[$newequip]['power']==9999 and $erow[$newequip]['aggrieve']==3333 and $erow[$newequip]['aggress']==3333 and 
				$erow[$newequip]['assail']==3333 and $erow[$newequip]['assault']==3333 and $erow[$newequip]['abuse']==3333 and 
				$erow[$newequip]['accuse']==3333 and $erow[$newequip]['abjure']==3333 and $erow[$newequip]['abstain']==3333))
				 setAchievement($charrow,'ultweapon');
		} elseif ($_POST['deckaction'] == "off") { //equipping a weapon to offhand slot
			$newequip = $deck[$_POST['deckitem']];
			//echo 'trying to equip in offhandslot, deckitem = ' . $deck[$_POST['deckitem']] . '<br>';
			if (empty($erow[$newequip]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $newequip);
				$erow[$newequip] = mysqli_fetch_array($eresult);
			}

			if (itemSize($erow[$newequip]['size']) == itemSize("large")) { //off-handed weapon is two-handed
				echo "You can't equip a two-handed weapon in your off-hand.<br />";
			} else {
				if (strpos($wequips, "off:2|") !== false) { //main weapon is two-handed
					echo "You can't equip an offhand weapon because both of your hands are already full!<br />";
				} else {
					//echo 'entering second phase of equipping, weapon is small enough, nothing equipped...<br>';
					//echo 'changing $deck[$_POST[deckitem] to ' . $deck[1] . '<br>';
					if($_POST['deckitem']!=0){
						$deck[$_POST['deckitem']] = $deck[1];
						$deck[1] = $newequip;
						//echo 'and now changing $deck[1] to $newequip which is ' . $newequip . '<br>';
						//echo "wequips currently " . $wequips . '<br>';
						$wequips = str_replace("off:0|", "off:1|", $wequips); //tell the off slot that the second item is equipped now, if it hasn't been already
						//echo "wequips changed to " . $wequips . '<br>';
						echo "You equip your " . $erow[$newequip]['name'] . " in your off-hand.<br />";
						$init = true;
						if(($erow[$newequip]['power']==9999 and $erow[$newequip]['aggrieve']==9999 and $erow[$newequip]['aggress']==9999 and 
						$erow[$newequip]['assail']==9999 and $erow[$newequip]['assault']==9999 and $erow[$newequip]['abuse']==9999 and 
						$erow[$newequip]['accuse']==9999 and $erow[$newequip]['abjure']==9999 and $erow[$newequip]['abstain']==9999) or 
						($erow[$newequip]['power']==9999 and $erow[$newequip]['aggrieve']==3333 and $erow[$newequip]['aggress']==3333 and 
						$erow[$newequip]['assail']==3333 and $erow[$newequip]['assault']==3333 and $erow[$newequip]['abuse']==3333 and 
						$erow[$newequip]['accuse']==3333 and $erow[$newequip]['abjure']==3333 and $erow[$newequip]['abstain']==3333))
						 setAchievement($charrow,'ultweapon');
					}
					else echo "Please drop or unequip your main weapon before equipping it in your off-hand.<br>";
				}
			}
		} elseif ($_POST['deckaction'] == "drop") { //returning weapon to sylladex
			if($_POST['deckitem']!=0 || stripos($wequips, 'off:1')==false){ //if you're dropping the main weapon with an off weapon equipped, stop it
				$charrow['invslots'] += 1; //the card that holds this item is returned to your inventory
				$success = addItem($charrow, $deck[$_POST['deckitem']]); //should always work since we just added the inv slot
				if ($success) {
					mysqli_query($connection, "UPDATE Characters SET invslots = " . $charrow['invslots'] . " WHERE ID = $cid"); //Just to make sure
					array_splice($deck, $_POST['deckitem'], 1); //blank this slot so that it doesn't get imploded
					echo "You take the weapon out of your strife deck, returning it to your sylladex.<br />";
					//echo "wequips currently " . $wequips . '<br>';
					if ($_POST['deckitem'] == 0) $wequips = str_replace("main:1|", "main:0|", $wequips); //this item was equipped in the main slot, unequip it
					if ($_POST['deckitem'] == 1) $wequips = str_replace("off:1|", "off:0|", $wequips); //was equipped in off slot, unequip it
					if ($_POST['deckitem'] == 0) $wequips = str_replace("off:2|", "off:0|", $wequips); //the only way to equip 2-handed is using the main slot, so just in case we'll do this, no problem
					//echo "wequips changed to " . $wequips . '<br>';
				} else { //just in case
					$charrow['invslots'] -= 1; //no exploits for you
					echo "You cannot insert that item back into your sylladex, for some reason.<br />";
				}
			}else echo "Please unequip or drop your offhand weapon first.<br>";
		}
		/*
		echo 'imploding deck: <br>';
		print_r($deck);
		echo '<br>';*/
		if((empty($deck[0])) && (!empty($deck[1]))){
			$newdeck=$deck[1] . '|';
		}else{
			$newdeck = implode("|", $deck); //reassemble the deck
		}
		//echo 'after implosion newdeck is ' . $newdeck . '<br>';
		if ($newdeck != $charrow['strifedeck'] || $init) { //check if there was actually a change
			$charrow['strifedeck'] = $newdeck;
			$charrow['equips'] = $wequips;
			//echo 'updating strifedeck to newdeck= ' . $newdeck . '<br>';
			mysqli_query($connection, "UPDATE Characters SET invslots = $charrow[invslots], strifedeck = '$newdeck', equips = '$wequips' WHERE ID = $cid"); //commit the change
			strifeInit($charrow,$connection);
			$initted = true;
		}
	}

	if (!empty($_POST['eqwr'])) { //player is equipping a wearable
		$i = $_POST['eqwear'];
		$meta = explode(":", $_SESSION['imeta'][$i]);
		$canequip = false;
		if ($meta[0] % 2 == 1) { //odd-numbered first arg, item is available
			$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $_SESSION['inv'][$i]);
			$erow[$_SESSION['inv'][$i]] = mysqli_fetch_array($eresult);
			if (!empty($erow[$_SESSION['inv'][$i]]['wearable']) && $erow[$_SESSION['inv'][$i]]['abstratus'] != "none") { //item is a weapon
				$itemabses = explode(", ", $erow[$_SESSION['inv'][$i]]['wearable']);
				$j = 0;
				$canequip = true;
				$eequips = "";
				while (!empty($itemabses[$j])) {
					if (strpos($wequips, $itemabses[$j]) !== false) $canequip = false; //already has a wearable in that slot, denied
					else $eequips .= $itemabses[$j] . ":" . $_SESSION['inv'][$i] . "|"; //item will take up this slot
					$j++;
				}
				if ($canequip) {
					$wequips .= $eequips; //add the slots the item takes up
					$charrow['invslots'] -= 1; //the wearables's card moves to equips with it
					echo "You equip your " . $erow[$_SESSION['inv'][$i]]['name'] . ".<br />";
					array_splice($_SESSION['inv'], $i, 1);
					array_splice($_SESSION['imeta'], $i, 1);
					mysqli_query($connection, "UPDATE Characters SET invslots = " . $charrow['invslots'] . " WHERE ID = $cid");
				} else echo "Error: you are already wearing something in one or more slots used by that wearable.<br />";
			} else echo "Error: that item is not a wearable.<br />";
		} else echo "Error: that item is not available in your sylladex.<br />";
	}

	$eqecho = "Equipment<br /><br />Your current equips:<br />"; //don't echo this yet because unequipping is handled in the equip parser, and we want those messages to echo first
	$equip = explode("|", $wequips);
	$i = 0;
	if(empty($_POST['unequip'])) $_POST['unequip'] = "";
	while (!empty($equip[$i])) {
		$thisequip = explode(":", $equip[$i]);
		if ($thisequip[0] == "main") {
			if ($_POST['unequip'] == "main") {
				//echo 'wequips is ' . $wequips . '<br>';
				if(stripos($wequips, 'off:1')==false){
					$equip[$i] = "main:0"; //so that it'll get imploded in as "empty slot" and updated as such
					echo "You unequip your main weapon, returning it to your strife deck.<br />";
					$thisequip[1] = 0;
				}else echo "Please unequip your offhand weapon before unequipping your main one.<br>";
			}
			if ($thisequip[1] == 1) $thisequip[1] = $deck[0]; //use the ID from the strife deck
		} elseif ($thisequip[0] == "off") {
			if ($thisequip[1] == 2) { //the main weapon is 2-handed
				if ($_POST['unequip'] == "main") { //unequipping 2-handed weapon
					$equip[$i] = "off:0";
				} else {
					$eqecho .= "offhand: (2 handed)<br />"; //for consistency
				}
				$thisequip[1] = 0; //don't display any of the other stuff regardless
			} else {
				if ($_POST['unequip'] == "off") {
					$equip[$i] = "off:0"; //so that it'll get imploded in as "empty slot" and updated as such
					echo "You unequip your offhand weapon, returning it to your strife deck.<br />";
					$thisequip[1] = 0;
				}
				if ($thisequip[1] == 1){
					if(($equip[0]=="main:0") and (count($deck)<3)) $thisequip[1] = $deck[0];
					else{
						$thisequip[1] = $deck[1]; //use the ID from the strife deck
					}
				}
			}
		} else {
			if ($thisequip[1] == $_POST['unequipid']) { //we're unequipping this
				if ($thisequip[0] == $_POST['unequip']) { //so that we only do this once
					$charrow['invslots'] += 1; //the card that holds this item is returned to your inventory
					$success = addItem($charrow, $_POST['unequipid']); //should always work since we just added the inv slot
					if ($success) {
						mysqli_query($connection, "UPDATE Characters SET invslots = " . $charrow['invslots'] . " WHERE ID = $cid"); //Just to make sure
						unset($equip[$i]); //blank this slot so that it doesn't get imploded
						echo "You take off the item in your " . $_POST['unequip'] . " slot, returning it to your sylladex.<br />";
						$thisequip[1] = 0; //don't display it
					} else { //just in case
						$charrow['invslots'] -= 1; //no exploits for you
						echo "You cannot insert the item that you have equipped back into your sylladex, for some reason.<br />";
					}
				} else {
					unset($equip[$i]); //blank this slot so that it doesn't get imploded
					$thisequip[1] = 0; //don't display it
				}
			}
		}
		if ($thisequip[1] != 0) { //there's something here that wasn't just unequipped
			$eqecho .= "<form action='portfolio.php' method='post'>";
			if (empty($erow[$thisequip[1]]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $thisequip[1]);
				$erow[$thisequip[1]] = mysqli_fetch_array($eresult);
			}
			if(stripos($thisequip[0], 'acc')!==false) $accp=true;
			if(stripos($thisequip[0], 'head')!==false) $headp=true;
			if(stripos($thisequip[0], 'main')!==false) $mainp=true;
			if(stripos($thisequip[0], 'face')!==false) $facep=true;
			if(stripos($thisequip[0], 'body')!==false) $bodyp=true;
			if($accp && $bodyp && ($headp OR $facep) && $mainp) setAchievement($charrow, 'fullport');
			$eqecho .= $thisequip[0] . " - " . $erow[$thisequip[1]]['name'];/* . ": " . $erow[$thisequip[1]]['power'];
			if ($thisequip[0] == "main" || $thisequip[0] == "off") $eqecho .= " Power";
			else $eqecho .= " Defense";
			$actives = $erow[$thisequip[1]]['aggrieve'] + $erow[$thisequip[1]]['aggress'] + $erow[$thisequip[1]]['assail'] + $erow[$thisequip[1]]['assault'];
			$passives = $erow[$thisequip[1]]['abuse'] + $erow[$thisequip[1]]['accuse'] + $erow[$thisequip[1]]['abjure'] + $erow[$thisequip[1]]['abstain'];
			if ($actives != 0 || $passives != 0) {
				echo "; ";
				if ($actives != 0) $eqecho .= bonusStr($actives) . " ";
				if ($passives != 0) $eqecho .= bonusStr($passives);
			}*/
			$eqecho .= "<input type='hidden' name='unequip' value='" . $thisequip[0] . "' /><input type='hidden' name='unequipid' value='" . $thisequip[1] . "' /><input type='submit' value='Unequip' /></form>";
		}
		$i++;
	}
	$equipstr = implode("|", $equip); //implode the string so that any changes will be updated below
	if ($charrow['equips'] != $equipstr) { //equips were updated
		mysqli_query($connection, "UPDATE Characters SET invslots = $charrow[invslots], equips = '$equipstr' WHERE ID = $cid");
		if (!$initted) strifeInit($charrow,$connection); //call strifeinit if we haven't already
	}
	echo $eqecho;
	echo "<br />Allocated kind abstrati:<br />";
	$abs = explode("|",$charrow['abstratus']);
	$i = 1; //the first "slot" will be blank because of the way these are formatted (| in front instead of in back)
	$open = false;
	while ($i <= $charrow['abslots']) {
		if (!empty($abs[$i])) echo $abs[$i] . "<br />";
		else {
			echo "(open slot)<br />";
			$open = true;
		}
		$i++;
	}
	if ($open) {
		echo "<form action='portfolio.php' method='post'>Assign an abstratus to one of your open slots manually: <select name='newabs'>";
		$abresult = mysqli_query($connection, "SELECT * FROM System");
		$row = mysqli_fetch_array($abresult);
		$abs = explode("|", $row['allabstrati']);
		$i = 0;
		while (!empty($abs[$i])) {
			echo "<option value='" . $abs[$i] . "'>" . $abs[$i] . "</option>";
			$i++;
		}
		echo "<b><span style=color:red>!!!WARNING: ONCE ASSIGNED, YOUR PROTOTYPINGS CANNOT BE CHANGED.!!!</span></b>";
		echo "</select><input type='submit' value='Assign it!' /></form>";
	}
	echo "<br />Your strife deck:<br /><br />";
	echo '<div class="inventory">';
	$i = 0;
	$deckform = "";
	if (empty($deck[0]) && !empty($deck[1])) $i = 1; //If the user equips their only weapon to their offhand, the first entry will be blank and the
	//game will think the deck is completely empty. This is a workaround for that.
	while (!empty($deck[$i])) {
		if (empty($erow[$deck[$i]]['name'])) {
			$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = " . $deck[$i]);
			$erow[$deck[$i]] = mysqli_fetch_array($eresult);
		}
		// context menu
		$menuid = 'deck_menu_'.strval($i);
		echo '<menu type="context" id="'.$menuid.'">';
		echo '<menuitem label="Equip as main weapon" onclick="deckAction('.$i.', \'main\')"></menuitem>';
		echo '<menuitem label="Equip as offhand weapon" onclick="deckAction('.$i.', \'off\')"></menuitem>';
		echo '<menuitem label="Remove from strife deck" onclick="deckAction('.$i.', \'drop\')"></menuitem>';
		echo '</menu>';
		renderItem2($erow[$deck[$i]], "", $menuid);
		$deckform .= "<option value='" . $i . "'>" . $erow[$deck[$i]]['name'] . "</option>"; //build this here because I am a genius
		$i++;
	}
	echo '</div>';
	if ($deckform != "") {
		echo "<form action='portfolio.php' method='post' id='deckform'>Strife deck: <select name='deckitem' id='deckitem'>";
		echo $deckform; //and echo it here! wahahaha
		echo "</select><select name='deckaction' id='deckaction'><option value='main'>Equip as main weapon</option><option value='off'>Equip as offhand weapon</option><option value='drop'>Remove from strife deck</option></select>";
		echo "<input type='submit' value='Go' /></form>";
	} echo "Currently empty! You can assign a weapon to it below.<br />";
	echo "<br />Available inventory items:<br /><br />";
	$i = 0;
	$w = 0;
	$e = 0;
	echo '<div class="inventory">';
	while ($i < $invslots) {
		$meta = explode(":", $_SESSION['imeta'][$i]);
		if ($meta[0] % 2 == 1) { //odd-numbered first arg, item is available
			if (empty($erow[$_SESSION['inv'][$i]]['name'])) {
				$eresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE ID = '" . $_SESSION['inv'][$i] . "';");
				$erow[$_SESSION['inv'][$i]] = mysqli_fetch_array($eresult);
			}
			// context menu
			$menuid = 'inv_menu_'.strval($i);
			echo '<menu type="context" id="'.$menuid.'">';
			if (!empty($erow[$_SESSION['inv'][$i]]['abstratus']) && strpos($erow[$_SESSION['inv'][$i]]['abstratus'], "notaweapon") === false) {
				$weaponchoice[$w] = $i;
				$w++;
				echo '<menuitem label="Add to strife deck" onclick="addToDeck('.$i.')"></menuitem>';
			}
			if (!empty($erow[$_SESSION['inv'][$i]]['wearable']) && $erow[$_SESSION['inv'][$i]]['wearable'] != "none") {
				$equipchoice[$e] = $i;
				$e++;
				echo '<menuitem label="Equip" onclick="equip('.$i.')"></menuitem>';
			}
			echo '</menu>';
			renderItem2($erow[$_SESSION['inv'][$i]], "", $menuid);
		}
		$i++;
	}
	echo '</div>';
	$i = 0;
	while ($i < $w) {
		if ($i == 0) echo "<form action='portfolio.php' method='post' id='addform'><input type='hidden' name='eqwpn' value='ys' />Add weapon to strife deck: <select name='eqweapon' id='eqweapon'>";
		echo "<option value='" . $weaponchoice[$i] . "'>" . $erow[$_SESSION['inv'][$weaponchoice[$i]]]['name'] . "</option>";
		$i++;
	}
	if ($i > 0) echo "</select><input type='submit' value='Add' /></form>";
	$i = 0;
	while ($i < $e) {
		if ($i == 0) echo "<form action='portfolio.php' method='post' id='equipform'><input type='hidden' name='eqwr' value='ys' />Equip a wearable: <select name='eqwear' id='eqwear'>";
		echo "<option value='" . $equipchoice[$i] . "'>" . $erow[$_SESSION['inv'][$equipchoice[$i]]]['name'] . "</option>";
		$i++;
	}
	if ($i > 0) echo "</select><input type='submit' value='Equip' /></form>";
}

require_once("footer.php");
?>
