<?php
$pagetitle = "Atheneum";
$headericon = "/images/header/atheneum.png";
require_once("header.php");
require_once("includes/additem.php");
require_once("includes/item_render.php");
if (empty($_SESSION['character'])) {
	echo "You can't look at the atheneum from a nonexistent character!<br />";
} else {
	echo 'Session Atheneum<br />';
	echo 'All items acquired or previewed by players in your session will be shown here.<br /><br />';
	//retrieve session atheneum
	$sessionresult = mysqli_query($connection, "SELECT `atheneum` FROM `Sessions` WHERE `Sessions`.`ID` = '" . $charrow['session'] . "' LIMIT 1;");
	$sesrow = mysqli_fetch_array($sessionresult);
	//if viewing an item:
	if(!empty($_GET['holoid'])) {
		$_GET['holid'] = mysqli_real_escape_string($connection, $_GET['holoid']);
		//make sure item is in atheneum
		if (strpos($sesrow['atheneum'], "|" . $_GET['holoid'] . ":") !== false) {
			//retrieve item entry
			$boom = explode("|", $sesrow['atheneum']);
			for ($i = 0; $i < count($boom); $i++) {
				if (strpos($boom[$i], $_GET['holoid'] . ":") === 0) {
					$itementry = explode(":", $boom[$i]);
					break;
				}
			}
			//retrieve item information
			$thisisanitem = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `Captchalogue`.`ID` = '" . $_GET['holoid'] . "' LIMIT 1;");
			$iteminfo = mysqli_fetch_array($thisisanitem);
			//if item has been obtained, display all information
			if ($itementry[1] == 2) {
				renderItem2($iteminfo);
			} elseif ($itementry[1] == 1) {
				renderItem2($iteminfo, NULL, "", true, true);
			} else {
				echo "<img src='images/art/Unknown item.png' /><br />";
				renderGristCosts($iteminfo['gristcosts']);
			}
		} else {
			echo "That item is not in your atheneum!<br />";
		}
		echo "<br />";
	}
	//get sorting method
	$sortby = "name";
	if (!empty($_GET['sortby'])) {$sortby = $_GET['sortby'];}
	else {$sortby = "name";}
	$showstring = " WHERE";
	$othersearches = 0;
	//check various possible search parameters
	if (!empty($_GET['weapons'])) {
		if ($_GET['weapons'] == "yes") {
			$showstring = $showstring . " `Captchalogue`.`abstratus` NOT LIKE '%notaweapon%'";
			$othersearches = 1;
		} elseif ($_GET['weapons'] == "no") {
			$showstring = $showstring . " `Captchalogue`.`abstratus` LIKE '%notaweapon%'";
			$othersearches = 1;
		}
	}
	if (!empty($_GET['abstratus1'])) {
		if ($othersearches == 1) {
			$showstring = $showstring . " AND";
		}
		$_GET['abstratus1'] = mysqli_real_escape_string($connection, $_GET['abstratus1']);
		$showstring = $showstring . " `Captchalogue`.`abstratus` LIKE '%" . $_GET['abstratus1'] . "%'";
	}
	if (!empty($_GET['abstratus2'])) {
		if ($othersearches == 1) {
			$showstring = $showstring . " AND";
		}
		$_GET['abstratus2'] = mysqli_real_escape_string($connection, $_GET['abstratus2']);
		$showstring = $showstring . " `Captchalogue`.`abstratus` LIKE '%" . $_GET['abstratus2'] . "%'";
	}
	if (!empty($_GET['wearables'])) {
		if ($_GET['wearables'] == "yes") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`wearable` != 'none'";
			$othersearches = 1;
		} elseif ($_GET['wearables'] == "no") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`wearable` = 'none'";
			$othersearches = 1;
		}
	}
	if (!empty($_GET['consume'])) {
		if ($_GET['consume'] == "yes") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`consumable` IS NOT NULL";
			$othersearches = 1;
		} elseif ($_GET['consume'] == "no") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`consumable` IS NULL";
			$othersearches = 1;
		}
	}
	if (!empty($_GET['base'])) {
		if ($_GET['base'] == "yes") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`base` = 1";
			$othersearches = 1;
		} elseif ($_GET['base'] == "no") {
			if ($othersearches == 1) {
				$showstring = $showstring . " AND";
			}
			$showstring = $showstring . " `Captchalogue`.`base` = 0";
			$othersearches = 1;
		}
	}
	if ($showstring == " WHERE") {
		$showstring = "";
	}
	echo 'View options:<br /><br />';
	echo '<a href="atheneum.php">All items</a><br />';
	echo '<form method="get" action="atheneum.php">Weapons: <input type="radio" name="weapons" value="yes">Weapons <input type="radio" name="weapons" value="no">Non-weapons <input type="radio" name="weapons" value="both">Both<br />';
	echo 'Abstratus 1: <input type="text" name="abstratus1"><br />';
	echo 'Abstratus 2: <input type="text" name="abstratus2"><br />';
	echo 'Wearables: <input type="radio" name="wearables" value="yes">Wearables <input type="radio" name="wearables" value="no">Non-wearables <input type="radio" name="wearables" value="both">Both<br />';
	echo 'Consumables: <input type="radio" name="consume" value="yes">Consumables <input type="radio" name="consume" value="no">Non-consumables <input type="radio" name="consumables" value="both">Both<br />';
	echo 'Base items: <input type="radio" name="base" value="yes">Base items <input type="radio" name="base" value="no">Non-base items <input type="radio" name="base" value="both">Both<br />';
	echo 'Sort by: <input type="radio" name="sortby" value="name">Name <input type="radio" name="sortby" value="power">Power<br />';
	echo '<input type="submit" value="Search"></form><br />';
	//retrieve all items
	$captcharesult = mysqli_query($connection, "SELECT `ID`,`code`,`name` FROM `Captchalogue`$showstring ORDER BY `$sortby` ASC, `aggrieve` + `aggress` + `assail` + `assault` + `abuse` + `accuse` + `abjure` + `abstain` ASC");
	$founditems = 0;
	$totalitems = 0;
	//while we're going through items:
	while ($crow = mysqli_fetch_array($captcharesult)) {
		$totalitems++;
		//if the item is in the atheneum
		if (strrpos($sesrow['atheneum'], "|" . $crow['ID'] . ":") !== false) {
			$founditems++;
			//display it
			echo '<a href="atheneum.php?holoid=' . $crow['ID'] . '">' . $crow['name'] . ' - <itemcode>' . $crow['code'] . '</itemcode></a></br>';
		}
	}
	//display percentage of items the session has found
	$percents = ($founditems / $totalitems) * 100;
	echo "</br>ITEMS FOUND: " . strval($founditems) . " / " . strval($totalitems) . " (" . strval($percents) . "%)</br>";
	//if displaying all items, comment on the perc
	if ($showstring == "") {
		echo "Prof. Blah's Analysis:</br>";
		if ($percents <= 1) echo "Try getting some base items from the catalogue!";
		elseif ($percents <= 2) echo "You've got a good starting assortment of items - time to mash them together randomly!";
		elseif ($percents <= 5) echo "You can find a lot of exotic items in the dungeons!";
		elseif ($percents <= 10) echo "Don't forget, you can find different kinds of loot from different dungeons. Explore as many lands as you can!";
		elseif ($percents <= 15) echo "Your session has discovered over one tenth of all the items this multiverse has to offer. That's nothing to sneeze at!";
		elseif ($percents <= 25) echo "Your collection is coming along quite nicely. Now's the perfect time to scour the Item List and find items within your reach!";
		elseif ($percents <= 35) echo "Now THAT is a collection! You and your friends are doing very well, but can you go ALL THE WAY...?";
		elseif ($percents <= 45) echo "AHA, the item pile doesnt stop from getting TALLER.";
		elseif ($percents <= 55) echo "How HIGH do you even have to BE just to DO something like that.........";
		elseif ($percents <= 65) echo "This selection has too many PRICES and VAULES!";
		elseif ($percents <= 75) echo "Ok jesus christ that is a lot of items. Seriously that's like way more than enough to play this game how do you even have the time???";
		elseif ($percents <= 85) echo "This is starting to get out of hand... I was only joking when I said you could go ALL THE WAY...";
		elseif ($percents <= 95) echo "MAYDAY! MAYDAY! ITEM COLLECTION REACHING CRITICAL MASS! BAIL, FOR THE LOVE OF HUSSIE, BAIL!!!";
		elseif ($percents < 100) echo "well at this point the only thing left to do is to get those last few items... we're all going to die anyway";
		else {
			echo "You've finally done it. You've acquired all of the items that exist in this game. If there was ever a session that deserved to beat the game... it's " . $sesrow['name'] . ".<br />";
			echo "I guess there's only one thing to do now...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />...<br />";
			echo "SUBMIT MORE ITEMS??????????? :L";
		}
	}
}
echo "<br /><br />";
require_once("footer.php");
?>
