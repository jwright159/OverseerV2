<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/global_functions.php");

function randomItem($querystring = "", $costcap = false, $session = 0) {
  // finds random item
  // takes querystring as `field` LIKE '%values%' AND...
  // optionally takes costcap as any int
  // returns rowof item, false if no item could be found, or -1 if errored

  global $connection;
  if ($querystring == 1) return -1;    // if sent querystring of 1 (which indicates compileSearchString has errored) then error
  if ($costcap !== false && !is_numeric($costcap)) logDebugMessage("randomItem given invalid string for costcap: $costcap");  // if costcap is errored print a debug message
  $query = "";
  if ($querystring !== "") $query .= $querystring . "AND ";      // build query string
  $query .= "`Captchalogue`.`effects` NOT LIKE '%NOCONSORT%' AND (`Captchalogue`.`session` = 0 OR `Captchalogue`.`session` = $session) ORDER BY RAND();";  // make sure not to get NOCONSORT items - consorts aren't going to give you the matriorb, nor ask for it as a hint
  $poolresult = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE " . $query);  // get the matching items
  $i = 0;
  while ($pool[$i] = mysqli_fetch_assoc($poolresult)) $i++;  // get the item rows from the sql result; this has the side effect of making it so we don't have to count the array afterwards
  if ($i == 0) return false;
  $foundone = false;
  $attempts = 0;
  while (!$foundone && $attempts < 100)   // go through the items up to 100 times
    {
      $pick = rand(0,$i-1);       // pick a random item (sadly we can't easily record whether we've seen this one already)
      if (is_numeric($costcap))   // if costcap is a number (ie not false or errored) check the grist cost against the requirement
	{
	  $thiscost = totalGristcost($pool[$pick]['gristcosts']);    // calculate the item's grist cost
	  if ($thiscost <= $costcap) $foundone = true;               // if it matches yay!
	}
      else
	{
	  $foundone = true;             // otherwise there's no costcap to worry about so we successfully found an item yay!
	}
      $attempts++;
    }
  if ($foundone) return $pool[$pick];    // if we found one return it, otherwise say we didn't
  return false;
}


