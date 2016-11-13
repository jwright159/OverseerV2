<?php
require_once("header.php");

if (empty($_SESSION['character'])) {
	echo "Choose a character to see your killed consorts.<br />";
} else {
	echo 'You are entering the graveyard of the Land of '.$charrow['land1'].' and '.$charrow['land2'].', a silent memorial to one of the grandest, most violent, most cataclysmic battles the Incipisphere has ever borne witness to.';
		$consortRows = mysqli_query($connection, "SELECT * FROM `DeadConsorts` WHERE `belongedto` = '".$charrow['ID']."';");
		$consortArray = mysqli_fetch_array($consortRows);
		if($consortArray) setAchievement($charrow, 'deadconsort');
		echo '<h5 style="text-align:center">';
	while ($row = mysqli_fetch_assoc($consortRows)) {
		$consortNickname = $row['name'];
		echo ''.$consortNickname.'<br>';
	}
	echo '</h5>';
}	
require_once("footer.php");