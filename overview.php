<?php
$pagetitle = "Character Overview";
$headericon = "/images/header/chummy.png";
require $_SERVER['DOCUMENT_ROOT'] . '/inc/header.php';
?>
<link rel="stylesheet" type="text/css" href="/css/tooltipster.css" />
<link rel="stylesheet" type="text/css" href="/css/tooltipster2.css" />
<script src="/js/jquery.min.js"></script>
<script type="text/javascript" src="/js/jquery.tooltipster.min.js"></script>
<?php

$query = "UPDATE Characters SET lasttick=" . time() . " WHERE ID=" . $me->id;
$queryex = mysqli_query($connection, $query);

// Server player is required on this page, load them now.
if ($me->server != 0) {
    $server = new \Overseer\Character($db, $me->server);
}

//client for achievement checking
if ($me->client != 0) {
    $client = new \Overseer\Character($db, $me->client);
}

//Player is wasting time
if(isset($_POST['wastetime'])){
    echo "You laze around for a while and generally waste time.<br>";
    spendFatigue(10,$charrow);
    if($charrow['wakefatigue']>1499 OR $charrow['dreamfatigue']>1499) setAchievement($charrow, 'fatigue');
}

// Player is attempting to change dream moon.
if (!empty($_POST['newmoon'])) {
    if ($me->dreamer != "Unawakened") {
        echo 'Error: You are already a ' . htmlspecialchars($me->dreamer) .
             ' dreamer!<br>'."\n";
    } elseif ($_POST['newmoon'] == "Derse" || $_POST['newmoon'] == "Prospit") {
        $me->dreamer = $_POST['newmoon'];
        echo 'Dream moon updated.<br>'."\n";
    } elseif ($_POST['newmoon'] == "Space station") {
        echo 'That\'s not a moon..<br>'."\n";
    } elseif ($_POST['newmoon'] == "The Battlefield"
        || $_POST['newmoon'] == "Battlefield"
        || $_POST['newmoon'] == "Skaia"
    ) {
        echo 'Nice try, but no.<br>'."\n";
    } elseif (strpos($_POST['newmoon'], "Land") != false) {
        echo 'You can\'t start your dreamself off on a Land, sorry.<br>'."\n";
    } else {
        echo 'Only Derse and Prospit are valid dream moons. ' .
             'Don\'t forget the capitalization!<br>'."\n";
    }
}


if (!empty($_POST['newcolor'])) { //player changing color
    $color = str_replace("#", "", $_POST['newcolor']);
    if (preg_match('/^[a-f0-9]{6}$/i', $color)) {
        $me->colour = $color;
        echo "Colour updated.<br />";
    } else {
        echo "Error: Colour must be a 6-digit hexidecimal number.<br />";
    }
}


/* Player wants to set a new player image
if (!empty($_POST['newimage'])) {
    if (!preg_match('/^\/images\//', $_POST['newimage'])) {
        die('sorry u r fale hakr pls go die :(');
    }
    if (preg_match('/"|\?|<|>| /', $_POST['newimage'])) {
        die('sorry ur fale hakr pls go die :(');
    }
    $me->symbol = $_POST['newimage'];
    echo 'Image updated.<br>'."\n";
}*/

