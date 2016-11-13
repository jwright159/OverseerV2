<?php
$pagetitle = "GristWire";
$headericon = "/images/header/gristy.png";
require($_SERVER['DOCUMENT_ROOT'] . '/inc/header.php');

// list all grists
$gresult = mysqli_query($connection, "SELECT * FROM `Grists`;");
$gristlist = array();
while($grow = mysqli_fetch_array($gresult, MYSQLI_ASSOC)) {
	$gristlist[$grow['name']] = $grow;
}

if (empty($_SESSION['character'])) {
	echo "You need to choose a character in order to use GristWire!<br />";
} else {
	$reachgrist = False;

	echo "GristWire client v2.0.0.Beta</br>";
	
	$compugood = true;
	$computability = calculateComputability($charrow);
	/*if (strpos($charrow['storeditems'], "GRISTWIRE") === false) { //player has the gristtorrent CD or an equivalent in their storage
		echo "You'd better ask your server player to deploy the Gristtorrent CD</br>";
		$compugood = false;
	}*/
	/*if ($charrow['enemydata'] != "" || $charrow['aiding'] != "") {
		if ($computability < 3) {
			if ($compugood == true) echo "You don't have a hands-free computer equipped, so you can't wire grist during strife.</br>";
			$compugood = false;
		}
	}*/
	/*if ($charrow['indungeon'] != 0 && $computability < 2) {
		if ($compugood == true) echo "You don't have a portable computer equipped, so you can't wire grist while away from home.</br>";
		$compugood = false;
	}*/
	if ($computability == 0) {
		if ($compugood == true) echo "You need a computer in storage or equipped to wire grist to other players.</br>";
		$compugood = false;
	}
 
	if ($compugood == true) {
		//--Begin wiring code here--
		if (isset($_POST['amount']) && intval($_POST['amount']) > 0) { //Player is attempting to wire a positive amount of grist.
			//$sessionresult = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `Sessions`.`name` = '" . $_POST['session'] . "' LIMIT 1;");
			//$sessionrow = mysqli_fetch_array($sessionresult);
			//$sessionid = $sessionrow['ID'];
			if (1==1 OR empty($_POST['session'])) $sessionid = $charrow['session']; //leaving that just in case we want to readd intersession stuff
			if ($_POST['target'] == $charrow['name']) { //Player is trying to mail themselves grist!
				echo "You can't send grist to yourself!</br>";
			} elseif (empty($_POST['target'])) {
				echo "You did not specify a recipient player.<br />";
			} elseif (empty($_POST['grist_type'])) {
				echo "You cannot wire a blank grist type!<br />";
			} else {
				$wireresult = mysqli_query($connection, "SELECT * FROM Characters WHERE `Characters`.`name` = '" . mysqli_real_escape_string($connection, $_POST['target']) . "'");
				$targetfound = False;
				$poor = False;
				$type = $_POST['grist_type'];
				if (intval($_POST['amount']) <= howMuchGrist($charrow['grists'], $type)) {
					while ($wirerow = mysqli_fetch_array($wireresult)) {
						if ($wirerow['name'] == $_POST['target'] && $wirerow['session'] == $sessionid) {
							$targetfound = True;
							$wirename = $wirerow['name'];
							$modifier = intval($_POST['amount']);
							$sendergrist = modifyGrist($charrow['grists'], $type, ($modifier * -1));
							$receivergrist = modifyGrist($wirerow['grists'], $type, $modifier);
							$string = $charrow['name'] . " has wired you " . $modifier . " " . $type . "!";
							notifyCharacter($wirerow['ID'], $string);
							mysqli_query($connection, "UPDATE `Characters` SET `grists` = '" . $receivergrist . "' WHERE `Characters`.`ID` = " . $wirerow['ID'] . " LIMIT 1 ;");
							mysqli_query($connection, "UPDATE `Characters` SET `grists` = '" . $sendergrist . "' WHERE `Characters`.`ID` = " . $charrow['ID'] . " LIMIT 1 ;");
							$remainder = howMuchGrist($charrow['grists'], $type) - $modifier;
							$charrow['grists'] = $sendergrist;
						}
					}
				} else {
					echo "Transaction failed: You only have $charrow[$type] $type";
					$quantity = $charrow[$type];
					$poor = True;
				}
				if ($targetfound == True) {
					echo "Transaction successful. You now have $remainder $type after wiring $modifier $type to $wirename!";

				} else if ($poor == False) {
					echo "Transaction failed: Target $_POST[target] of session $_POST[session] does not exist.";
				}
				echo "</br>";
			}
		}
		if (empty($type)) $type = "";

		//--End wiring code here.--
		echo '<form action="gristwire.php" method="post" id="wire">Target name: <input id="target" name="target" type="text" /><br />'; 
		//Target session (defaults to your session if left empty): <input id="session" name="session" type="text" /><br />
		echo 'Type of grist: <select name="grist_type"> ';
		$gristarray = explode('|', $charrow['grists']);
		foreach ($gristarray as $grist) {
			$griststr = explode(':', $grist);
			if($griststr[0])
			echo '<option value="' . $griststr[0] . '">' . $griststr[0] . '</option>'; //Produce an option in the dropdown menu for this grist.
		}
		$reachgrist = False; //Paranoia: Reset this just in case
		echo '</select></br>Amount to wire: <input id="amount" name="amount" type="text" /><br /><input type="submit" value="Wire it!" /> </form><br />';
	}
	
	$grists =  explode('|', $charrow['grists'], -1);
	echo "You have the following grist:<br />";
	echo '<div class="grister">';
	foreach ($grists as $grist) {
		$gristexplode = explode(':', $grist);
		$gristtype = $gristexplode[0];
		if($gristtype!='9782' && $gristtype!='7'){
			$gristq = intval($gristexplode[1]);
			if($gristtype!='Dev_Grist') $gristrow = $gristlist[$gristtype];
			$extension = ".png";
			if ($gristrow['gif'] == 1) $extension = ".gif";
			echo '<div class="grist">';
			$imagestr = '<img src="images/grist/' . $gristtype . $extension . '" height="50" width="50"></img>';
			echo '<div class="gristimg">'.$imagestr.'</div>';
			echo $gristtype.' - '.$gristq;
			echo '</div>';
		}
	}
	echo '</div>';
}
require($_SERVER['DOCUMENT_ROOT'] . '/inc/footer.php');
?>