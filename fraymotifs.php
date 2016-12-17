<?php
$pagetitle = "Fraymotifs";
$headericon = "/images/header/rancorous.png";
require_once("header.php");
//Note on formatting: Fraymotifs are stored as I, II, and III for single motifs, and as the supporting Aspect for combo motifs
if (empty($_SESSION['username'])) {
	echo "Log in to access fraymotifs.<br />";
} else {
	if (empty($_SESSION['motiflist']['Aspect'])) $_SESSION['motiflist']['Aspect'] = ""; //Prevent complaints about empty values
	if ($_SESSION['motiflist']['Aspect'] != $charrow['aspect']) { //List of fraymotifs does not match current Aspect (or is empty!)
		$motifresult = mysqli_query($connection, "SELECT * FROM `Fraymotifs` WHERE `Fraymotifs`.`Aspect` = '$charrow[aspect]' LIMIT 1;");
		$_SESSION['motiflist'] = mysqli_fetch_array($motifresult); //Fetch the fraymotif names for this aspect and put them in the session variable
	}
	if ($charrow['dreamingstatus'] == "Awake") { //Grab the character's waking row
		$sid = $charrow['wakeself'];
	} else { //Grab their dream row
		$sid = $charrow['dreamself'];
	}
	$striferesult = mysqli_query($connection, "SELECT * FROM `Strifers` WHERE `Strifers`.`ID` = $sid LIMIT 1;");
	$striferow = mysqli_fetch_array($striferesult);
	$motiflist = array(0 => "I", "II", "III", "Hope", "Heart", "Life", "Doom", "Mind", "Space", "Time", "Void", "Blood", "Breath", "Light", "Rage");
	if (!empty($_POST['motifbuy'])) { //Fraymotif being purchased. DATA SECTION: Stores costs for fraymotifs
		$_POST['motifbuy'] = mysqli_real_escape_string($connection, $_POST['motifbuy']);
		$motifname = "Unnamed Fraymotif";
		if ($_SESSION['motiflist'][$_POST['motifbuy']] != "") $motifname = $_SESSION['motiflist'][$_POST['motifbuy']];
		switch ($_POST['motifbuy']) {
			case "I":
				$cost = 10000000;
				break;
			case "II":
				$cost = 100000000;
				break;
			case "III":
				$cost = 1000000000;
				break;
			default: //Combo fraymotifs
				$cost = 10000000000;
				break;
		}
		if ($cost <= $charrow['boondollars']) {
			if($charrow['fraymotifs'] == "") $charrow['fraymotifs'] = "|"; //Add a leading | to allow for strpos lookup of fraymotifs later on.
			$charrow['fraymotifs'] .= $_POST['motifbuy'] . "|";
			$charrow['boondollars'] -= $cost;
			echo "You have successfully purchased $charrow[aspect]/" . $_POST['motifbuy'] . ": $motifname<br>";
			mysqli_query($connection, "UPDATE `Characters` SET `fraymotifs` = '$charrow[fraymotifs]', `boondollars` = $charrow[boondollars] WHERE `Characters`.`ID` = $charrow[ID] LIMIT 1;");
		} else {
			echo "You cannot afford that fraymotif! It is unknown at this time whether that was because a teammate convinced you to hand over some of your money.<br>";
		}
	}
	if (!empty($_POST['motifuse'])) {
		$_POST['motifuse'] = mysqli_real_escape_string($connection, $_POST['motifuse']); //Paranoia. It shouldn't be possible to use DERP; DROP TABLE `Characters`;
		$motifname = "Unnamed Fraymotif";
		if ($_SESSION['motiflist'][$_POST['motifuse']] != "") $motifname = mysqli_real_escape_string($connection, $_SESSION['motiflist'][$_POST['motifuse']]);
		if(strpos($charrow['fraymotifs'], ("|" . $_POST['motifuse'] . "|")) === false) { //Fraymotif not owned!
			echo "ERROR: You do not own the fraymotif $charrow[aspect]/" . $_POST['motifuse'] . ": $motifname<br>";
		} else { //Fraymotif owned: Load strife row.
			if ($striferow['strifeID'] == 0) { //Not strifing!
				echo "You cannot activate kickass strife beats while not strifing!<br>";
			} elseif ($striferow['currentmotif'] != "") { //Another fraymotif is currently active
				echo "You already have a fraymotif active this round!<br>";
			} elseif (strpos($striferow['motifsused'], ("|" . $_POST['motifuse'] . ":")) !== false) { //Fraymotif still on cooldown.
				echo "You have already used that fraymotif recently. Gotta keep it fresh!<br>";
				//IMPORTANT NOTE - Fraymotifs are stored as used with a "|" before and a : after to separate them from the cooldown.
			} elseif ($striferow['teammotif'] == 1) { //A teammate is already using a fraymotif
				echo "A teammate is already using a fraymotif this round. Turning yours on would ruin the effect of both, so you'd better not.<br>";
			} else { //No failures: set the player's fraymotif, set teammates as having had a teammate use a fraymotif this round
				//DATA SECTION: Cooldown on fraymotifs i.e. number of encounters before they can be used again.
				$cooldown = 10;
				if ($_POST['motifuse'] == "I") $cooldown = 2;
				if ($_POST['motifuse'] == "II") $cooldown = 5;
				//Optimization spot: Figure out how to roll these two queries together into a single database transaction.
				$striferow['motifsused'] .= "|" . $_POST['motifuse'] . ":" . $cooldown;
				mysqli_query($connection, "UPDATE `Strifers` SET `teammotif` = 1, `motifsused` = '" . $striferow['motifsused'] . "' WHERE `strifeID` = $striferow[strifeID] AND `side` = $striferow[side];");
				mysqli_query($connection, "UPDATE `Strifers` SET `currentmotif` = '" . $charrow['aspect'] . "/" . $_POST['motifuse'] . "', `currentmotifname` = '$motifname' WHERE `Strifers`.`ID` = $sid LIMIT 1;");
				$motifname=$_SESSION['motiflist'][$_POST['motifuse']];
				echo "You have successfully activated $charrow[aspect]/" . $_POST['motifuse'] . ": $motifname<br>";
				//Format will be as printed: The currentmotif field would be set to "Breath/I" for the Breath I fraymotif, for instance.
			}
		}
	}
	echo "Fraymotifs owned:</br>";
	$motifs = explode("|", $charrow['fraymotifs']);
	$i = 1; //NOTE - Since $charrow['fraymotifs'] begins with an |, the first entry of the explode is empty. Hence, i is set to 1 instead of 0
	$motifsused = explode("|", $striferow['motifsused']);
	$cooldowns = array(0,3,5,10);
	foreach($motifsused as $motif){
		$motifarray=explode(":",$motif); //we create an array with the cooldowns of all three fraymotifs
		switch($motifarray[0]){
			case "I":
				$cooldowns[1] = $motifarray[1];
				break;
			case "II":
				$cooldowns[2] = $motifarray[1];
				break;
			case "III":
				$cooldowns[3] = $motifarray[1];
				break;
		}
	}
	while (!empty($motifs[$i])) { //Owned fraymotif. Print a designation and provide an option to use it.
		$motifowned[$motifs[$i]] = true;
		if($motifowned[$motifs[1]] and $motifowned[$motifs[2]] and $motifowned[$motifs[3]]) setAchievement($charrow, 'fray3');
		echo "<form action='fraymotifs.php' method='post'>" . $charrow['aspect'] . "/" . $motifs[$i];
		
		if (strpos($striferow['motifsused'], ("|" . $motifs[$i] . ":")) !== false) { //Fraymotif on cooldown
			switch($motifs[$i]){
				case "I":
				$cooldown = $cooldowns[1]; //dirty hack, but oh well
				break;
			case "II":
				$cooldown = $cooldowns[2];
				break;
			case "III":
				$cooldown = $cooldowns[3];
				break;	
			}
			echo "[COOLDOWN: $cooldown encounters]</form></br>";
		} else {
			echo "<input type='hidden' name='motifuse' value='" . $motifs[$i] . "'> <input type='submit' value='[S] ==&gt;'></form></br>";
		}
		$i++;
	}

	echo "</br>Boondollars:$charrow[boondollars]<br>Fraymotifs not owned:</br>";
	$i = 0;
	while (!empty($motiflist[$i])) {
		if(empty($motifowned[$motiflist[$i]])) { //Motif is not owned. Provide the option to buy. DATA: Prices are printed here.
			switch ($motiflist[$i]) {
				case "I":
					$cost = "10,000,000";
					break;
				case "II":
					$cost = "100,000,000";
					break;
				case "III":
					$cost = "1,000,000,000";
					break;
				default: //Combo fraymotifs
					$cost = "10,000,000,000";
					break;
			}
			echo "<form action='fraymotifs.php' method='post'>" . $charrow['aspect'] . "/" . $motiflist[$i] . ": $cost Boondollars ";
			echo "<input type='hidden' name='motifbuy' value='" . $motiflist[$i] . "'><input type='submit' value='Purchase'></form></br>";
		}
		$i++;
	}
}
require_once("footer.php");
?>
