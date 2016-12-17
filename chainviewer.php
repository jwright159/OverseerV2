<?php
$pagetitle = "Chain Viewer";
$headericon = "/images/header/spirograph.png";
require_once("header.php");
require_once("includes/global_functions.php");

echo '<form action="chainviewer.php" method="get">';
echo 'Session to retrieve info about: <input id="session" name="session" type="text" /><input type="submit" value="Examine it!" /> </form></br>';

if(!empty($_GET['session'])){
	$sessionesc = str_replace("'", "''", $_GET['session']);
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE `name` ='" . mysqli_real_escape_string($connection, $sessionesc) . "';");
	$sessiont = mysqli_fetch_array($ownsession);
	echo "Showing multi-player chains in session " . $_GET['session'] . ":</br>";
}
else{
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE `ID` = $charrow[session];");
	$sessiont = mysqli_fetch_array($ownsession);
	echo "Showing multi-player chains in session " . $sessiont['name'] . ":</br>";
}

if(!$sessiont) echo 'No chains found, or session not found';

$characterid = explode("|", $sessiont['members']);

if($sessiont and sizeof($characterid)<=2) echo 'No chains found';

$backup=array();

$charid=$characterid[0];
while(sizeof($characterid)>2){
	$skip=false;
	$closed = 0;
	//we get the very first member of the chain or if the chain is closed we go back to where we were
	$candidate = $charid;
	if(in_array($candidate,$characterid)) $previous = getChar($candidate)['client'];
	else $previous = $candidate;
		while($previous){
		$candidate=$previous;
		$previous=getChar($previous)['client'];
		if($previous==$charid){
			$candidate=$charid;
			break;
		}
	}
	//we go from there to the last server, or we find the same guy again, in which case we break
	$chain = array($candidate);
	$next = getchar($candidate)['server'];
	while($next){
		array_push($chain, $next);
		$next=getChar($next)['server'];
		if($next==$candidate){
			$closed=1;
			break;
		}
	}


	if($chain==$backup){
		echo "<br>Infinite loop detected, check your chain structure and consult session administrator<br>";
		echo "<br>This is likely to happen if developers edited your chain or if your chain name contains special characters<br>";
		$skip = true;
		array_shift($characterid);
		//break;
	}else $backup = $chain;

	if(!$closed) array_push($chain, -1);

	$characterid=array_diff($characterid,$chain);
	if(sizeof($chain)>2 && !$skip){
		$chain = urlencode(serialize($chain));

		$charid=array_values($characterid)[0];
	
		echo '<img src="/sessiongraph.php?chain='. $chain . '&closed=' . $closed . '"/>';
	}else $charid=array_values($characterid)[0];

	
}

	




require_once("footer.php");
?>
