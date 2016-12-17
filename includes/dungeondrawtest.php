<?php
	
	$enemystring = $encstr;
	$lootstring = $lootstr;
	$rstuff = explode("|",$roomstr);
	$o = 0;
	while (!empty($rstuff[$o])) {
		$map = explode(":", $rstuff[$o]);
		$coord = explode(",", $map[1]);
		$dungeonstring[$coord[0]][$coord[1]] = $rstuff[$o];
		$o++;
	}
	
	//draw that dungeon grid
	$i = $upmost;
	$j = $leftmost;
	$tiles = true;
	$onentrance = False;
	$borderstr = "1px solid black;";
	if ($tiles) echo '<table cellspacing="0" cellpadding="0">';
	while ($i <= $downmost) {
		if ($tiles) echo '<tr>';
		while ($j <= $rightmost) {
			$thisroom = strval($j) . "," . strval($i);
			$roomstr = $dungeonstring[$j][$i];
			$blank = true;
			if (!empty($roomstr)) {
				$map = explode(":", $roomstr);
				/*if (strpos($roomstr,"VISITED") !== False) {
					$blank = false;
				} else {
					if (strpos($map[3],"w") !== false && strpos($dungeonstring[$i-1][$j], "VISITED") !== false) $blank = false;
					elseif (strpos($map[3],"e") !== false && strpos($dungeonstring[$i+1][$j], "VISITED") !== false) $blank = false;
					elseif (strpos($map[3],"n") !== false && strpos($dungeonstring[$i][$j-1], "VISITED") !== false) $blank = false;
					elseif (strpos($map[3],"s") !== false && strpos($dungeonstring[$i][$j+1], "VISITED") !== false) $blank = false;
				}*/
				$blank = false;
			}
			echo '<td style="width:64;height:64;line-height:0px;';
			if ($blank) {
				if ($tiles) echo 'background-image:url(./images/dungeon/unknown_tile.png);border-left:' . $borderstr . 'border-bottom:' . $borderstr . 'border-top:' . $borderstr . 'border-right:' . $borderstr;
				else echo '&nbsp;';
			} else {
				if (strpos($map[3],"w") === False) { //Rooms not connected.
					echo 'border-left:' . $borderstr;
				}
				if (strpos($map[3],"s") === False) { //Rooms not connected.
					echo 'border-bottom:' . $borderstr;
				}
				if (strpos($map[3],"n") === False) { //Rooms not connected.
					echo 'border-top:' . $borderstr;
				}
				if (strpos($map[3],"e") === False) { //Rooms not connected.
					echo 'border-right:' . $borderstr;
				}
				//player location will be handled differently below
				$tilename = "";
				//if (strpos($roomstr,"VISITED") !== False) {
					if (strpos($enemystring,$thisroom . ":BOSS") !== false) $tilename .= "boss_";
					elseif (strpos($enemystring,$thisroom) !== false) $tilename .= "enemy_";
					elseif (strpos($roomstr,"ENTRANCE") !== False) $tilename .= "entrance_";
					if (strpos($lootstring,$thisroom) !== false) $tilename .= "loot_";
					if (strpos($map[3],"u") !== false || strpos($map[3],"d") !== false) $tilename .= "stairs_";
					elseif (strpos($roomstr,"TRANSPORT") !== false) $tilename .= "transport_";
				//} else $tilename .= "unknown_";
				$tilename .= "tile";
				echo 'background-image:url(./images/dungeon/' . $tilename . '.png);';
			}
			echo 'background-repeat:no-repeat;">';
			echo "<img src='./images/symbols/nobody.png'>";
			echo $thisroom;
			echo '</td>';
			$j++;
		}
		echo "</tr>";
		$j = $leftmost; //Reset it again for the next actual loop
		$i++;
	}
	echo '</table>';

?>