function compileSearchString($requirements) {
  // compile mysqli_query string from item requirements
  // takes requirement string as CAT1:req1/req2|CAT2:req3:req4 or ITEM:CAT1/req1.req2:CAT2/req3/req4
  // returns SQL requirements (WHERE `x` = y AND...) or 1 if errored

  if (empty($requirements)) return "";  // if no requirements then yay
  if (strpos("|" . $requirements, "|ITEM:") === 0 || strpos("|" . $requirements, "|ITEM:") == 1) // if this is a reward string:
    {
      if (strrpos("|", $requirements) != 0)
	{
	  logDebugMessage("Unexpected | in item requirement string: " . $requirements);  // print to log and error if there's a | somewhere not at the start, in case given something like |ITEM:STUFF|MONIES:AMOUNT
	  return 1;
	}
      $requirements = str_replace("ITEM:", "", $requirements);   // otherwise reformat the strings so they're the same as the quest requirement strings
      $requirements = str_replace("|", "", $requirements);
      $requirements = str_replace(":", "|", $requirements);
      $requirements = str_replace("/", ":", $requirements);
      $requirements = str_replace(".", "/", $requirements);
    }
  $querystring = "";                       // initialize the query string
  $boom = explode("|", $requirements);           // explode requirements so we can add each tag's contents to the query
  for ($i = 0; $i < count($boom); $i++)
    {
      if ($boom[$i] == NULL || $boom[$i] == "" || $boom[$i] == " ") continue;  // if this tag is empty then skip it
      if (strpos(":", $boom[$i]) === 0)                             //if there's a : in the wrong place, print to log and error
	{
	  logDebugMessage("Invalid item requirement string: " . $boom[$i]);
	  return 1;
	}
      if ($querystring != "") $querystring .= " AND ";        // if this isn't the first tag, add AND
      $thisone = explode(":", $boom[$i]);                          // explode the current tag being examined, it's time to interpret!
      if ($thisone[0] == "NAME" && count($thisone >= 2))            // if looking for specific item(s) by name
	{
	  $querystring = "`name` IN ('" . $thisone[1] . "'";       // search for items matching given names - note we're resetting the query string!
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= ", '" . $thisone[$j] . "'";
	    }
	  $querystring .= ")";
	  return $querystring;
	}
      else if ($thisone[0] == "ID" && count($thisone >= 2))            // if looking for specific item(s) by id
	{
	  $querystring = "`ID` IN ('" . $thisone[1] . "'";         // search for items matching given ids - note we're resetting the query string!
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= ", '" . $thisone[$j] . "'";
	    }
	  $querystring .= ")";
	  return $querystring;
	}
      else if ($thisone[0] == "ABSTRATUS" && count($thisone) >= 2)   // if searching by abstratus, search for items matching at least one given abstrati: ie `abstratus` IN ('backscratcherkind', 'metakind')
	{
	  $querystring .= "(`abstratus` LIKE '%" . $thisone[1] . "%'";
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= "OR `abstratus` LIKE '%" . $thisone[$j] . "%'";
	    }
	  $querystring .= ")";
	}
      else if ($thisone[0] == "BASE" && count($thisone) == 2)   // if searching by base, search for items that are or aren't base items
	{
	  $querystring .= "`base` = " . $thisone[1];
	}
      else if ($thisone[0] == "CONSUMABLE" && count($thisone) == 2)   // if searching by consumableness, check by every possible way something could be or not be a consumable
	{
	  if ($thisone[1] == 1)
	    {
	      $querystring .= "`consumable` != 0 AND `consumable` != '' AND `consumable` IS NOT NULL";
	    }
	  else
	    {
	      $querystring .= "(`consumable` = 0 OR `consumable` = '' OR `consumable` IS NULL)";
	    }
	}
      else if ($thisone[0] == "GRIST" && count($thisone) >= 2)  // if searching by grists, search for items matching at least one given grist: ie (`gristcosts` LIKE '%artifact%' OR `gristcosts` LIKE '%shale%')
	{
	  $querystring .= "(`gristcosts` LIKE '%" . $thisone[1] . "%'";
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= " OR `gristcosts` LIKE '%" . $thisone[$j] . "%'";
	    }
	  $querystring .= ")";
	}
      else if ($thisone[0] == "WEARABLE" && count($thisone) >= 2)  // if searching by wearable, search for items matching at least one given type: ie `wearable` LIKE '%bodygear%'
	{
	  $querystring .= "(`wearable` LIKE '%" . $thisone[1] . "%'";
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= " OR `wearable` LIKE '%" . $thisone[$j] . "%'";
	    }
	  $querystring .= ")";
	}
      else if ($thisone[0] == "SIZE" && count($thisone) == 3)  // if searching by size
	{
	  if ($thisone[2] == "exact")                          // and only looking for one size
	    {
	      $querystring .= "`size` = '" . $thisone[1] . "'";  // search for that size only
	    }
	  else        // otherwise:
	    {
	      $sizes = array("miniature", "tiny", "small", "average", "large", "huge", "immense", "ginormous");  // initialize array of sizes
	      $querystring .= "`size` IN ('" . $thisone[1] . "'";     // add given size to search list
	      if ($thisone[2] == "max") $startsearch = true;          // if searching <=, start adding to search list immediately, otherwise don't start yet
	      else $startsearch = false;
	      for ($j = 0; $j < 8; $j++)                              // for each size
		{
		  if ($sizes[$j] == $thisone[1])                      // if it's the size given, then toggle whether we're adding to the search list
		    {
		      if ($thisone[2] == "max") $startsearch = false;
		      else $startsearch = true;
		    }
		  else if ($startsearch)                              // otherwise if we're adding to the list, add it
		    {
		      $querystring .= ", '" . $sizes[$j] . "'";
		    }
		}
	      $querystring .= ")";                                    // resulting string should be something like `size` IN ('small', 'miniature', 'tiny') or `size` IN ('huge', 'immense', 'ginormous')
	    }
	}
      else if ($thisone[0] == "KEYWORD" && count($thisone) >= 2)      // if searching for item names matching a keyword, search for things matching at least one: ie (`name` LIKE 'thing1' OR `name` LIKE 'thing2')
	{
	  $querystring .= "(`name` LIKE '%" . $thisone[1] . "%'";
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= " OR `name` LIKE '%" . $thisone[$j] . "%'";
	    }
	  $querystring .= ")";
	}
      else if ($thisone[0] == "POWER" && count($thisone) == 3)   // if searching by power, search for items with power matching a certain range: ie `power` <= 5
	{
	  $querystring .= "`power` ";
	  if ($thisone[2] == "exact")
	    {
	      $querystring .= "= ";
	    }
	  else if ($thisone[2] == "min")
	    {
	      $querystring .= ">= ";
	    }
	  else
	    {
	      $querystring .= "<= ";
	    }
	  $querystring .= $thisone[1];
	}
      else if ($thisone[0] == "EFFECT" && count($thisone) >= 2)     // if searching by effect, search for items matching at least one effect type: ie `effects` LIKE '%COMPUTER%'
	{
	  $querystring .= "(`effects` LIKE '%" . $thisone[1] . "'";
	  for ($j = 2; $j < count($thisone); $j++)
	    {
	      $querystring .= " OR `effects` LIKE '%" . $thisone[$j] . "%'";
	    }
	  $querystring .= ")";
	}
      else if ($thisone[0] == "LOOTONLY" && count($thisone) == 2)   // if searching by loot type, find or don't find items that can only be dropped as boss loot
	{
	  $querystring .= "`loot` = " . $thisone[1];
	}
      else if (strrpos($querystring, " AND ") === (strlen($querystring)-5))
	{
	  $querystring = substr($querystring, 0, -5);
	}
    }
  return $querystring . " ";
}


