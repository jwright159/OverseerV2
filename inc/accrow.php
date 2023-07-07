<?php
require_once $_SERVER['DOCUMENT_ROOT']."/includes/global_functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inc/database.php";
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
		$time = time();
	}
}
//////////