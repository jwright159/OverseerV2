<?php
$pagetitle = "Strife!";
$headericon = "/images/header/rancorous.png";
require_once("header.php");
require_once("includes/strifefunctions.php");
if (!empty($_POST['strifetojoin'])) {
	$newID = intval($_POST['strifetojoin']); //This covers against SQL injection so escaping is unnecessary
	$fatiguestr = 'wakefatigue'; //Should be overwritten by the below code, but just in case
	if ($charrow['dreamingstatus'] == "Awake") {
		$sid = $charrow['wakeself']; //$sid for strife ID
		$fatiguestr = 'wakefatigue';
	} else {
		$sid = $charrow['dreamself'];
		$fatiguestr = 'dreamfatigue';
	}
	$charrow = spendFatigue(10, $charrow); //Dumb magic number: strifes cost 10 fatigue
	$playerside = 0;
	mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID, `side` = $playerside, `leader` = 0, `fatigue` = " . $charrow[$fatiguestr] . " WHERE `Strifers`.`ID` = $sid LIMIT 1;"); //Add the player
	if ($charrow['dreamingstatus'] == "Awake") { //Allies can't follow you to the moons. Temporary, we might add moon allies later.
		mysqli_query($connection, "UPDATE `Strifers` SET `strifeID` = $newID, `side` = $playerside WHERE `Strifers`.`owner` = " . $charrow['ID'] . " AND `Strifers`.`Aspect` = '';"); //Add allies
		$lead=mysqli_query($connection, "SELECT owner FROM Strifers WHERE leader=1 AND strifeID=". $newID . ";");
		$leader=mysqli_fetch_array($lead);
		notifyCharacter($leader['owner'], $charrow['name'] . " has joined you in Strife #" . $newID . "!");
	}
	require_once("strifedisplay.php");
}
require_once("footer.php");
?>