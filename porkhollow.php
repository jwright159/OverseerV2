<?php
$pagetitle = "Virtual Porkhollow";
$headericon = "/images/header/boon.png";
require_once("header.php");
require_once("includes/global_functions.php");


#ripped off from the gristwire page
if (empty($_SESSION['character'])) echo "You need to choose a character in order to use the Virtual Porkhollow!<br />";
else {

$reachgrist = False;

echo "Virtual Porkhollow v2.0.0.Beta</br>";

$compugood = true;
$computability = calculateComputability($charrow);

if ($computability == 0) {
	if ($compugood == true) echo "You need a computer in storage or equipped to wire boondollars to other players.</br>";
	$compugood = false;
}

if ($compugood == true) {
//--Begin wiring code here--

if (($_POST['amount'] ?? 0) > 0) { //We have a positive amount of Boondollars to transfer.
	if(!$_POST['session']){ 
		$sessionid = $charrow['session'];
	} else {
		$sessionesc = str_replace("'", "''", $_POST['session']);
		$sessionresult = mysqli_query($connection, "SELECT * FROM Sessions WHERE `name` = '". mysqli_real_escape_string($connection, $sessionesc) ."';");
		$sessionrow = mysqli_fetch_array($sessionresult);
		$sessionid = $sessionrow['ID'];
	}
	
	if ($_POST['target'] == $charrow['name']) { //Player is trying to wire themselves boondollars!
		echo "You can't wire boondollars to yourself!</br>";
	} else if (empty($_POST['target'])) {
			echo "You didn't specify a recipient!<br />";
	} else {
			$wireresult2 = mysqli_query($connection, "SELECT * FROM Characters WHERE name = '". mysqli_real_escape_string($connection, $_POST['target']) ."' AND session = $sessionid;");
			$targetfound = False;
			$poor = False;
			if(intval($_POST['amount']) <= $charrow['boondollars']){;
				while($wirerow2 = mysqli_fetch_array($wireresult2)){
					if($wirerow2['name'] == $_POST['target']){
						$targetfound = True;
						$modifier = intval($_POST['amount']);
						notifyCharacter($wirerow2['ID'], $charrow['name'] . " has sent you ". $modifier . " boondollars!");
						$lessmoney = mysqli_query($connection, "UPDATE Characters SET boondollars = boondollars - $modifier WHERE name = '$charrow[name]' AND session = $charrow[session];");
						$quantity = $charrow['boondollars']-$modifier;
						$moremoney = mysqli_query($connection, "UPDATE Characters SET boondollars = boondollars + $modifier WHERE name = '". mysqli_real_escape_string($connection, $_POST['target']) ."' AND session = $sessionid;");
					}
				}

			} else {
				echo "Transaction failed: You only have $charrow[$boon] boondollars";
				$poor = True;
			}
			if ($targetfound){
				echo "Transaction successful. Boondollars: $quantity";
			} else if (!$poor) {
				echo "Transaction failed: Target does not exist.</br>Boondollars: " . $charrow['boondollars'];
			}
			echo "</br>";
		}
	}else{
		echo "Boondollars: " . $charrow['boondollars'] . "<br/>";
	}




//--End wiring code here--
	echo '<form action="porkhollow.php" method="post" id="wire">
	Target username: <input id="target" name="target" type="text" /><br />
	Target session (defaults to your session if left empty): <input id="session" name="session" type="text" /><br />
	Amount of boondollars to transfer: <input id="amount" name="amount" type="text" /><br /><input type="submit" value="Wire it!" /></form>';
	} else echo "Boondollars: " . strval($charrow['boondollars']);
}



require_once("footer.php");
?>
