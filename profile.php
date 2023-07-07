<?php
$pagetitle = "Character Profile";
$headericon = "/images/header/chummy.png";
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/header.php';
?>
<link rel="stylesheet" type="text/css" href="/css/tooltipster.css" />
<link rel="stylesheet" type="text/css" href="/css/tooltipster2.css" />
<script src="/js/jquery.min.js"></script>
<script type="text/javascript" src="/js/jquery.tooltipster.min.js"></script>
<?php

if(isset($_GET['ID']))
    $profiler = getChar(intval($_GET['ID']));
else $profiler = $charrow;

if($profiler){

    $strifer = loadStriferow($profiler['wakeself']);

    $server = getChar($profiler['server']);
    $client = getChar($profiler['client']);


    echo 'Name: <a style="text-decoration:none" href="profile.php?ID=' . $profiler["ID"] . '"><span id="charname" style="color:#' . $profiler['colour'] . '"><b>' . $profiler['name'] . '</b></span></a><br>';
    if (($profiler['class'] == '') || ($profiler['aspect'] == '')) {
        echo 'Title: As of yet unknown.<br>';
    } else {
        echo "Title: " . $profiler['class'] . " of " . $profiler['aspect'] . "<br>";
    }
    echo "Moon: " . $profiler['dreamer'] . "<br>";

    $ownsession = mysqli_query($connection, "SELECT name FROM Sessions WHERE `ID` = $profiler[session];");
    $sessiont = mysqli_fetch_array($ownsession);
    $session = str_replace("''", "'", $sessiont['name']);
    echo 'Symbol: <img id="imgchar" style="display:inline" src="' . $profiler['symbol'] . '"><br />';


    if (!$profiler['inmedium'])
        echo "This player has not yet entered the medium. <br>Their land and strife stats will be visible here once they become relevant.<br>";
    else {
        echo 'Echeladder rung: ' . $strifer['echeladder'] . '<br>';
        echo 'Health Vial: <b>' . ceil(($strifer['health']/$strifer['maxhealth'])*100) . '%</b><br>';
        echo 'Aspect Vial: <b>' . ceil(($strifer['energy']/$strifer['maxenergy'])*100) . '%</b><br>';
        echo 'Land: The Land of ' . $profiler['land1'] . ' and ' . $profiler['land2'] . '<br>';
        //echo "Grists available on this land: " . implode(', ', $profiler[grist_type) . '<br>';
        echo "Consorts: " . $profiler['consort'] . "<br><br>";
    }
    $psession = mysqli_query($connection, "SELECT name FROM Sessions WHERE `ID` =" . $profiler['session'] . ";");
    $psessionarray = mysqli_fetch_array($psession);
    echo 'Session: <a href="/sessioninfo.php?session=' . $psessionarray['name'] . '">' . $psessionarray['name'] . "</a><br>";
    if (isset($server))
        echo "Server Player: " . profileString($server['ID']) . "<br>";
    else
        echo "Server Player: Not yet connected. <br>";
    if (isset($client))
        echo "Client Player: " . profileString($client['ID']) . "<br>";
    else
        echo "Client Player: Not yet connected. <br>";

    if ($profiler['inmedium'])
        echo 'Gates accessible: ' . $profiler['gatescleared'] . '<br>';
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
    foreach($achievements as $name => $achievement) if(getAchievement($profiler, $name)) $achieved++;
    echo "<br>Achievement badges({$achieved}/{$nachievement}):<br><br>";


    foreach($achievements as $name => $achievement){
        if(getAchievement($profiler, $name))
            echo '<span class="tooltip" title="&lt;img src=&quot;/images/achievements/'. $name . '.png&quot; style=&quot;float:left;margin-right:10px;&quot; /&gt; &lt;strong&gt;'. $achievement[0] .'&lt;/strong&gt;&lt;br&gt;'. '????' . '"><img src="/images/achievements/' . $name . '.png"></span>';
        else
            echo '<span class="tooltip2" title="&lt;img src=&quot;/images/achievements/unknown.png&quot; style=&quot;float:left;margin-right:10px;&quot; /&gt; &lt;strong&gt;Unknown Achievement&lt;/strong&gt;&lt;br&gt;???"><img src="/images/achievements/unknown.png"></span>';
    }
}
else echo "Error retrieving player.";

?>
<script>
$(document).ready(function(){
    var character = document.getElementById('charname');
    var title = character.innerHTML;
    document.getElementById('pageimg').src=document.getElementById('imgchar').src;
    document.getElementById('pageimg').style.width= '49px';
    document.getElementById('pageimg').style.height= '49px';

    $("#content-header-text").html(title);
});
</script>
<?php

require $_SERVER['DOCUMENT_ROOT'] . '/inc/footer.php';
