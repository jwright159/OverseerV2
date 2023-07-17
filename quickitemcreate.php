<?php
$pagetitle = "Quick Item Creator";
$headericon = "/images/header/inventory.png";
require_once "header.php";
require_once "includes/designix.php"; ?>
<script>
    function validateForm() {
        var name = document.forms["itemcreate"]["name"].value;
        var description = document.forms["itemcreate"]["description"].value;
        if (description == '') {
            alert("Must have a description!");
            return false;
        } else {
        //Abstrati?
        var power = document.forms["itemcreate"]["power"].value;
        var aggrieve = document.forms["itemcreate"]["aggrieve"].value;
        var aggress = document.forms["itemcreate"]["aggress"].value;
        var assail = document.forms["itemcreate"]["assail"].value;
        var assault = document.forms["itemcreate"]["assault"].value;
        var abjure = document.forms["itemcreate"]["abjure"].value;
        var abuse = document.forms["itemcreate"]["abuse"].value;
        var accuse = document.forms["itemcreate"]["accuse"].value;
        var abstain = document.forms["itemcreate"]["abstain"].value;
        var checked = $("input[type=checkbox]:checked");
        }
        if (checked.length != 0) {
            var proceed = confirm("PLEASE CHECK THIS CAREFULLY.\n\nYou are creating the item " + name + " with the description '" + description + "' with one or more abstratii.\n\nIs this correct?");
        }
        else {
            var proceed = confirm("PLEASE CHECK THIS CAREFULLY.\n\nYou are creating the item " + name + " with the description '" + description + "' and NO abstratii.\n\nIs this correct?");
        }
        if (proceed == true) {
            return true;
        } else {
            return false;
        }
    }
</script>

<?php
function realpower($row) {
    $power = $row['power'];
    $bonuses = array(
        "aggrieve" => $row['aggrieve'],
        "aggress" => $row['aggress'],
        "assail" => $row['assail'],
        "assault" => $row['assault'],
        "abuse" => $row['abuse'],
        "accuse" => $row['accuse'],
        "abjure" => $row['abjure'],
        "abstain" => $row['abstain']
        );
    $power += max($bonuses);
    echo "<br />--testing for power of " . $row['name'] . " = " . strval($power) . "--<br />";
    if ($power == 0) { //no power, probably because the item isn't a weapon, so let's determine what its effective power would be based on grist cost
        $grists = $row['gristcosts'];
        $gristarray = array();
        $gristshit = explode( '|', $grists);
        array_pop($gristshit);
        foreach( $gristshit as $val ){
            $tmp = explode( ':', $val );
            $gristarray[ $tmp[0] ] = $tmp[1];
        }
        $gristcost = array_sum($gristarray);
        $power = ceil(sqrt($gristcost*8));
        echo "<br />--grist cost $gristcost = power $power--<br />";
    }
    if ($power == 0) $power = 1; //vo¬ov
    return $power;
}