function matchItem($itemrow, $requirements) {
  // decides whether item matches quest item requirements
  // takes requirement string as CAT1:req1/req2|CAT2:req3:req4 and an item's row
  // returns true if item matches requirements, false if not

  if (empty($requirements)) return true;  // if no requirements then yay
  $boom = explode("|", $requirements);           // explode requirements so we can check each tag's contents against the item
  for ($i = 0; $i < count($boom); $i++)
    {
      if ($boom[$i] == NULL || $boom[$i] == "" || $boom[$i] == " ") continue;  // if this tag is empty then skip it
      else if (strpos(":", $boom[$i]) === 0)                             //if there's a : in the wrong place, print to log and error
	{
	  logDebugMessage("Invalid item requirement string: " . $boom[$i]);
	  continue;
	}
      $thisone = explode(":", $boom[$i]);                          // explode the current tag being examined, it's time to interpret!
      if ($thisone[0] == "NAME" && count($thisone >= 2))            // if looking for specific item(s) by name
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if ($thisone[$j] == $itemrow['name'])  $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "ID" && count($thisone >= 2))            // if looking for specific item(s) by ID
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if ($thisone[$j] == $itemrow['ID'])  $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "ABSTRATUS" && count($thisone >= 2))
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if (strpos(", " . $itemrow['abstratus'], ", " . $thisone[$j])) $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "BASE" && count($thisone) == 2)   // if searching by base, search for items that are or aren't base items
	{
	  if ($thisone[1] !== $itemrow['base']) return false;
	}
      else if ($thisone[0] == "CONSUMABLE" && count($thisone) == 2)
	{
	  if ($thisone[1] == 1 && empty($itemrow['consumable'])) return false;
	  else if (!empty($itemrow['consumable'])) return false;
	}
      else if ($thisone[0] == "GRIST" && count($thisone >= 2))
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if (strpos($itemrow['gristcosts'], $thisone[$j])) $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "WEARABLE" && count($thisone >= 2))
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if (strpos($itemrow['wearable'], $thisone[$j])) $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "SIZE" && count($thisone) == 3)  // if searching by size
	{
	  if ($thisone[2] == "exact")                          // and only looking for one size
	    {
	      if ($thisone[1] !== $itemrow['size']) return false;  // search for that size only
	    }
	  else        // otherwise:
	    {
	      $sizes = array("miniature", "tiny", "small", "average", "large", "huge", "immense", "ginormous");  // initialize array of sizes
	      for ($j = 0; $j < 8; $j++)                              // for each size
		{
		  if ($thisone[1] == $sizes[$j]) $searchsize = $j;
		  if ($itemrow['size'] == $sizes[$j]) $itemsize = $j;
		}
	      if (empty($searchsize) || empty($itemsize))
		{
		  logDebugMessage("Invalid size while scanning item requirement string: searchsize = " . strval($searchsize) . ", itemsize = " . strval($itemsize));
		  continue;
		}
	      else if ($thisone[2] == "min" && $searchsize > $itemsize) return false;
	      else if ($thisone[2] == "max" && $searchsize < $itemsize) return false;
	    }
	}
      else if ($thisone[0] == "KEYWORD" && count($thisone >= 2))
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if (strpos($itemrow['name'], $thisone[$j])) $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "POWER" && count($thisone == 3))
	{
	  if ($thisone[2] == "exact" && $thisone[1] !== $itemrow['power']) return false;
	  else if ($thisone[2] == "min" && $thisone[1] > $itemrow['power']) return false;
	  else if ($thisone[2] == "max" && $thisone[1] < $itemrow['power']) return false;
	}
      else if ($thisone[0] == "EFFECT" && count($thisone >= 2))
	{
	  $matched = false;
	  for ($j = 1; $j < count($thisone) && !$matched; $j++)
	    {
	      if (strpos($itemrow['effects'], $thisone[$j])) $matched = true;
	    }
	  if (!$matched) return false;
	}
      else if ($thisone[0] == "LOOTONLY" && count($thisone) == 2)   // if searching by loot type, find or don't find items that can only be dropped as boss loot
	{
	  if ($thisone[1] != $itemrow['loot']) return false;
	}
      else
	{
	  logDebugMessage("Something went wrong while reading an item requirement tag of " . $boom[$i]);
	}
    }
  return true;
}


