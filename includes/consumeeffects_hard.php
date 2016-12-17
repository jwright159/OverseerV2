<?php
//Note: this file assumes you are coming from consumeeffects.php and is in the middle of sorting through a consumable's effects.
//It is included separately so that it doesn't have to load in a bunch of hardcoded effect data that won't be used 99.9% of the time.
//It is strongly advised that you don't use it anywhere else.
switch ($args[1]) {
	case "cruxite_artifact":
		if ($charrow['wakeself'] == 0) { //player's strife row not initialized
			$pname = mysqli_real_escape_string($connection, $charrow['name']);
			mysqli_query($connection, "INSERT INTO Strifers (`name`,`owner`,`side`,`leader`,`teamwork`,`control`,`description`) VALUES ('$pname', $cid, 0, 1, 100, 1, '$pname\\'s waking self.')");
			$wakeid = mysqli_insert_id($connection);
			mysqli_query($connection, "INSERT INTO Strifers (`name`,`owner`,`side`,`leader`,`teamwork`,`control`,`description`) VALUES ('$pname', $cid, 0, 1, 100, 1, '$pname\\'s dreamself.')");
			$dreamid = mysqli_insert_id($connection);
			mysqli_query($connection, "UPDATE Characters SET `wakeself` = $wakeid, `dreamself` = $dreamid WHERE ID = $cid");
			echo "You smash your cruxite artifact, enabling you to finally enter the game. <a href='enter.php'>Click here to enter the Medium.</a>";
		} else {
			echo "You have already smashed your cruxite artifact. <a href='enter.php'>Click here to enter the Medium.</a>";
			$donotconsume = true; //not that the user will particularly miss it if it didn't preserve
		}
		break;
  case "add_cards":
    echo "After much fiddling with your Sylladex, you manage to flush one of your blank Captchalogue Cards into it, increasing your storage capacity. Hooray.<br>";
    $charrow['invslots']++;
    mysqli_query($connection, "UPDATE Characters SET invslots = " . $charrow['invslots'] . " WHERE ID = $cid");
    break;
	default:
		echo "ERROR: Unrecognized hardcoded effect ID: " . $args[1] . "<br />";
		break;
}
?>