<table>
<?php 
require_once("header.php");
require_once("includes/designix.php");
//name
//description
//power
// aggrieve
// aggress
// assail
// assault
//abuse
//accuse
//abjure
//abstain
//abstratus <--- more than one possible checkbox
// wearable <---- more than one possible checkbox
// size
// grists <--- ok fuck same as above
// comments
function getMaxBonus($array) { //take an array containing bonus values and find the highest one, we'll use this several times here
  $b = 0;
  $thisbonus = 0;
  $maxbonus = -9999; //an item with all negative bonuses will indeed lower the item's effective power
  while ($b < 8) { //run through each bonus, find the highest
    $thisbonus = intval($array[getBonusname($b)]);
    if ($thisbonus > $maxbonus) $maxbonus = $thisbonus;
    $b++;
  }
  return $maxbonus;
}


function gristSeeSaw($power) {
	// Formula lowGrist + [(newPower - lowPower)(highGrist - lowGrist) / (highPower - lowPower)]
	// 7687245 + ((9999 - 8400) * (9875292 - 7687245)) / (10103 - 8400)
	// Powers correspond to an Example Stamp item in the database, these act as "stepping stones"
	// That we use to calculate.
	if ($power >= 0 && $power < 7) {
		$lowestPower = 0;
		$highestPower = 7;
		$lowerGristBound = 1;
		$upperGristBound = 7;		
	} elseif ($power >= 7 && $power < 18) {
		$lowestPower = 7;
		$highestPower = 18;
		$lowerGristBound = 7;
		$upperGristBound = 85;
	} elseif ($power >= 18 && $power < 50) {
		$lowestPower = 18;
		$highestPower = 50;		
		$lowerGristBound = 85;
		$upperGristBound = 140;
	} elseif ($power >= 50 && $power < 110) {
		$lowestPower = 50;
		$highestPower = 110;
		$lowerGristBound = 140;
		$upperGristBound = 1600;
	} elseif ($power >= 110 && $power < 168) {
		$lowestPower = 110;
		$highestPower = 168;		
		$lowerGristBound = 1600;
		$upperGristBound = 4500;
	} elseif ($power >= 168 && $power < 309) {
		$lowestPower = 168;
		$highestPower = 309;		
		$lowerGristBound = 4500;
		$upperGristBound = 49000;	
	} elseif ($power >= 309 && $power < 507) {
		$lowestPower = 309;
		$highestPower = 507;		
		$lowerGristBound = 49000;
		$upperGristBound = 106000;	
	} elseif ($power >= 507 && $power < 650) {
		$lowestPower = 507;
		$highestPower = 650;		
		$lowerGristBound = 106000;
		$upperGristBound = 129000;	
	} elseif ($power >= 650 && $power < 1253) {
		$lowestPower = 650;
		$highestPower = 1253;		
		$lowerGristBound = 129000;
		$upperGristBound = 242000;
	} elseif ($power >= 1253 && $power < 1347) {
		$lowestPower = 1253;
		$highestPower = 1347;		
		$lowerGristBound = 242000;
		$upperGristBound = 314370;		
	} elseif ($power >= 1347 && $power < 2401) {
		$lowestPower = 1347;
		$highestPower = 2401;		
		$lowerGristBound = 314370;
		$upperGristBound = 415104;	
	} elseif ($power >= 2401 && $power < 3222) {
		$lowestPower = 2401;
		$highestPower = 3222;		
		$lowerGristBound = 415104;
		$upperGristBound = 977281;	
	} elseif ($power >= 3222 && $power < 4745) {
		$lowestPower = 3222;
		$highestPower = 4745;		
		$lowerGristBound = 977281;
		$upperGristBound = 2695735;	
	} elseif ($power >= 4745 && $power < 6924) {
		$lowestPower = 4745;
		$highestPower = 6924;		
		$lowerGristBound = 2695735;
		$upperGristBound = 5158470;
	} elseif ($power >= 6924 && $power < 8400) {
		$lowestPower = 6924;
		$highestPower = 8400;		
		$lowerGristBound = 5158570;
		$upperGristBound = 7687245;
	} elseif ($power >= 8400 && $power < 10103) {
		$lowestPower = 8400;
		$highestPower = 10103;		
		$lowerGristBound = 7687245;
		$upperGristBound = 9875292;	
	} elseif ($power >= 10103 && $power < 16399) {
		$lowestPower = 10103;
		$highestPower = 16399;		
		$lowerGristBound = 9875292;
		$upperGristBound = 24370125;	
	} elseif ($power >= 16399 && $power < 33999) {
		$lowestPower = 16399;
		$highestPower = 33999;		
		$lowerGristBound = 24370125;
		$upperGristBound = 30204292;
	} elseif ($power >= 33999 && $power < 60663) {
		$lowestPower = 33999;
		$highestPower = 60663;		
		$lowerGristBound = 30204292;
		$upperGristBound = 43658786;	
	} elseif ($power >= 60663 && $power < 89991) {
		$lowestPower = 60663;
		$highestPower = 89991;		
		$lowerGristBound = 43658786;
		$upperGristBound = 85024933;
	}
		// Formula lowGrist + [(newPower - lowPower)(highGrist - lowGrist) / (highPower - lowPower)]
	$gristTotalAmount = $lowerGristBound + (($power - $lowestPower) * ($upperGristBound - $lowerGristBound)) / ($highestPower - $lowestPower);	
	return $gristTotalAmount;
}

