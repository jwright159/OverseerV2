<?php
$pagetitle = "SBURB Client";
$headericon = "/images/header/spirograph.png";
require_once("header.php");
require 'includes/additem.php';
require 'includes/designix.php';

if (empty($_SESSION['username'])) {
	echo "Log in to mess with devices.<br />";
} elseif (empty($_SESSION['character'])) {
	echo "Choose a character to mess with devices.<br />";
} else {
	if (strpos($charrow['storeditems'], "CRUXTRUDER.") !== false) {
		$crux = true;
	} else {
		$crux = false; }
	if (strpos($charrow['storeditems'], "TOTEMLATHE.") !== false) {
		$lathe = true;
	} else {
		$lathe = false; }
	if (strpos($charrow['storeditems'], "ALCHEMITER.") !== false) {
		$alch = true;
	} else {
		$alch = false; }
	if (!empty($_POST['cruxopen']) && $crux) { //User is attempting to open the cruxtruder
		if ($charrow['sprite'] == 0) { //Cruxtruder is closed, we can tell because the player lacks a sprite
			if (strpos($charrow['equips'], "main:1") !== false) { //Player has a weapon equipped
				$deck = explode("|", $charrow['strifedeck']);
				$mainwep = $deck[0];
				$wepresult = mysqli_query($connection, "SELECT `name` FROM Captchalogue WHERE ID = " . $mainwep);
				$weprow = mysqli_fetch_array($wepresult);
				$wname = $weprow['name'];
				echo "Brandishing your trusty $wname, you manage to pop the lid off after some effort! Almost immediately, a glowing ball of jumbled lines and colors flies out of the now open shaft. It hovers near you, as if waiting for you to do something.<br />";
				mysqli_query($connection, "INSERT INTO Strifers (name, owner, side, teamwork, control, description, power, maxpower, health, maxhealth) VALUES ('Sprite', $cid, 0, 100, 1, 'An unprototyped Kernelsprite.', 0, 0, 1, 1)");
				$newid = mysqli_insert_id($connection);
				mysqli_query($connection, "UPDATE Characters SET sprite = $newid WHERE ID = $cid");
				$charrow['sprite'] = $newid;
				//Additional cruxtruder opening effects can go here such as the entry timer
			} else {
				echo "You try to pry open the lid on the Cruxtruder with your bare hands, but it's too tight to even budge! It'd be easier if you had some sort of object to hit it with or stick under the lid...<br />";
			}
		} else echo "The Cruxtruder is already open!<br />";
	}

	//OVERSEER - It would probably make sense for newly minted cruxite dowels (and captchalogue cards, come to think of it) to have the code
	//00000000 imprinted on them in their metadata. (I think at the moment newly extruded cruxite will just have no code?).

	if (!empty($_POST['cruxtrude']) && $crux) { //User extruding cruxite
		$success = storeItem($charrow, 13, $_POST['cruxtrude']); //13 is the dowel's item ID
		if ($success) {
			echo "You turn the valve, retrieving " . $_POST['cruxtrude'] . " Cruxite Dowel(s) and setting them aside.<br />";
		} else echo "There isn't enough space in your house for that many dowels! You need to captchalogue or recycle something first.<br />";
	} //wow that was surprisingly short code

	if (!empty($_POST['lathecard1']) && $lathe) { //User carving a dowel
		$store = explode("|", $charrow['storeditems']);
		$arg = explode(":", $store[$_POST['firstdowel']]);
		if (strpos($arg[2], "CODE=") !== false) { //Has a code
			$tcode = substr($arg[2], strpos($arg[2], "CODE=")+5, 8);
		} else $tcode = "00000000";
		if ($tcode == "00000000" && $arg[0] == 13) { //Double check that the item here is a dowel and that it is uncarved
			$aok = true;
			$i = 1;
			while ($i < 3) { //Max. 2 cards, so we check both card inputs
				$area = substr($_POST['lathecard' . strval($i)], 0, 1);
				$n = substr($_POST['lathecard' . strval($i)], 1);
				if ($area == "i") { //Card is in inventory
					if ($_SESSION['inv'][$n] == 11) { //Double check that there is a card here
						$meta = explode(":", $_SESSION['imeta'][$n]);
						if ($meta[0] % 2 == 1) { //card is available
							if (strpos($meta[1], "CODE=") !== false) { //has a code
								$code[$i] = substr($meta[1], strpos($meta[1], "CODE=")+5, 8); //Retrieve the card code from the storage string
							} else $code[$i] = "00000000";
						} else $aok = false;
					} else $aok = false;
				} elseif ($area == "s") { //Card is in storage
					$arg = explode(":", $store[$n]);
					if ($arg[0] == 11) { //there is a card here
						if (strpos($arg[2], "CODE=") !== false) { //has a code
							$code[$i] = substr($arg[2], strpos($arg[2], "CODE=")+5, 8); //Retrieve the card code from the storage string
						} else $code[$i] = "00000000";
					} else $aok = false;
				} else $code[$i] = "!!!!!!!!"; //using no card is the equivalent of having all holes punched
				$i++;
			}
			if ($aok) {
				echo "DEBUG: andcombining " . $code[1] . " and " . $code[2] . "<br />";
				$newcode = andcombine($code[1], $code[2]);
				$arg = explode(":", $store[$_POST['firstdowel']]);
				$arg[1]--; //the uncarved dowel is consumed
				if ($arg[1] == 0) { //no uncarved dowels left in this slot
					array_splice($store, $_POST['firstdowel'], 1); //remove it from storage
				} else {
					$store[$_POST['firstdowel']] = implode(":", $arg);
				}
				$charrow['storeditems'] = implode("|", $store); //reassemble the user's storage with updated dowel status
				$charrow['storedspace'] -= 20; //make room for the new carved dowel
				storeItem($charrow, 13, 1, "CODE=" . $newcode . "."); //should always succeed
				echo "You carve the Cruxite Dowel into a totem, using your card(s) as input. It now has the code $newcode.<br />";
			} else echo "Looks like one or both of the cards you selected were moved since you last took inventory - please try the operation again.<br />";
		} else echo "Looks like your uncarved dowel was moved or carved since you last took inventory - please try the operation again.<br />";
	}

	if (empty($_POST['alchnum'])) $_POST['alchnum'] = 0;
	if ($_POST['alchnum'] > 0 && $alch) { //user is alchemizing something
		$i = $_POST['alchtotem'];
		if ($_SESSION['inv'][$i] == 13) { //this item is a cruxite dowel/totem
			$meta = explode(":", $_SESSION['imeta'][$i]);
			if ($meta[0] % 2 == 1) { //this totem is available
				if (strpos($meta[1], "CODE=") !== false) { //has a code
					$alchcode = substr($meta[1], strpos($meta[1], "CODE=")+5, 8); //Retrieve the card code from the storage string
				} else $alchcode = "00000000";
				echo "The alchemiter scans the code on the totem and begins to process it.<br />";
				$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$alchcode' AND (session = 0 OR session = " . $charrow['session'] . ")");
				//Above: Search for the item. Note that items that were created by other sessions and not yet approved are treated as nonexistent.
				if ($itemrow = mysqli_fetch_array($itemresult)) {
					echo "The alchemiter requests: ";
					$canafford = true;
					$g = 0;
					$gcost = explode("|", $itemrow['gristcosts']); //sort through the item's cost string
					$newgrists = $charrow['grists'];
					while (!empty($gcost[$g])) {
						$boomo = explode(":", $gcost[$g]);
						$realcost = $boomo[1] * $_POST['alchnum']; //multiply cost by quantity to determine how much this will cost in total
						if (strpos($newgrists, $boomo[0] . ":") !== false || $realcost < 0) { //check if this is a discovered grist type, or that it'll generate the grist (artifact, for instance)
							echo '<img src="' . gristImage($boomo[0]) . '" height="50" width="50" title="' . $boomo[0] . '"></img>'; //yee
							if (howmuchGrist($newgrists, $boomo[0]) >= $realcost || $realcost < 0) { //check if the user has enough grist. Negative costs will always work.
	      				echo " <gristvalue2>" . strval($boomo[1]) . "</gristvalue2>";
	      				$newgrists = modifyGrist($newgrists, $boomo[0], $realcost * -1); //Subtract (or add) the cost of the item here.
	      				//If alchemy fails, this change won't commit.
	      			} else {
	      				echo " <gristvalue>" . strval($realcost) . "</gristvalue>";
	      				$canafford = false;
	      			}
						} else {
							echo '<img src="images/grist/Unknown.png" height="50" width="50" title="Unknown Grist"></img>';
	      			echo " <gristvalue>" . strval($realcost) . "</gristvalue>";
	      			$canafford = false; //the user doesn't have this grist. Note that this will deny the user from creating the item even if the cost of this
	      			//undiscovered grist type is 0. This may or may not be useful. (Negative grists will still allow the item to be made)
						}
						$g++;
					}
					echo "<br />";
					if ($canafford) { //user can afford to spend the grist
						$success = storeItem($charrow, $itemrow['ID'], $_POST['alchnum']);
						if ($success) { //user has room for all the newly-created items
							echo "You successfully create " . $itemrow['name'] . " x " . strval($_POST['alchnum']) . "!<br />";
							mysqli_query($connection, "UPDATE Characters SET grists = '" . $charrow['grists'] . "' WHERE ID = $cid"); //Pay.
						} else echo "Alchemy failed: not enough space in storage.<br />";
					} else echo "Alchemy failed: your grist stores are insufficient.<br />";
				} else echo "The item corresponding to this code does not currently exist. You may create it using the <a href='quickitemcreate.php'>Quick Creation Form</a> or make a full submission.<br />";
			} else echo "Alchemy failed: that totem is not currently available in your inventory.<br />";
		} else echo "Alchemy failed: that item is not a cruxite totem!<br />";
	}

	echo "SBURB Devices<br /><br />";
	$nodevice = true;
	if ($crux) {
		echo "<b>Cruxtruder</b><br />";
		if ($charrow['sprite'] == 0) { //sprite not yet freed
			echo "This tank-like device has no obvious purpose. At the top of the tank is a shaft with a valve on the side and a lid over it. Turning the valve is impossible, as it seems to be trying to force something out of the closed-off top.<br />";
			if (strpos($charrow['equips'], "main:1") !== false) { //player has a weapon equipped
				$deck = explode("|", $charrow['strifedeck']);
				$mainwep = $deck[0];
				$wepresult = mysqli_query($connection, "SELECT `name` FROM Captchalogue WHERE ID = " . $mainwep);
				$weprow = mysqli_fetch_array($wepresult);
				$wname = $weprow['name'];
				echo '<form action="sburbdevices.php" method="post"><input type="hidden" name="cruxopen" value="yes" /><input type="submit" value="Open the lid with your '.$wname.'" /></form>';
			} else {
				echo "<form action='sburbdevices.php' method='post'><input type='hidden' name='cruxopen' value='yes' /><input type='submit' value='Open the lid' /></form>";
			}
			echo "<br />";
		} else {
			echo "This device is used to produce Cruxite Dowels. Simply turn the valve and one will pop out.<br />";
			echo "<form action='sburbdevices.php' method='post' />Number of dowels to produce: <input type='text' name='cruxtrude' /><br /><input type='submit' value='Turn it!' /></form>";
		}
		$nodevice = false;
	}

	if ($lathe) {
		echo "<b>Totem Lathe</b><br />";
		echo "This machine is capable of carving Cruxite Dowels with a pattern based on the card(s) inserted into it.<br />";
		echo "One uncarved dowel will be consumed per operation. Select one or two of your available Captchalogue Cards to begin.<br />";
		echo "<form action='sburbdevices.php' method='post'>";
		$i = 0;
		$l = 1;
		$list[0] = "None";
		$listv[0] = "none";
		while ($i < $invslots) { //sort through inventory and record all available captcha cards
			$meta = explode(":", $_SESSION['imeta'][$i]);
			if ($meta[0] % 2 == 1) { //this item is available
				if ($_SESSION['inv'][$i] == 11) { //this is a captcha card
					if (strpos($meta[1], "CODE=") !== false) { //has a code
						$code = substr($meta[1], strpos($meta[1], "CODE=")+5, 8); //Retrieve the card code from the storage string
					} else $code = "00000000";
					$list[$l] = "Card with code " . $code . " (inventory)";
					$listv[$l] = "i" . strval($i);
					$l++;
				}
			}
			$i++;
		}
		$i = 0;
		$cruxcount = 0;
		$store = explode("|", $charrow['storeditems']);
		while (!empty($store[$i])) { //now sort through storage, since not all modi will allow the user more than 1 card at a time
			$arg = explode(":", $store[$i]);
			if ($arg[0] == 11) { //this is a captcha card
				if (strpos($arg[2], "CODE=") !== false) { //has a code
					$code = substr($arg[2], strpos($arg[2], "CODE=")+5, 8);
				} else $code = "00000000";
				$list[$l] = "Card with code " . $code . " (storage)";
				$listv[$l] = "s" . strval($i);
				$l++;
			} elseif ($arg[0] == 13) { //a cruxite dowel
				if (strpos($arg[2], "CODE=") !== false) { //has a code
					$code = substr($arg[2], strpos($arg[2], "CODE=")+5, 8);
				} else $code = "00000000";
				if ($code == "00000000") { //dowel is uncarved
					if ($cruxcount == 0) echo "<input type='hidden' name='firstdowel' value='$i' />"; //mark the first dowel now so we don't have to run through storage when the form is submitted
					$cruxcount += $arg[1];
				}
			}
			$i++;
		}
		echo "Number of uncarved dowels in storage: $cruxcount<br />";
		echo "First card: <select name='lathecard1'>";
		$i = 0;
		while ($i < $l) {
			echo "<option value='" . $listv[$i] . "'>" . $list[$i] . "</option>";
			$i++;
		}
		echo "</select><br />Second card: <select name='lathecard2'>";
		$i = 0;
		while ($i < $l) {
			echo "<option value='" . $listv[$i] . "'>" . $list[$i] . "</option>";
			$i++;
		}
		echo "</select><br /><input type='submit' value='Carve it!' /></form>";
		$nodevice = false;
	}

	if ($alch) {
		echo "<b>Alchemiter</b><br />";
		echo "This device scans carved Cruxite Totems and attempts to create an item based on the totem's code, using your reserves of grist as the raw materials.<br />";
		echo "Note: Newly created items will be placed into storage automatically. You can then captchalogue them as needed.<br />";
		echo "NOTE: Totems must be captchalogued from storage before they can be used in the Alchemiter!<br />";
		echo "<form action='sburbdevices.php' method='post'>Choose a Cruxite Totem to scan: <select name='alchtotem'>";
		$i = 0;
		while ($i < $invslots) {
			if ($_SESSION['inv'][$i] == 13) { //this item is a cruxite dowel/totem
				$meta = explode(":", $_SESSION['imeta'][$i]);
				if ($meta[0] % 2 == 1) { //this totem is available
					if (strpos($meta[1], "CODE=") !== false) { //has a code
						$code = substr($meta[1], strpos($meta[1], "CODE=")+5, 8); //Retrieve the card code from the storage string
					} else $code = "00000000";
					echo "<option value='" . $i . "'>Totem with code " . $code . "</option>";
				}
			}
			$i++;
		}
		echo "</select>Quantity to alchemize: <input type='text' name='alchnum' value='1' /><br />";
		echo "<input type='submit' value='Alchemize!' /></form>";
		$nodevice = false;
	}


	if ($nodevice) {
		echo "You don't have any devices! Ask your server player to deploy something.<br />";
	}
}

require_once("footer.php");
?>