function totalGristcost($costfield) { //calculates total grist cost of an item from the gristcosts field - EDIT THIS TO CHECK GRIST NAMES
  if (empty($costfield)) {            //if no grist types, return 0
    return 0;
  }
  $totalcost = 0;  //now that we know the item has cost, initialize it
  if (strrpos($costfield, "|") === strlen($costfield)-1) $costfield = substr($costfield, 0, -1);  // if costfield ends with a | remove it
  $gristcosts = explode("|", $costfield);  //explode $gristcosts to get at individual grists
  for ($i = 0; $i < count($gristcosts); $i++)
    {   //for each grist in the item
      $singlegrist = explode(":", $gristcosts[$i]);  //explode its element in $gristcosts
      if (count($singlegrist) != 2 || !is_string($singlegrist[0]) || !is_numeric($singlegrist[1]))
	{        //if there aren't exactly two grist elements (name and cost) then
	  logDebugMessage("Unexpected text in an item's cost! Expected grist:cost, found " . $gristcosts[$i] . " in " . $costfield);  //print to debug log
	}
      else if ($singlegrist[0] != "Artifact")
	{    //otherwise, if a valid grist other than artifact
	  $totalcost = $totalcost + $singlegrist[1];   //add it to the total cost
	}
    }
  return $totalcost;
}


function totalBooncost($costfield, $charrow) {
  // given costfield and the charrow of the land's owner
  // returns default cost of item in that session

  $totalcost = 0;   // initialize cost
  $grists = initGrists();   // get array of grists
  for ($i = 0; $i < count($grists); $i++)   // for each grist
    {
      $gristnames[$grists[$i]['name']] = $grists[$i]['tier'];  // set gristnames[name] = tier
    }
  $griststrings = explode("|", $costfield);  // explode costfield into individual grists
  for ($i = 0; $i < count($griststrings); $i++)  // for each one
    {
      $agrist = substr($griststrings[$i], 0, strpos($griststrings[$i], ":"));  // get the name of the grist
      if (isset($gristnames[$agrist]))  // if it's a grist we got earlier from initGrists
	{
	  $thisgrist = explode(":", $griststrings[$i]);  // explode the grist string to get at the cost
	  $gristvalue = 0;
	  if ($thisgrist[0] == "Artifact") $gristvalue = 0;  // if artifact grist it's worthless
	  else if ($thisgrist[0] == "Build_Grist" || $thisgrist[0] == "Meta" || $thisgrist[0] == "Zillium") $gristvalue = 20;  // and if build/meta/zillium it has a set value
	  else
	    {
	      $gristvalue = calcGristValue($thisgrist[0], $charrow);  // otherwise calculate the value based on the lands in the session
	    }
	  $totalcost += $thisgrist[1]*$gristvalue;  // increment totalcost by the worth of this type of grist
	}
    }
  return $totalcost;
}


