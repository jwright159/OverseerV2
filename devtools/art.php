<?php
$pagetitle = "Art Approver";
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";
require ($_SERVER['DOCUMENT_ROOT'] . "/includes/item_render.php");

if ($accrow['modlevel'] < 4) {
	echo "You're not supposed to be here.";
} else {
	echo 'Art Submission Approver v0.41.2<br/><br/>';

	if(isset($_POST['ap'])) {
		echo 'Approving submission...<br/>';
		$result = mysqli_query($connection, "SELECT * FROM `Art_Submissions` WHERE ID = '$_POST[id]' LIMIT 1;");
		$row = mysqli_fetch_array($result);
		mysqli_query($connection, "DELETE FROM `Art_Submissions` WHERE ID = '$_POST[id]' LIMIT 1;");

		$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE code = '".$row['code']."' LIMIT 1;");
		$irow = mysqli_fetch_array($iresult);

		$sresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE ID = '$row[submitter]' LIMIT 1;");
		$srow = mysqli_fetch_array($sresult);

		$aresult = mysqli_query($connection, "SELECT * FROM `Users` WHERE ID = '$srow[owner]' LIMIT 1;");
		$arow = mysqli_fetch_array($aresult);

		$img = $arow['username'].'_'.$irow['code'].".png";
		$filename = "../images/art/".$img;
		file_put_contents($filename, $row['data']);

		mysqli_query($connection, "UPDATE `Captchalogue` SET art = '".mysqli_real_escape_string($connection, urlencode($img))."', credit = '".mysqli_real_escape_string($connection, $arow['username'])."' WHERE code = '$irow[code]' LIMIT 1;");
		
		//achievement+stat code
		$playerchars = explode("|", $arow['characters']);
		array_pop($playerchars); //remove phantom character from the end
		foreach($playerchars as $character){
	  		incrementStat(getChar($character), 'artapproved'); //increments stat for every character
		  	sendAchievement(getChar($character), 'artsub'); //sends achievement to each character
		}


		echo 'Submission successfully approved!<br/>';
	} elseif(isset($_POST['re'])) {
		echo 'Removing submission...<br/>';
		mysqli_query($connection, "DELETE FROM `Art_Submissions` WHERE ID = '$_POST[id]' LIMIT 1;");
	}

	$result = mysqli_query($connection, "SELECT * FROM `Art_Submissions`");
	echo '<hr/>';
	while($row = mysqli_fetch_array($result)) {
		$iresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE code = '".$row['code']."' LIMIT 1;");
		$irow = mysqli_fetch_array($iresult);

		$sresult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE ID = '$row[submitter]' LIMIT 1;");
		$srow = mysqli_fetch_array($sresult);

		$aresult = mysqli_query($connection, "SELECT * FROM `Users` WHERE ID = '$srow[owner]' LIMIT 1;");
		$arow = mysqli_fetch_array($aresult);

		renderItem2($irow, NULL, "", false);
		echo '<br/>';
		echo 'Submitted by '.$arow['username'].'.<br/>';
		echo 'Suggested art:<br/>';
		echo '<img src="/getartsubmission.php?id='.$row['ID'].'" />';
		echo '<form action="" method="POST">';
		echo '<input type="hidden" name="id" value="'.$row['ID'].'" />';
		echo '<input type="submit" name="ap" value="Approve" />';
		echo '<input type="submit" name="re" value="Remove" />';
		echo '</form>';
		echo '<br/><hr/>';
	}
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>
