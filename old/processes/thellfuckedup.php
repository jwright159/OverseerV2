<?php

# "Live database - OverseerDev"

$live = mysqli_connect("localhost","thellfuckedup","hereallydid","OverseerDev");

# "Backup Database - OverseerBak"

$backup = mysqli_connect("localhost","thellfuckedup2","hereallydid","OverseerBak");

$liveRows = mysqli_query($live, "SELECT * FROM `Users`;");
$backupRows = mysqli_query($backup, "SELECT * FROM `Users`;");
$n = 1;
while ($liveRow = mysqli_fetch_assoc($liveRows)) {
    if ($liveRow['password'] == '0') {
        $restoreID = $liveRow['ID'];
        $backupQuery = mysqli_query($backup, "SELECT * FROM `Users` WHERE `ID` = '$restoreID';");
        $backupRow = mysqli_fetch_array($backupQuery);
        $restorePassword = $backupRow['password'];
        mysqli_query($live, "UPDATE `Users` SET `password` = '$restorePassword' WHERE `ID` = '$restoreID';");
        echo $n.'. Restored '.$liveRow['username'].'\'s password.<br>';
        $n++;
    }
    
}
    echo 'Restored '.$n.' passwords. Don\'t fuck up next time.';