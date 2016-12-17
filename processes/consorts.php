<?php
require_once("/var/www/overseer2.com/includes/global_functions.php");
require_once("/var/www/overseer2.com/inc/database.php");


session_start();
// Get the consort table and start the loop
$consortsResult = mysqli_query($connection, "SELECT * FROM `Consorts`;");
while ($row = mysqli_fetch_assoc($consortsResult)) {
	echo "</br>";
	$charID = $row['belongsto'];
	$consortName = $row['name'];
	$consortID = $row['id'];
	$consortDisplay = '<a href="/mercenaries.php?info='.$consortID.'">'.$consortName.'</a>';
	$charResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '$charID';");
	$charRow = mysqli_fetch_array($charResult);
	$timeSinceStrife = time() - $row['lastcombat'];
	$timeSinceAction = time() - $row['lastaction'];
	if ($row['status'] == 'INJURED') { // If the consort is injured, see if they heal this turn.
		$initialArray = array (
			'skip' => array('min' => 0, 'max' => 100),
		);
		if ($row['injurycount'] >= 3) {
			mysqli_query($connection, "INSERT INTO `DeadConsorts` (`belongedto`, `name`) VALUES ('$charID', '$consortName');");
			mysqli_query($connection, "DELETE FROM `Consorts` WHERE `id` = '$consortID';");
			$consortCountNew = $charRow['consortcount'] - 1;
			mysqli_query($connection, "UPDATE `Characters` SET `consortcount` = '$consortCountNew' WHERE `ID` = '$charID';");
			logThis("".$consortDisplay." succumbed to their injuries, and has died.", $charID);
		} else {
			$injuryRand = rand(0, 3); // 1 in 4 chance
			if ($injuryRand == 2) {  // with a random number to do it. 
				$consortid = $row['id'];
				mysqli_query($connection, "UPDATE `Consorts` SET `status` = 'ALIVE' WHERE `id` = '$consortid';");
				logThis("".$consortDisplay." has recovered from their injuries, and are raring to get back in the fight!", $charID);
				// Log recovery here
			}
		}
		
	} elseif ($timeSinceAction < 120) { // Don't do anything if its done something in the last two minutes
		$initialArray = array (
			'skip' => array('min' => 0, 'max' => 100), 
		);
	} elseif ($timeSinceStrife >= 1200 && $timeSinceStrife <= 2400 && $timeSinceAction >= 300) { // If more than five minutes since any action, and between 20 and 40 minutes since strife
		$initialArray = array ( 
			'strife'    =>  array('min' =>  0, 'max' =>  25),
			'other'  =>  array('min' => 25, 'max' =>  75),
			'skip' => array('min' => 75, 'max' => 100), 
		);
	} elseif ($timeSinceStrife < 300) { // Less than 5 minutes since strife
		$initialArray = array (
			'other' => array('min' => 0, 'max' => 75),
			'skip' => array('min' => 75, 'max' => 100),
		);
	} elseif ($timeSinceStrife > 300 && $timeSinceStrife <= 1200) { // beween 5 and 20 minutes since strife
		$initialArray = array (
			'strife' => array('min' => 0, 'max' => 10),
			'other' => array('min' => 10, 'max' => 90),
			'skip' => array('min' => 90, 'max' => 100),
		);
	} elseif ($timeSinceStrife > 2400 && $timeSinceStrife <= 3600 ) { // between 40 and 60 minutes for strife
		$initialArray = array (
			'strife' => array('min' => 0, 'max' => 66),
			'other' => array('min' => 66, 'max' => 100),
		);	
	} elseif ($timeSinceStrife > 3600 ) { // More than an hour since strife. Garunteed fight if not injured.
		$initialArray = array (
			'strife' => array('min' => 0, 'max' => 100),
		);	
	}		
			
	$rnd = rand(1,100);
	foreach($initialArray as $k => $v) {
		if ($rnd > $v['min'] && $rnd <= $v['max']) {
			$action = $k; 
		}
	} // End of deciding initial conditions
	if ($action == 'other') { // 'Other' actions
		//Now need to make $otherArray
		if ((empty($row['equipped']) || $row['equipped'] == '0') && !empty($charRow['storeditems'])) {
			$otherArray = array (
				'equip' => array('min' => 0, 'max' => 90),
				'goof' => array('min' => 90, 'max' => 92),
				'nothing' => array('min' => 92, 'max' => 100),
			);
		} else {
			$otherArray = array (
				'goof' => array('min' => 0, 'max' => 2),
				'nothing' => array('min' => 2, 'max' => 100),
				);
		}
		$rnd2 = rand(1,100);
		foreach($otherArray as $k2 => $v2) {
			if ($rnd2 > $v2['min'] && $rnd2 <= $v2['max']) {
				$action2 = $k2; 
			}
		}
		if ($action2 == 'nothing') {
			echo "Other - nothing.";
			//Do nothing. 
		} elseif ($action2 == 'goof') {
			// RNG random shit here - alchemy, funny notes, etc etc.
			echo "Other - Goofing off...";
			$updateTime = time();
			mysqli_query($connection, "UPDATE `Consorts` SET `lastaction` = '$updateTime' WHERE `id` = '$consortID';");
			$goofArray = array("Some of your consorts wonder why they're fighting. They decide to form a secret wizard's society.",
			"".$consortDisplay." has died of dysentry. <a href='http://www.mspaintadventures.com/?s=6&p=007980'>Luckily, something in a far off place revived them before they turned into a skeleton.</a>",
			"A consort lies down and thinks about the nature of existence. A nearby underling politely decides not to attack them - they've been there before.",
			"Your consorts somehow end up in their own session. Yo dawg...",
			"".$consortDisplay." knows kung-fu.",
			"".$consortDisplay." wishes they had a better weapon... perhaps you should leave some in storage for them?",
			"".$consortDisplay." examines his injuries - he decides they're not too bad and presses on. We salute you and your loyalty, ".$consortDisplay.". ;_;7",
			"".$consortDisplay." appears to have gotten their foot jammed into their mouth.",
			"The consorts have gathered dice and paper and are about to begin dark rituals.",
			"The consorts are embroiled in a heated debate over who is the best superhero. Best not get involved.",
			"A consort shows you their favourite finger. You point out that that thumb isn't technically a finger, but they don't seem to care.",
			"".$consortDisplay." says not to mind them, they have a book.",
			"".$consortDisplay." is playing Galaga. You noticed. They thought you hadn't, but you did.",
			"".$consortDisplay." is worrying about its allergies.",
			"".$consortDisplay." closes its web browser when you turn to look.",
			"".$consortDisplay." is trying to harness the power of electricity by painting itself yellow.",
			"".$consortDisplay." has fallen and can't get up!",
			"".$consortDisplay." has a jar of dirt. Can you guess what's inside it?",
			"".$consortDisplay." wants to wrestle a bear.",
			"".$consortDisplay." eyes up an Acheron. Yeah, he can take th- OH OK NO HE CAN'T LET'S RUN NOW",
			"".$consortDisplay." wants to be the very best.",
			"".$consortDisplay." did 100 situps, 100 pushups, 100 squats, and 10km running today.",
			"".$consortDisplay." has no idea what this is.",
			"".$consortDisplay." plays a smooth jazz solo.",
			"".$consortDisplay." is buying grist to make it look like they did something. Goddammit, ".$consortDisplay.".",
			"".$consortDisplay." found spam in their sandwich. They don't like Spam... They throw it on the ground in disgust. An imp tentatively tries to take it and is slaughtered. Fuck Spam.",
			"".$consortDisplay." rolled a 4. Why are they even rolling dice?",
			"".$consortDisplay." understood that reference.",
			"".$consortDisplay." rides a bicycle. For JUSTICE.",
			"".$consortDisplay." is roleplaying a carapace online. You see something on his screen about the Black Queen..? You decide you don't want to read anymore.",
			"".$consortDisplay." took a selfie with an ogre.",
			"".$consortDisplay." bought <a href='http://thebest404pageever.com/swf/epic_box.swf'>a box with legs.</a>",
			"".$consortDisplay." is a Wizzard. You know this because they've got a hat with 'Wizzard' on it.",
			"".$consortDisplay." lost the stylus again. Dammit.",
			"A group of consorts are being scolded for running with scissors.",
			"".$consortDisplay." wants you to be their Sensei. You back away slowly, offering some sage advice you just made up.",
			"".$consortDisplay." popped, and literally cannot stop.",
			"".$consortDisplay.". Just an ordinary consort who does this for fun.",
			"A consort offers you an egg in these trying times.",
			"".$consortDisplay." is idling. Lazy jerk.",
			"".$consortDisplay." hung a lampshade on the striking imps.",
			
			);
			// [consort] has a knife.
			// [consort] has a REAL knife.
			// Add when squads are done
			$randomGoof = array_rand($goofArray);
			logThis($goofArray[$randomGoof], $charID);
		} elseif ($action2 == 'equip') {
			// Code to give consort a weapon here. 
			echo "Other - equip attempt - ";
			$storageArray = explode('|', $charRow['storeditems']);
			$itemToCheck = array_rand($storageArray);
			//var_dump($storageArray);
			$itemCodeArray = explode(":", $storageArray[$itemToCheck]);
			$itemID = $itemCodeArray[0];
			$itemQuery = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = '$itemID';");
			$itemRow = mysqli_fetch_array($itemQuery);
			if (substr_count($itemRow['abstratus'], 'notaweapon') == 0 && $itemRow['abstratus'] != "") {
				$newPower = $itemRow['power'] + 20; 
				$updateTime = time();
				echo $itemRow['abstratus'];
				echo substr_count($itemRow['abstratus'], 'notaweapon');
				//var_dump($storageArray); 
				mysqli_query($connection, "UPDATE `Consorts` SET `equipped` = '$itemID', `power` = '$newPower', `lastaction` = '$updateTime' WHERE `id` = '$consortID';");
				if ($itemCodeArray[1] == 1) {
					unset($storageArray[$itemToCheck]);
				} else {
					$itemCodeArray[1] = $itemCodeArray[1] - 1;
					$itemToInsert = implode(':', $itemCodeArray);
					unset($storageArray[$itemToCheck]);
					//var_dump($storageArray);
					array_pop($storageArray);
					array_push($storageArray, $itemToInsert);
					array_push($storageArray, '');
				}
				//var_dump($storageArray);
				$recombinedStorageArray = implode('|', $storageArray);
				mysqli_query($connection, "UPDATE `Characters` SET `storeditems` = '$recombinedStorageArray' WHERE `ID` = '$charID';");
				logThis("".$consortDisplay." equipped ".$itemRow['name'].".", $charID);
				echo 'Equipped a weapon<br>';
			} else {
				// Do nothing.
				echo 'Tried to equip something but wasn\'t a weapon<br>';
			}
		} // End equip
	} elseif ($action == 'strife') {
		echo "FIGHT!!";
		include_once("/var/www/overseer2.com/processes/consortstrife.php");
		$updateTime = time();
		mysqli_query($connection, "UPDATE `Consorts` SET `lastaction` = '$updateTime' WHERE `id` = '$consortID';");
		mysqli_query($connection, "UPDATE `Consorts` SET `lastcombat` = '$updateTime' WHERE `id` = '$consortID';");
		rowRowFightThePower($consortID, $charID);
	} elseif ($action == 'skip') {
		echo "Do nothing.";
		//Do nothing.
	}
}
