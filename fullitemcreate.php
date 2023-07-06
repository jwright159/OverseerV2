<?php
$pagetitle = "Full Item Creator";
$headericon = "/images/header/inventory.png";
require_once "header.php";
require_once "includes/designix.php";

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
		"abstain" => $row['abstain']);
	$power += max($bonuses);
	return $power;
}

function lookup($code,$table) {
  global $connection;
  $codevar = "code";
  $result = mysqli_query($connection, "SELECT * FROM `$table` WHERE `$codevar` = '$code'");
  //echo "SELECT * FROM `$table` WHERE `$codevar` = '$code'<br />";
  return mysqli_fetch_array($result);
}

function lookleft($name) {
  global $connection;
  $name = mysqli_real_escape_string($connection, $name);
  $result = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `name` = '$name'");
  $row = mysqli_fetch_array($result);
  if (empty($row['name'])) {
    $result = mysqli_query($connection, "SELECT * FROM `Feedback` WHERE `name` = '$name'");
    $row = mysqli_fetch_array($result);
  }
  return $row;
}

if ($_SESSION['username'] != "") {
  
  $grist = initGrists();
	$totalgrists = count($grist);
  
  $bonusarray = Array("aggrieve","aggress","assail","assault","abuse","accuse","abjure","abstain");
    
  if (!empty($_POST['name'])) {
    $con = $connection; //sooo lazy. why isn't it just called this to begin with
    $invreason = "";
    $username = mysqli_real_escape_string($con, $accrow['username']);
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $code = $_POST['code']; //add a regexp match later to make sure this is a valid code
    if (!empty($code) && !preg_match("/^[a-zA-Z0-9!?]{8}$/", $code)) {
      $invreason .= "Code must be exactly 8 characters and consist only of upper/lowercase letters, numbers, and/or ?!<br />";
    } else {
      $erow = lookup($code,"Captchalogue");
      if (!empty($erow['name']) && $erow['old'] != 1) {
        $invreason .= "Provided code already belongs to an existing item: " . $erow['name'] . "<br />";
      }
    }
    if (!empty($_POST['recipe1']) && !empty($_POST['recipe2'])) {
      if ($_POST['op'] == "and") $op = " && ";
      else $op = " || ";
      $erow = lookleft($_POST['recipe1']);
      if (empty($erow['name'])) {
        $invreason .= "No item found matching first given component: " . $_POST['recipe1'] . "<br />";
      }
      $erow = lookleft($_POST['recipe2']);
      if (empty($erow['name'])) {
        $invreason .= "No item found matching second given component: " . $_POST['recipe2'] . "<br />";
      }
      $recipe = mysqli_real_escape_string($con, $_POST['recipe1'] . $op . $_POST['recipe2']);
    } else {
      $recipe = "none given";
    }
    $power = strval($_POST['power']);
    if (abs(intval($power)) > 9999) $invreason .= "Base power level cannot be higher than 9999 or lower than -9999<br />";
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $comments = mysqli_real_escape_string($con, $_POST['comments']);
    $ctime = time();
    $bonus = '';
    $b = 0;
    while (!empty($bonusarray[$b])) {
      if (!empty($_POST[$bonusarray[$b]])) {
        $bonus .= $bonusarray[$b] . ':' . strval($_POST[$bonusarray[$b]]) . '|';
        if (abs(intval($_POST[$bonusarray[$b]])) > 9999) $invreason .= $bonusarray[$b] . " cannot be higher than 9999 or lower than -9999<br />";
      }
      $b++;
    }
    $gristcosts = '';
    $i = 0;
    while (!empty($grist[$i]['name'])) { //go through the grists now that they're being done differently
      $gristnam = $grist[$i]['name'];
      if (!empty($_POST[$gristnam])) {
        $gristcosts .= $gristnam . ":" . strval($_POST[$gristnam]) . "|";
      }
      $i++;
    }
    if (!empty($_POST['abstratus'])) $abs = mysqli_real_escape_string($con, $_POST['abstratus']);
    else $abs = "notaweapon";
    if (!empty($_POST['wearable'])) $wear = mysqli_real_escape_string($con, $_POST['wearable']);
    else $wear = "none";
    $size = $_POST['size'];
    if (!empty($_POST['consume'])) $consume = "1";
    else $consume = "0";
    if (!empty($_POST['base'])) $base = "1";
    else $base = "0";
    if (!empty($_POST['lootonly'])) $loot = "1";
    else $loot = "0";
    if (!empty($_POST['refrance'])) $ref = "1";
    else $ref = "0";
    if ($invreason != "") {
      echo "This submission is invalid for the following reason(s):<br />" . $invreason;
    } else {
      if($power=='') $power=0;
      $query = "INSERT INTO Feedback (`user`,`name`,`code`,`recipe`,`power`,`description`,`comments`,`lastupdated`,`bonuses`,`grists`,`abstratus`,`wearable`,`size`,`consumable`,`catalogue`,`lootonly`,`refrance`) VALUES ('$username','$name','$code','$recipe',$power,'$description','$comments',$ctime,'$bonus','$gristcosts','$abs','$wear','$size',$consume,$base,$loot,$ref);";
      if ($accrow['modlevel'] >= 10) echo $query . "<br />";
      mysqli_query($con, $query);
      echo "Item submitted successfully!<br />";
    }
  }
    
  $loaded = false;
  $code = "00000000";
	echo "Full Creation Form<br /><br /><i>Information:</i>
	If you want to have as much control as possible over the properties of your item, this is the form you'll want to use.<br />
  Here are some quick reminders:<br />
  - If you have a recipe, the code (if provided) should match the actual result of combining the two items involved.<br />
  - If, when combining two items, the resulting code matches one of the two items used, this is not a bug; the two items cannot be combined.<br />
  - Both items in the recipe must exist, either as an item available to all sessions or as a submission made with this form.<br />
  - Please try to ensure that the item you are submitting is not too similar in design to an existing item. We don't need 500 chain swords, for example. The less 'generic' the item, the less you have to worry about.<br />
  - Your submission will not be added to the game immediately; it will be added to the queue of user submissions to be reviewed by the staff. Keep an eye on it in case additional information is requested. While we reserve the right to change or delete a submission for any reason, we do prioritize input from the submitter.<br />
  <strike>- no bladekinds plz</strike><br /><br />";
  
  if (!empty($_GET['codesearch'])) {
    $alreadyexists = false;
    $code = $_GET['codesearch'];
    if (!preg_match("/^[a-zA-Z0-9!?]{8}$/", $code)) {
      echo "Invalid code. Code must be exactly 8 characters and consist only of upper/lowercase letters, numbers, and/or ?!";
    } else {
      echo "Searching all databases for items with code: " . $code . "<br />";
      $itemrow = lookup($code,"Feedback"); //look up in feedback to see if someone else submitted this
      if (!empty($itemrow['name'])) {
        echo "A <a href='submissions.php?view=" . strval($itemrow['ID']) . "'>submission</a> already exists with this code. You may submit another, but keep in mind only one of them will be accepted into the game.<br />";
        //don't keep from loading other existing versions
      }
      $itemrow = lookup($code,"Captchalogue"); //next look up in current database
      if (!empty($itemrow['name'])) { //item exists in v2
        if ($itemrow['session'] != 0) { //item is not generally available yet
          echo "Item found (Quick Creation): " . $itemrow['name'];
          $loaded = true;
        } elseif ($itemrow['old'] == 1) {
          echo "Item found (Old): " . $itemrow['name'];
          $loaded = true;
        } else {
          echo "An item matching this code already exists in the current iteration of the game and is available to all.";
          $alreadyexists = true;
        }
      } else {
        $itemrow = lookup($code,"Feedback_Old");
        if (!empty($itemrow['name'])) { //item was a submission on the old site
          echo "Item found (Feedback Archive): " . $itemrow['name'];
          if (!empty($itemrow['bonuses'])) {
            $b = 0;
            $bonuss = explode("|", $itemrow['bonuses']);
            while (!empty($bonuss[$b])) {
              $bonust = explode(":", $bonuss[$b]);
              $itemrow[$bonust[0]] = $bonust[1];
              $b++;
            }
          }
          $loaded = true;
        }
      }
      if (!$loaded && !$alreadyexists) {
        echo "No item found with this code in any form. You may create one from scratch or search a different code.";
      }
    }
    echo "<br />";
  }
  
  if (!$loaded) {
		echo "Optionally, you may input a code here to search all databases for it. Information of the item in its most relevant state will auto-populate the fields for you to edit at your leisure.<br />";
		echo "<form action='fullitemcreate.php' method='get'>Code: <input type='text' name='codesearch' /><br />";
		echo "<input type='submit' value='Go!' /></form><br />";
	}
    
  $sizearray = Array("miniature","tiny","small","average","large","huge","immense","ginormous");
  $sizedarray = Array("miniature (1)", "tiny (5)", "small (10)", "average (20) - most one-handed weapons", "large (40) - two-handed weapons", "huge (100) - items this size and up can't be equipped", "immense (250)", "ginormous (1000)");
  if (empty($itemrow['size'])) $itemrow['size'] = "average";
  
  $b = 0;
  while (!empty($bonusarray[$b])) {
    if (empty($itemrow[$bonusarray[$b]])) {
      $itemrow[$bonusarray[$b]] = 0;
    }
    $b++;
  }
  
  echo "<form action='fullitemcreate.php' method='post' id='itemcreate'>Fill out the below to submit this item:<br />";
  echo "Code: <input type='text' name='code' value='" . $code . "' /><br />";
  echo "Name: <input type='text' name='name' value='" . $itemrow['name'] . "' /><br />";
  echo "Recipe: <input type='text' name='recipe1' value='First item name' />
  <select name='op'><option value='and'>&&</option><option value='or'>||</option></select>
  <input type='text' name='recipe2' value='Second item name' /><br />";
  echo "Properties:<br />
  <input type='checkbox' name='consume' value='yes' /> Item can be consumed (specify effect in comments)<br />
  <input type='checkbox' name='base' value='yes' /> Item is available from base item catalogue<br />
  <input type='checkbox' name='lootonly' value='yes' /> Item is meant to be found exclusively in dungeons<br />
  <input type='checkbox' name='refrance' value='yes' /> Item is a direct reference to non-Homestuck fictional media<br />";
  echo "Description:<br /><textarea id='itemcreate' name='description' rows='6' cols='40'>" . $itemrow['description'] . "</textarea><br />";
  echo "Base power: <input type='text' name='power' value='" . strval($itemrow['power']) . "' /><br />";
  echo "Bonuses:<table><tr><td>Aggrieve: <input type='text' name='aggrieve' value='" . strval($itemrow['aggrieve']) . "' /></td><td>Abuse: <input type='text' name='abuse' value='" . strval($itemrow['abuse']) . "' /></td></tr>";
  echo "<tr><td>Aggress: <input type='text' name='aggress' value='" . strval($itemrow['aggress']) . "' /></td><td>Accuse: <input type='text' name='accuse' value='" . strval($itemrow['accuse']) . "' /></td></tr>";
  echo "<tr><td>Assail: <input type='text' name='assail' value='" . strval($itemrow['assail']) . "' /></td><td>Abjure: <input type='text' name='abjure' value='" . strval($itemrow['abjure']) . "' /></td></tr>";
  echo "<tr><td>Assault: <input type='text' name='assault' value='" . strval($itemrow['assault']) . "' /></td><td>Abstain: <input type='text' name='abstain' value='" . strval($itemrow['abstain']) . "' /></td></tr></table><br />";
  echo "Kind Abstratus/i: <input type='text' name='abstratus' value='" . $itemrow['abstratus'] . "' /><br />";
  echo "Wearable slot(s): <input type='text' name='wearable' value='" . $itemrow['wearable'] . "' /><br />Note: A wearable takes up ALL listed slots when equipped, not just one<br />";
  echo "Item size: <select name='size'>";
  $s = 0;
  while (!empty($sizearray[$s])) {
    echo "<option value='" . $sizearray[$s] . "'";
    if ($itemrow['size'] == $sizearray[$s]) echo " selected";
    echo ">" . $sizedarray[$s] . "</option>";
    $s++;
  }
  echo "</select><br />";
  echo "Grist costs:<br />Put an approximation of what costs you think this item should have. Note that your numbers will probably be tweaked, but any ratios or numerical schemes we will honor as best as we can.<br /><table cellpadding='0' cellspacing='0'><tbody>";
  $i = 0;
  $col = 1;
  while (!empty($grist[$i]['name'])) { //go through the grists now that they're being done differently
    if ($col == 1) echo '<tr>';
    echo '<td align="right">';
    $gristnam = $grist[$i]['name'];
    if ($grist[$i]['gif'] == 1) $gristimg = $gristnam . ".gif";
    else $gristimg = $gristnam . ".png";
    echo "<img src='images/grist/".$gristimg."' height='15' width='15' alt = 'xcx'/>";
    echo $gristnam . '(' . strval($grist[$i]['tier']) . '):</td><td> <input type="text" name="' . $gristnam . '"></td>';
    $col++;
    if ($col == 4) {
      echo '</tr>';
      $col = 1;
    }
    $i++;
  }
  echo '</tbody></table>';
  echo "Comments about this item - anything else you'd like to say to the devs about the item. Proposed effects, consumable or otherwise, go here.<br />";
  echo "<textarea form='itemcreate' name='comments' rows='6' cols='40'></textarea>";
  echo "<input type='submit' value='Submit!' />";
  echo "</form>";

	echo "If you just want a simple item to become available to you ASAP without the frills and the fancy effects, you can use the <a href='quickitemcreate.php'>Quick Item Creator</a>.<br />";
} else {
	echo "Log in to create items.";
}

require_once "footer.php";
?>

