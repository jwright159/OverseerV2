<?php
$pagetitle = "SESSION ADMIN";
require_once("header.php");

// Check theyre session admin first
$sessionQuery = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `ID` = '".$charrow['session']."';");
$sessionRow = mysqli_fetch_array($sessionQuery);
if ($_SESSION['username'] != $sessionRow['creator']) {
	echo "ERROR: You're not the admin.<br><br>";
} elseif (!empty($_POST['exilechar'])) {
		$exileChar = mysqli_escape_string($connection, $_POST['exilechar']);
		$exiledQuery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '$exileChar'");
		$exiledRow = mysqli_fetch_array($exiledQuery);
		$exiledClient = $exiledRow['client'];
		$exiledServer = $exiledRow['server'];
		$exiledSession = $exiledRow['session'];
		if ($charrow['session'] == $exiledRow['session']) {
			if ($exiledClient != 0) {
				mysqli_query($connection, "UPDATE `Characters` SET `server` = '0' WHERE `ID` = '$exiledClient';");
			}
			if ($exiledServer != 0) {
				mysqli_query($connection, "UPDATE `Characters` SET `client` = '0' WHERE `ID` = '$exiledServer';");
			}
			mysqli_query($connection, "UPDATE `Characters` SET `session` = '-1', `client` = '0', `server` = '0' WHERE `ID` = '$exileChar';");
			// Resets server
			mysqli_query($connection, "UPDATE `Characters` SET `client` = '0' WHERE `client` = '$exileChar';");
			// Resets Client
			mysqli_query($connection, "UPDATE `Characters` SET `server` = '0' WHERE `server` = '$exileChar';");

			//Update session members array
			$oldArray = $sessionRow['members'];
			$newArray = str_replace($exileChar."|", "", $oldArray);
			mysqli_query($connection, "UPDATE `Sessions` SET `members` = '$newArray' WHERE `ID` = '$exiledSession';");
			$doomQuery = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `ID` = '-1';");
			$doomRow = mysqli_fetch_array($doomQuery);
			// Append to Doomheim member list here
			$doomMembers = $doomRow['members'].$exileChar."|";
			mysqli_query($connection, "UPDATE `Sessions` SET `members` = '$doomMembers' WHERE `ID` = '-1';");
			echo "Exiled ".$exiledRow['name']."!<br><br>";
			notifyCharacter($exiledRow['ID'], "You have been exiled! Welcome to DoomHeim, the exile session!");
		} else {
			echo "This character isn't in your session.<br><br>";
		}
}elseif (isset($_POST['adminchar'])) {
	$adminChar = mysqli_real_escape_string($connection, $_POST['adminchar']);
	$adminQuery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$adminChar."';");
	$adminRow = mysqli_fetch_array($adminQuery);

	$ownerQuery = mysqli_query($connection, "SELECT * FROM `Users` WHERE `ID` = '".$adminRow['owner']."';");
	$ownerRow = mysqli_fetch_array($ownerQuery);
	$newAdmin = $ownerRow['username'];

	mysqli_query($connection, "UPDATE `Sessions` SET `creator` = '$newAdmin' WHERE `ID` = '".$charrow['session']."';");

	if($adminQuery) notifyCharacter($_POST['adminchar'], "You have become the session's admin!");

	echo "Admin successfully changed</br> </br>";

}elseif (isset($_POST['deleteS'])){
	$session = $charrow['session'];
	$session_query = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$session'");

	while($exileplayer = mysqli_fetch_assoc($session_query)){
		$exiledRow = $exileplayer;
		$exileChar = $exiledRow['ID'];
		$exiledClient = $exiledRow['client'];
		$exiledServer = $exiledRow['server'];
		$exiledSession = $exiledRow['session'];
		if ($charrow['session'] == $exiledRow['session']) {
			if ($exiledClient != 0) {
				mysqli_query($connection, "UPDATE `Characters` SET `server` = '0' WHERE `ID` = '$exiledClient';");
			}
			if ($exiledServer != 0) {
				mysqli_query($connection, "UPDATE `Characters` SET `client` = '0' WHERE `ID` = '$exiledServer';");
			}
			mysqli_query($connection, "UPDATE `Characters` SET `session` = '-1', `client` = '0', `server` = '0' WHERE `ID` = '$exileChar';");
			// Resets server
			mysqli_query($connection, "UPDATE `Characters` SET `client` = '0' WHERE `client` = '$exileChar';");
			// Resets Client
			mysqli_query($connection, "UPDATE `Characters` SET `server` = '0' WHERE `server` = '$exileChar';");

			//Update session members array
			$oldArray = $sessionRow['members'];
			$newArray = str_replace($exileChar."|", "", $oldArray);
			mysqli_query($connection, "UPDATE `Sessions` SET `members` = '$newArray' WHERE `ID` = '$exiledSession';");
			$doomQuery = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `ID` = '-1';");
			$doomRow = mysqli_fetch_array($doomQuery);
			// Append to Doomheim member list here
			$doomMembers = $doomRow['members'].$exileChar."|";
			mysqli_query($connection, "UPDATE `Sessions` SET `members` = '$doomMembers' WHERE `ID` = '-1';");
			echo "Exiled ".$exiledRow['name']."!<br><br>";
			echo "Welcome to the exile session</br>";
			notifyCharacter($exileChar, "You have been sent to DoomHeim as a result of your session being destroyed!");
		}
	}

	mysqli_query($connection, "DELETE FROM Sessions WHERE `ID` = '$session'");
}elseif (isset($_POST['delete1'])){
	echo "<span style='color:red;'>" . "Are you SURE you want to delete your entire session and doom every single member? This can't be undone.". "</span><br><br>";
	echo '<form id="deleteS" action="sessionadmin.php" method="post">
		<input type="submit" value="Yes, I want to permanently delete this session">
		<input type="hidden" name="deleteS" value="true">
	</form><br>';
}elseif (isset($_POST['changepw'])){
	if(isset($_POST['changepw2'])){
		if($_POST['changepw2'] == $_POST['changepw']){
			$password = password_hash($_POST['changepw'], PASSWORD_BCRYPT);
			mysqli_query($connection, "UPDATE Sessions SET password='" . $password . "' WHERE ID=" . $charrow['session'] . " LIMIT 1;");
			echo "Password changed successfully!<br>";
			}else echo "The two passwords are different!<br>";
	}else echo "You need to fill the password confirmation box!<br>";
}elseif (!isset($_POST['chainclient']) || empty($_POST['chainclient'])) {
		echo "You need to set people to rearrange their chain.<br><br>";
}elseif (!isset($_POST['chainserver']) || empty($_POST['chainserver'])) {
	echo "You need to set people to rearrange their chain.<br><br>";
} else {
	$chainClient = mysqli_escape_string($connection, $_POST['chainclient']);
	$chainServer = mysqli_escape_string($connection, $_POST['chainserver']);
	// They're sent as names
	$clientQuery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$chainClient."';");
	$clientRow = mysqli_fetch_array($clientQuery);
	$serverQuery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '".$chainServer."';");
	$serverRow = mysqli_fetch_array($serverQuery);
	if ($clientRow['session'] != $charrow['session']) {
		echo "The client isn't in your session.<br><br>";
	} elseif ($serverRow['session'] != $charrow['session']) {
		echo "The server isn't in your session.<br><br>";
	} else {
		// Start the stuff
		$serverID = $serverRow['ID'];
		$clientID = $clientRow['ID'];
		// Do we need to collison check? Yes - check nobody else has that client
		mysqli_query($connection, "UPDATE `Characters` SET `client` = '0' WHERE `client` = '$clientID';");
		mysqli_query($connection, "UPDATE `Characters` SET `server` = '0' WHERE `server` = '$serverID';");
		mysqli_query($connection, "UPDATE `Characters` SET `client` = '$clientID' WHERE `ID` = '$serverID';");
		mysqli_query($connection, "UPDATE `Characters` SET `server` = '$serverID' WHERE `ID` = '$clientID';");
		echo 'Successfully set '.$serverRow['name'].' to '.$clientRow['name'].'\'s server.<br><br>';
		notifyCharacter($clientRow['ID'], $serverRow['name'] . " has become your server player!");
	}
}