function calcGristValue($gristname, $charrow) {
  // given a grist, the grist's tier, and a character row
  // returns the value of the grist within the character's land based on land and session

  $charrow = mysqli_fetch_assoc($thisrow);
  if (strpos($charrow['grist_type'], $gristname) !== false)   // if the grist is on this land
    {
      $thistier =  substr_count(substr($charrow['grist_type'], 0, strpos($thisland['grist_type'], $gristname)), "|")+1;    // count what number grist this is in the string (equal to number of previous "|"s + 1)
      if ($thistier > 9) return 40 * ($thistier-8);   // if it's grist number 10-18 (ie a bonus grist) it's worth double
      return 20 * ($thistier + 1);                    // otherwise it's worth 20 per tier
    }
  global $connection;
  $characterresult = mysqli_query($connection, "SELECT `grist_type` FROM `Characters` WHERE `session` = " . $charrow['session']);   // if the grist wasn't on the land (in which case the function is over since we already returned) get all griststrings for the session
  $totallands = 0;
  $totaltier = 0;
  while ($characterrow = mysqli_fetch_assoc($characterresult))   // for each person's grist string
    {
      if (strpos($characterrow['grist_type'], $gristname) !== false)  // if the grist is in the string
	{
	  $thistier =  substr_count(substr($characterrow['grist_type'], 0, strpos($characterrow['grist_type'], $gristname)), "|")+1;  // count which number grist it is
	  if ($thistier > 9) $thistier = ($thistier-9)*2;  // if a bonus grist it's worth double
	  $totallands++;                 // add it to the total tier and increment how many lands we've found it in
	  $totaltier += $thistier;
	}

    }
  if ($totallands != 0) return round((($totaltier / $totallands) + 1) * 25);   // if we found it in any lands, return a resultant cost
  return 500;  // otherwise it costs 500
}




function getDialogue($dtype, $charrow, $gate = 1) {
  // given type of dialogue, charrow, and optionally a gate
  // returns a random matching row of dialogue

  global $connection;
  if ($gate < 1) $gate = 1; // just in case gate gets screwed up
  else if ($gate > 7) $gate = 7;
  $countresult = mysqli_query($connection, "SELECT COUNT(*) FROM `Consort_Dialogue` WHERE `Consort_Dialogue`.`context` = '$dtype' AND `gate` <= $gate");  // count the number of matching dialogues
  $count = mysqli_fetch_row($countresult);   // grab the count
  $pick = rand(0,$count[0]-1);                   // pick a random dialogue
  $pickresult = mysqli_query($connection, "SELECT * FROM `Consort_Dialogue` WHERE `Consort_Dialogue`.`context` = '$dtype' AND `gate` <= $gate LIMIT $pick,1");  // grab the random dialogue
  $pickrow = mysqli_fetch_assoc($pickresult);  // fetch the array
  if (!empty($pickrow['dialogue']))            // if the array exists, ie if there's a matching dialogue
    {
      $pickrow = parseDialogue($pickrow, $charrow);   // parse the dialogue strings and stuff
    }
  else
    {
      $pickrow['dialogue'] = "I AM ERROR.";
      logDebugMessage("No matching quests found for $dtype and $gate!");
    }
  return $pickrow;
}


