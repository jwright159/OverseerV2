<?php
$pagetitle = "Item Catalogue";
$headericon = "/images/header/atheneum.png";
require_once("header.php");
require 'includes/additem.php';

if (empty($_SESSION['username'])) {
	echo "Log in to captchalogue stuff.<br />";
} elseif (empty($_SESSION['character'])) {
	echo "Choose a character to captchalogue stuff.<br />";
} else {
	$captchas = 500 - $charrow['captchalogues'];

	if (!empty($_POST['item'])) {
		if ($captchas > 0) {
		$itemresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = " . mysqli_real_escape_string($connection, $_POST['item']));
		while ($irow = mysqli_fetch_array($itemresult)) {
			if ($irow['base'] == 1) {
				$iname = $irow['name'];
				$success = addItem($charrow, $_POST['item']);
				if ($success) {
					echo $iname . " captchalogued successfully!<br />";
				} else {
					$success = storeItem($charrow, $_POST['item'], 1);
					if ($success) {
						echo "Your inventory is full, so $iname was placed in storage.<br />";
						setAchievement($charrow, 'itemfull');
					} else {
						echo "Captchalogue failed: Your inventory is full, and so is your storage!<br />";
					}
				}
				if ($success) {
					$captchas -= 1;
					mysqli_query($connection, "UPDATE Characters SET captchalogues = " . strval($charrow['captchalogues'] + 1) . " WHERE ID = $cid");
				}
			} else echo "Error: that item is not a base item!<br />";
		}
		} else echo "Error: you have no captchalogues remaining!<br />";
	}

	echo "Base Item Catalogue<br />";
	echo "Select an item to captchalogue. Captchas remaining: $captchas<br />";
	echo "All items:<br>";
	echo "<form action='catalogue.php' method='post'><select name='item'>";
	$baseresult = mysqli_query($connection, "SELECT `ID`,`name` FROM `Captchalogue` WHERE `base` = 1 ORDER BY name ASC");
	while ($row = mysqli_fetch_array($baseresult)) {
		echo "<option value='" . $row['ID'] . "'>" . $row['name'] . "</option>";
	}
	echo "</select><input type='submit' value='Captchalogue' /></form>";

	echo "<br>Weapons ordered by abstratus:<br>";
	echo "<form action='catalogue.php' method='post'><select name='item'>";
	$baseresult = mysqli_query($connection, "SELECT `ID`,`name`,`abstratus` FROM `Captchalogue` WHERE `base` = 1 AND abstratus IS NOT NULL AND abstratus!='notaweapon' ORDER BY abstratus ASC, name ASC");
	while ($row = mysqli_fetch_array($baseresult)) {
		echo "<option value='" . $row['ID'] . "'>" . $row['name'] . " - " . $row['abstratus'] ."</option>";
	}
	echo "</select><input type='submit' value='Captchalogue' /></form>";

	echo "<br>Wearables ordered by type:<br>";
	echo "<form action='catalogue.php' method='post'><select name='item'>";
	$baseresult = mysqli_query($connection, "SELECT `ID`,`name`, `wearable` FROM `Captchalogue` WHERE `base` = 1 AND wearable IS NOT NULL AND wearable!='' AND wearable!='none'
		ORDER BY wearable like '%accessory%', wearable like '%body%', wearable like '%face', wearable like '%head%', name ASC");
	while ($row = mysqli_fetch_array($baseresult)) {
		echo "<option value='" . $row['ID'] . "'>" . $row['name'] . " - " . $row['wearable'] ."</option>";
	}
	echo "</select><input type='submit' value='Captchalogue' /></form>";

	echo "<br>Reference items:<br>";
	echo "<form action='catalogue.php' method='post'><select name='item'>";
	$baseresult = mysqli_query($connection, "SELECT `ID`,`name` FROM `Captchalogue` WHERE `base` = 1 AND `refrance` = 1 ORDER BY name ASC");
	while ($row = mysqli_fetch_array($baseresult)) {
		echo "<option value='" . $row['ID'] . "'>" . $row['name'] ."</option>";
	}
	echo "</select><input type='submit' value='Captchalogue' /></form>";

}

require_once("footer.php");
?>
