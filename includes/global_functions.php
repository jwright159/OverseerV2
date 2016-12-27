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

function showTraitArt($traitID) { // Pass a Trait ID to this and it'll show the art. 
	return "/images/art/traits/" .$traitID. ".png"; // If we're not using PNG we're doing it wrong. 
	// It's the responsibility of the code calling this to set the right dimensions!
}