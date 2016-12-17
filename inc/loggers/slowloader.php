<?php

function slowLogThis($string) {
    $filename = "/errorlogs/slowQuery.txt";
    $newString = $string.'<br>';
    if (!file_exists($filename)) {
        $myFile = fopen($filename, "w");
        fwrite($myFile, $newString);
        fclose($myFile);
    } else {
        $myFile = fopen($filename, "a");
        fwrite($myFile, $newString);
        fclose($myFile);
    }   
}

$errorOutput = "Page:". $_SERVER['REQUEST_URI'] ."\n\r";
$errorOutput = $errorOutput."Session Variables:\n\r";



   foreach ($_SESSION as $key => $value) {
        $errorOutput = $errorOutput.$key." = ".$value."\n\r";
    }
$errorOutput = $errorOutput."\n\rPOST Variables (if any):\n\r";    
    foreach ($_POST as $key => $value) {
        $errorOutput = $errorOutput.$key." = ".$value."\n\r";
    }
$errorOutput = $errorOutput."\n\r\n\r";
slowLogThis($errorOutput);
echo " The server detected this page loaded slowly, and has logged information for us to check it out. ";
?>