////////////////////////////
// End of "do shit" block //
////////////////////////////
$charSession = $charrow['session'];
$namesResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$charSession';");

// CHANGEADMIN //

echo 'Select a character to be the new session admin: <form id="admin" action="sessionadmin.php" method="post">
	Username: <select name="adminchar">
  <option value="">Select...</option>';
  while ($namesRow = mysqli_fetch_assoc($namesResult)) {
	echo '<option value="'.$namesRow['ID'].'">'.$namesRow['name'].'</option>';
  }
echo '</select> <input type="submit" value="ADMIN"></form><br><br>';

// EXILE //
echo 'Exile a character from the session: <form id="exile" action="sessionadmin.php" method="post">
	Username: <select name="exilechar">
  <option value="">Select...</option>';
  $namesResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$charSession';");
  while ($namesRow = mysqli_fetch_assoc($namesResult)) {
	echo '<option value="'.$namesRow['ID'].'">'.$namesRow['name'].'</option>';
  }
echo '</select> <input type="submit" value="EXILE"></form><br><br>';

// Get exiled characters ID

// Set them to Doomheim

// Append char ID to Doomheim list

// CHAIN MANAGEMENT //
echo 'Overview of session chains<br><br>';
$namesResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$charSession';");
while ($namesRow = mysqli_fetch_assoc($namesResult)) {
	$clientNameID = $namesRow['client'];
	if ($clientNameID != 0) {
		$clientNameRow = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '$clientNameID';");
		$clientNameResult = mysqli_fetch_array($clientNameRow);
		$clientName = $clientNameResult['name'];
		echo $namesRow['name'].' is '.$clientName.'\'s server.<br>';
	} else {
		echo $namesRow['name'].' doesn\'t have a client set.<br>';
	}
}
$namesResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$charSession';");
echo '<br> Set <form id="chain" action="sessionadmin.php" method="post">
	<select name="chainserver">
	<option value="">Select Server</option>'; // Start selecting server.
while ($namesRow = mysqli_fetch_assoc($namesResult)) {
	echo '<option value="'.$namesRow['ID'].'">'.$namesRow['name'].'</option>';
}
$namesResult = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `session` = '$charSession';");
echo '</select>\'s client to <select name="chainclient">
	<option value="">Select Client</option>'; // Client
while ($namesRow = mysqli_fetch_assoc($namesResult)) {
	echo '<option value="'.$namesRow['ID'].'">'.$namesRow['name'].'</option>';
}
	echo ' <input type="submit" value="REASSIGN"><br><br>';

	echo '</form>';

	echo 'Change Session Password <br>';

	echo "<form action='sessionadmin.php' method='post'>New Password: <input type='password' name='changepw' />";
	echo "  Verify Password: <input type='password' name='changepw2' />";
	echo "<input type='submit' value='CHANGE' /></form><br />";

	echo 'Delete Session <br><br>';

	echo '<form id="delete1" action="sessionadmin.php" method="post">
		When deleting this session all players in the session will be exiled.
		<input type="submit" value="DELETE SESSION">
		<input type="hidden" name="delete1" value="true">
	</form>';

require_once("footer.php");
