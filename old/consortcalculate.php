<?php
	$enemiesBeaten = array(); //This section is adapted from quickitemcreate. 
	$enemiesList = $charRow['beatenenemies'];
	$enemiesBeatenPre = explode( '|', $item1Grist);
	array_pop($enemiesBeatenPre); 
		foreach( $enemiesBeatenPre as $val ){
		$tmp = explode( ':', $val );
		$enemiesBeaten[ $tmp[0] ] = $tmp[1];
	}