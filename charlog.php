<?php

require_once "header.php";


if (empty($_SESSION['username'])) {
	echo "Choose a character to view your log.<br>";
} else {
    
    $charID = $charrow['ID'];
    $filename = "/logs/char".$charID."txt";
    echo '<h1>Log for '.$charrow['name'].':</h1><br><br>';
    echo file_get_contents($filename); // Echoes out the content of the file
    file_put_contents($filename, ''); // Empties the file.
}
require_once "footer.php";

?>