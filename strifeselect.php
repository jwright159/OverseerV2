<?php
$pagetitle = "Strife!";
$headericon = "/images/header/rancorous.png";
require_once "header.php";
if (!empty($_POST['land'])) {
	$maxenemies = 12;
	if ($_POST['land'] == "battlefield") {
		$enemyresult = mysqli_query($connection, "SELECT `basename`, `basepower` FROM `Enemy_Types` WHERE `Enemy_Types`.`appearson` = 'Battlefield'");
	} else {
		$enemyresult = mysqli_query($connection, "SELECT `basename`, `basepower` FROM `Enemy_Types` WHERE `Enemy_Types`.`appearson` = 'Lands'");
	}
	$n = 0;
	while ($row = mysqli_fetch_array($enemyresult)) {
		$enemyarray[$n] = $row;
		$n++;
	}
	$landresult = mysqli_query($connection, "SELECT `grist_type` FROM `Characters` WHERE `Characters`.`ID` = '". mysqli_real_escape_string($connection, $_POST['land']) ."' LIMIT 1;");
	$landrow = mysqli_fetch_array($landresult);
	$gristarray = explode('|', $landrow['grist_type']); //Items 0 through 8 are the grists for tiers 1 through 9 here
	$i = 1;
	echo '<form action="strifebegin.php" method="post">';
	while ($i <= $maxenemies && $i <= $_POST['quantity']) {
		$enemyname = "enemy" . strval($i);
		if ($_POST['land'] != "battlefield") { //Not selecting Battlefield enemies: Allow grist choice
			$gristname = $enemyname . "grist";
			echo "<select name='$gristname'>";
			$j = 0;
			while ($j < 9) { //Nine tiers of grist.
				$tier = $j + 1;
				echo "<option value='$tier'>" . $gristarray[$j] . " (x$tier)</option>";
				$j++;
			}
		}
		echo "</select>";
		echo "<select name='$enemyname'>";
		$j = 0;
		while (!empty($enemyarray[$j])) {
			echo "<option value='" . $enemyarray[$j]['basename'] . "'>" . $enemyarray[$j]['basename'] . " (Base power: " . $enemyarray[$j]['basepower'] . ")</option>";
			$j++;
		}
		echo "</select><br />";
		$i++;
	}
	echo '<input type="hidden" name="land" value="' . $_POST['land'] . '">';
	echo '<input type="submit" value="Go looking for these enemies!" /></form><br />';
	if ($_POST['land'] == "battlefield") {
		$sessionresult = mysqli_query($connection, "SELECT battlefield_power FROM Sessions WHERE Sessions.ID = " . $charrow['session'] . " LIMIT 1;");
		$sessionrow = mysqli_fetch_array($sessionresult);
		echo "Dersite Army power remaining: " . $sessionrow['battlefield_power'] . "<br />";
		echo "Warning! The Black King hasn't been implemented yet so the above doesn't matter right now!<br>";
	}
	if ($charrow['gatescleared'] >= 7 && $charrow['denizendown'] == 0 && $_POST['land'] == $charrow['ID']) {
		echo '<br /><form action="strifebegin.php" method="post">';
		echo '<input type="hidden" name="Denizen" value="true">';
		echo '<input type="hidden" name="enemy1" value="Denizen">';
		echo '<input type="submit" value="Face your Denizen" /></form><br />';
	} elseif ($charrow['denizendown'] == 1) {
		echo 'Your Denizen has been defeated already!<br />';
	}
} else {
	echo "You need to select a Land to go find underlings to beat up on!<br />";
}
require_once "footer.php";
?>
