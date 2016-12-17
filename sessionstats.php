<?php
$pagetitle = "Session Stats";
$headericon = "/images/header/spirograph.png";
require_once("header.php");
require_once("includes/global_functions.php");
?>
<script src="/js/sorttable.js"></script>
<?php

echo '<form action="sessionstats.php" method="get">';
echo 'Session to retrieve stats from: <input id="session" name="session" type="text" /><input type="submit" value="Examine it!" /> </form></br>';

if(!empty($_GET['session'])){
	$sessionesc = mysqli_real_escape_string($connection, str_replace("'", "''", $_GET['session']));
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE `name` ='" . $sessionesc . "';");
	$sessiont = mysqli_fetch_array($ownsession);
	echo "Showing stats in session " . $_GET['session'] . ":</br>";
}
else{
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE `ID` = $charrow[session];");
	$sessiont = mysqli_fetch_array($ownsession);
	echo "Showing stats in session " . $sessiont['name'] . ":</br>";
}

$characterids = explode("|", $sessiont['members']);

$stats = array('enemiesbeaten', 'alchemy','artapproved', 'itemapproved', 'maxdamage');

echo '<table class="sortable">
		<thead><tr>
		<th>ID</th>
		<th>Character</th>
		<th>Echeladder</th>
		<th class="sorttable_alpha">Top Gate</th>
		<th>Boondollars</th>';
foreach($stats as $stat){
	$title='';
	switch ($stat){
		case 'artapproved':
			$stat='Approved Art';
			break;
		case 'itemapproved':
			$stat='Approved Items';
			break;
		case 'enemiesbeaten':
			$stat='Enemies Beaten';
			break;
		case 'alchemy':
			$stat='Alchemized';
			$title='Number of items alchemized';
			break;
		case 'maxdamage':
			$stat='Max. Damage';
			$title='Maximum offense power reached during strife';
			break;
		default:
			$stat=ucfirst($stat);
	}
	echo '<th title="' . $title . '">' . $stat . '</th>';
}
echo '</tr></thead><tbody>';

array_pop($characterids);

foreach($characterids as $chara){
	$char = getChar($chara);
	if($char['name']!='[ERROR RETRIEVING PLAYER ID]'){
		echo 
		'<tr>
		<td>' . $char['ID'] . '</td>
		<td><div title="'.$char['name'].'"><b>' . rowProfileStringSoft($char) . '</b></div></td>
		<td>' . $char['echeladder'] . '</td>
		<td>' . (($char['denizendown']==1)?'Skaia':$char["gatescleared"]) . '</td>
		<td>' . $char['boondollars'] . '</td>';
		foreach($stats as $stat){
			$statvalue = getStat($char,$stat) ?? '0';
			echo '<td>' . $statvalue . '</td>';
		};
		echo '</tr>';
	}
}
echo '</tbody><tfoot></tfoot></table>';

require_once("footer.php");
?>