if ($_SESSION['username'] != "") {
    $loaded = false;
    if (!empty($_GET['op'])) {
        $op = $_GET['op'];
        $aok = false;

        $code1 = $_GET['code1'];
		$code1Binary = breakdown($code1);
		if (substr_count($code1Binary, '0') <= 12) {
			echo 'The first code has a lot of holes punched... You feel like it might be best used in && alchemy. <br/>';
		} elseif (substr_count($code1Binary, '0') >= 36) {
			echo 'The first code doesn\'t have many holes punched... You feel like it might be best used in || alchemy. <br/>';
		}

        $code2 = $_GET['code2'];
		$code2Binary = breakdown($code2);
		if (substr_count($code2Binary, '0') <= 12) {
			echo 'The second code has a lot of holes punched. You feel like it might be best used in && alchemy. <br/>';
		} elseif (substr_count($code2Binary, '0') >= 36) {
			echo 'The second code doesn\'t have many holes punched. You feel like it might be best used in || alchemy. <br/>';
		}

		if ($op == "and") { // Begin new item code generation
			$code = andcombine($code1,$code2);
		} elseif ($op == "or") {
			$code = orcombine($code1,$code2);
		}
		
        $usableCode = true;
        $binaryString = breakdown($code);
		if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
            if ($op = 'or') {
				if (substr_count($binaryString, '0') <= 12) {
					echo 'The result code has too many holes punched, which makes for terrible alchemy. You try && alchemy instead. <br/>';
				} elseif (substr_count($binaryString, '0') >= 36) {
					echo 'The result code doesn\'t have enough holes punched, which makes for terrible alchemy. You try && alchemy instead. <br/>';
				}
				
                $newCode = andCombine($code1, $code2);
                $binaryString = breakdown($newCode);
                $op = 'and';
                if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
					echo 'That still didn\'t work! Try again with different items. <br/>';
                    $usableCode = false;
                } else {
                    $code = $newCode;
                }
            } elseif ($op = 'and') {
				if (substr_count($binaryString, '0') <= 12) {
					echo 'The result code has too many holes punched, which makes for terrible alchemy. You try || alchemy instead. <br/>';
				} elseif (substr_count($binaryString, '0') >= 36) {
					echo 'The result code doesn\'t have enough holes punched, which makes for terrible alchemy. You try || alchemy instead. <br/>';
				}

                $newCode = orCombine($code1, $code2);
                $binaryString = breakdown($newCode);
                $op = 'or';
                if (substr_count($binaryString, '0') <= 12 || substr_count($binaryString, '0') >= 36) {
					echo 'That still didn\'t work! Try again with different items. <br/>';
                    $usableCode = false;
                } else {
                    $code = $newCode;
                }
            }
        }
		
        if ($usableCode) {
            echo "Quick Creation Form<br><br><i>Information:</i>
    If you found a code that doesn't belong to an item, you can use this form to create one and have it available for use instantly!<br>
    The item will be session-bound; that is, only characters in your session can use it.<br>
    A member of the dev team will look at it in the future and finalize it, changing things if necessary so that it can be made available to everyone in the game.<br>
    An item can only be created in this way with a recipe in mind - this is so that it can be automatically balanced.<br>
    Current limitations: Effects cannot be directly added, including consumable effects. If you want to create a consumable, please use the full submission form for now.<br><br>";

            $i1result = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$code1' AND (session = 0 OR session = " . $charrow['session'] . ")");
            $i2result = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$code2' AND (session = 0 OR session = " . $charrow['session'] . ")");
            while ($i1rowo = mysqli_fetch_array($i1result)) {
                while ($i2rowo = mysqli_fetch_array($i2result)) {
                    $aok = true; //both items exist
                    $i1row = $i1rowo;
                    $i2row = $i2rowo;
                    if ($op == "and") echo "Recipe: " . $i1row['name'] . " && " . $i2row['name'] . "<br />";
                    elseif ($op == "or") echo "Recipe: " . $i1row['name'] . " || " . $i2row['name'] . "<br />";
                }
            }
            if ($aok) {
                $combine = "";
                if ($op == "and") {
                    $combine = andcombine($code1,$code2);
                } elseif ($op == "or") {
                    $combine = orcombine($code1,$code2);
                } else {
                    echo "Error: invalid operation.<br />";
                }
                if ($combine != "") {
                    $itemfound = false;
                    $itemresult = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE code = '$combine'");
                    while ($itemrow = mysqli_fetch_array($itemresult)) {
                        echo "Error: This combination already creates an existing item. Try changing the operation or one of the components.<br />";
                        $itemfound = true;
                    }
                    if (!$itemfound) {
                        $_SESSION['code1'] = $code1;
                        $_SESSION['code2'] = $code2;
                        $_SESSION['combineop'] = $op;
                        $loaded = true;
                        echo "<form action='quickitemgen.php' method='post' onsubmit='return validateForm()' id='itemcreate'>Fill out the below to create this item:<br />";
                        echo "Name: <input type='text' name='name' value='" . $itemrow['name'] . "' /><br />";
                        echo "Description:<br /><textarea id='itemcreate' name='description' rows='6' cols='40'>" . $itemrow['description'] . "</textarea><br />";
                        $power1 = realpower($i1row);
                        $power2 = realpower($i2row);
                        $ratio = max($power1,$power2) / min($power1,$power2);
                        $bonus = (((-1/3)*(pow($ratio,2)))-($ratio*3)+(199/3)-3)/100;
                        if ($bonus < 0) $bonus = 0;
                        $maxpower = ceil(($power1 + $power2) * (1 + $bonus));
                        echo "Max weapon power allowed from this combination (including highest bonus): $maxpower<br />";
                        $_SESSION['maxpower'] = $maxpower;
                        echo "Base power: <input type='text' name='power' value='" . strval($itemrow['power']) . "' /><br />";
                        echo "Bonuses:<table><tr><td>Aggrieve: <input type='text' name='aggrieve' value='" . strval($itemrow['aggrieve']) . "' /></td><td>Abuse: <input type='text' name='abuse' value='" . strval($itemrow['abuse']) . "' /></td></tr>";
                        echo "<tr><td>Aggress: <input type='text' name='aggress' value='" . strval($itemrow['aggress']) . "' /></td><td>Accuse: <input type='text' name='accuse' value='" . strval($itemrow['accuse']) . "' /></td></tr>";
                        echo "<tr><td>Assail: <input type='text' name='assail' value='" . strval($itemrow['assail']) . "' /></td><td>Abjure: <input type='text' name='abjure' value='" . strval($itemrow['abjure']) . "' /></td></tr>";
                        echo "<tr><td>Assault: <input type='text' name='assault' value='" . strval($itemrow['assault']) . "' /></td><td>Abstain: <input type='text' name='abstain' value='" . strval($itemrow['abstain']) . "' /></td></tr></table><br />";
                        $abstrati1 = explode(", ", $i1row['abstratus']);
                        $abstrati2 = explode(", ", $i2row['abstratus']);
                        $abstrati = array_merge($abstrati1, $abstrati2);
                        $abscount = 0;
                        $i = 0;
                        while (!empty($abstrati[$i])) {
                            if ($abstrati[$i] != "notaweapon") {
                                $abscount++;
                                $possiblekinds[$abscount] = $abstrati[$i];
                            }
                            $i++;
                        }
                        if ($abscount > 0) {
                            echo "Kind Abstrati (check all that apply, or none if the item is not a weapon):<br />";
                            $i = 1;
                            while ($i <= $abscount) {
                                echo "<input type='checkbox' name='abstratus" . strval($i) . "' value='" . $possiblekinds[$i] . "' />" . $possiblekinds[$i] . "<br />";
                                $i++;
                            }
                            $_SESSION['abscount'] = $abscount;
                        } else {
                            echo "This combination has no potential kind abstrati.<br />";
                        }
                        $wearable1 = explode(", ", $i1row['wearable']);
                        $wearable2 = explode(", ", $i2row['wearable']);
                        $wearable = array_merge($wearable1, $wearable2);
                        $wearcount = 0;
                        $i = 0;
                        while (!empty($wearable[$i])) {
                            if ($wearable[$i] != "none") {
                                $wearcount++;
                                $possiblewears[$wearcount] = $wearable[$i];
                            }
                            $i++;
                        }
                        $_SESSION['wearcount'] = $wearcount;
                        //if ($wearcount > 0) {
                        //    echo "Wearable slots (check all that apply, or none if the item is not a wearable):<br />Note: A wearable takes up ALL checked slots when equipped, not just one<br />";
                        //    $i = 1;
                        //    while ($i <= $abscount) {
                        //        echo "<input type='checkbox' name='wearable" . strval($i) . "' value='" . $possiblewears[$i] . "' />" . $possiblewears[$i] . "<br />";
                        //        $i++;
                        //    }
                        //} else {
                        //    echo "This combination has no potential as a wearable.<br />";
                        //}
                        echo "Item size: <select name='size'>
<option value='miniature'>miniature (1)</option>
<option value='tiny'>tiny (5)</option>
<option value='small'>small (10)</option>
<option value='average' selected>average (20) - most one-handed weapons</option>
<option value='large'>large (40) - two-handed weapons</option>
<option value='huge'>huge (100) - items this size and up can't be equipped</option>
<option value='immense'>immense (250)</option>
<option value='ginormous'>ginormous (1000)</option></select><br />";
                        echo "Grist costs:<br />For each grist type that you want this item to use, put a number representing its weight among all grists.<br />
For example, putting a 2 in grist A and a 3 in grist B will make the item's Grist A cost 2/5 of the total cost, and Grist B 3/5 of the total cost (2+3=5 in total).<br />";
                        $possiblegrists = explode("|", $charrow['grists']); //only list grist types the user has
                        $i = 0;
                        while (!empty($possiblegrists[$i])) {
                            $thisgrist = explode(":", $possiblegrists[$i]);
                            if($thisgrist[0]!='Artifact') echo $thisgrist[0] . " - <input type='text' name='" . $thisgrist[0] . "' /><br />";
                            $i++;
                        }
                        echo "Comments about this item - anything else you'd like to say to the devs about the item, such as possible effects or numerical themes in grist cost<br />";
                        echo "<textarea form='itemcreate' name='comments' rows='6' cols='40'></textarea>";
                        echo "<input type='submit' value='Create!' />";
                        echo "</form>";
                    }
                }
            } else {
                echo "Error: one or both of the codes you entered don't belong to an existing item.<br />";
            }
        }
    }
    if (!$loaded) {
        echo "You do not have a combination loaded. Please input two codes and the operation to use.<br />";
        echo "<form action='quickitemcreate.php' method='get'>First code: <input type='text' name='code1' /><br />";
        echo "Second code: <input type='text' name='code2' /><br />";
        echo "Operation: <select name='op'><option value='and'>&&</option><option value='or'>||</option></select><br />";
        echo "<input type='submit' value='Go!' /></form><br />";
    }
    
    echo "If you find this editor too restrictive, you can use the <a href='fullitemcreate.php'>full item submission form</a> to submit an item idea the classic way.<br />";
} else {
    echo "Log in to create items.";
}

require_once "footer.php";
?>
