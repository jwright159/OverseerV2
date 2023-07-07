<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/global_functions.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/inc/database.php";
if (!empty($_SESSION['forever'])) {
	if ($_SESSION['forever']) setcookie(session_name(),session_id(),time()+60*60*24*365); //Reset cookie expiration date to a year in the future if user logged in forever.
}
if (!empty($_SESSION['username'])) {
	$username = $_SESSION['username'];
	$accresult = mysqli_query($connection, "SELECT * FROM `Users` WHERE `username` = '" . $_SESSION['username'] . "' LIMIT 1;");
	$accrow = mysqli_fetch_array($accresult);
	if (!empty($_SESSION['character'])) {
		$charresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = " . $_SESSION['character'] . " LIMIT 1;");
		$charrow = mysqli_fetch_array($charresult);
		if ($charrow['owner'] != $accrow['ID']) {
			echo "ERROR: You tried to select a character that doesn't belong to you!";
			$_SESSION['character'] = 0; //reset character
			$charrow = array(); //blank charrow
		} else {
			$cid = $charrow['ID'];
			//$striferesult = mysqli_query($connection, "SELECT * FROM `Users` WHERE `ID` = " . $_SESSION['character'] . " LIMIT 1;");
			//maybe only load in strifer row when on a strife-related page?
		}
		$_SESSION['inv'] = array_filter(explode("|", $charrow['inventory']), function($item){ return (bool)$item; });
		$invslots = count($_SESSION['inv']);
		$_SESSION['imeta'] = explode("|", $charrow['metadata'], $invslots);
		$fatiguetimer = 100;
		if (!empty($charrow['Aspect'])) {
			if ($charrow['Aspect'] == "Time") $fatiguetimer = floor($fatiguetimer * 0.9); //Hack - Temporal Warp. Not worth checking abilities just for this.
		}
		$time = time();
		if ($charrow['fatiguetimer'] == 0) { //Fatigue timer uninitialized. Should not happen.
			mysqli_query($connection, "UPDATE `Characters` SET `fatiguetimer` = $time WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1;");
			$charrow['fatiguetimer'] = $time;
		} else {
			$recovery = floor(($time - $charrow['fatiguetimer']) / $fatiguetimer); //Recover 3 points every $fatiguetimer seconds
			if ($recovery > 0) {
				$recoverytime = floor($fatiguetimer * $recovery); //Recovery time is per point.
				$charrow['fatiguetimer'] += $recoverytime;
				$charrow['wakefatigue'] = max(($charrow['wakefatigue'] - ($recovery * 10)), 0);
				$charrow['dreamfatigue'] = max(($charrow['dreamfatigue'] - ($recovery * 10)), 0);
				mysqli_query($connection, "UPDATE `Characters` SET `fatiguetimer` = $charrow[fatiguetimer], `wakefatigue` = $charrow[wakefatigue], `dreamfatigue` = $charrow[dreamfatigue] WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1;");
			}
		}
	}
}