$abscount = $_SESSION['abscount'];
unset($_SESSION['abscount']);
$i = 1;
while ($i <= $abscount) {
	${'abstratus'.$i} = mysqli_escape_string($connection, $_POST['abstratus'.$i]); // Echoes out all the abstrati into a variable
	$i++;
} 
$wearcount = $_SESSION['wearcount'];
unset($_SESSION['wearcount']);
$i = 1;
while ($i <= $wearcount) {
	${'wearable'.$i} = mysqli_escape_string($connection, $_POST['wearable'.$i]); // Echoes out all the abstrati into a variable
	$i++;
} 
$code1 = mysqli_escape_string($connection, $_SESSION['code1']); // Codes of the 
$code2 = mysqli_escape_string($connection, $_SESSION['code2']); // initial items
$combineop = mysqli_escape_string($connection, $_SESSION['combineop']); // And or or?
$maxPower = mysqli_escape_string($connection, $_SESSION['maxpower']); // Used in a check for whether the maximum power exceeds the given power
unset($_SESSION['code1']); 
unset($_SESSION['code2']);
unset($_SESSION['combineop']);
unset($_SESSION['maxpower']);
if ($combineop == "and") { // Begin new item code generation
	$code = andcombine($code1,$code2);
} elseif ($combineop == "or") {
	$code = orcombine($code1,$code2);
} 
	// BEGIN STABILITY CHECK
