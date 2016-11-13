<?php

$pagetitle = "Announcer";
require_once($_SERVER['DOCUMENT_ROOT'] . "/header.php");



if ($accrow['modlevel'] < 99) {
	echo "You're not supposed to be here.";
} else {
	if(isset($_POST['clear'])){
		mysqli_query($connection, "UPDATE System SET announcements='';");
		echo "Announcements column cleared. <br>";
	}

	if(isset($_POST['delete'])){
		$announce = mysqli_query($connection, "SELECT * FROM System;");
		$announcements = mysqli_fetch_array($announce);
		$exploded = explode("|", urldecode($announcements['announcements']));
		unset($exploded[$_POST['delete']]);
		$imploded = implode("|", array_values($exploded));
		mysqli_query($connection, "UPDATE System SET announcements='".urlencode($imploded) . "';");

	}

	if(isset($_POST['announcement'])) {
		$dateone = strtotime(DateTime::createFromFormat('d-m H.i', $_POST['start'])->format('Y-m-d H.i'));
		$datetwo = strtotime(DateTime::createFromFormat('d-m H.i', $_POST['end'])->format('Y-m-d H.i'));
		if(strtotime("now")<=$dateone && strtotime("now")<$datetwo){
			echo "Announcement Submitted" . ": <br>" . $_POST['announcement'] . "<br>From: " . $_POST['start'] . "<br>To: " . $_POST['end'] . "<br><br>";
			$escapedannounce=urlencode(mysqli_escape_string($connection, str_replace("@", "", $_POST['announcement'])) .'@' . $dateone .'@' . $datetwo . '|');
			mysqli_query($connection, "UPDATE System SET announcements=concat(announcements, '". $escapedannounce . "');");
		}
		else{
			echo "Invalid date.<br>";
			echo date("F j, Y, g:i a", $dateone) . " " . date("F j, Y, g:i a", strtotime("now")) . " " . date("F j, Y, g:i a", $datetwo) . " <br>";
			if(strtotime("now")<=$dateone)
				echo "Second date was invalid, needs to be in the future.<br>";
			if(strtotime("now")<$datetwo)
				echo "First date was invalid, needs to be in the future.<br>";

		}
	} 
	echo '<form action="" method="POST" id="clear"><input type="hidden" name="clear" value="clear"><input type="submit" value="Clear All Announcements"/></form><br>';
	$announce = mysqli_query($connection, "SELECT * FROM System;");
	$announcements = mysqli_fetch_array($announce);
	$exploded = explode("|", urldecode($announcements['announcements']));
	if ($exploded[0]!='') foreach ($exploded as $index=>$ann){
		$anno = explode("@", $ann);
		if($anno[0]!=''){ echo "Announcement " . $index . ":<br> " . stripcslashes(urldecode($anno[0])) . "<br>From: " . date("F j, Y, g:i a", $anno[1]) . "<br>To: " . date("F j, Y, g:i a", $anno[2]) . "<br>";
		echo '<form action="" method="POST" id="delete"><input type="hidden" name="delete" value="'. $index .'"><input type="submit" value="Delete"/></form><br>';}
	}
	else echo "No announcements.<br>";
	echo '<br/>';
	echo 'New announcement:<br/>';
	echo '<form action="" method="POST" id="announce">';
	echo '<textarea name="announcement" rows="6" cols="50" form="announce"></textarea><br>';
	echo 'From: <input class="datetimer" data-format="DD-MM HH.mm" data-template="DD / MM     HH : mm" name="start" value="' . date("d-m H.i", strtotime("+ 5 minutes")) . '" type="text"><br>';
	echo 'To: <input class="datetimer" data-format="DD-MM HH.mm" data-template="DD / MM     HH : mm" name="end" value="' . date("d-m H.i", strtotime("+ 1 days")) . '" type="text"><br>';
	echo "Default inputs are in local server time.<br>";
	echo '<input type="submit" value="Announce"/>';
	echo '</form>';
	echo '<br/>';
}

?>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
?>
<script src="/js/moment.min.js"></script> 
<script src="/js/combodate.js"></script> 
<script>
	$(function(){
	    $('.datetimer').combodate();  
	});
</script>

