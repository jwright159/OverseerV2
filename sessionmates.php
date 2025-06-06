<?php
$pagetitle = "Session Mates";
$headericon = "/images/header/chummy.png";
require($_SERVER['DOCUMENT_ROOT'] . '/inc/header.php');
require_once "includes/global_functions.php";

$characters = [];
$querycount = 0;

$querycount++;
$sessionquery = mysqli_query($connection, "SELECT * FROM `Sessions` WHERE `ID` = '" . $charrow['session'] . "' LIMIT 1;");
$session = mysqli_fetch_array($sessionquery, MYSQLI_ASSOC);

  $characterids = explode("|", $session['members']);
  echo 'Session ' . $session['ID'] . ' named "' . $session['name'] . '" owned by ' . $session['creator'] . ' with ' . (count($characterids) - 1) .' members.<br>';
?>
<table>
<tr><th>Name</th><th>Server Player</th><th>Client Player</th></tr>
<?php
  array_pop($characterids);
  foreach ($characterids as $characterid) {
    $curchar = getChar($characterid);
    echo('<tr>');
    if ($curchar['server'] == $curchar['client']) echo '<td style="background-color: #DDDDFF;">'; else echo '<td>'; echo rowProfileStringSoft($curchar) . '</td>';
    if ($curchar['server'] == 0) echo '<td style="background-color: #FFDDDD;"></td>';
    else if ($curchar['server'] == $curchar['ID']) echo '<td style="background-color: #DDDDDD;">' . rowProfileStringSoft($curchar) . '</td>';
    else echo '<td>' . profileStringSoft($curchar['server']) . '</td>';
    if ($curchar['client'] == 0) echo '<td style="background-color: #FFDDDD;"></td>';
    else if ($curchar['client'] == $curchar['ID']) echo '<td style="background-color: #DDDDDD";>' . rowProfileStringSoft($curchar) . '</td>';
    else echo '<td>' . profileStringSoft($curchar['client']) . '</td>';
    echo('</tr>');
  }
?>
</table><br>
<?php
foreach ($characterids as $characterid) {
  $charused[$characterid] = false;
}

$chainnum = 1;
$nochain = '';
foreach($characterids as $i) {
  if ($charused[$i] == false) {
    $charused[$i] == true;
    if ((getchar($i)['server'] == 0) && (getchar($i)['client'] == 0)) $nochain .= profileStringSoft($i).' is not part of a chain.'."<br>\n";
    else {
      echo('Chain ' . $chainnum . "<br>\n");
      $chainlist = '';
      $chainlist .= '  '. profileStringSoft($i) . "<br>\n";
      if (getChar($i)['client'] != 0) {
        $curchar = getChar($i)['client'];
        while ($charused[$curchar] == false) { // check to make sure character hasn't already been iterated over
          $chainlist .= '  '.profileStringSoft($curchar)."<br>\n";
          $charused[$curchar] = true;
          if (getChar($curchar)['client'] != 0) $curchar = getChar($curchar)['client'];
          else $curchar = $i;
        }
      } else echo('Player has no client.'."\n");
      if (getChar($i)['server'] != 0) {
        $curchar = getChar($i)['server'];
        while ($charused[$curchar] == false) { // check to make sure character hasn't already been iterated over
          $chainlist = '  '.profileStringSoft($curchar)."<br>\n".$chainlist;
          $charused[$curchar] = true;
          if (getChar($curchar)['server'] != 0) $curchar = getChar($curchar)['server'];
          else $curchar = $i;
        }
      } else echo('Player has no server.'."\n");
      echo($chainlist."<br><br>\n");
      $chainnum++;
    }
  }
}
echo($nochain);
?>
<br>Used <?php echo($querycount); ?> queries to generate this page.

<?php require($_SERVER['DOCUMENT_ROOT'] . '/inc/footer.php');