function parseDialogue($pickrow, $charrow) {
  // for a dialogue row and charrow
  // replaces various placeholders with the character's info
  // returns pickrow as parsed dialogue

  $pickrow['dialogue'] = str_replace("[user]", $charrow['name'], $pickrow['dialogue']);
  if (empty($charrow['class'])) $pickrow['dialogue'] = str_replace("[class]", "whoever", $pickrow['dialogue']);
  else $pickrow['dialogue'] = str_replace("[class]", $charrow['class'], $pickrow['dialogue']);
  if (empty($charrow['aspect'])) $pickrow['dialogue'] = str_replace("[aspect]", "whatever", $pickrow['dialogue']);
  else $pickrow['dialogue'] = str_replace("[aspect]", $charrow['aspect'], $pickrow['dialogue']);
  $pickrow['dialogue'] = str_replace("[landfull]", "The Land of " . $charrow['land1'] . " and " . $charrow['land2'], $pickrow['dialogue']);
  if (strpos($pickrow['dialogue'], "[landshort]") !== false) {
    $landshort = abbreviateLand($charrow['land1'], $charrow['land2']);
    $pickrow['dialogue'] = str_replace("[landshort]", $landshort, $pickrow['dialogue']);
  }
  if (!empty($pickrow['victory_dialogue']))
    {
      $pickrow['victory_dialogue'] = str_replace("[user]", $charrow['name'], $pickrow['victory_dialogue']);
      if (empty($charrow['class'])) $pickrow['victory_dialogue'] = str_replace("[class]", "whoever", $pickrow['victory_dialogue']);
      else $pickrow['victory_dialogue'] = str_replace("[class]", $charrow['class'], $pickrow['victory_dialogue']);
      if (empty($charrow['aspect'])) $pickrow['victory_dialogue'] = str_replace("[aspect]", "whatever", $pickrow['victory_dialogue']);
      else $pickrow['victory_dialogue'] = str_replace("[aspect]", $charrow['aspect'], $pickrow['victory_dialogue']);
      $pickrow['victory_dialogue'] = str_replace("[landfull]", "The Land of " . $charrow['land1'] . " and " . $charrow['land2'], $pickrow['victory_dialogue']);
      if (strpos($pickrow['victory_dialogue'], "[landshort]") !== false) {
	$landshort = abbreviateLand($charrow['land1'], $charrow['land2']);
	$pickrow['victory_dialogue'] = str_replace("[landshort]", $landshort, $pickrow['victory_dialogue']);
      }
    }
  if (!empty($pickrow['completion_dialogue']))
    {
      $pickrow['completion_dialogue'] = str_replace("[user]", $charrow['name'], $pickrow['completion_dialogue']);
      if (empty($charrow['class'])) $pickrow['completion_dialogue'] = str_replace("[class]", "whoever", $pickrow['completion_dialogue']);
      else $pickrow['completion_dialogue'] = str_replace("[class]", $charrow['class'], $pickrow['completion_dialogue']);
      if (empty($charrow['aspect'])) $pickrow['completion_dialogue'] = str_replace("[aspect]", "whatever", $pickrow['completion_dialogue']);
      else $pickrow['completion_dialogue'] = str_replace("[aspect]", $charrow['aspect'], $pickrow['completion_dialogue']);
      $pickrow['completion_dialogue'] = str_replace("[landfull]", "The Land of " . $charrow['land1'] . " and " . $charrow['land2'], $pickrow['completion_dialogue']);
      if (strpos($pickrow['completion_dialogue'], "[landshort]") !== false) {
	$landshort = abbreviateLand($charrow['land1'], $charrow['land2']);
	$pickrow['completion_dialogue'] = str_replace("[landshort]", $landshort, $pickrow['completion_dialogue']);
      }
    }
  return $pickrow;
}


function abbreviateLand($land1, $land2) {
  // land1 and land2 are the land name strings
  // returns acronym, ie LOWAS, LOLCAT, etc or 1 if errored

  if (empty($land1) || empty($land2))
    {
      logDebugMessage("Invalid land names passed to abbreviateLand: $land1, $land2");
      return 1;
    }
  $landshort = "LO";
  $land1 = preg_replace('/\s+/', ' ',$land1);  // if land names have multiple spaces in a row, replace them with one space
  $land2 = preg_replace('/\s+/', ' ',$land2);
  $boom = explode(" ", $land1);
  $bcount = 0;
  while ($bcount <= count($boom)) {
    $landshort .= strtoupper(substr($boom[$bcount], 0, 1));  // for each word in land1, add first initial to landshort
    $bcount++;
  }
  $landshort .= "A";
  $boom = explode(" ", $land2);
  $bcount = 0;
  while ($bcount <= count($boom)) {
    $landshort .= strtoupper(substr($boom[$bcount], 0, 1));  // for each word in land2, add first initial to landshort
    $bcount++;
  }
  return $landshort;  // return finished acronym
}


