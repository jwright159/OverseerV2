<?php

function rowRowFightThePower($consortID, $charID): void {
	global $connection;
	// Get necesary data here
	$consortQuery = mysqli_query($connection, "SELECT * FROM `Consorts` WHERE `id` = '$consortID';");
	$consortRow = mysqli_fetch_array($consortQuery);
	$consortName = $consortRow['name'];
    $consortDisplay = '<a href="/mercenaries.php?info='.$consortRow['id'].'">'.$consortName.'</a>';
	$charQuery = mysqli_query($connection, "SELECT * FROM `Characters` WHERE `ID` = '$charID';");
	$charRow = mysqli_fetch_array($charQuery);
	$failure = 0; //They han't lost yet
	// Pick an enemy here
	// Determine which enemies are beaten
	$enemiesBeaten = $charRow['enemiesbeaten'];
	$baseImpPower = 1; // Set base power for enemies.
	$baseOgrePower = 30;
	$baseBasiliskPower = 150;
	$baseLichPower = 450;
	$baseGiclopsPower = 700;
	$baseTitachnidPower = 1000;
	$baseAcheronPower = 2000;
	// Add any additional enemies here
	$powerBeaten = $enemiesBeaten;
	$enemyChanceArray = array ( // Determines the chance of an enemy. Adding new enemies will need  to re-adjust chances
		'Imp'    =>  array('min' =>  0, 'max' =>  40),
		'Ogre'  =>  array('min' => 40, 'max' =>  60),
		'Basilisk' => array('min' => 60, 'max' => 70), 
		'Lich' => array('min' => 70, 'max' => 80), 
		'Giclops' => array('min' => 80, 'max' => 88), 
		'Titachnid' => array('min' => 88, 'max' => 95), 
		'Acheron' => array('min' => 95, 'max' => 100), 
		);
		
	$rnd = rand(1,100);
	foreach($enemyChanceArray as $k =>$v) {
		if ($rnd > $v['min'] && $rnd <= $v['max']) {
			$fightThis = $k; 
		}	
	}	
	$tierToFight = rand(1,9);
	$enemyPowerBase = ${'base'.$fightThis.'Power'} ;
	$enemyPower = $enemyPowerBase * $tierToFight + ($tierToFight * $tierToFight);
	if ($powerBeaten < $enemyPower) {
		// Do nothing
	} else {

        // Calculate any squad bonuses here
        $consortPower = $consortRow['power'];
        $squadBonus = ($charRow['consortcount'] / 50); 
        if ($squadBonus < 1) { // Don't want them penalised for having less than 50 consorts
            $squadBonus = 1;
        }
        $consortEffectivePower = $consortPower * $squadBonus;
        
        // F I G H T 
        $time = time();
        mysqli_query($connection, "UPDATE `Consorts` SET `lastaction` = '$time', `lastcombat` = '$time' WHERE `id` = '$consortID';");
        $criticalChance = round($squadBonus  / 100);
        if ($criticalChance == 0) {
            $criticalChance = 1;
        }
        $rndCrit = rand(1,100);
        if ($rndCrit <= $criticalChance) {
            $criticalHit = 1; // If it's a crit, they automatically win
        }else{
			$criticalHit = 0; // So that the variable are started
		}	
        if ($criticalHit != 1) { // If not... 
            if ($consortEffectivePower < $enemyPower) {
                $injuryRand = rand(1,20);
                if ($injuryRand == 3) {
                mysqli_query($connection, "UPDATE `Consorts` SET `status` = 'INJURED', `injurycount` = injurycount + 1 WHERE `id` = '$consortID';");
                $failure = 1; // They lost :(
                logThis("The ".$fightThis." that ".$consortDisplay." tried to fight was too strong! They got injured and need some time to recover.", $charID);
                } else {
                    $failure = 1;
                    echo "They ran away";
                }
            }
        } // End of strife
        if ($failure == 0) { // Only award grist if they won
        
            // Tier 1-3 Archeron? See if the small chance of Tier+3 grist is given
            if (in_array($tierToFight,array(1, 2, 3, 4))) {
                if ($fightThis == 'Acheron' || $fightThis == 'Giclops' || $fightThis == 'Titachnid') {
                    if (rand(1,20) == 9) { // Small chance to award bonus grist
                        $tierToFight = $tierToFight + 3;
                        if ($tierToFight > 9) {
                            $tierToFight = 9;
                        }
                    }
                }
            }
            
            $rareMult = 0;
            // If Tier 7+, calculate drop chance for rare grist
            if ($tierToFight >= 7) {
                $rareMultChance = rand(1, 10);
                if ($rareMultChance == 3) {
                    if ($tierToFight == 9) {
                        $rareMult = 1;
                    } else {
                        $tierToFight = 9;
                    }
                }	
            }
            
            // I suppose grist tables go here in a format that makes me want to stab a spoon into my eyes
			// First in array must be build grist
			// The GRIST:x:x:x stuff is lifted from the database - The first digit represents Tier. 0 is always build grist, 1 is always a tier matching the enemy, and everything higher is tier+n. If
			// tier+n goes above 9, it defaults to 9. An underling cannot drop grist below it's tier other than build. 
            if ($fightThis == 'Imp') {
                if ($tierToFight == 1) { // GRIST:0:2:50|GRIST:0:2:50|GRIST:0:6:50|GRIST:0:6:33|GRIST:0:20:10|GRIST:0:20:5|GRIST:1:2:50|GRIST:1:2:50|GRIST:1:6:50|GRIST:1:6:33|GRIST:1:20:10|GRIST:1:20:5|
                    $gristTable = array(10, 10);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(10, 0, 10);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(10, 0, 0, 10);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(10, 0, 0, 0, 10);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(10, 0, 0, 0, 0, 10);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(10, 0, 0, 0, 0, 0, 10);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(10, 0, 0, 0, 0, 0, 0, 10);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(10, 0, 0, 0, 0, 0, 0, 0, 10);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(10, 0, 0, 0, 0, 0, 0, 0, 0, 10);
                }	// End of Imps
            } elseif ($fightThis == 'Ogre') { //GRIST:0:500:100|GRIST:0:30:50|GRIST:0:200:50|GRIST:0:1000:33|GRIST:0:1000:10|GRIST:0:1000:5|GRIST:1:250:100|
			//GRIST:1:20:50|GRIST:1:100:50|GRIST:1:500:33|GRIST:1:500:10|GRIST:1:1000:5|GRIST:2:125:100|GRIST:2:20:50|GRIST:2:50:50|GRIST:2:250:33|GRIST:2:250:10|
			//GRIST:2:500:5|GRIST:3:75:100|GRIST:3:20:50|GRIST:3:25:50|GRIST:3:125:33|GRIST:3:125:10|GRIST:2:250:5|
                if ($tierToFight == 1) {
                    $gristTable = array(1090, 640, 275, 150);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(1090, 0, 640, 275, 150);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(1090, 0, 0, 640, 275, 150);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(1090, 0, 0, 0, 640, 275, 150);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(1090, 0, 0, 0, 0, 640, 275, 150);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(1090, 0, 0, 0, 0, 0, 640, 275, 150);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(1090, 0, 0, 0, 0, 0, 0, 640, 275, 150);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(1090, 0, 0, 0, 0, 0, 0, 0, 640, 425);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(1090, 0, 0, 0, 0, 0, 0, 0, 0, 1065);
                }	// End of Ogre
            } elseif ($fightThis == 'Basilisk') { //GRIST:0:1500:100|GRIST:0:200:50|GRIST:0:900:50|GRIST:0:2500:33|GRIST:0:3000:10|GRIST:0:4000:5|GRIST:1:800:100|GRIST:1:100:50|
			//GRIST:1:400:50|GRIST:1:2000:33|GRIST:1:2500:10|GRIST:1:5000:5|GRIST:2:400:100|GRIST:2:100:50|GRIST:2:150:50|GRIST:2:1000:33|GRIST:2:1000:10|GRIST:2:2500:5|GRIST:3:200:100|
			//GRIST:3:75:50|GRIST:3:100:50|GRIST:3:600:33|GRIST:3:600:10|GRIST:3:1250:5|GRIST:4:400:10|GRIST:4:800:5|
                if ($tierToFight == 1) {
                    $gristTable = array(3200, 2000, 1000, 590);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(3200, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(3200, 0, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(3200, 0, 0, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(3200, 0, 0, 0, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(3200, 0, 0, 0, 0, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(3200, 0, 0, 0, 0, 0, 0, 2000, 1000, 590);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(3200, 0, 0, 0, 0, 0, 0, 0, 2000, 1590);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(3200, 0, 0, 0, 0, 0, 0, 0, 0, 3590);
                }	// End of Basilisk
            } elseif ($fightThis == 'Lich') { //GRIST:0:3000:100|GRIST:0:600:50|GRIST:0:2500:50|GRIST:0:7500:33|GRIST:0:8000:10|GRIST:0:15000:5|GRIST:1:2000:100|GRIST:1:300:50|
			//GRIST:1:1500:50|GRIST:1:4666:33|GRIST:1:7666:10|GRIST:1:10000:5|GRIST:2:1000:100|GRIST:2:150:50|GRIST:2:666:50|GRIST:2:2666:33|GRIST:2:4000:10|GRIST:2:7666:5|
			//GRIST:3:500:100|GRIST:3:100:50|GRIST:3:250:50|GRIST:3:1250:33|GRIST:3:2000:10|GRIST:3:5000:5|GRIST:4:666:10|GRIST:4:1666:5|GRIST:5:333:10|GRIST:5:666:5|
                if ($tierToFight == 1) {
                    $gristTable = array(8600, 4600, 2900, 1360, 150, 60);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(8600, 0, 4600, 2900, 1360, 150, 60);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(8600, 0, 0, 4600, 2900, 1360, 150, 60);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(8600, 0, 0, 0, 4600, 2900, 1360, 150, 60);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(8600, 0, 0, 0, 0, 4600, 2900, 1360, 150, 60);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(8600, 0, 0, 0, 0, 0, 4600, 2900, 1360, 210);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(8600, 0, 0, 0, 0, 0, 0, 4600, 2900, 1570);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(8600, 0, 0, 0, 0, 0, 0, 0, 4600, 4470);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(8600, 0, 0, 0, 0, 0, 0, 0, 0, 9070);
                }	// End of Lich
            } elseif ($fightThis == 'Giclops') { //GRIST:0:5500:100|GRIST:0:1000:50|GRIST:0:6000:50|GRIST:0:20000:33|GRIST:0:20000:10|GRIST:0:50000:5|GRIST:1:4000:100|GRIST:1:500:50|
			//GRIST:1:3000:50|GRIST:1:7500:33|GRIST:1:15000:10|GRIST:1:30000:5|GRIST:2:2000:100|GRIST:2:300:50|GRIST:2:1000:50|GRIST:2:5000:33|GRIST:2:7500:10|GRIST:2:15000:5|
			//GRIST:3:1000:100|GRIST:3:200:50|GRIST:3:500:50|GRIST:3:2500:33|GRIST:3:3500:10|GRIST:3:8001:5|GRIST:4:1000:10|GRIST:4:2500:5|GRIST:5:600:10|GRIST:5:1000:5|GRIST:6:300:10|GRIST:6:600:5|
                if ($tierToFight == 1) {
                    $gristTable = array(19500, 9200, 5600, 2900, 200, 100, 60);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(19500, 0, 9200, 5600, 2900, 200, 100, 60);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(19500, 0, 0, 9200, 5600, 2900, 200, 100, 60);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(19500, 0, 0, 0, 9200, 5600, 2900, 200, 100, 60);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(19500, 0, 0, 0, 0, 9200, 5600, 2900, 200, 160);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(19500, 0, 0, 0, 0, 0, 9200, 5600, 2900, 360);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(19500, 0, 0, 0, 0, 0, 0, 9200, 5600, 3260);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(19500, 0, 0, 0, 0, 0, 0, 0, 9200, 8860);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(19500, 0, 0, 0, 0, 0, 0, 0, 0, 18060);
                }	// End of Giclops	
            } elseif ($fightThis == 'Titachnid') { //GRIST:0:8000:100|GRIST:0:1500:50|GRIST:0:8500:50|GRIST:0:30000:33|GRIST:0:30000:10|GRIST:0:70000:5|GRIST:1:6000:100|GRIST:1:800:50|GRIST:1:4000:50|
			//GRIST:1:12500:33|GRIST:1:20000:10|GRIST:1:40000:5|GRIST:2:3000:100|GRIST:2:500:50|GRIST:2:1500:50|GRIST:2:7500:33|GRIST:2:12000:10|GRIST:2:25000:5|GRIST:3:1500:100|GRIST:3:300:50|
			//GRIST:3:800:50|GRIST:3:4000:33|GRIST:3:6000:10|GRIST:3:12000:5|GRIST:4:750:100|GRIST:4:200:50|GRIST:4:400:50|GRIST:4:2000:33|GRIST:4:3000:10|GRIST:4:6000:5|GRIST:5:1000:10|GRIST:5:2500:5|
			//GRIST:6:600:10|GRIST:6:1000:5|GRIST:7:300:10|GRIST:7:600:5|
                if ($tierToFight == 1) {
                    $gristTable = array(29500, 16400, 9000, 4400, 2500, 225, 110, 60);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(29500, 0, 16400, 9000, 4400, 2500, 225, 110, 60);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(29500, 0, 0, 16400, 9000, 4400, 2500, 225, 110, 60);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(29500, 0, 0, 0, 16400, 9000, 4400, 2500, 225, 170);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(29500, 0, 0, 0, 0, 16400, 9000, 4400, 2500, 395);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(29500, 0, 0, 0, 0, 0, 16400, 9000, 4400, 2895);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(29500, 0, 0, 0, 0, 0, 0, 16400, 9000, 7495);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(29500, 0, 0, 0, 0, 0, 0, 0, 16400, 16495);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(29500, 0, 0, 0, 0, 0, 0, 0, 0, 32895);
                }	// End of Titachnid
            } elseif ($fightThis == 'Acheron') { //GRIST:0:16000:100|GRIST:0:3000:50|GRIST:0:17000:50|GRIST:0:60000:33|GRIST:0:60000:10|GRIST:0:140000:5|GRIST:1:12000:100|GRIST:1:1600:50|GRIST:1:8000:50|
			//GRIST:1:25000:33|GRIST:1:40000:10|GRIST:1:80000:5|GRIST:2:6000:100|GRIST:2:1000:50|GRIST:2:3000:50|GRIST:2:15000:33|GRIST:2:24000:10|GRIST:2:50000:5|GRIST:3:3000:100|GRIST:3:600:50|
			//GRIST:3:1600:50|GRIST:3:8000:33|GRIST:3:12000:10|GRIST:3:24000:5|GRIST:4:1500:100|GRIST:4:400:50|GRIST:4:800:50|GRIST:4:4000:33|GRIST:4:6000:10|GRIST:4:12000:5|GRIST:5:2000:10|
			//GRIST:5:5000:5|GRIST:6:1200:10|GRIST:6:2000:5|GRIST:7:600:10|GRIST:7:1200:5|
                if ($tierToFight == 1) {
                    $gristTable = array(59000, 30000, 17000, 9000, 4500, 450, 220, 120);
                } elseif ($tierToFight == 2) {
                    $gristTable = array(59000, 0, 30000, 17000, 9000, 4500, 450, 220, 120);
                } elseif ($tierToFight == 3) {
                    $gristTable = array(59000, 0, 0, 30000, 17000, 9000, 4500, 450, 220, 120);
                } elseif ($tierToFight == 4) {
                    $gristTable = array(59000, 0, 0, 0, 30000, 17000, 9000, 4500, 450, 340);
                } elseif ($tierToFight == 5) {
                    $gristTable = array(59000, 0, 0, 0, 0, 30000, 17000, 9000, 4500, 790);
                } elseif ($tierToFight == 6) {
                    $gristTable = array(59000, 0, 0, 0, 0, 0, 30000, 17000, 9000, 5290);
                } elseif ($tierToFight == 7) {
                    $gristTable = array(59000, 0, 0, 0, 0, 0, 0, 30000, 17000, 14290);
                } elseif ($tierToFight == 8) {
                    $gristTable = array(59000, 0, 0, 0, 0, 0, 0, 0, 30000, 31290);
                } elseif ($tierToFight == 9) {
                    $gristTable = array(59000, 0, 0, 0, 0, 0, 0, 0, 0, 61290);
                }	// End of Acheron
            }		   
            
            if ($rareMult == 1) {
                foreach( $gristTable as &$val ) { 
                    $val *= 1.25; 
                }
            }
            
            // And a bonus chance to recieve double grist drop
            $doubleChance = rand(1, 50);
            if ($doubleChance >= 1 && $doubleChance <= 3) {
                foreach( $gristTable as &$val ) { 
                    $val *= 2; 
                }
            }
            
            // Award grist here (+- 10%)
            foreach( $gristTable as &$val ) { 
                $gristMultiplier = (rand(90,110)/100); // Gets a random number between 90 and 110, then divides by 100 - giving an effective range of 0.9 to 1.10, i.e. +/- 10%
                $val *= $gristMultiplier;
            }	// Multiplies by a random value between 0.9 and 1.1
            $gristTypes = $charRow['grist_type']; // They're already in order, thank fuck. Seperated by |
            $gristTypeArray = explode('|', $gristTypes);
            array_unshift($gristTypeArray , 'Build_Grist'); // Need to add Build to position 0 and shift everything over
            $gristStr = $charRow['consortgrist'];
            $gristI = 0;   
            while (($gristI < count($gristTable))) {
				if($gristI < count($gristTypeArray)){
					$gristStr = modifyGrist($gristStr, $gristTypeArray[$gristI], round($gristTable[$gristI]));
				}
                $gristI++;
            }
            mysqli_query($connection, "UPDATE `Characters` SET `consortgrist` = '$gristStr' WHERE `ID` = '$charID';") or die("test");
            // Need to get index of gristTable and match it to gristTypeArray. Variable lengths of the grist table array make things hard and make me sad.
        } // End of failure check
	} // End of "consort is stronger" else	
}  // End of function

