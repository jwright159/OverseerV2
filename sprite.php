<?php
$pagetitle = "Sprite";
$headericon = "/images/header/chummy.png";
require_once("header.php");

if (empty($_SESSION['character'])) {
	echo "Choose a character to interact with your sprite.<br />";
} elseif ($charrow['sprite'] == 0) {
	echo "<div class='alert alert-warning' role='alert'>You don't have a sprite yet!</div>";
} else {
	$striferow = loadStriferow($charrow['wakeself']);
	$spriterow = loadStriferow($charrow['sprite']);

	if (!empty($_POST['protoobj'])) { //player is prototyping something
		$power = $_POST['protopower'];
		if ($power > 999 || $power < -999) {
			echo "You cannot prototype with more than 999 power or less than -999 power at one time.<br />";
		} elseif ($charrow['inmedium'] == 1 && !empty($charrow['proto_obj1']) && $power > $spriterow['maxpower']) { //second prototyping, too much power
			echo "The sprite dodges the object! It seems that it can't sustain that much power now that it has acquired a semi-corporeal form.<br />";
		} elseif ($charrow['inmedium'] == 1 && empty($charrow['proto_obj1']) && $power > 333) { //first prototyping post entry, adding limit to stop people with 2k power out of the gate
			echo "The sprite dodges the object! It seems that it can't sustain that much power now that it has acquired a semi-corporeal form.<br />";
		} elseif (empty($charrow['proto_obj1']) || empty($charrow['proto_obj2'])) {
			if (empty($_POST['protoname'])) $name = "Sprite";
			else $name = $_POST['protoname'];
			$obj = $_POST['protoobj'];
			if (empty($_POST['protoname'])) $desc = "A prototyped sprite.";
			else $desc = $_POST['protodesc'];
			if ($charrow['inmedium'] == 0) {
				if (empty($_POST['protoenemydesc'])) $enemydesc = "It has appearance aspects from $obj.";
				else $enemydesc = $_POST['protoenemydesc'];
			}
			$powerchunk = floor($_POST['protopower'] / 5); //the amount that goes into each effect. Any remainder will stay with the main "chunk".
			$i = 1;
			$effectstr = $spriterow['effects'];
			while ($i <= 4) { //4 = max number of effects
				$blank = false;
				$nonstandardstack = false;
				$thiseff = "effect" . strval($i);
				unset($exist); //Paranoia: ensure effects from previous calculations aren't tampered with!
				$exist = surgicalSearch($effectstr, "|" . $_POST[$thiseff]); //see if this effect already exists, so that we can stack it
				switch ($_POST[$thiseff]) {
					case "TIMESTOP":
						$chance = floor($powerchunk / 20);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "TIMESTOP@2:" . strval($chance) . ":Time:%USER% freezes %TARGET% in time!|";
						break;
					case "HOPELESS":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "HOPELESS@0:" . strval($chance) . ":Hope:%USER%'s attack causes %TARGET% to lose hope!|";
						break;
					case "KNOCKDOWN":
						$chance = floor($powerchunk / 5);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "KNOCKDOWN:" . strval($chance) . "|";
						break;
					case "WATERYGEL":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "WATERYGEL@4:" . strval($chance) . ":Life:%USER%'s attack lowers %TARGET%'s gel viscosity!|";
						break;
					case "POISON":
						$severity = floor($powerchunk / 10);
						if ($severity < 1) $severity = 1;
						if (!empty($exist[0][0])) { //since poison increases severity rather than chance, handle stacking here
							$boom = explode("@", $exist[0][0]);
							$boom[2] += $severity;
							$replacethis = implode(":", $exist[0]);
							$exist[0] = implode("@", $boom);
							$replacewith = implode(":", $exist[0]);
							$effectstr = str_replace($replacethis, $replacewith, $effectstr);
							$nonstandardstack = true;
						} else $effectstr .= "POISON@5@" . strval($severity) . ":10:Doom:%USER%'s attack poisons %TARGET%!|";
						break;
					case "BLEEDING":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "BLEEDING@5:" . strval($chance) . ":Blood:%USER%'s attack inflicts a deep wound on %TARGET%!|";
						break;
					case "DISORIENTED":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "DISORIENTED@4:" . strval($chance) . ":Mind:%TARGET% is disoriented by %USER%'s attack!|";
						break;
					case "DISTRACTED":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "DISTRACTED@2:" . strval($chance) . ":Mind:%TARGET% is distracted by %USER%!|";
						break;
					case "ENRAGED":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "ENRAGED@4:" . strval($chance) . ":Rage:%TARGET% is enraged by %USER%'s attack!|";
						break;
					case "MELLOW":
						$chance = floor($powerchunk / 10);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "MELLOW@4:" . strval($chance) . ":Rage:%USER% causes %TARGET% to chill out, man...|";
						break;
					case "GLITCHED":
						$chance = floor($powerchunk / 25); //glitchin is powerful stuff
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "GLITCHED@0:" . strval($chance) . ":Void:%USER%'s attack causes %TARGET% to glitch out!|";
						break;
					case "UNLUCKY":
						$chance = floor($powerchunk / 20);
						if ($chance < 1) $chance = 1;
						if (empty($exist[0][0])) $effectstr .= "UNLUCKY@0:" . strval($chance) . ":Light:%USER% introduces %TARGET% to a world of misfortune!|";
						break;
					default: //none, blank, or an unknown effect. Nothing happens.
						$blank = true;
						break;
				}
				if (!$nonstandardstack && !empty($exist[0][0])) { //effect was added to the sprite at some point already, so it stacks with the existing effect
					$replacethis = implode(":", $exist[0]);
					$exist[0][1] += $chance;
					$replacewith = implode(":", $exist[0]);
					$effectstr = str_replace($replacethis, $replacewith, $effectstr);
				}
				if (!$blank) $power -= $powerchunk;
				$i++;
			}
			$maxpower = $power + $spriterow['maxpower']; //add the new power to the current base power
			$maxhealth = abs($maxpower * 10) + 10; //absolute value in case of negative prototyping lol
			$health = $maxhealth; //Prototyping fully heals
			mysqli_query($connection, "UPDATE Strifers SET name = '" . mysqli_real_escape_string($connection, $name) . "', description = '" . mysqli_real_escape_string($connection, $desc) . "', power = $maxpower, maxpower = $maxpower, health = $health, maxhealth = $maxhealth, effects = '" . mysqli_real_escape_string($connection, $effectstr) . "' WHERE ID = " .strval($spriterow['ID']));
			$spriterow['name'] = $name;
			$spriterow['description'] = $desc;
			$spriterow['maxpower'] = $maxpower;
			$spriterow['health'] = $health;
			$spriterow['maxhealth'] = $maxhealth;
			if (empty($charrow['proto_obj1'])) $thisobj = 'proto_obj1';
			else $thisobj = 'proto_obj2';
			if ($charrow['inmedium'] == 0) { //Character not yet in medium: prototyping also gets added to enemies.
				$effectstr = "POWER:" . strval($power) . "|DESCRIPTION:" . $enemydesc . "|" . $effectstr;
				$pre = $charrow['proto_preentry'] + 1;
				mysqli_query($connection, "UPDATE Characters SET proto_preentry = $pre, $thisobj = '" . mysqli_real_escape_string($connection, $obj) . "', proto_effects = '" . mysqli_real_escape_string($connection, $effectstr) . "' WHERE ID = $cid");
			} else { //Character in medium, just update the object string so that we know that a prototyping happened
				mysqli_query($connection, "UPDATE Characters SET $thisobj = '" . mysqli_real_escape_string($connection, $obj) . "' WHERE ID = $cid");
			}
			$charrow[$thisobj] = $obj;
			echo "You successfully prototype your sprite with $obj!<br />";
		} else echo "Your sprite is already fully developed! It cannot accept further prototypings.<br />";
	} elseif (!empty($_POST['protoname']) || !empty($_POST['protodesc'])) { //player's sprite is fully prototyped, but wants to rename/redescribe
		if (empty($_POST['protoname'])) $name = $spriterow['name'];
		else $name = $_POST['protoname'];
		if (empty($_POST['protoname'])) $desc = $spriterow['description'];
		else $desc = $_POST['protodesc'];
		mysqli_query($connection, "UPDATE Strifers SET name = '" . mysqli_real_escape_string($connection, $name) . "', description = '" . mysqli_real_escape_string($connection, $desc) . "' WHERE ID = " .strval($spriterow['ID']));
		$spriterow['name'] = $name;
		$spriterow['description'] = $desc;
		echo "Sprite name and/or description updated.<br />";
	}

	echo "Sprite interaction<br /><br />Your sprite: ";
	echo "<b>" . $spriterow['name'] . "</b><br />";
	echo $spriterow['description'] . "<br />";
	echo "Power level: " . $spriterow['maxpower'] . "<br />";
	echo "Health vial: ";
	$health = ceil(($spriterow['health'] / $spriterow['maxhealth']) * 100);
	echo $health . "%<br />";
	if ($striferow['strifeID'] == $spriterow['strifeID']) { //Sprite is in the user's party
		echo "Currently: in party.<br />";
	} else echo "Currently: idle.<br />";
	echo "<br />";
	if (empty($charrow['proto_obj1']) || empty($charrow['proto_obj2'])) { //Player can still prototype (at least one of the prototype entries is empty)
		echo "<form action='sprite.php' id='spriteproto' method='post'>Prototype sprite:<br />";
		echo "Object to prototype: <input type='text' name='protoobj' /><br />";
		echo "New sprite name: <input type='text' name='protoname' /><br />";
		echo "New sprite description (will overwrite the current one):<br /><textarea name='protodesc' rows='6' cols='40' form='spriteproto'></textarea><br />";
		if ($charrow['inmedium'] == 0) {
			echo "Description snippet given to enemies that inherit this prototyping (optional):<br /><textarea name='protoenemydesc' rows='6' cols='40' form='spriteproto'></textarea><br />";
		}
		echo "Added power level (max 999): <input type='text' name='protopower' /><br />";
		echo "Effect slots: You can give your sprite up to 4 unique effects per prototyping. Each effect slot counts for 1/5 of the sprite's base power; choosing an effect will lower the base power by that much and convert it to effect strength. Filling multiple slots with the same effect will make that effect more powerful.<br />";
		$effects = "<option value='none'>None (no base power deduction for this slot)</option>
		<option value='TIMESTOP'>Timestop</option>
		<option value='HOPELESS'>Hopeless</option>
		<option value='KNOCKDOWN'>Knockdown</option>
		<option value='WATERYGEL'>Watery Health Gel</option>
		<option value='POISON'>Poison</option>
		<option value='BLEEDING'>Bleeding</option>
		<option value='DISORIENTED'>Disoriented</option>
		<option value='DISTRACTED'>Distracted</option>
		<option value='ENRAGED'>Enraged</option>
		<option value='MELLOW'>Mellow</option>
		<option value='GLITCHED'>Glitched Out</option>
		<option value='UNLUCKY'>Misfortune</option>";
		echo "<select name='effect1'>" . $effects . "</select><br />";
		echo "<select name='effect2'>" . $effects . "</select><br />";
		echo "<select name='effect3'>" . $effects . "</select><br />";
		echo "<select name='effect4'>" . $effects . "</select><br />";
		echo "<input type='submit' value='Prototype it!' /></form><br>";
		echo '<b><span style=color:red>!!!WARNING: ONCE ASSIGNED, YOUR SPRITE PROTOTYPE CANNOT BE CHANGED!!!</span></b>';
	} else { //player can't prototype, but let them rename/redescribe if desired
		echo "Your sprite is fully prototyped, but you may still edit its name and description if you desire:<br />";
		echo "<form action='sprite.php' id='spriteproto' method='post'>";
		echo "New sprite name: <input type='text' name='protoname' /><br />";
		echo "New sprite description (will overwrite the current one):<br /><textarea name='protodesc' rows='6' cols='40' form='spriteproto'></textarea><br />";
		echo "<input type='submit' value='Edit' /></form>";
	}
}

require_once("footer.php");
?>
