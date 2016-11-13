<?php
$pagetitle = "Sleep";
$headericon = "/images/header/sleep.png";
require_once("header.php");
require_once("includes/global_functions.php");
if (empty($_SESSION['character'])) {
	echo "You must have a character selected to switch selves!<br />";
} elseif ($charrow['dreamer'] == "Unawakened") {
	echo "Your dreamself is not awakened!<br />";
} else {
	if ($charrow['dreamingstatus'] == "Awake") {
		$striferow = loadStriferow($charrow['wakeself']);
		if ($striferow['strifeID'] == 0 && $charrow['dungeon'] == 0) {
			mysqli_query($connection, "UPDATE `Characters` SET `dreamingstatus` = '" . $charrow['dreamer'] . "' WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
			echo "You drift off to sleep...or do you wake up? Either way, you are now in control of your dreamself.<br />";
		} elseif($charrow['dungeon'] == 0) {
			echo "It's kind of hard to get to sleep in the middle of strife!<br />";
		} else echo "It's kind of hard to get to sleep in a dangerous dungeon!<br>";
	} else {
		$striferow = loadStriferow($charrow['dreamself']);
		if ($striferow['strifeID'] == 0) {
			mysqli_query($connection, "UPDATE `Characters` SET `dreamingstatus` = 'Awake' WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1;");
			echo "You drift off to sleep...or do you wake up? Either way, you are now in control of your waking self.<br />";
		} else {
			echo "It's kind of hard to get to sleep in the middle of strife!<br />";
		}
	}
}
require_once("footer.php");
?>