// Player wants to set a new custom player image
if (isset($_POST['symbol'])) {

        $ownsession = mysqli_query($connection, "SELECT name FROM Sessions WHERE `ID` = $charrow[session];");
        $sessiont = mysqli_fetch_array($ownsession);
        $session = str_replace("''", "'", $sessiont['name']);

        if($_FILES["file"]["name"]){
            $filename = $_FILES["file"]["name"];
            $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
            $file_ext = substr($filename, strripos($filename, '.')); // get file name
            $filesize = $_FILES["file"]["size"];
            $allowed_file_types = array('.png');
            $image_info = getimagesize($_FILES["file"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if (in_array($file_ext,$allowed_file_types) && ($filesize < 200000) && ($image_width == 64) && ($image_height == 64) ) {
                // Rename file
                $newfilename = $charrow['name'] . '_' . $session . $file_ext;
                move_uploaded_file($_FILES["file"]["tmp_name"], "images/symbols/" . $newfilename);
                $me->symbol="images/symbols/" . $newfilename;
                echo "File uploaded successfully.";
            } elseif (empty($file_basename)) {
                // file selection error
                echo "Please select a file to upload.";
            } elseif ($filesize > 200000) {
                // file size error
                echo "The file you are trying to upload is too large.";
            } elseif (($image_width != 64) || ($image_height != 64)) {
                // file size error
                echo "The file's dimensions need to be 64x64 pixels.";
            } else {
                // file type error
                echo "Only these file typs are allowed for upload: " . implode(', ',$allowed_file_types);
                unlink($_FILES["file"]["tmp_name"]);
            }
        } else {
            $me->symbol='images/symbols/aspect_' . strtolower($me->aspect) . '.png';
        }
    }

?>

Name: <?php echo profileString($me->id); ?><br>
Title: <?php
if (($me->class == '') || ($me->aspect == '')) {
    echo 'As of yet unknown.<br>'."\n";
} else { 
    echo $me->class . " of " . $me->aspect . "<br>"."\n";
} ?>
Moon: <?php echo $me->dreamer; ?><br>
<?php if ($me->dreamer == "Unawakened") {
    echo "<form method='post'>Input a moon: <input type='text' name='newmoon'>".
         "<input type='submit' value='Register' /></form>";
} ?>
<form method='post'>
    Colour: <font color='#<?php echo $me->colour; ?>'>
        #<?php echo $me->colour; ?>
    </font> -
    <input type='color' name='newcolor' value='#<?php echo $me->colour; ?>'>
    <input type='submit' value='Change'>
</form>


<br>
<?php
$ownsession = mysqli_query($connection, "SELECT name FROM Sessions WHERE `ID` = $charrow[session];");
$sessiont = mysqli_fetch_array($ownsession);
$session = str_replace("''", "'", $sessiont['name']);
echo 'Symbol: <img style="display:inline" src="' . $me->symbol . '"><br />';
echo '<br><form action="overview.php" method="post" enctype="multipart/form-data">
Upload custom symbol image (PNG, 64x64):
</br>
<input type="file" name="file" id="file">
<input type="submit" value="Submit" name="symbol">
</form>';
echo 'Or just press submit to reset image.<br />';
?>
<br>



<?php
if (!$me->inmedium) { ?>
You have not yet entered the medium.
Your land and strife stats will be visible here once they become relevant.<br>
<?php
} else { ?>
Description: <?php echo $me->strife->description; ?><br>
Echeladder rung: <?php echo $me->strife->echeladder; ?><br>
Health Vial: <b><?php echo ceil($me->strife->healthpercent); ?>%</b><br>
Aspect Vial: <b><?php echo ceil($me->strife->energypercent); ?>%</b><br>
Land: The Land of <?php echo $me->land1 . ' and ' . $me->land2; ?><br>
Grists available on this land: <?php echo implode(', ', $me->grist_type); ?><br>
Consorts: <?php echo $me->consort; ?><br>
<?php
} ?>
<br>
<?php
if (isset($server))
    echo "Server Player: " . profileString($server->id) . "<br>";
else
    echo "Server Player: Not yet connected. <br>";
?>
Build grist expended on your dwelling: <?php echo $me->house_build; ?><br>
<?php
if ($me->inmedium) { ?>
Gates reached: <?php echo $me->gatesreached; ?><br>
<?php // blah's code for manually clearing gates
if (!empty($_GET['debugclear'])) {
    if ($me->gatescleared < $me->gatesreached) {
        $me->gatescleared++;
    }
} ?>
Gates accessible: <?php echo $me->gatescleared; ?><br>
<?php // blah's code for triggering a manual clear of gates ... #triggered
if ($me->gatescleared < $me->gatesreached) {
    echo "You cannot reach the highest gate to which your server has built yet, ".
         "as this newly-built structure is unexplored and full of underlings! ".
         "You will have to fight your way up if you want to make use of it.<br>\n".
         "Click here to enter the new territory and claim it as your own.<br>\n";
         //will add link here when dungeons are in
    echo "NOTE: This feature is not yet implemented. For the time being, you can ".
         "<a href='?debugclear=yep'>click here</a> to clear the next gate.";
}

} // end of if ($me->inmedium)

if($me->inmedium) setAchievement($charrow, 'medium');
if($me->denizendown) setAchievement($charrow, 'denizen');
if(isset($client)) if($client->gatescleared > 6) setAchievement($charrow, 'gate7');
if($me->echeladder>611) setAchievement($charrow, 'topeche');

echo "<br><form method='post'><input type='submit' name='wastetime' value='Waste time' /></form>";

$achievements = array(
                        'medium' =>['[S] Enter', 'Enter the medium'],
                        'ko'=>['You Tried', 'Get KO\'d'],
                        'fatigue'=>['Weekend At Player\'s', 'It\'s time to stop clicking'],
                        'deadconsort'=>['Black Liquid Sorrow', 'Visit the grave of a consort mercenary'],
                        'itemfull'=>['Act 1 Nostalgia','Fill your inventory'],
                        'assist'=>['Game Bro', 'Assist a fellow player on a strife and win'],
                        'aspectheal'=>['Doctor Remix', 'Heal a fellow player by using an Aspect Pattern'],
                        'allgrist'=>['Colours And Mayhem','Recycle a Perfectly Unique Object'],
                        'boonshop'=>['LODS OF BOONE', 'Spend one hundred Boonbucks on the Consort Shop'],
                        'fray3'=>['Fraymothree In The Morning', 'Get the full set of your Aspect\'s Fraymotifs'],
                        'fullport'=>['Like Fucking Christmas Up In Here', 'Get fully equipped'],
                        'ultweapon'=>['Nonanonacontanonactanonaliagonal Ultimatum','Equip an Ultimate Weapon'],
                        'topeche'=>['Sike, That\'s The Right Number','Reach rung 612 of the Echeladder'],
                        'dungeon1'=>['Tentacle Therapist','Kill the Kraken'],
                        'dungeon2'=>['Here Come The Arms','Kill the Hekatonchire'],
                        'dungeon3'=>['Killer Queen','Kill the Lich Queen'],
                        'moonprince' =>['Princes Of The Incipisphere', 'Defeat a full set of the best your moon has to offer'],
                        'gate7'=>['Clientship Aneurysm','Build your client\'s house up to Gate 7'],
                        'denizen'=>['Screw The Choice','Defeat your denizen'],
                        'thebug'=>['Achievement Name', 'Face The Bug'],
                        'itemsub'=>['Not Another Sword','Submit a non-QIC item and get it greenlit'],
                        'artsub'=>['Not Another Sword Pic','Submit art for an item and get it approved']
                        );
$nachievement=sizeof($achievements);
$achieved=0;
foreach($achievements as $name => $achievement) if(getAchievement($charrow, $name)) $achieved++;


echo "<br>Achievement badges({$achieved}/{$nachievement}):<br><br>";


foreach($achievements as $name => $achievement){
    if(getAchievement($charrow, $name))
        echo '<span class="tooltip" title="&lt;img src=&quot;/images/achievements/'. $name . '.png&quot; style=&quot;float:left;margin-right:10px;&quot; /&gt; &lt;strong&gt;'. $achievement[0] .'&lt;/strong&gt;&lt;br&gt;'. $achievement[1] . '"><img src="/images/achievements/' . $name . '.png"></span>';
    else
        echo '<span class="tooltip2" title="&lt;img src=&quot;/images/achievements/unknown.png&quot; style=&quot;float:left;margin-right:10px;&quot; /&gt; &lt;strong&gt;Unknown Achievement&lt;/strong&gt;&lt;br&gt;???"><img src="/images/achievements/unknown.png"></span>';
}

require $_SERVER['DOCUMENT_ROOT'] . '/inc/footer.php';
