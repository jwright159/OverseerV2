<?php
$pagetitle = "old wearable fixer upper";
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";

if ($accrow['modlevel'] < 10) {
  echo "nope.rtf";
} else {
  echo "let's do a wearable fix.<br/>";
  $wears = mysqli_query($connection, "SELECT * FROM Captchalogue WHERE abstratus LIKE '%bodygear%' OR abstratus LIKE '%headgear%' OR abstratus LIKE '%facegear%' OR abstratus LIKE '%accessory%' LIMIT 100;"); //doing 100 at a time
  while ($wrow = mysqli_fetch_array($wears)) {
    $theseabs = explode(", ", $wrow['abstratus']);
    $i = 0;
    $count = count($theseabs);
    while ($i < $count) {
      $newwears = "";
      $newabs = "";
      switch ($theseabs[$i]) {
        case "bodygear": $newwears .= "body, "; break;
        case "headgear": $newwears .= "head, "; break;
        case "facegear": $newwears .= "face, "; break;
        case "accessory": $newwears .= "accessory, "; break;
        default: $newabs .= $theseabs[$i] . ", "; //not a wearable category: preserve
      }
      $i++;
    }
    $newabs = substr($newabs, 0, -2); //trim the final ", "
    $newwears = substr($newwears, 0, -2); //trim the final ", "
    if (empty($newabs)) $newabs = "notaweapon"; //safety precaution
    if (empty($newwears)) $newwears = "none"; //probably not going to happen but you know what
    echo $wrow['name'] . ": " . $newabs . "; " . $newwears . "<br/>";
    if (!empty($_GET['doit']))  {
      mysqli_query($connection, "UPDATE Captchalogue SET abstratus = '$newabs', wearable = '$newwears' WHERE ID = " . $wrow['ID']);
    }
  }
  echo "done with those 100. refresh the page to do more";
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>