function econonyLevel($exp) {
  // given exp in the land's economy
  // returns land's economy level

  $level = floor(pow($exp / 1000, 1/3));  // calculate level from xp
  return $level;
}


function explorationLevel($exp) {
  // given exp in the land's exploration
  // returns land's exploration level

  $level = floor(pow($exp, 1/3));  // calculate level from xp
  return $level;
}


function phatLoot(& $charrow, $qrow, & $landrow, $gate = 1, $itemcost = 0) {
  // given a character row, the quest row, the land row, the gate reached on the land, and the cost of the item if an item quest
  // echoes the quest reward information and updates charrow and the database with the rewards

  $errormessage = "";
  if (empty($charrow)) $errormessage = "charrow, ";
  if (empty($qrow)) $errormessage .= "qrow, ";
  if (empty($landrow)) $errormessage .= "landrow, ";
  if (!is_numeric($gate)) $errormessage .= "a valid gate, ";
  if (!is_numeric($itemcost)) $errormessage .= "a valid itemcost!";
  if ($errormessage !== "")
    {
      logDebugMessage("phatLoot didn't recieve $errormessage");   // if not properly given an input complain to the debug log
      return -1;
    }
  if ($charrow['currentquest'] == 0)
    {
      echo "No current quest to complete!<br />";
      return -1;
    }

  global $connection;
  $boonreward = 0;
  $itemreward;
  $rewardstring = "";
  $unlockreward = "";
  $questreward = 0;
  $reward = rand(1,(100 - ($userrow['luck'] / 2))); //chance of getting an item instead of boons
  $inflation = rand(-90,-50) + econonyLevel($landrow['economy']);
  if ($inflation > 100) $inflation = 100;
  if (!empty($qrow['rewards']))
    {
      $rewards = explode("|", $qrow['rewards']);
      for ($i = 0; $i < count($rewards); $i++)
	{

	  if (strpos($rewards[$i], "ITEM:") !== false)  // this quest gives an item with a specific requirement
	    {
	      $searchstring = compileSearchString($rewards[$i]);
	      if (strpos($searchstring, "NAME") || strpos($searchstring, "ID")) $costtemp = false;
	      else if ($itemcost == 0) $costtemp = 100;
	      else $costtemp = floor($itemcost/20);
	      $itemreward = randomItem($searchstring, $costtemp);
	      if ($itemreward == -1)
		{
		  if ($rewardstring !== "") $rewardstring .= ", ";
		  $rewardstring .= "an error x1";
		  logDebugMessage("randomItem returned -1 for $searchstring, $costtemp!");
		}
	      else if ($itemreward !== false)
		{
		  $rewarditemcost = totalBooncost($itemreward['gristcosts'], $landrow);
		  $basecost = $itemcost - $rewarditemcost;
		  $basecost = ceil($basecost * (1 + ($inflation / 100)));
		  $rewardname = str_replace("\\", "", $itemreward['name']);
		  if ($basecost <= 0)
		    {
		      $basecost = 0;
		      if ($rewardstring !== "") $rewardstring .= ", ";
		      $rewardstring .= $rewardname . " x1";
		    }
		  else
		    {
		      if ($rewardstring !== "") $rewardstring .= ", ";
		      $rewardstring .= $rewardname . " x1, $basecost Boondollars";
		      $boonreward += $basecost;
		    }
		}
	      else
		{
		  $itemreward = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = 23 LIMIT 1;");
		  $itemreward = mysqli_fetch_assoc($itemreward);
		  $basecost = 0;
		  if ($rewardstring !== "") $rewardstring .= ", ";
		  $rewardstring .= "hOPY shIT x1";
		}
	    }
	  else if (strpos($rewards[$i], "UNLOCK:") !== false) // this quest unlocks a new merc type, who joins you as a reward
	    {
	      $thisthing = explode(":", $rewards[$i]);
	      $pick = rand(1, count($thisthing)-1);
	      $unlockreward .= "|" . $thisthing[$pick];
	    }
	  else if (strpos($rewards[$i], "BOONS:") !== false)  // this quest gives a specific amount of money
	    {
	      $thisthing = explode(":", $rewards[$i]);
	      $pick = rand(1, count($thisthing)-1);
	      $boonreward += $thisthing[$pick];
	    }
	  else if (strpos($rewards[$i], "QUEST:") !== false)  // this quest gives another quest
	    {
	      $thisthing = explode(":", $rewards[$i]);
	      $pick = rand(1, count($thisthing)-1);
	      $questreward = $thisthing[$pick];
	    }
	  else
	    {
	      echo "A special reward tag couldn't be interpreted. Please alert a developer.<br />";
	      logDebugMessage("Unknown reward tag: " . $rewards[$i]);
	    }
	}
      if ($rewardstring == "")
	{
	  $rewardstring = "nothing";
	  logDebugMessage("User got no rewards for quest " . $charrow['currentquest']);
	}
    }
  else if ($reward < 10)  // 10% chance normally of getting an item in return, 20% if max luck
    {
      $itemreward = randomItem("", floor($itemcost/20));
      if ($randomitem !== false && $randomitem !== -1)
	{
	  $rewarditemcost = totalBooncost($itemreward['gristcosts'], $landrow);
	  $basecost = $itemcost - $rewarditemcost;
	  $basecost = ceil($basecost * (1 + ($inflation / 100)));
	  $rewardname = str_replace("\\", "", $itemreward['name']);
	  if ($basecost <= 0)
	    {
	      $basecost = 0;
	      $rewardstring = $rewardname . " x1";
	    }
	  else
	    {
	      $rewardstring = $rewardname . " x1, and $basecost Boondollars";
	      $boonreward += $basecost;
	    }
	}
      else
	{
	  $itemreward = mysqli_query($connection, "SELECT * FROM `Captchalogue` WHERE `ID` = 23 LIMIT 1;");
	  $itemreward = mysqli_fetch_assoc($itemreward);
	  $basecost = 0;
	  $rewardstring = "hOPY shIT x1";
	  lobDebugMessage("Couldn't find random item with no special query!");
	}
    }
  else
    {
      if ($itemcost <= 0)
	{
	  $gaterow = mysqli_query($connection, "SELECT * FROM Gates;");
	  $gaterow = mysqli_fetch_row($connection, $gaterow);
	  $itemcost = rand(1, $gaterow[$gate]);
	}
      $basecost = ceil($itemcost * (1 + ($inflation / 100)));
      $rewardstring = "$basecost Boondollars";
      $boonreward += $basecost;
    }
  if (!empty($qrow['completion_dialogue'])) echo $qrow['completion_dialogue'] . "<br />";
  echo "You receive $rewardstring for completing the quest!<br />";
  if (!empty($itemreward))
    {
      $item = false;
      $item = addItem($charrow, $itemreward['ID']);
      if ($item == false) $item = storeItem($charrow, $itemreward['ID']);
    }
  else $item = true;
  $rewardquery = "";
  if ($boonreward > 0)
    {
      $charrow['boondollars'] += $boonreward;
      $boons = mysqli_query($connection, "UPDATE `Characters` SET `boondollars` = " . $charrow['boondollars'] . " WHERE `ID` = " . $charrow['ID'] . " LIMIT 1;");
    }
  else $boons = true;
  if ($unlockreward !== "")
    {
      $landrow['landallies'] .= $unlockreward;
      $ally = mysqli_query($connection, "UPDATE `Characters` SET `landallies` = " . $landrow['landallies'] . " WHERE `ID` = " . $landrow['ID'] . " LIMIT 1;");
    }
  else $ally = true;
  if ($boons && $item && $ally)
    {
      $charrow['currentquest'] = $questreward;
      if ($questreward == 0) $charrow['questland'] = 0;
      $charrow['economy'] = $charrow['economy']+$itemcost;
      mysqli_query($connection, "UPDATE `Characters` SET `currentquest` = " . $charrow['currentquest'] . ", `questland` = " . $charrow['questland'] . ", `economy` = " . $charrow['economy'] . " WHERE `ID` = " . $charrow['ID'] . " LIMIT 1;");
      return true;
    }
  echo "There was an issue giving the reward! Please contact a developer. <br />";
  logDebugMessage("There was an issue giving a reward! Item: " . $item . ", boons: " . $boons . ", unlock: " . $ally);
  return false;
}

?>
