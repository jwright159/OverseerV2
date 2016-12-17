<?php
require_once("header.php");

if (empty($_SESSION['username'])) {
	echo "Log in to be someone else.<br />";
} else {
	if (!empty($_POST['char'])) {
		if (strpos($accrow['characters'], strval($_POST['char']) . "|") !== false) {
			$_SESSION['character'] = $_POST['char'];
			$charresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = " . mysqli_real_escape_string($connection, $_SESSION['character']) . " LIMIT 1;");
			$charrow = mysqli_fetch_array($charresult);
			echo "You are now $charrow[name].<br /><br />What will you do?<br />";
			if ($accrow['lastchar'] != $_SESSION['character']) {
				$accrow['lastchar'] = $_SESSION['character'];
				mysqli_query($connection, "UPDATE Users SET lastchar = " . mysqli_real_escape_string($connection, $_SESSION['character']) . " WHERE ID = " . $accrow['ID']);
			}
		} else echo "You can't be a character that doesn't belong to you!<br />";
	}
	
	if (empty($_SESSION['character'])) {
		echo "You do not currently have a character selected.<br />";
	} else {
		$sesrow = loadSessionrow($charrow['session']);
		$sname[$charrow['session']] = $sesrow['name']; //commit this session name to memory so we don't have to look it up again
		echo "You are currently " . $charrow['name'] . " of session " . $sesrow['name'] . ".<br />";
	}
	$chars = explode("|", $accrow['characters']);
	$i = 0;
	$charquery = "SELECT `ID`,`name`,`session` FROM Characters WHERE ";
	$foundone = false;
	while (!empty($chars[$i])) {
		$foundone = true;
		$charquery .= "ID = " . strval($chars[$i]) . " OR ";
		$i++;
	}
	echo "<br />";
	if ($foundone) {
		echo "<form action='betheotherguy.php' method='post'>Choose a character: <select name='char'>";
		$charquery = substr($charquery, 0, -4);
		$charresult = mysqli_query($connection, $charquery);
		while ($row = mysqli_fetch_array($charresult)) {
			if (empty($sname[$row['session']])) {
				$sesrow = loadSessionrow($row['session']);
				$sname[$row['session']] = $sesrow['name'];
			}
			echo "<option value='" . $row['ID'] . "'>" . $row['name'] . " - " . $sname[$row['session']] . "</option>";
		}
		echo "</select><input type='submit' value='Switch!' /></form>";
	} else echo "You don't appear to have any characters yet! <a href='characterform.php'>Click here to create one.</a>";
}

require_once("footer.php");
?>