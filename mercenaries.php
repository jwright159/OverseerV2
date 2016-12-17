<?php
$pagetitle = "CONSORT MERCENARIES";
require_once("header.php");

if (empty($_SESSION['username'])) {
	echo "Choose a character to see your hired consorts.<br>";
} elseif ($charrow['inmedium'] == 0) {
	echo "Can't hire consorts until you enter!<br>";
} else {
	if(!empty($_GET['info'])) {
		$consresult = mysqli_query($connection, "SELECT * FROM Consorts WHERE id = '".mysqli_real_escape_string($connection, $_GET['info'])."' LIMIT 1;");
		if(mysqli_num_rows($consresult) == 0) {
			echo 'There is no such consort!<br/><br/>';
		} else {
			$consrow = mysqli_fetch_array($consresult);

			if($consrow['belongsto'] != $charrow['ID']) {
				echo 'That is not your consort!<br/><br/>';
			} else {
				if(!empty($_GET['name'])) {
					$newname = htmlentities($_GET['name']);
					if(strlen($newname) > 30) {
						echo 'That name is too long.<br/><br/>';
					} else {
						mysqli_query($connection, "UPDATE Consorts SET name = '".mysqli_real_escape_string($connection, $newname)."' WHERE id = '".$_GET['info']."' LIMIT 1;");
					}
				} else {
					echo '<b>'.$consrow['name'].'</b><br/>';
					echo '<form action="" method="GET">	Rename this mercenary: <input type="text" name="name" /><input type="hidden" name="info" value="'.$_GET['info'].'" /> <input type="submit" value="Name!" /></form>';
					if(!empty($consrow['equipped'])) {
						$itemresult = mysqli_query($connection, "SELECT name FROM Captchalogue WHERE ID = '".$consrow['equipped']."' LIMIT 1;");
						$itemrow = mysqli_fetch_array($itemresult);
						echo 'Equipped: '.$itemrow['name'].'<br/>';
					}
					echo '<br/>';
				}
			}
		}
	}

	$consortRows = mysqli_query($connection, "SELECT * FROM `Consorts` WHERE `belongsto` = '".$charrow['ID']."';");
//	$consortArray = mysqli_fetch_array($consortRows);

	echo 'This is a list of your hired consorts, working tirelessly to find grist for your noblest of noble causes. You\'re currently 
		employing '.$charrow['consortcount'].' of the industrious little fellows!<br>You can hire some more,
		or <a href="mercenaries.php?collect">collect</a> their spoils of war, if you like.<br>';
	echo 'HIRE CONSORTS:<br>';
	echo "<form action='mercenaries.php' method='post'>Hire: <input type='text' name='hire' /> consorts for 100,000 Boondollars each, plus an additional 10,000 for every consort you already have.<br>";
	echo "<input type='submit' value='Go!' /></form><br><br>";	
	if (!empty($_POST['hire'])) {
		$hireCount = $_POST['hire'];
		if ($hireCount < 0) {
			echo "The consorts you try and fire show you their contracts. Blast! You forgot to add a clause about termination, and now you're stuck with them.<br><br>";
		} else if ($hireCount == 0) {
			echo "You're not sure what you're doing. The consorts look at you, crestfallen, as you decide not to hire any.<br><br>";
		} else {
			$consortsAdded = 0;
			$consortCount = $charRow['consortcount'];
			$boondollarsRequired = 0;
			while ($consortsAdded < $hireCount) {
				$hireLoopCharRow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$charrow['ID']."';"));
				$newConsortCount = $hireLoopCharRow['consortcount'] + $consortsAdded;
				$boondollarsRequired = $boondollarsRequired + 100000 + (10000 * $newConsortCount); 
				$consortsAdded++;
			}
			$boondollars = $charrow['boondollars'];
			if ($boondollarsRequired >= $charrow['boondollars']) {
				echo 'You don\'t have enough money to hire that many consorts! You need '.$boondollarsRequired.' for that many.<br><br>';
			} else {
				$i = 0;
				$newBoonies = $boondollars - $boondollarsRequired;
				while ($i < $hireCount) {
					$randomNumber = rand(0, 1000000);
					$consortName = 'Minion #'.$randomNumber;
					$owner = $_SESSION['character'];
					$currentTime = time();
					mysqli_query($connection, "INSERT INTO `Consorts` (`belongsto`, `name`, `status`, `lastaction`) VALUES ('$owner', '$consortName', 'ALIVE', '$currentTime');");
					$i++;
				}
				echo 'Hired <b>'.$i.'</b> consorts!<br><br>';
				$consortCount = $charrow['consortcount'];
				$newCount = $consortCount + $i;
				//echo 'ConsortCount:'.$consortCount.' NewCount:'.$newCount.' "i":'.$i; // Debug Line
				mysqli_query($connection, "UPDATE `Characters` SET `consortcount` = '$newCount', `boondollars` = '$newBoonies' WHERE `ID` = '".$charrow['ID']."';");
				$charrow['boondollars'] = $newBoonies;
			}
		}
	}
	if(isset($_GET['unequip'])) {
		require_once("includes/additem.php");
		
		while ($row = mysqli_fetch_assoc($consortRows)) {
			$consortID = $row['id'];
			$equippedItem = $row['equipped'];
			if ($row['equipped'] != 0) {
			$intCharrow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$charrow['ID']."';"));
			storeItem($intCharrow, $equippedItem, 1);
			mysqli_query($connection, "UPDATE `Consorts` SET `equipped` = '0', `power` = '20' WHERE `ID` = '$consortID';");
			}            
		}
	}
	if (isset($_GET['collect'])) {
		// Get list from charrow - both grist AND stored
		// Split stored into an array
		// foreach loop - split by : which gives $type, $amount
		$gristString = $charrow['grists'];
		$storedGrist = explode("|", $charrow['consortgrist']);
		array_pop($storedGrist);
		foreach($storedGrist as $grist) {
			$explodedGrist = explode(':', $grist);	
			$typeToAdd = $explodedGrist[0];
			$valueToAdd = $explodedGrist[1];
			$gristString = modifyGrist($gristString, $typeToAdd, $valueToAdd);
		}    
		mysqli_query($connection, "UPDATE `Characters` SET `consortgrist` = '', `grists` = '$gristString' WHERE `ID` = '".$charrow['ID']."';");
		echo 'Collected grist!<br><br>';
	}
	echo 'Your minions:<br><br>';
	$consortRows = mysqli_query($connection, "SELECT * FROM `Consorts` WHERE `belongsto` = '".$charrow['ID']."';");
	while ($row = mysqli_fetch_assoc($consortRows)) {
		$consortID = $row['id'];
		if ($row['status'] == 'ALIVE') {
			$consortStatus = '<span style="color:#00CC00">ALIVE</span>';
		} elseif ($row['status'] == 'INJURED') {
			$consortStatus = '<span style="color:#FF9900">INJURED</span>';
		}
		$consortNickname = $row['name'];
		if (!empty($consortStatus)) {
			if (!empty($row['equipped'])) {
				$itemresult = mysqli_query($connection, "SELECT name FROM Captchalogue WHERE ID = '".$row['equipped']."' LIMIT 1;");
				$itemrow = mysqli_fetch_array($itemresult);
				echo '<a href="?info='.$consortID.'">'.$consortNickname.'</a> ('.$itemrow['name'].') is '.$consortStatus.'.<br>';
			} else {
				echo '<a href="?info='.$consortID.'">'.$consortNickname.'</a> is '.$consortStatus.'.<br>';
			}
		}
	}
	echo '<br><br>To unequip items from all your consorts (especially if they took something you wanted from storage!), <a href="mercenaries.php?unequip">click here</a>.';
	echo '<br><br>For your fallen allies, click <a href="/fallenmercs.php">here</a>.';
	echo '<br><br>Between them, your consorts have stored up the following grist:<br>';
	$gristCharrow = mysqli_fetch_array(mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$charrow['ID']."';"));
	$grists =  explode('|', $gristCharrow['consortgrist']);
	array_pop($grists); // "pops" the last item in the array - in this case, the blank entry after the final |
	foreach ($grists as $grist) {
		echo "<li>$grist</li>"; // echoes as a list
	}
	echo 'To collect it, <a href="mercenaries.php?collect">click here.</a>';
	}

require_once("footer.php");
?>