$fullyUnstable = 0;
$stringArray = str_split($code, 1); // Splits the combined code into single characters. 
$binaryString = binary($stringArray[0]).binary($stringArray[1]).binary($stringArray[2]).binary($stringArray[3]).binary($stringArray[4]).binary($stringArray[5]).binary($stringArray[6]).binary($stringArray[7]);
if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
	if ($op = '||') {	
		$newCode = andCombine($code1, $code2);
		$stringArray = str_split($newCode, 1);
		$binaryString = binary($stringArray[0]).binary($stringArray[1]).binary($stringArray[2]).binary($stringArray[3]).binary($stringArray[4]).binary($stringArray[5]).binary($stringArray[6]).binary($stringArray[7]);
		$partiallyUnstable = 1;
		$op = '&&';
		if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
			$fullyUnstable = 1;
		} else {
			$code = $newCode;
		}
	} elseif ($op = '&&') {
		$newCode = orCombine($code1, $code2);
		$stringArray = str_split($newCode, 1);
		$binaryString = binary($stringArray[0]).binary($stringArray[1]).binary($stringArray[2]).binary($stringArray[3]).binary($stringArray[4]).binary($stringArray[5]).binary($stringArray[6]).binary($stringArray[7]);
		$partiallyUnstable = 1;
		$op = '||';
		if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
			$fullyUnstable = 1;
		} else {
			$code = $newCode;
		}
	}
}	
// END STABILITY CHECK 
//End new item code generation. $code is now the code of the new item.
// Begin Power Check Block
$power = mysqli_escape_string($connection, $_POST['power']);
$maxBonus = getMaxBonus($_POST);
// Calculate:
$powerCheck = $power + $maxBonus;
$b = 0;
$powerAllowedBonus = True;
while ($b < 8) { //build this query snippet containing bonuses
      $thisbonus = intval($_POST[getBonusname($b)]);
      if ($thisbonus > 9999) {
          $powerAllowedBonus = False;
      }
      $b++;
}
if ($fullyUnstable == 1) {
	echo 'This item cannot be made - the resulting code is too unstable in either operation. Please make a full submission instead. Any additional errors below may serve to refine your submission.';
}
if ($powerAllowedBonus == False) {
    echo "Invalid - One or more bonuses is too high.";
}
if ($powerCheck > $maxPower || $power > 9999) { 
	$powerAllowed = False;
    echo "Invalid - You tried to give it too much power! Must be below " .$maxPower. " or if that's higher than 9999, 9999.";
} else {
	$powerAllowed = True;
}
if ($powerAllowed == True && $powerAllowedBonus == True && $fullyUnstable == 0) {
	if ($partiallyUnstable == 1) {
		echo "Be advised: Do to code stability reasons, the combining operation you selected has been switched to ".$op.".";
	}
	// Set the rest of the variables
	$i = 2;
	if (isset($abstratus1) || isset($abstratus2) || isset($abstratus3) || isset($abstratus4)) { //This query is bad and I feel bad
		$abstrati = $abstratus1;
		$seperator = ', ';
		while ($i <= $abscount) {
			if ($abstrati != "" && ${'abstratus'.$i} != "") {
				$abstrati = $abstrati.$seperator.${'abstratus'.$i};
			} elseif (${'abstratus'.$i} != "") {
				$abstrati = ${'abstratus'.$i};
			}
			$i++;
		}
	} else {
		$abstrati = 'notaweapon';
	}
	$abstrati = mysqli_escape_string($connection, $abstrati); // Yay
	$name = mysqli_escape_string($connection, $_POST['name']);
	$sessionID = mysqli_escape_string($connection, $charrow['session']);
	$size = mysqli_escape_string($connection, $_POST['size']);
	$comments = mysqli_escape_string($connection, $_POST['comments']);
	$description = mysqli_escape_string($connection, $_POST['description']);
	// Grist stuff here
	$maxoffense = max(intval($_POST['aggrieve']), intval($_POST['aggress']), intval($_POST['assail']), intval($_POST['assault']));
	$maxdefense = max(intval($_POST['abuse']), intval($_POST['accuse']), intval($_POST['abjure']), intval($_POST['abstain']));
	$totalPower = $_POST['power'] + ($maxoffense * 4) + ($maxdefense * 4); //Hotfix: Use the highest bonus times 4. Won't account for versatility.
	$newGristTotal = round(gristSeeSaw($totalPower));
	// Calculate the new grist total 
  //We prefer the standard grist/power ratio, but multiply it by the net deviation of the components from the standard value (if positive).
  //This is to account for components with higher costs due to effects, etc. The grist costs can always be modified by staff when finalizing the item,
  //but this ensures that a user can't create a super easy power upgrade for themselves without any staff intervention.
	//$newGristTotal = $oldGristTotal * 1.2 + ($power + $abuse + $accuse + $abstain + $abjure + $aggrieve + $assail + $aggress + $assault) * 15;
	// Pull grist list from DB
/*	$gristResult = mysqli_query($connection, "SELECT `name` FROM `Grists`;");
	$gristArray = mysqli_fetch_array($gristResult); // This is what we use to run through the _POST vars
	$gristArray = array_flip($gristArray); // This has the grist name as keys */
	// $$key can be used to set a variable with the name
	$grists = "";
	$gristWeightTotal = 0;
/*	foreach($gristArray as $key) {
		if (isset(${"_POST['".$$key."']"})) {
			$gristWeightTotal = $gristWeightTotal + ${"_POST['".$$key."']"};
		}
	}
	*/
	$gristResult = mysqli_query($connection, "SELECT * FROM `Grists`;");
	while ($v = mysqli_fetch_assoc($gristResult)) {
		$$v['name'] = 0;
		if (isset($_POST[''.$v['name'].''])) {
				if ($_POST[''.$v['name'].''] > 20) {
					$_POST[''.$v['name'].''] = 20;
				}
			$gristWeightTotal = $gristWeightTotal + $_POST[''.$v['name'].''];
		}
	}
	$gristTiersSatisfied = 0;
	$gristResult = mysqli_query($connection, "SELECT * FROM `Grists`;");
	while ($v = mysqli_fetch_assoc($gristResult)) {	
		if (isset($_POST[''.$v['name'].''])) {
			$gristWeight = $_POST[''.$v['name'].''];
			$gristName = $v['name'];
			$gristTier = $v['tier'];
			if ($gristWeight != 0) {
				$gristWeight = $newGristTotal / $gristWeightTotal * $gristWeight;
				$gristWeight = round($gristWeight);
				//echo $gristName.' '.$gristWeight.'<br>'; //Debug Line
				$grists = $grists.$gristName.':'.$gristWeight.'|';
				$gristTiersSatisfied = $gristTiersSatisfied.$gristTier;
			}
		}
	}
	$overallGristSatisfied = 1;
    if ($gristWeightTotal <= 0) {
        echo "Items MUST cost grist.";
    } else {
		if ($totalPower < 100) {
			$gristTierRequired = 0;
		} elseif ($totalPower >= 100 && $totalPower < 400) {
			$gristTierRequired = 1;
		} elseif ($totalPower >= 400 && $totalPower <1000) {
			$gristTierRequired = 2;
		} elseif ($totalPower >= 1000 && $totalPower < 3000) {
			$gristTierRequired = 3;
		} elseif ($totalPower >= 3000 && $totalPower < 7500) {
			$gristTierRequired = 4;
		} elseif ($totalPower >= 7500 && $totalPower < 12000) {
			$gristTierRequired = 5;
		} elseif ($totalPower >= 12000 && $totalPower < 19000) {
			$gristTierRequired = 6;
		} elseif ($totalPower >= 19000 && $totalPower < 30000) {
			$gristTierRequired = 7;
		} elseif ($totalPower >= 30000 && $totalPower < 45000) {
			$gristTierRequired = 8;
		} elseif ($totalPower >= 45000 && $totalPower < 60000) {
			$gristTierRequired = 9;
		} elseif ($totalPower >= 60000) {
			$gristTierRequired = 10;
		}
	}
	$gristRunthrough = 0;
	while ($gristRunthrough <= $gristTierRequired) {
		if (strpos($gristTiersSatisfied, (string)$gristRunthrough) !== False) {
			// Continue
		} else {
			$overallGristSatisfied = 0;
		}
		$gristRunthrough++;
	}
	if ($overallGristSatisfied == 0) {
	echo "Your weapon is rated as a Tier ".$gristTierRequired." weapon, and therefore needs grist tiers equivalent. At least one grist from each tier up to and including the weapon tier must be selected.";
	} else {	
		// Pass the grists to whatever function before adding here
		// Wearable stuff goes here
		$i = 2;
		if (isset($wearable1)) {
		$wearables = $wearable1;
		while ($i <= $wearcount) {
			$wearables = $wearables.', '.${'wearable'.$i};
			$i++;
		} 
		$wearables = mysqli_escape_string($connection, $wearables);
		} else {
		$wearables = 'none';	
		}
		//Insert into DB here

		$bonusstr = "";
		$b = 0;
		while ($b < 8) { //build this query snippet containing bonuses
		  $thisbonus = intval($_POST[getBonusname($b)]);
		  $bonusstr .= "'$thisbonus', ";
		  $b++;
		}
		if ($grists == '|' || $grists == '' || $grists == ':0|') {
			echo 'The item must cost grist.<br />';
		} elseif ($name == "") {
			echo 'The item must have a name!<br />';
		} elseif ($_POST['Artifact']){
			echo 'Nice try, but no.<br />';
		} elseif ($power <= 0){
			echo "The item's base power must be higher than 0!<br />";
		} else {
		mysqli_query($connection, "INSERT INTO `Captchalogue` (`code`, `name`, `description`, `session`, `power`, `aggrieve`, `aggress`, `assail`, `assault`, `abuse`, `accuse`, `abjure`, `abstain`, `abstratus`, `wearable`, `size`, `gristcosts`, `notes`) VALUES ('$code', '$name', '$description', '$sessionID', '$power', " . $bonusstr . "'$abstrati', '$wearables', '$size', '$grists', '$comments');");
		echo 'Success! You can now alchemise your item.';
		}
	}
}

//print_r($gristArray);
//echo 'Grists: ';
//echo $grists.'<br>'; //Debug lines


require_once("footer.php");
// Test data:
/*name	Commemorative Second Amendment Pistol
description	Celebrate your right to bear arms with this trendy pistol! Fires miniature American flags. The ends are quite pointy.
power	13
aggrieve	1
abuse	0
aggress	3
accuse	0
assail	0
abjure	0
assault	2
abstain	0
abstratus1	flagkind
abstratus2	pistolkind
size	average
Build_Grist	1
comments	This is a comment
*/

/* // Echo out a table with all POST vars - debug
    foreach ($_POST as $key => $value) {
        echo "<tr>";
        echo "<td>";
        echo $key;
        echo "</td>";
        echo "<td>";
        echo $value;
        echo "</td>";
        echo "</tr>";
    }

*/
?>
</table>