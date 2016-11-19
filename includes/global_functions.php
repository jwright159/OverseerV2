<?php 

function initGrists() { //compiles an array with all grists in the game
	global $connection;
	$result2 = mysqli_query($connection, "SELECT * FROM `Grists` ORDER BY `tier` ASC"); //document grist types now so we don't have to do it later
  $totalGrists = 0;
  while ($gristrow = mysqli_fetch_array($result2)) {
    $grist[$totalGrists] = $gristrow;
    $totalGrists++;
  }
  return $grists;
}