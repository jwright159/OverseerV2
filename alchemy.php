<?php
$pagetitle = "Alchemy";
$headericon = "/images/header/inventory.png";
require_once "header.php";
require_once "includes/designix.php";
require_once "includes/additem.php";

if ($_SESSION['username'] != "") {
	if (!empty($_POST['alchcode'])) { //user is alchemizing something
		if (!isset($_POST['alchnum'])) $_POST['alchnum'] = 1;
		$_POST['alchcode'] = mysqli_real_escape_string($connection, $_POST['alchcode']);
		echo "The alchemiter scans the code and begins to process it.<br />";
		$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '" . $_POST['alchcode'] . "' AND (session = 0 OR session = " . $charrow['session'] . ")");
		//Above: Search for the item. Note that items that were created by other sessions and not yet approved are treated as nonexistent.
		if ($itemrow = mysqli_fetch_assoc($itemresult)) {
			echo "You attempt to make a " . $itemrow['name'] . ".<br />";
			echo "The alchemiter requests: ";
			$canafford = true;
			$g = 0;
			$gcost = explode("|", $itemrow['gristcosts']); //sort through the item's cost string
			$newgrists = $charrow['grists'];
			while (!empty($gcost[$g])) {
				$boomo = explode(":", $gcost[$g]);
				$realcost = $boomo[1] * $_POST['alchnum']; //multiply cost by quantity to determine how much this will cost in total
				if (strpos($newgrists, $boomo[0] . ":") !== false || $realcost < 0) { //check if this is a discovered grist type, or that it'll generate the grist (artifact, for instance)
					echo '<img src="' . gristImage($boomo[0]) . '" height="50" width="50" title="' . $boomo[0] . '"></img>'; //yee
					if (howmuchGrist($newgrists, $boomo[0]) >= $realcost || $realcost < 0) { //check if the user has enough grist. Negative costs will always work.
	      				echo " <gristvalue2>" . strval($boomo[1]) . "</gristvalue2>";
	      				$newgrists = modifyGrist($newgrists, $boomo[0], $realcost * -1); //Subtract (or add) the cost of the item here.
	      				//If alchemy fails, this change won't commit.
	      			} else {
	      				$howmuch = howmuchGrist($newgrists, $boomo[0]) - strval($realcost);
	      				echo " <gristvalue>" . strval($realcost) ."(". $howmuch .")</gristvalue>";echo " <gristvalue>" . strval($realcost) . "</gristvalue>";
	      				$canafford = false;
	      			}
				} else {
					echo '<img src="images/grist/Unknown.png" height="50" width="50" title="Unknown Grist"></img>';
	      			echo " <gristvalue>" . strval($realcost) . "</gristvalue>";
	      			$canafford = false; //the user doesn't have this grist. Note that this will deny the user from creating the item even if the cost of this
	      			//undiscovered grist type is 0. This may or may not be useful. (Negative grists will still allow the item to be made)
				}
				$g++;
			}
			echo "<br />";
			if ($canafford) { //user can afford to spend the grist
				$success = storeItem($charrow, $itemrow['ID'], $_POST['alchnum']);
				if ($success) { //user has room for all the newly-created items
					echo "You successfully create " . $itemrow['name'] . " x " . strval($_POST['alchnum']) . "!<br />";
					incrementStat($charrow, 'alchemy');
					mysqli_query($connection, "UPDATE Characters SET grists = '" . $newgrists . "' WHERE ID = $cid"); //Pay.
				} else echo "Alchemy failed: not enough space in storage.<br />";
			} else echo "Alchemy failed: your grist stores are insufficient.<br />";
		} else { 
			$itemResult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '" . $_POST['alchcode'] . "';");
			$itemRow = mysqli_fetch_array($itemResult);
			if ($itemRow['session'] != 0) {
				echo 'This item is created by another session and is awaiting approval!<br>'; 
			} else {
			echo "The item corresponding to this code does not currently exist. You may create it using a full submission.<br />"; 
			}
		}
	}

	if (!empty($_POST['op'])) {
		$op = $_POST['op'];
		$code1 = $_POST['code1'];
		$code2 = $_POST['code2'];
		if (!empty($code1) && !empty($code2)) {
			$combine = "";
			if ($op == "and") {
				$combine = andcombine($code1,$code2);
			} elseif ($op == "or") {
				$combine = orcombine($code1,$code2);
			} else {
				echo "Error: invalid operation.<br />";
			}
			if ($combine != "") {
				echo "The two codes combine into: $combine<br />Scanning code...<br />";
				$itemfound = false;
				$itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$combine'");
				while ($itemrow = mysqli_fetch_array($itemresult, MYSQLI_ASSOC)) {
					echo "Item found!<br />";
					if ($itemrow['session'] != 0) {
						echo "Warning: This code belongs to an item that has been created, but not finalized. You may still submit a Quick Creation Form, and your design will be considered for the final iteration of the item as well as the existing design.<br />";
					} else $itemfound = true;
				}
				if (!$itemfound) {
					echo "The item corresponding to this code does not currently exist. You may create it using the <a href='quickitemcreate.php?code1=$code1&code2=$code2&op=$op'>Quick Creation Form</a> or make a full submission.<br />";
				} else {
					echo "This code belongs to item: " . $itemrow['name'] . "<br />";
				}
			}
		} else {
			echo "Error: one of the codes was left blank.<br />";
		}
	}

	echo "Code Combiner<br />";
	echo "Insert two codes and the operation to use.<br />";
	echo "<form action='alchemy.php' method='post'>First code: <input type='text' name='code1' /><br />";
	echo "Second code: <input type='text' name='code2' /><br />";
	echo "Operation: <select name='op'><option value='and'>&&</option><option value='or'>||</option></select><br />";
	echo "<input type='submit' value='Go!' /></form><br /><br />";
	echo "Alchemy<br />";
	echo "Insert a code to create an item from.<br />";
	echo "<form action='alchemy.php' method='post'>Code: <input type='text' name='alchcode' /><br />";
	echo "<input type='submit' value='Go!' /></form><br /><br />";
} else {
	echo "Log in to do alchemy.<br />";
}

require_once "footer.php";
?>
