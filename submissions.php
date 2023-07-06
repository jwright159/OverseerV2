<?php
$pagetitle = "Item Submissions";
$headericon = "/images/header/inventory.png";
require_once "header.php";

function updateSubmission($subid) {
  global $connection;
	$currenttime = time();
	mysqli_query($connection, "UPDATE `Feedback` SET `lastupdated` = $currenttime WHERE `Feedback`.`ID` = $subid ;");
	$feedrow['lastupdated'] = $currenttime;
}

if (empty($_SESSION['username'])) {
  echo "Log in to view item submissions.</br>";
} else {
echo "<!DOCTYPE html><html><head><style>itemcode{font-family:'Courier New'}</style><style>normal{color: #111111;}</style><style>urgent{color: #0000CC;}</style><style>defunct{color: #CC0000;}</style><style>clarify{color: #CCCC00;}</style><style>greenlit{color: #00AA00;}</style><style>suspended{color: #999999;}</style><style>randomized{color: #EE6606;}</style><style>halp{color: #FFFFFF;}</style></head><body>";
  if (empty($_GET['page'])) {
    $page = 1;
    } else {
    $page = intval($_GET['page']);
    }

  if (empty($_GET['mode'])) {
    $mode = "none";
    } else {
    $mode = $_GET['mode'];
    }

  if (empty($_GET['sort'])) {
    $sort = "id";
    } else {
    $sort = $_GET['sort'];
    }

  if (empty($_GET['order'])) {
  	if ($sort == "name") $order = "ASC";
  	else $order = "DESC";
    } else {
    $order = $_GET['order'];
    }

  if (!empty($_POST['delete'])) {
    $feedresult = mysqli_query($connection, "SELECT * FROM `Feedback` WHERE `Feedback`.`ID` = '" . strval($_POST['delete']) . "' ;");
    $feedrow = mysqli_fetch_array($feedresult);
    if ($feedrow['user'] == $username || $accrow['modlevel'] >= 3) {
      if ($accrow['modlevel'] >= 99) echo $feedrow['user'] . " - Item suggestion. " . $feedrow['name'] . ": " . $feedrow['description'] . ". Made from: " . $feedrow['recipe'] . " with code " . $feedrow['code'] . " and suggested power level " . strval($feedrow['power']) . ". Additional comments: " . $feedrow['comments'] . " User comments: " . $feedrow['usercomments'] . "; </br>";
        mysqli_query($connection, "DELETE FROM `Feedback` WHERE `Feedback`.`ID` = '" . strval($_POST['delete']) . "' ;");
				echo 'Submission deleted.</br>';
				//Mod message log not currently implemented
				//$modmsg = $username . " (Level " . strval($accrow['modlevel']) . " Mod) <b>deleted submission</b>";
				//logModMessage($modmsg, 0);
    } else echo "You don't have permission to delete that submission (need mod level 3).</br>";
  }

  if (!empty($_POST['moderatethis'])) {
    $feedresult = mysqli_query($connection, "SELECT * FROM `Feedback` WHERE `Feedback`.`ID` = '" . strval($_POST['moderatethis']) . "' ;");
    $feedrow = mysqli_fetch_array($feedresult);
    if ($accrow['modlevel'] >= 1) {
      if (!empty($_POST['modaction'])) $dostring = $_POST['modaction'];
      else $dostring = "";
      if ($dostring == "clear") {
        if ($accrow['modlevel'] >= 2) {
          mysqli_query($connection, "UPDATE `Feedback` SET `defunct` = 0, `clarify` = 0, `greenlight` = 0, `suspended` = 0, `halp` = 0 WHERE `Feedback`.`ID` = '" . strval($_POST['moderatethis']) . "' ;");
          echo 'All flags cleared.</br>';
        } else echo "Your mod level is not yet high enough to clear or overwrite existing flags (need level 2).<br />";
      } else {
        if (!empty($dostring)) {
          if (!empty($_POST['body'])) {
            $aok = false;
            if ($feedrow['defunct'] == 1 || $feedrow['clarify'] == 1 || $feedrow['greenlight'] == 1 || $feedrow['suspended'] == 1 || $feedrow['halp'] == 1) {
              if ($accrow['modlevel'] >= 2) {
                $aok = true;
              } else echo "Your mod level is not yet high enough to clear or overwrite existing flags (need level 2).<br />";
            } else {
              $aok = true;
            }
            if ($aok == true) {
              mysqli_query($connection, "UPDATE `Feedback` SET `defunct` = 0, `clarify` = 0, `greenlight` = 0, `suspended` = 0, `halp` = 0 WHERE `Feedback`.`ID` = '" . strval($_POST['moderatethis']) . "' ;");
              mysqli_query($connection, "UPDATE `Feedback` SET `$dostring` = 1 WHERE `Feedback`.`ID` = '" . strval($_POST['moderatethis']) . "' ;");
              if($dostring=="greenlight") {
	              $playersql = mysqli_query($connection, "SELECT * FROM Users WHERE username ='" . $feedrow['user'] . "';"); //should give me the user row
	              $playerarray = mysqli_fetch_array($playersql);
	              $playerchars = explode("|", $playerarray['characters']);
	              array_pop($playerchars); //remove phantom character from the end
	              foreach($playerchars as $character){
	              	incrementStat(getChar($character), 'itemapproved');
	              	sendAchievement(getChar($character), 'itemsub'); //sends achievement to each character
	              }
	          }
              echo 'Submission moderated.</br>';
              if ($dostring == "halp") echo '<iframe width="1" height="1" frameborder=false src="http://www.youtube.com/embed/0ApstMKNEMI?autoplay=1"></iframe>';
            }
          } else echo "You must give some kind of reasoning via comment if you want to set a flag.</br>";
        } else echo 'You did not select an action.</br>';
      }
    } else echo "You don't have permission to moderate item submissions (need mod level 1).</br>";
  }

  //first thing's first, view the submission if the player clicked on one
  if (!empty($_GET['view'])) {
    $feedresult = mysqli_query($connection, "SELECT * FROM `Feedback` WHERE `Feedback`.`ID` = '" . mysqli_real_escape_string($connection, strval($_GET['view'])) . "' ;");
    $feedrow = mysqli_fetch_array($feedresult);
    if ($feedrow['ID'] == $_GET['view']) {
        if (!empty($_POST['vote'])) {
	  if ($feedrow['user'] != $username) {
	    if (strrpos($feedrow['likers'], $username) === false) {
	      mysqli_query($connection, "UPDATE `Feedback` SET `likes` = '" . strval($feedrow['likes'] + 1) . "' WHERE `Feedback`.`ID` = '" . mysqli_real_escape_string($connection, strval($_GET['view'])) . "' ;");
	      $feedrow['likes']++;
	      echo "Your vote has been cast.</br>";
	      $newlikerlist = $feedrow['likers'] . $username . "|";
	      mysqli_query($connection, "UPDATE `Feedback` SET `likers` = '" . $newlikerlist . "' WHERE `Feedback`.`ID` = '" . mysqli_real_escape_string($connection, strval($_GET['view'])) . "' ;");
	      } else echo "You have already cast a vote for this submission.</br>";
	    } else echo "You can't vote up your own submission.</br>";
	  }
	if (!empty($_POST['body'])) {
		if ($accrow['modlevel'] >= 0) {
	  $realbody = $_POST['body']; //start cleaning up body, remove HTML and do the liney thing
	  $realbody = str_replace("<", "", $realbody);
	  $realbody = str_replace(">", "", $realbody);
	  $realbody = str_replace("|", "THIS IS A LINE", $realbody);
	  $exstring = ": ";
	  if ($accrow['modlevel'] > 0) $exstring = " (Level " . strval($accrow['modlevel']) . " Mod): ";
	  if ($accrow['modlevel'] >= 99) $exstring = " (Developer): ";
	  if ($accrow['modlevel'] >= 10) $exstring = " (G. Moderator): ";
	  if ($feedrow['user'] == $username) $exstring = " (Submitter): ";
	  if (!empty($dostring) && $dostring != "halp") {
	  	if ($dostring == "greenlight") {
	  		$rainbows = "greenlit";
	  	} else {
	  		$rainbows = $dostring;
	  	}
	  	$realbody = "<" . $rainbows . ">" . $realbody . "</" . $rainbows . ">";
	  	$msgresult = mysqli_query($connection, "SELECT * FROM `Messages` WHERE `Messages`.`username` = '" . $feedrow['user'] . "' LIMIT 1;");
  		$msgrow = mysqli_fetch_array($msgresult);
  		if ($msgrow['feedbacknotice'] == 1) {
  			$check = 0;
  			$foundempty = false;
  			while ($check < 50 && !$foundempty) {
	  			if (empty($msgrow['msg' . strval($check + 1)])) $foundempty = true;
	  			$check++;
  			}
  			if ($foundempty) {
  				$msgfield = "msg" . strval($check);
  				$newmsgstring = "Submissions|";
  				if ($dostring == "greenlight") {
	  				$newmsgstring = $newmsgstring . "Your submission was greenlit (ID " . strval($feedrow['ID']) . ")|";
	  			} elseif ($dostring == "defunct") {
	  				$newmsgstring = $newmsgstring . "Your submission was marked for deletion (ID " . strval($feedrow['ID']) . ")|";
	  			} elseif ($dostring == "clarify") {
	  				$newmsgstring = $newmsgstring . "Your submission requires clarification (ID " . strval($feedrow['ID']) . ")|";
	  			} elseif ($dostring == "suspended") {
	  				$newmsgstring = $newmsgstring . "Your submission was suspended (ID " . strval($feedrow['ID']) . ")|";
	  			}
	  			$newmsgstring = mysqli_real_escape_string($connection, $newmsgstring . 'A moderator flagged <a href="submissions.php?view=' . strval($feedrow['ID']) . '">' . $feedrow['name'] . "</a> and said the following:</br>" . $realbody);
	  			if (!empty($_POST['greenenc']) && $dostring == "greenlight") {
	  				if ($accrow['modlevel'] >= 3) {
	  				$reward = intval($_POST['greenenc']);
	  				if ($reward > 0) {
	  					$subresult = mysqli_query($connection, "SELECT `username`,`encounters` FROM `Players` WHERE `Players`.`username` = '" . $feedrow['user'] . "'");
	  					$subrow = mysqli_fetch_array($subresult);
	  					if ($subrow['encounters'] + $reward < 100) {
	  						echo "The submitter was gifted $reward encounters.<br />";
	  					} else {
	  						$newenc = 100;
	  						echo "The submitter was gifted $reward encounters, topping them off at 100.<br />";
	  					}
	  					mysqli_query($connection, "UPDATE Players SET `encounters` = $newenc WHERE `Players`.`username` = '" . $feedrow['user'] . "'");
	  					$newmsgstring .= "</br>You were also granted $reward encounter(s) for your creativity!";
	  				}
	  				} else echo "Your mod level is not yet high enough to grant encounters (need level 3).<br />";
	  			}
	  			mysqli_query($connection, "UPDATE Messages SET `$msgfield` = '$newmsgstring' WHERE `Messages`.`username` = '" . $feedrow['user'] . "'");
	  			mysqli_query($connection, "UPDATE Players SET `newmessage` = `newmessage` + 1 WHERE `Players`.`username` = '" . $feedrow['user'] . "'");
  			}
  		}
	  }
	  //echo $_POST['body'] . "</br>";
	  if ($exstring != " (Submitter): ") {
	  	$modmsg = $username . $exstring . $realbody;
	  	if (!empty($reward)) {
	  		$modmsg .= " // $reward encounters were also given.";
	  	}
	  	//logModMessage($modmsg, $_GET['view']);
	  }
	  $newcomments = $feedrow['usercomments'] . $username . $exstring . $realbody . "|";
	  $newncomments = mysqli_real_escape_string($connection, $newcomments);
	  //echo $newcomments . "</br>";
	  mysqli_query($connection, "UPDATE `Feedback` SET `usercomments` = '" . $newncomments . "' WHERE `Feedback`.`ID` = '" . strval($_GET['view']) . "' ;");
	  $feedrow['usercomments'] = $newcomments;
	  echo "Your comment has been posted.</br>";
	  updateSubmission($_GET['view']);
	  if ($feedrow['clarify'] == 1 && !empty($_POST['clearedup'])) {
	  	mysqli_query($connection, "UPDATE `Feedback` SET `clarify` = 0 WHERE `Feedback`.`ID` = '" . strval($_GET['view']) . "' ;");
	  	echo 'Yellow flag unset. A mod will get back to this submission shortly.</br>';
	  }
		} else echo "You are unable to post comments because you have a negative mod level. This is probably because some of your previous comments have been inappropriate or hurtful in some fashion.<br />";
	  }
	  $stylestring = "normal";
  	if ($feedrow['defunct'] == 1) {
  		$stylestring = "defunct";
  	} elseif ($feedrow['clarify'] == 1) {
  		$stylestring = "clarify";
  	} elseif ($feedrow['greenlight'] == 1) {
  		$stylestring = "greenlit";
  	} elseif ($feedrow['suspended'] == 1) {
  		$stylestring = "suspended";
  	} elseif ($feedrow['halp'] == 1) {
  		$stylestring = "halp";
  	} elseif ($feedrow['urgent'] == 1) {
  		$stylestring = "urgent";
  	} elseif ($feedrow['randomized'] == 1) {
  		$stylestring = "randomized";
  	}
	$likestring = "+" . strval($feedrow['likes']);
        echo '<' . $stylestring . '>Submission ID: ' . strval($feedrow['ID']) . ' <b>(' . $likestring . ')</b></' . $stylestring . '></br>';
        if ($accrow['modlevel'] >= 1) {
        	echo 'Submitted by: ' . $feedrow['user'] . '</br>';
        	echo 'Item code: <itemcode>' . $feedrow['code'] . '</itemcode></br>';
        }
        elseif ($feedrow['user'] == $username) {
        	echo 'This is one of your submissions.</br>';
        	echo 'Item code: <itemcode>' . $feedrow['code'] . '</itemcode></br>';
        }
        echo 'Item name: ' . $feedrow['name'] . '</br>';
        if ($feedrow['urgent'] == 1) echo '<urgent>This item was submitted by a Challenge Mode player</urgent></br>';
	echo 'Recipe: ' . $feedrow['recipe'] . '</br>';
	if ($feedrow['randomized'] == 1) echo '<randomized>This recipe came from the Randomizer</randomized></br>';
	$props = false;
	if ($feedrow['consumable'] == 1) {
		if (!$props) {
			echo "Properties: ";
			$props = true;
		}
		echo "Consumable";
	}
	if ($feedrow['catalogue'] == 1) {
		if (!$props) {
			echo "Properties: ";
			$props = true;
		} else echo ", ";
		echo "Base Item";
	}
	if ($feedrow['lootonly'] == 1) {
		if (!$props) {
			echo "Properties: ";
			$props = true;
		} else echo ", ";
		echo "Loot Only";
	}
	if ($feedrow['refrance'] == 1) {
		if (!$props) {
			echo "Properties: ";
			$props = true;
		} else echo ", ";
		echo "Reference";
	}
	if ($props) echo "<br />";
	echo 'Power level: ' . strval($feedrow['power']) . '</br>';
	if (!empty($feedrow['bonuses'])) {
		$barray = explode("|", $feedrow['bonuses']);
		$i = 0;
		while (!empty($barray[$i])) {
			$aarray = explode(":", $barray[$i]);
			echo $aarray[0] . ": ";
			$amoutn = intval($aarray[1]);
			if ($amoutn > 0) echo "+";
			echo strval($amoutn) . "<br />";
			$i++;
		}
	}
	if ($accrow['modlevel'] >= 1 && $feedrow['recpower'] != 0) {
		echo 'Recommended power (item1 power + item2 power) x 1.5: ' . strval($feedrow['recpower']) . '</br>';
	}
	echo 'Description: ' . $feedrow['description'] . '</br>';
	if (!empty($feedrow['grists'])) {
		echo "Grist weights: ";
		$barray = explode("|", $feedrow['grists']);
		$i = 0;
		while (!empty($barray[$i])) {
			$aarray = explode(":", $barray[$i]);
			echo $aarray[0] . ": ";
			$amoutn = intval($aarray[1]);
			echo strval($amoutn) . "; ";
			$i++;
		}
		echo "<br />";
	}
	if (!empty($feedrow['abstratus'])) echo "Abstratus: " . $feedrow['abstratus'] . "<br />";
	if (!empty($feedrow['size'])) echo "Size: " . $feedrow['size'] . "<br />";
	if ($feedrow['comments'] != "") echo "Submitter's comments: " . $feedrow['comments'] . "</br>";
	echo "</br>";
	if ($feedrow['usercomments'] != "") {
	  echo "Viewers' comments:</br>";
	  $count = 0;
	  $boom = explode("|", $feedrow['usercomments']);
	  $allmessages = count($boom);
	  while ($count < $allmessages) {
	    $boom[$count] = str_replace("THIS IS A LINE", "|", $boom[$count]);
	    echo $boom[$count] . "</br>";
	    $count++;
	    }
	  }
	//echo produceTimeSinceUpdate($feedrow['lastupdated']);
	echo "</br>";
	echo '<form action="submissions.php?view=' . strval($feedrow['ID']) . '&page=' . strval($page) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '" method="post" id="usercomment">';
	echo '<input type="checkbox" name="vote" value="vote"> Vote Up</br>';
	echo 'Leave a comment (optional): Use this field to offer improvements on the submission, such as recipe changes, grist costs, or power levels. Every idea helps!</br><textarea name="body" rows="6" cols="40" form="usercomment"></textarea></br>';
	if ($feedrow['clarify'] == 1) echo '<input type="checkbox" name="clearedup" value="clearedup">Unset yellow flag with this post (so that the item mods know that some necessary input has been given)</br>';
	if ($accrow['modlevel'] >= 1) {
		echo '</br>Moderative actions:</br>';
		//echo '<form action="submissions.php?view=' . strval($feedrow['ID']) . '&page=' . strval($page) . '&sort=' . $sort . '&mode=' . $mode . '" method="post">';
		echo '<input type="hidden" name="moderatethis" value="' . strval($feedrow['ID']) . '">';
		echo '<input type="radio" name="modaction" value="defunct"> <defunct>Mark for deletion</defunct></br>';
		echo '<input type="radio" name="modaction" value="clarify"> <clarify>Request clarification</clarify></br>';
		echo '<input type="radio" name="modaction" value="greenlight"> <greenlit>Greenlight item</greenlit>';
		if ($accrow['modlevel'] >= 3) echo ' - Also grant encounters: <input type="text" name="greenenc" /></br>';
		echo '<input type="radio" name="modaction" value="suspended"> <suspended>Suspend until further notice</suspended></br>';
		echo '<input type="radio" name="modaction" value="halp"> <halp>Summon a developer</halp></br>';
		if ($accrow['modlevel'] >= 2) echo '<input type="radio" name="modaction" value="clear"> <normal>Clear moderative flags</normal></br>';
		//echo '<input type="submit" value="Moderate" /></form>';
	}
	echo '<input type="submit" value="Share your opinion" /></form>';
	if ($feedrow['user'] == $username || ($accrow['modlevel'] >= 3 && $feedrow['defunct'] == 1)) {
	  echo '</br><form action="submissions.php?page=' . strval($page) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '" method="post"><input type="hidden" name="delete" value="' . strval($feedrow['ID']) . '"><input type="submit" value="Delete this submission"></form></br>';
	}
	if ($feedrow['greenlight'] == 1 && $accrow['modlevel'] >= 4) {
		echo '</br><form action="devtools/itemedit.php" method="get" target="_blank"><input type="hidden" name="sub" value="' . strval($feedrow['ID']) . '"><input type="submit" value="Take this to the Item Editor"></form></br>';
	}
      } else echo 'No item submission with that ID exists.</br>';
    }
  echo 'Submission viewer v0.0.1a. Click on an item submission to view/review it.</br>';
  echo 'Color key:</br>';
  echo '<defunct>red</defunct>: submission is marked for deletion.</br>';
  echo '<clarify>yellow</clarify>: clarification is requested from the submitter (or anyone). See the comments for details.</br>';
  echo '<greenlit>green</greenlit>: the item is ready to be processed.</br>';
  echo '<urgent>blue</urgent>: the item was submitted by someone playing a Challenge Mode game. These submissions will be prioritized above others.</br>';
  echo '<suspended>gray</suspended>: the item is suspended for the time being. It will be saved until something else is addressed.</br>';
  echo '<randomized>orange</randomized>: the submission came from the Randomizer.</br>';
  echo '<halp>white</halp>: I need an adult!</br>';
  //let's generate that message table~
    //$feedresult = mysqli_query($connection, "SELECT `ID`,`name`,`likes`,`usercomments`,`defunct`,`clarify`,`greenlight` FROM `Feedback` WHERE `Feedback`.`type` = 'item' AND `Feedback`.`user` = '" . $username . "' ORDER BY `Feedback`.`ID` ASC ;");

  if ($sort == "name") $sortstring = "ORDER BY `Feedback`.`name` $order ";
  elseif ($sort == "like") $sortstring = "ORDER BY `Feedback`.`likes` $order ";
  elseif ($sort == "comm") $sortstring = "ORDER BY `Feedback`.`usercomments` $order ";
  elseif ($sort == "time") $sortstring = "ORDER BY `Feedback`.`lastupdated` $order ";
  else $sortstring = "ORDER BY `Feedback`.`ID` $order ";

  if ($mode == "yours") $modestring = "AND `Feedback`.`user` = '" . $username . "' ";
  elseif ($mode == "red") $modestring = "AND `Feedback`.`defunct` = 1 ";
  elseif ($mode == "yellow") $modestring = "AND `Feedback`.`clarify` = 1 ";
  elseif ($mode == "green") $modestring = "AND `Feedback`.`greenlight` = 1 ";
  elseif ($mode == "blue") $modestring = "AND `Feedback`.`urgent` = 1 ";
  elseif ($mode == "gray") $modestring = "AND `Feedback`.`suspended` = 1 ";
  elseif ($mode == "orange") $modestring = "AND `Feedback`.`randomized` = 1 ";
  elseif ($mode == "white") $modestring = "AND `Feedback`.`halp` = 1 ";
  elseif ($mode == "black") $modestring = "AND `Feedback`.`defunct` = 0 AND `Feedback`.`clarify` = 0 AND `Feedback`.`greenlight` = 0 AND `Feedback`.`suspended` = 0 ";
  elseif ($mode == "aotw") {
  	$aotwresult = mysqli_query($connection, "SELECT `abstratusoftheweek` FROM System WHERE 1");
  	$aotwrow = mysqli_fetch_array($aotwresult);
  	$aotwstring = $aotwrow['abstratusoftheweek'];
  	$modestring = "AND `Feedback`.`comments` LIKE '%" . $aotwstring . "%' ";
  }
  else $modestring = "";
  $search = "";
  $sfield = "";
  if (!empty($_GET['search']) && !empty($_GET['sfield'])) {
  	$sfield = $_GET['sfield'];
  	if (empty($_GET['sfield'])) $sfield = 'name';
  	$modestring .= "AND `Feedback`.`$sfield` LIKE '%" . $_GET['search'] . "%' ";
  }

    $startpoint = strval(($page - 1) * 20);
    //add ,`likes`,`usercomments`,`defunct`,`clarify`,`greenlight` before dev build is pushed
    //echo "SELECT `ID`,`name`,`likes`,`usercomments` FROM `Feedback` WHERE `Feedback`.`type` = 'item' " . $modestring . $sortstring . "LIMIT " . $startpoint . ",20 ;</br>";
    $feedresult = mysqli_query($connection, "SELECT `ID`,`name`,`likes`,`usercomments`,`defunct`,`clarify`,`greenlight`,`urgent`,`suspended`,`randomized`,`halp`,`lastupdated` FROM `Feedback` WHERE 1 " . $modestring . $sortstring . "LIMIT " . $startpoint . ",20 ;");
  echo '<table border="1" bordercolor="#CCCCCC" style="background-color:#EEEEEE" width="100%" cellpadding="3" cellspacing="3">';
  echo '<tr><td>ID</td><td>Item Name</td><td>Rating</td><td>Comments</td></tr>';
  $results = false;
  while ($showrow = mysqli_fetch_array($feedresult)) {
  	$results = true;
  	$stylestring = "normal";
  	if ($showrow['defunct'] == 1) {
  		$stylestring = "defunct";
  	} elseif ($showrow['clarify'] == 1) {
  		$stylestring = "clarify";
  	} elseif ($showrow['greenlight'] == 1) {
  		$stylestring = "greenlit";
  	} elseif ($showrow['suspended'] == 1) {
  		$stylestring = "suspended";
  	} elseif ($showrow['halp'] == 1) {
  		$stylestring = "halp";
  	} elseif ($showrow['urgent'] == 1) {
  		$stylestring = "urgent";
  	} elseif ($showrow['randomized'] == 1) {
  		$stylestring = "randomized";
  	}
    $boom = explode("|", $showrow['usercomments']);
    $allmessages = count($boom) - 1;
    echo '<tr><td><' . $stylestring . '>' . strval($showrow['ID']) . '</' . $stylestring . '></td><td><a href="submissions.php?view=' . strval($showrow['ID']) . '&page=' . strval($page) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '">' . $showrow['name'] . '</a></td><td>+' . strval($showrow['likes']) . '</td><td>' . strval($allmessages) . '</td></tr>';
  }
  if (!$results) echo '<tr><td colspan="4">No submissions found. Either this is an invalid page number, or nothing matches those parameters.</td></tr>';
  echo '</table></br>';
  $countresult = mysqli_query($connection, "SELECT `ID` FROM `Feedback` WHERE 1 " . $modestring . $sortstring);
  $pcount = 20;
  $ptotal = 0;
  $alltotal = 0;
  echo '<center>Pages:</br>';
  if ($page > 1) {
  	echo '<a href="submissions.php?page=' . strval($page - 1) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '&search=' . $_GET['search'] . '&sfield=' . $sfield . '">Previous page</a> | ';
  } else {
  	echo 'Previous page | ';
  }
  while ($row = mysqli_fetch_array($countresult)) {
  	$alltotal++;
  	if ($pcount == 20) {
  		$ptotal++;
  		if ($ptotal == $page) {
  			echo strval($ptotal) . ' | ';
  		} else {
			$search = "";
			if (!empty($_GET['search'])) $search = $_GET['search'];
  			echo '<a href="submissions.php?page=' . strval($ptotal) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '&search=' . $search . '&sfield=' . $sfield . '">' . strval($ptotal) . '</a> | ';
  		}
  		$pcount = 0;
  	}
  	$pcount++;
  }
  if ($page < $ptotal) {
	$search = "";
	if (!empty($_GET['search'])) $search = $_GET['search'];
    echo '<a href="submissions.php?page=' . strval($page + 1) . '&sort=' . $sort . '&mode=' . $mode . '&order=' . $order . '&search=' . $search . '&sfield=' . $sfield . '">Next page</a>';
  } else {
  	echo 'Next page';
  }
  echo "<br />Total results: $alltotal</center><br /><br />";
  $currentitem = "";
  if(!empty($feedrow['ID'])) $currentitem = strval($feedrow['ID']);
  echo '<form action="submissions.php" method="get"><input type="hidden" name="view" value="' . $currentitem . '"><table width="100%" cellpadding="3" cellspacing="3"><tr><td><center>Show only:</center></br>';
  echo '<input type="radio" name="mode" value="none" checked /> All<br /><input type="radio" name="mode" value="yours" /> Your submissions<br /><input type="radio" name="mode" value="black" /> Unmarked<br /><input type="radio" name="mode" value="red" /> Defunct<br /><input type="radio" name="mode" value="yellow" /> Clarification needed<br /><input type="radio" name="mode" value="green" /> Greenlit<br /><input type="radio" name="mode" value="blue" /> Challenge Mode<br /><input type="radio" name="mode" value="gray" /> Suspended<br /><input type="radio" name="mode" value="orange" /> Randomized<br /><input type="radio" name="mode" value="white" /> Dev requested</td>';
  echo '<td><center>Sort by:</center></br>';
  echo '<input type="radio" name="sort" value="id" checked /> ID<br /><input type="radio" name="sort" value="name" /> Name<br /><input type="radio" name="sort" value="like" /> Likes<br /><input type="radio" name="sort" value="comm" /> Comments<br /><input type="radio" name="sort" value="time" /> Time since last update</td>';
  echo '<td><center>In this order:</center></br>';
  echo '<input type="radio" name="order" value="ASC" checked /> Ascending<br /><input type="radio" name="order" value="DESC" /> Descending</td></tr><tr><td colspan="3"><center>Search: <input type="text" name="search" />';
  echo ' In: <select name="sfield"><option value="name">Item Name</option><option value="user">Submitter</option><option value="recipe">Recipe</option><option value="description">Description</option><option value="comments">Submitter Comments</option><option value="usercomments">Viewer Comments</option></select>';
  echo '</center></td></tr><tr><td colspan="3"><center><input type="submit" value="Go for it!" /></center></td></tr></table></form>';
  if ($accrow['modlevel'] > 0) {
  	echo "The dev team has deemed you worthy of utilizing moderator powers!<br />";
  	echo "Your mod level is: <b>" . strval($accrow['modlevel']) . "</b><br />Your abilities:<br />";
  	if ($accrow['modlevel'] >= 1) echo "- You may apply the five moderative flags (red, yellow, green, gray, white) to any unmarked submission.<br />";
  	if ($accrow['modlevel'] >= 2) echo "- You may overwrite an existing flag or clear all flags on a submission.<br />";
  	if ($accrow['modlevel'] >= 3) echo "- You may delete red-flagged subs and grant encounters for greenlit items.<br />";
  	if ($accrow['modlevel'] >= 4) echo "- You may use the Item Editor to add greenlit items to the game. Look for the 'Take this to the Item Editor' button when viewing a greenlit item.<br />";
  	if ($accrow['modlevel'] >= 5) echo "- You may freely use the <a href='devtools/itemedit.php'>Item Editor</a> to edit items or create items from scratch. You may also use the <a href='devtools/consumedit.php'>Consumable Editor</a>.<br />";
  	if ($accrow['modlevel'] >= 6) echo "- You can view the working item addlog and may post item updates from the Item/Consumable Editor.<br />";
  	if ($accrow['modlevel'] >= 10) echo "- You have the powers of a Global Moderator and can access <a href='devtools/index.php'>a variety of tools</a>.<br />";
  	if ($accrow['modlevel'] >= 99) echo "- You have the powers of a Developer and can do pretty much anything!";
  }
  echo "<br /><center>You can submit your own using one of these forms:<br /><a href='quickitemcreate.php'>Quick</a> | <a href='fullitemcreate.php'>Full</a></center>";
}
require_once "footer.php";
?>
